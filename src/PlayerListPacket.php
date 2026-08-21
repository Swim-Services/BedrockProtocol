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
use pocketmine\color\Color;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use pocketmine\network\mcpe\protocol\types\PlayerListEntry;
use function count;

class PlayerListPacket extends DataPacket implements ClientboundPacket{
	public const NETWORK_ID = ProtocolInfo::PLAYER_LIST_PACKET;

	public const TYPE_ADD = 0;
	public const TYPE_REMOVE = 1;

	/**
	 * The packet-level action used by legacy protocols and as a compatibility
	 * fallback for modern entries whose action is null.
	 */
	public int $type = self::TYPE_REMOVE;
	/** @var PlayerListEntry[] */
	public array $entries = [];

	/**
	 * @generate-create-func
	 * @param PlayerListEntry[] $entries
	 */
	private static function create(int $type, array $entries) : self{
		$result = new self;
		$result->type = $type;
		$result->entries = $entries;
		return $result;
	}

	/**
	 * @param PlayerListEntry[] $entries
	 */
	public static function add(array $entries) : self{
		return self::create(self::TYPE_ADD, $entries);
	}

	/**
	 * @param PlayerListEntry[] $entries
	 */
	public static function remove(array $entries) : self{
		return self::create(self::TYPE_REMOVE, $entries);
	}

	protected function decodePayload(ByteBufferReader $in, int $protocolId) : void{
		if($protocolId < ProtocolInfo::PROTOCOL_1_26_40){
			$this->type = Byte::readUnsigned($in);
		}
		$count = VarInt::readUnsignedInt($in);
		if($protocolId >= ProtocolInfo::PROTOCOL_1_26_40 && $count === 0){
			$this->type = self::TYPE_REMOVE;
		}
		for($i = 0; $i < $count; ++$i){
			$entry = new PlayerListEntry();
			$type = $this->type;
			if($protocolId >= ProtocolInfo::PROTOCOL_1_26_40){
				$wireType = VarInt::readUnsignedInt($in);
				$type = match($wireType){
					0 => self::TYPE_REMOVE,
					1 => self::TYPE_ADD,
					default => throw new PacketDecodeException("Unknown player list entry type " . $wireType),
				};
				$action = Byte::readUnsigned($in);
				if($action !== $type){
					throw new PacketDecodeException("Player list entry action $action does not match discriminator $wireType");
				}
				$entry->action = $type;
				if($i === 0){
					$this->type = $type;
				}
			}
			if($type === self::TYPE_ADD){
				$entry->uuid = CommonTypes::getUUID($in);
				$entry->actorUniqueId = CommonTypes::getActorUniqueId($in);
				$entry->username = CommonTypes::getString($in);
				$entry->xboxUserId = CommonTypes::getString($in);
				$entry->platformChatId = CommonTypes::getString($in);
				$entry->buildPlatform = LE::readSignedInt($in);
				$entry->skinData = CommonTypes::getSkin($in, $protocolId);
				$entry->isTeacher = CommonTypes::getBool($in);
				$entry->isHost = CommonTypes::getBool($in);
				if($protocolId >= ProtocolInfo::PROTOCOL_1_20_60){
					$entry->isSubClient = CommonTypes::getBool($in);
					if($protocolId >= ProtocolInfo::PROTOCOL_1_21_80){
						$entry->color = Color::fromARGB(LE::readUnsignedInt($in));
					}
				}
			}else{
				$entry->uuid = CommonTypes::getUUID($in);
			}

			$this->entries[$i] = $entry;
		}
		if($protocolId < ProtocolInfo::PROTOCOL_1_26_40 && $this->type === self::TYPE_ADD){
			for($i = 0; $i < $count; ++$i){
				($this->entries[$i]->skinData ?? throw new PacketDecodeException("Missing skin data"))->setVerified(CommonTypes::getBool($in));
			}
		}
	}

	protected function encodePayload(ByteBufferWriter $out, int $protocolId) : void{
		if($protocolId < ProtocolInfo::PROTOCOL_1_26_40){
			Byte::writeUnsigned($out, $this->type);
		}
		VarInt::writeUnsignedInt($out, count($this->entries));
		foreach($this->entries as $entry){
			$type = $protocolId >= ProtocolInfo::PROTOCOL_1_26_40 ? ($entry->action ?? $this->type) : $this->type;
			if($protocolId >= ProtocolInfo::PROTOCOL_1_26_40){
				$wireType = match($type){
					self::TYPE_REMOVE => 0,
					self::TYPE_ADD => 1,
					default => throw new \InvalidArgumentException("Unknown player list entry action " . $type),
				};
				VarInt::writeUnsignedInt($out, $wireType);
				Byte::writeUnsigned($out, $type);
			}
			if($type === self::TYPE_ADD){
				CommonTypes::putUUID($out, $entry->uuid);
				CommonTypes::putActorUniqueId($out, $entry->actorUniqueId);
				CommonTypes::putString($out, $entry->username);
				CommonTypes::putString($out, $entry->xboxUserId);
				CommonTypes::putString($out, $entry->platformChatId);
				LE::writeSignedInt($out, 1);
				$skinData = $entry->skinData ?? throw new \InvalidArgumentException("Player list addition entries must have skin data");
				CommonTypes::putSkin($out, $skinData, $protocolId);
				CommonTypes::putBool($out, $entry->isTeacher);
				CommonTypes::putBool($out, $entry->isHost);
				if($protocolId >= ProtocolInfo::PROTOCOL_1_20_60){
					CommonTypes::putBool($out, $entry->isSubClient);
					if($protocolId >= ProtocolInfo::PROTOCOL_1_21_80){
						LE::writeUnsignedInt($out, ($entry->color ?? new Color(255, 255, 255))->toARGB());
					}
				}
			}else{
				CommonTypes::putUUID($out, $entry->uuid);
			}
		}
		if($protocolId < ProtocolInfo::PROTOCOL_1_26_40 && $this->type === self::TYPE_ADD){
			foreach($this->entries as $entry){
				CommonTypes::putBool($out, ($entry->skinData ?? throw new \InvalidArgumentException("Missing skin data"))->isVerified());
			}
		}
	}

	public function handle(PacketHandlerInterface $handler) : bool{
		return $handler->handlePlayerList($this);
	}
}
