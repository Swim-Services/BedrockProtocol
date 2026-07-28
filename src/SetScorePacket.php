<?php

/*
 * This file is part of BedrockProtocol.
 * Copyright (C) 2014-2022 PocketMine Team <https://github.com/pmmp/BedrockProtocol>
 *
 * BedrockProtocol is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

declare(strict_types=1);

namespace pocketmine\network\mcpe\protocol;

use pmmp\encoding\Byte;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\LE;
use pmmp\encoding\VarInt;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use pocketmine\network\mcpe\protocol\types\ScorePacketEntry;
use function count;

class SetScorePacket extends DataPacket implements ClientboundPacket{
	public const NETWORK_ID = ProtocolInfo::SET_SCORE_PACKET;

	public const TYPE_CHANGE = 0;
	public const TYPE_REMOVE = 1;
	private const ACTION_IDS = [
		ScorePacketEntry::TYPE_REMOVE => "remove",
		ScorePacketEntry::TYPE_PLAYER => "changeplayer",
		ScorePacketEntry::TYPE_ENTITY => "changeentity",
		ScorePacketEntry::TYPE_FAKE_PLAYER => "changefakeplayer",
	];

	public int $type;
	/** @var ScorePacketEntry[] */
	public array $entries = [];

	/**
	 * @generate-create-func
	 * @param ScorePacketEntry[] $entries
	 */
	public static function create(int $type, array $entries) : self{
		$result = new self;
		$result->type = $type;
		$result->entries = $entries;
		return $result;
	}

	protected function decodePayload(ByteBufferReader $in, int $protocolId) : void{
		if($protocolId < ProtocolInfo::PROTOCOL_1_26_40){
			$this->type = Byte::readUnsigned($in);
		}
		$onlyRemovals = true;
		for($i = 0, $count = VarInt::readUnsignedInt($in); $i < $count; ++$i){
			$entry = new ScorePacketEntry();
			if($protocolId >= ProtocolInfo::PROTOCOL_1_26_40){
				$entry->type = VarInt::readUnsignedInt($in);
				CommonTypes::getString($in); //action ID, redundant with the type
			}
			$entry->scoreboardId = VarInt::readSignedLong($in);
			if($protocolId >= ProtocolInfo::PROTOCOL_1_26_40){
				switch($entry->type){
					case ScorePacketEntry::TYPE_REMOVE:
						$entry->objectiveName = CommonTypes::readOptional($in, CommonTypes::getString(...));
						break;
					case ScorePacketEntry::TYPE_PLAYER:
					case ScorePacketEntry::TYPE_ENTITY:
						$onlyRemovals = false;
						$entry->objectiveName = CommonTypes::getString($in);
						$entry->score = LE::readSignedInt($in);
						$entry->actorUniqueId = CommonTypes::getActorUniqueId($in);
						break;
					case ScorePacketEntry::TYPE_FAKE_PLAYER:
						$onlyRemovals = false;
						$entry->objectiveName = CommonTypes::getString($in);
						$entry->score = LE::readSignedInt($in);
						$entry->customName = CommonTypes::getString($in);
						break;
					default:
						throw new PacketDecodeException("Unknown entry type $entry->type");
				}
			}else{
				$entry->objectiveName = CommonTypes::getString($in);
				$entry->score = LE::readSignedInt($in);
				if($this->type !== self::TYPE_REMOVE){
					$entry->type = Byte::readUnsigned($in);
					switch($entry->type){
						case ScorePacketEntry::TYPE_PLAYER:
						case ScorePacketEntry::TYPE_ENTITY:
							$entry->actorUniqueId = CommonTypes::getActorUniqueId($in);
							break;
						case ScorePacketEntry::TYPE_FAKE_PLAYER:
							$entry->customName = CommonTypes::getString($in);
							break;
						default:
							throw new PacketDecodeException("Unknown entry type $entry->type");
					}
				}
			}
			$this->entries[] = $entry;
		}
		if($protocolId >= ProtocolInfo::PROTOCOL_1_26_40){
			$this->type = $onlyRemovals ? self::TYPE_REMOVE : self::TYPE_CHANGE;
		}
	}

	protected function encodePayload(ByteBufferWriter $out, int $protocolId) : void{
		if($protocolId < ProtocolInfo::PROTOCOL_1_26_40){
			Byte::writeUnsigned($out, $this->type);
		}
		VarInt::writeUnsignedInt($out, count($this->entries));
		foreach($this->entries as $entry){
			$entryType = $entry->type;
			if($protocolId >= ProtocolInfo::PROTOCOL_1_26_40){
				$entryType = $this->type === self::TYPE_REMOVE ? ScorePacketEntry::TYPE_REMOVE : $entry->type;
				$actionId = self::ACTION_IDS[$entryType] ?? throw new \InvalidArgumentException("Unknown entry type $entryType");
				VarInt::writeUnsignedInt($out, $entryType);
				CommonTypes::putString($out, $actionId);
			}
			VarInt::writeSignedLong($out, $entry->scoreboardId);
			if($protocolId >= ProtocolInfo::PROTOCOL_1_26_40){
				switch($entryType){
					case ScorePacketEntry::TYPE_REMOVE:
						CommonTypes::writeOptional($out, $entry->objectiveName, CommonTypes::putString(...));
						break;
					case ScorePacketEntry::TYPE_PLAYER:
					case ScorePacketEntry::TYPE_ENTITY:
						CommonTypes::putString($out, $entry->objectiveName ?? throw new \InvalidArgumentException("objectiveName must be set"));
						LE::writeSignedInt($out, $entry->score);
						CommonTypes::putActorUniqueId($out, $entry->actorUniqueId ?? throw new \InvalidArgumentException("actorUniqueId must be set"));
						break;
					case ScorePacketEntry::TYPE_FAKE_PLAYER:
						CommonTypes::putString($out, $entry->objectiveName ?? throw new \InvalidArgumentException("objectiveName must be set"));
						LE::writeSignedInt($out, $entry->score);
						CommonTypes::putString($out, $entry->customName ?? throw new \InvalidArgumentException("customName must be set"));
						break;
				}
			}else{
				CommonTypes::putString($out, $entry->objectiveName ?? throw new \InvalidArgumentException("objectiveName must be set"));
				LE::writeSignedInt($out, $entry->score);
				if($this->type !== self::TYPE_REMOVE){
					Byte::writeUnsigned($out, $entry->type);
					switch($entry->type){
						case ScorePacketEntry::TYPE_PLAYER:
						case ScorePacketEntry::TYPE_ENTITY:
							CommonTypes::putActorUniqueId($out, $entry->actorUniqueId);
							break;
						case ScorePacketEntry::TYPE_FAKE_PLAYER:
							CommonTypes::putString($out, $entry->customName);
							break;
						default:
							throw new \InvalidArgumentException("Unknown entry type $entry->type");
					}
				}
			}
		}
	}

	public function handle(PacketHandlerInterface $handler) : bool{
		return $handler->handleSetScore($this);
	}
}
