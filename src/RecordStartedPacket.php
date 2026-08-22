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
use pmmp\encoding\LE;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;

class RecordStartedPacket extends DataPacket implements ClientboundPacket{
	public const NETWORK_ID = ProtocolInfo::RECORD_STARTED;

	private Vector3 $position;
	private int $handle;

	/**
	 * @generate-create-func
	 */
	public static function create(Vector3 $position, int $handle) : self{
		$result = new self;
		$result->position = $position;
		$result->handle = $handle;
		return $result;
	}

	protected function decodePayload(ByteBufferReader $in, int $protocolId) : void{
		$this->position = CommonTypes::getVector3($in);
		$this->handle = LE::readUnsignedLong($in);
	}

	protected function encodePayload(ByteBufferWriter $out, int $protocolId) : void{
		CommonTypes::putVector3($out, $this->position);
		LE::writeUnsignedLong($out, $this->handle);
	}

	public function handle(PacketHandlerInterface $handler) : bool{
		return $handler->handleRecordStarted($this);
	}
}
