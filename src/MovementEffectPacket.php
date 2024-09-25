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

class MovementEffectPacket extends DataPacket implements ClientboundPacket{
	public const NETWORK_ID = ProtocolInfo::MOVEMENT_EFFECT;

	public const GLIDE_BOOST = 0;

	public int $actorRuntimeId;
	public int $effectType;
	public int $effectDuration;
	public int $tick;

	/**
	 * @generate-create-func
	 */
	public static function create(
		int $actorRuntimeId,
		int $effectType,
		int $effectDuration,
		int $tick,
	) : self{
		$result = new self;
		$result->actorRuntimeId = $actorRuntimeId;
		$result->effectType = $effectType;
		$result->effectDuration = $effectDuration;
		$result->tick = $tick;
		return $result;
	}

	protected function decodePayload(PacketSerializer $in) : void{
		$this->actorRuntimeId = $in->getActorRuntimeId();
		$this->effectType = $in->getVarInt();
		$this->effectDuration = $in->getVarInt();
		$this->tick = $in->getPlayerInputTick();
	}

	protected function encodePayload(PacketSerializer $out) : void{
		$out->putActorRuntimeId($this->actorRuntimeId);
		$out->putVarInt($this->effectType);
		$out->putVarInt($this->effectDuration);
		$out->putPlayerInputTick($this->tick);
	}

	public function handle(PacketHandlerInterface $handler) : bool{
		return $handler->handleMovementEffect($this);
	}
}
