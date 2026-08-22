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

use pmmp\encoding\Byte;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pocketmine\network\mcpe\protocol\types\FurnaceOptions;

class SetPlayerFurnaceOptionsPacket extends DataPacket implements ClientboundPacket, ServerboundPacket{
	public const NETWORK_ID = ProtocolInfo::SET_PlAYER_FURNACE_OPTIONS;

	public const FURNACE_TYPE_NONE = 0;
	public const FURNACE_TYPE_FURNACE = 1;
	public const FURNACE_TYPE_BLAST_FURNACE = 2;
	public const FURNACE_TYPE_SMOKER = 3;

	private int $furnaceType;
	private FurnaceOptions $furnaceOptions;

	/**
	 * @generate-create-func
	 */
	public static function create(int $furnaceType, FurnaceOptions $furnaceOptions) : self{
		$result = new self;
		$result->furnaceType = $furnaceType;
		$result->furnaceOptions = $furnaceOptions;
		return $result;
	}

	protected function decodePayload(ByteBufferReader $in, int $protocolId) : void{
		$this->furnaceType = Byte::readUnsigned($in);
		$this->furnaceOptions = FurnaceOptions::read($in, $protocolId);
	}

	protected function encodePayload(ByteBufferWriter $out, int $protocolId) : void{
		Byte::writeUnsigned($out, $this->furnaceType);
		$this->furnaceOptions->write($out, $protocolId);
	}

	public function handle(PacketHandlerInterface $handler) : bool{
		return $handler->handleSetPlayerFurnaceOptions($this);
	}
}
