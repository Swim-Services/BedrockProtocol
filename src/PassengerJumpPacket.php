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
<<<<<<<< HEAD:src/PassengerJumpPacket.php
use pmmp\encoding\VarInt;

class PassengerJumpPacket extends DataPacket implements ServerboundPacket{
	public const NETWORK_ID = ProtocolInfo::PASSENGER_JUMP_PACKET;

	public int $jumpStrength; //percentage
========
use pocketmine\network\mcpe\protocol\types\DataStoreUpdate;

class ServerboundDataStorePacket extends DataPacket implements ServerboundPacket{
	public const NETWORK_ID = ProtocolInfo::SERVERBOUND_DATA_STORE_PACKET;

	private DataStoreUpdate $update;
>>>>>>>> upstream/master:src/ServerboundDataStorePacket.php

	/**
	 * @generate-create-func
	 */
<<<<<<<< HEAD:src/PassengerJumpPacket.php
	public static function create(int $jumpStrength) : self{
		$result = new self;
		$result->jumpStrength = $jumpStrength;
		return $result;
	}

	protected function decodePayload(ByteBufferReader $in, int $protocolId) : void{
		$this->jumpStrength = VarInt::readSignedInt($in);
	}

	protected function encodePayload(ByteBufferWriter $out, int $protocolId) : void{
		VarInt::writeSignedInt($out, $this->jumpStrength);
	}

	public function handle(PacketHandlerInterface $handler) : bool{
		return $handler->handlePassengerJump($this);
========
	public static function create(DataStoreUpdate $update) : self{
		$result = new self;
		$result->update = $update;
		return $result;
	}

	public function getUpdate() : DataStoreUpdate{ return $this->update; }

	protected function decodePayload(ByteBufferReader $in) : void{
		$this->update = DataStoreUpdate::read($in);
	}

	protected function encodePayload(ByteBufferWriter $out) : void{
		$this->update->write($out);
	}

	public function handle(PacketHandlerInterface $handler) : bool{
		return $handler->handleServerboundDataStore($this);
>>>>>>>> upstream/master:src/ServerboundDataStorePacket.php
	}
}
