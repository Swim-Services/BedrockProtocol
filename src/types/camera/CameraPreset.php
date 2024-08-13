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

namespace pocketmine\network\mcpe\protocol\types\camera;

use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;

final class CameraPreset{
	public const AUDIO_LISTENER_TYPE_CAMERA = 0;
	public const AUDIO_LISTENER_TYPE_PLAYER = 1;

	public function __construct(
		private string $name,
		private string $parent,
		private ?float $xPosition,
		private ?float $yPosition,
		private ?float $zPosition,
		private ?float $pitch,
		private ?float $yaw,
		private ?int $audioListenerType,
		private ?bool $playerEffects
	){}

	public function getName() : string{ return $this->name; }

	public function getParent() : string{ return $this->parent; }

	public function getXPosition() : ?float{ return $this->xPosition; }

	public function getYPosition() : ?float{ return $this->yPosition; }

	public function getZPosition() : ?float{ return $this->zPosition; }

	public function getPitch() : ?float{ return $this->pitch; }

	public function getYaw() : ?float{ return $this->yaw; }

	public function getAudioListenerType() : ?int{ return $this->audioListenerType; }

	public function getPlayerEffects() : ?bool{ return $this->playerEffects; }

	public static function read(PacketSerializer $in) : self{
		$name = $in->getString();
		$parent = $in->getString();
		$xPosition = $in->readOptional(fn() => $in->getLFloat());
		$yPosition = $in->readOptional(fn() => $in->getLFloat());
		$zPosition = $in->readOptional(fn() => $in->getLFloat());
		$pitch = $in->readOptional(fn() => $in->getLFloat());
		$yaw = $in->readOptional(fn() => $in->getLFloat());
		$audioListenerType = $in->readOptional(fn() => $in->getByte());
		$playerEffects = $in->readOptional(fn() => $in->getBool());

		return new self(
			$name,
			$parent,
			$xPosition,
			$yPosition,
			$zPosition,
			$pitch,
			$yaw,
			$audioListenerType,
			$playerEffects
		);
	}

	public static function fromNBT(CompoundTag $nbt) : self{
		return new self(
			$nbt->getString("identifier"),
			$nbt->getString("inherit_from"),
			$nbt->getTag("pos_x") === null ? null : $nbt->getFloat("pos_x"),
			$nbt->getTag("pos_y") === null ? null : $nbt->getFloat("pos_y"),
			$nbt->getTag("pos_z") === null ? null : $nbt->getFloat("pos_z"),
			$nbt->getTag("rot_x") === null ? null : $nbt->getFloat("rot_x"),
			$nbt->getTag("rot_y") === null ? null : $nbt->getFloat("rot_y"),
			$nbt->getTag("audio_listener_type") === null ? null : match($nbt->getString("audio_listener_type")){
				"camera" => self::AUDIO_LISTENER_TYPE_CAMERA,
				"player" => self::AUDIO_LISTENER_TYPE_PLAYER,
				default => throw new \InvalidArgumentException("Invalid audio listener type: " . $nbt->getString("audio_listener_type")),
			},
			$nbt->getTag("player_effects") === null ? null : $nbt->getByte("player_effects") !== 0
		);
	}

	public function write(PacketSerializer $out) : void{
		$out->putString($this->name);
		$out->putString($this->parent);
		$out->writeOptional($this->xPosition, fn(float $v) => $out->putLFloat($v));
		$out->writeOptional($this->yPosition, fn(float $v) => $out->putLFloat($v));
		$out->writeOptional($this->zPosition, fn(float $v) => $out->putLFloat($v));
		$out->writeOptional($this->pitch, fn(float $v) => $out->putLFloat($v));
		$out->writeOptional($this->yaw, fn(float $v) => $out->putLFloat($v));
		$out->writeOptional($this->audioListenerType, fn(int $v) => $out->putByte($v));
		$out->writeOptional($this->playerEffects, fn(bool $v) => $out->putBool($v));
	}

	public function toNBT(int $protocolId) : CompoundTag{
		$nbt = CompoundTag::create()
			->setString("identifier", $this->name)
			->setString("inherit_from", $this->parent);

		if($this->xPosition !== null){
			$nbt->setFloat("pos_x", $this->xPosition);
		}

		if($this->yPosition !== null){
			$nbt->setFloat("pos_y", $this->yPosition);
		}

		if($this->zPosition !== null){
			$nbt->setFloat("pos_z", $this->zPosition);
		}

		if($this->pitch !== null){
			$nbt->setFloat("rot_x", $this->pitch);
		}

		if($this->yaw !== null){
			$nbt->setFloat("rot_y", $this->yaw);
		}

		if($protocolId >= ProtocolInfo::PROTOCOL_1_20_10){
			if($this->audioListenerType !== null){
				$nbt->setString("audio_listener_type", match($this->audioListenerType){
					self::AUDIO_LISTENER_TYPE_CAMERA => "camera",
					self::AUDIO_LISTENER_TYPE_PLAYER => "player",
					default => throw new \InvalidArgumentException("Invalid audio listener type: $this->audioListenerType"),
				});
			}

			if($this->playerEffects !== null){
				$nbt->setByte("player_effects", (int) $this->playerEffects);
			}
		}

		return $nbt;
	}
}
