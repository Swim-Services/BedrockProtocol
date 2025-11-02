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

use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\VarInt;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use pocketmine\network\mcpe\protocol\types\command\CommandOriginData;

class CommandRequestPacket extends DataPacket implements ServerboundPacket{
	public const NETWORK_ID = ProtocolInfo::COMMAND_REQUEST_PACKET;

	public string $command;
	public CommandOriginData $originData;
	public bool $isInternal;
	public int $version;

	/**
	 * @generate-create-func
	 */
	public static function create(string $command, CommandOriginData $originData, bool $isInternal, int $version) : self{
		$result = new self;
		$result->command = $command;
		$result->originData = $originData;
		$result->isInternal = $isInternal;
		$result->version = $version;
		return $result;
	}

	protected function decodePayload(ByteBufferReader $in, int $protocolId) : void{
		$this->command = CommonTypes::getString($in);
		$this->originData = CommonTypes::getCommandOriginData($in);
		$this->isInternal = CommonTypes::getBool($in);
		if($protocolId >= ProtocolInfo::PROTOCOL_1_19_60){
			$this->version = VarInt::readSignedInt($in);
		}
	}

	protected function encodePayload(ByteBufferWriter $out, int $protocolId) : void{
		CommonTypes::putString($out, $this->command);
		CommonTypes::putCommandOriginData($out, $this->originData);
		CommonTypes::putBool($out, $this->isInternal);
		if($protocolId >= ProtocolInfo::PROTOCOL_1_19_60){
			VarInt::writeSignedInt($out, $this->version);
		}
	}

	public function handle(PacketHandlerInterface $handler) : bool{
		return $handler->handleCommandRequest($this);
	}
}
