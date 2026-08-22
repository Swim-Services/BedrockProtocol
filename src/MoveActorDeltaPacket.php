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
use pmmp\encoding\DataDecodeException;
use pmmp\encoding\LE;
use pmmp\encoding\VarInt;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;

class MoveActorDeltaPacket extends DataPacket implements ClientboundPacket{
	public const NETWORK_ID = ProtocolInfo::MOVE_ACTOR_DELTA_PACKET;

	public const FLAG_HAS_X = 0x01;
	public const FLAG_HAS_Y = 0x02;
	public const FLAG_HAS_Z = 0x04;
	public const FLAG_HAS_PITCH = 0x08;
	public const FLAG_HAS_YAW = 0x10;
	public const FLAG_HAS_HEAD_YAW = 0x20;
	public const FLAG_GROUND = 0x40;
	public const FLAG_TELEPORT = 0x80;
	public const FLAG_FORCE_MOVE_LOCAL_ENTITY = 0x100;
	public const FLAG_FORCE_COMPLETION = 0x200;
	private const VALUE_FLAGS = self::FLAG_HAS_X | self::FLAG_HAS_Y | self::FLAG_HAS_Z |
		self::FLAG_HAS_PITCH | self::FLAG_HAS_YAW | self::FLAG_HAS_HEAD_YAW;

	public int $actorRuntimeId;
	public int $flags = 0;
	public ?float $xPos = null;
	public ?float $yPos = null;
	public ?float $zPos = null;
	public ?float $pitch = null;
	public ?float $yaw = null;
	public ?float $headYaw = null;
	public int $ticks = 3;

	/** @throws DataDecodeException */
	private function readCoord(ByteBufferReader $in, int $protocolId, int $flag) : ?float{
		$present = $protocolId >= ProtocolInfo::PROTOCOL_1_26_40
			? CommonTypes::getBool($in)
			: ($this->flags & $flag) !== 0;
		if($present){
			return LE::readFloat($in);
		}
		return null;
	}

	/** @throws DataDecodeException */
	private function readRotation(ByteBufferReader $in, int $protocolId, int $flag) : ?float{
		$present = $protocolId >= ProtocolInfo::PROTOCOL_1_26_40
			? CommonTypes::getBool($in)
			: ($this->flags & $flag) !== 0;
		if($present){
			return CommonTypes::getRotationByte($in);
		}
		return null;
	}

	protected function decodePayload(ByteBufferReader $in, int $protocolId) : void{
		$this->actorRuntimeId = CommonTypes::getActorRuntimeId($in);
		if($protocolId >= ProtocolInfo::PROTOCOL_1_26_40){
			$this->flags = 0;
		}else{
			$this->flags = LE::readUnsignedShort($in);
		}
		$this->xPos = $this->readCoord($in, $protocolId, self::FLAG_HAS_X);
		$this->yPos = $this->readCoord($in, $protocolId, self::FLAG_HAS_Y);
		$this->zPos = $this->readCoord($in, $protocolId, self::FLAG_HAS_Z);
		$this->pitch = $this->readRotation($in, $protocolId, self::FLAG_HAS_PITCH);
		$this->yaw = $this->readRotation($in, $protocolId, self::FLAG_HAS_YAW);
		$this->headYaw = $this->readRotation($in, $protocolId, self::FLAG_HAS_HEAD_YAW);
		if($protocolId >= ProtocolInfo::PROTOCOL_1_26_40){
			foreach([
				self::FLAG_HAS_X => $this->xPos,
				self::FLAG_HAS_Y => $this->yPos,
				self::FLAG_HAS_Z => $this->zPos,
				self::FLAG_HAS_PITCH => $this->pitch,
				self::FLAG_HAS_YAW => $this->yaw,
				self::FLAG_HAS_HEAD_YAW => $this->headYaw,
			] as $flag => $value){
				if($value !== null){
					$this->flags |= $flag;
				}
			}
		}
		if($protocolId >= ProtocolInfo::PROTOCOL_1_26_40){
			$this->readBooleanFlag($in, self::FLAG_GROUND);
			$this->readBooleanFlag($in, self::FLAG_TELEPORT);
			$this->readBooleanFlag($in, self::FLAG_FORCE_MOVE_LOCAL_ENTITY);
			$this->readBooleanFlag($in, self::FLAG_FORCE_COMPLETION);
			if ($protocolId >= ProtocolInfo::PROTOCOL_1_26_50) {
				$this->ticks = VarInt::readUnsignedLong($in);
			}
		}
	}

	private function writeCoord(ByteBufferWriter $out, int $protocolId, ?float $value) : void{
		if($protocolId >= ProtocolInfo::PROTOCOL_1_26_40){
			CommonTypes::putBool($out, $value !== null);
		}
		if($value !== null){
			LE::writeFloat($out, $value);
		}
	}

	private function writeRotation(ByteBufferWriter $out, int $protocolId, ?float $value) : void{
		if($protocolId >= ProtocolInfo::PROTOCOL_1_26_40){
			CommonTypes::putBool($out, $value !== null);
		}
		if($value !== null){
			CommonTypes::putRotationByte($out, $value);
		}
	}

	protected function encodePayload(ByteBufferWriter $out, int $protocolId) : void{
		CommonTypes::putActorRuntimeId($out, $this->actorRuntimeId);
		$this->flags &= ~self::VALUE_FLAGS;
		foreach([
			self::FLAG_HAS_X => $this->xPos,
			self::FLAG_HAS_Y => $this->yPos,
			self::FLAG_HAS_Z => $this->zPos,
			self::FLAG_HAS_PITCH => $this->pitch,
			self::FLAG_HAS_YAW => $this->yaw,
			self::FLAG_HAS_HEAD_YAW => $this->headYaw,
		] as $flag => $value){
			if($value !== null){
				$this->flags |= $flag;
			}
		}
		if($protocolId < ProtocolInfo::PROTOCOL_1_26_40){
			LE::writeUnsignedShort($out, $this->flags & ~self::FLAG_FORCE_COMPLETION);
		}
		$this->writeCoord($out, $protocolId, $this->xPos);
		$this->writeCoord($out, $protocolId, $this->yPos);
		$this->writeCoord($out, $protocolId, $this->zPos);
		$this->writeRotation($out, $protocolId, $this->pitch);
		$this->writeRotation($out, $protocolId, $this->yaw);
		$this->writeRotation($out, $protocolId, $this->headYaw);
		if($protocolId >= ProtocolInfo::PROTOCOL_1_26_40){
			CommonTypes::putBool($out, ($this->flags & self::FLAG_GROUND) !== 0);
			CommonTypes::putBool($out, ($this->flags & self::FLAG_TELEPORT) !== 0);
			CommonTypes::putBool($out, ($this->flags & self::FLAG_FORCE_MOVE_LOCAL_ENTITY) !== 0);
			CommonTypes::putBool($out, ($this->flags & self::FLAG_FORCE_COMPLETION) !== 0);
			if ($protocolId >= ProtocolInfo::PROTOCOL_1_26_50) {
				VarInt::writeUnsignedLong($out, $this->ticks);
			}
		}
	}

	private function readBooleanFlag(ByteBufferReader $in, int $flag) : void{
		if(CommonTypes::getBool($in)){
			$this->flags |= $flag;
		}
	}

	public function handle(PacketHandlerInterface $handler) : bool{
		return $handler->handleMoveActorDelta($this);
	}
}
