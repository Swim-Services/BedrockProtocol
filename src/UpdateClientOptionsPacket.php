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

use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;

class UpdateClientOptionsPacket extends DataPacket implements ServerboundPacket {
	public const NETWORK_ID = ProtocolInfo::UPDATE_CLIENT_OPTIONS_PACKET;

	private ?int $newGraphicsMode = null;

	/**
	 * @generate-create-func
	 */
	public static function create(?int $newGraphicsMode) : self{
		$result = new self;
		$result->newGraphicsMode = $newGraphicsMode;
		return $result;
	}

	protected function decodePayload(PacketSerializer $in) : void{
		$this->newGraphicsMode = $in->readOptional($in->getByte(...));
	}

	protected function encodePayload(PacketSerializer $out) : void{
		$out->writeOptional($this->newGraphicsMode, $out->putByte(...));
	}

	public function handle(PacketHandlerInterface $handler) : bool{
		return $handler->handleUpdateClientOptions($this);
	}
}
