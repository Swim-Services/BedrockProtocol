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

class AddEntityPacket extends DataPacket implements ClientboundPacket{
	public const NETWORK_ID = ProtocolInfo::ADD_ENTITY_PACKET;

	private int $entityNetId;

	/**
	 * @generate-create-func
	 */
	public static function create(int $entityNetId) : self{
		$result = new self;
		$result->entityNetId = $entityNetId;
		return $result;
	}

	public function getEntityNetId() : int{
		return $this->entityNetId;
	}

	protected function decodePayload(ByteBufferReader $in, int $protocolId) : void{
		//NOOP
	}

	protected function encodePayload(ByteBufferWriter $out, int $protocolId) : void{
		//NOOP
	}

	public function handle(PacketHandlerInterface $handler) : bool{
		return $handler->handleAddEntity($this);
	}
}
