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

class PlayerVideoCapturePacket extends DataPacket implements ClientboundPacket {
	public const NETWORK_ID = ProtocolInfo::PLAYER_VIDEO_CAPTURE_PACKET;

	private ?int $frameRate = null;
	private ?string $filePrefix = null;

	/**
	 * @generate-create-func
	 */
	public static function create(?int $frameRate, ?string $filePrefix) : self{
		$result = new self;
		$result->frameRate = $frameRate;
		$result->filePrefix = $filePrefix;
		return $result;
	}

	protected function decodePayload(PacketSerializer $in) : void{
		if ($in->getBool()) {
			$this->frameRate = $in->getInt();
			$this->filePrefix = $in->getString();
		}
	}

	protected function encodePayload(PacketSerializer $out) : void{
		$action = $this->frameRate !== null && $this->filePrefix !== null;
		$out->putBool($action);
		if ($action) {
			$out->putInt($this->frameRate);
			$out->putString($this->filePrefix);
		}
	}

	public function handle(PacketHandlerInterface $handler) : bool{
		return $handler->handlePlayerVideoCapture($this);
	}
}
