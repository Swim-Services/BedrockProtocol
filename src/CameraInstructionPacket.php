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

use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;
use pocketmine\network\mcpe\protocol\types\CacheableNbt;
use pocketmine\network\mcpe\protocol\types\camera\CameraFadeInstruction;
use pocketmine\network\mcpe\protocol\types\camera\CameraSetInstruction;

class CameraInstructionPacket extends DataPacket implements ClientboundPacket{
	public const NETWORK_ID = ProtocolInfo::CAMERA_INSTRUCTION_PACKET;

	private ?CameraSetInstruction $set;
	private ?bool $clear;
	private ?CameraFadeInstruction $fade;

	/**
	 * @generate-create-func
	 */
	public static function create(?CameraSetInstruction $set, ?bool $clear, ?CameraFadeInstruction $fade) : self{
		$result = new self;
		$result->set = $set;
		$result->clear = $clear;
		$result->fade = $fade;
		return $result;
	}

	public function getSet() : ?CameraSetInstruction{ return $this->set; }

	public function getClear() : ?bool{ return $this->clear; }

	public function getFade() : ?CameraFadeInstruction{ return $this->fade; }

	protected function decodePayload(PacketSerializer $in) : void{
		if($in->getProtocolId() >= ProtocolInfo::PROTOCOL_1_20_30){
			$this->set = $in->readOptional(fn() => CameraSetInstruction::read($in));
			$this->clear = $in->readOptional(fn() => $in->getBool());
			$this->fade = $in->readOptional(fn() => CameraFadeInstruction::read($in));
		}else{
			$this->fromNBT($in->getNbtCompoundRoot());
		}
	}

	protected function fromNBT(CompoundTag $nbt) : void{
		$setTag = $nbt->getCompoundTag("set");
		$this->set = $setTag === null ? null : CameraSetInstruction::fromNBT($setTag);

		$this->clear = $nbt->getTag("clear") === null ? null : $nbt->getByte("clear") !== 0;

		$fadeTag = $nbt->getCompoundTag("fade");
		$this->fade = $fadeTag === null ? null : CameraFadeInstruction::fromNBT($fadeTag);
	}

	protected function encodePayload(PacketSerializer $out) : void{
		if($out->getProtocolId() >= ProtocolInfo::PROTOCOL_1_20_30){
			$out->writeOptional($this->set, fn(CameraSetInstruction $v) => $v->write($out));
			$out->writeOptional($this->clear, fn(bool $v) => $out->putBool($v));
			$out->writeOptional($this->fade, fn(CameraFadeInstruction $v) => $v->write($out));
		}else{
			$data = new CacheableNbt($this->toNBT());
			$out->put($data->getEncodedNbt());
		}
	}

	protected function toNBT() : CompoundTag{
		$nbt = CompoundTag::create();

		if($this->set !== null){
			$nbt->setTag("set", $this->set->toNBT());
		}
		if($this->clear !== null){
			$nbt->setByte("clear", (int) $this->clear);
		}
		if($this->fade !== null){
			$nbt->setTag("fade", $this->fade->toNBT());
		}

		return $nbt;
	}

	public function handle(PacketHandlerInterface $handler) : bool{
		return $handler->handleCameraInstruction($this);
	}
}
