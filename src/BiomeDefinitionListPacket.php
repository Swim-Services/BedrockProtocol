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
use pocketmine\network\mcpe\protocol\types\CacheableNbt;

class BiomeDefinitionListPacket extends DataPacket implements ClientboundPacket{
	public const NETWORK_ID = ProtocolInfo::BIOME_DEFINITION_LIST_PACKET;

	/** @phpstan-var CacheableNbt<\pocketmine\nbt\tag\CompoundTag> */
	public CacheableNbt $definitions;

	public string $rawPayload; // TODO

	/**
	 * @generate-create-func
	 * @phpstan-param CacheableNbt<\pocketmine\nbt\tag\CompoundTag> $definitions
	 */
	public static function create(CacheableNbt $definitions, string $rawPayload) : self{
		$result = new self;
		$result->definitions = $definitions;
		$result->rawPayload = $rawPayload;
		return $result;
	}

	protected function decodePayload(PacketSerializer $in) : void{
		if ($in->getProtocolId() < ProtocolInfo::PROTOCOL_1_21_80) {
			$this->definitions = new CacheableNbt($in->getNbtCompoundRoot());
		} else {
			$this->rawPayload = $in->getRemaining();
		}
	}

	protected function encodePayload(PacketSerializer $out) : void{
		if ($out->getProtocolId() < ProtocolInfo::PROTOCOL_1_21_80) {
			$out->put($this->definitions->getEncodedNbt());
		} else {
			$out->put($this->rawPayload);
		}
	}

	public function handle(PacketHandlerInterface $handler) : bool{
		return $handler->handleBiomeDefinitionList($this);
	}
}
