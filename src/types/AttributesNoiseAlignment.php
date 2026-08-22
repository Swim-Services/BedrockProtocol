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

namespace pocketmine\network\mcpe\protocol\types;

use pmmp\encoding\Byte;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\VarInt;

/**
 * @see AttributeLayer&AttributesUpdateEnvironment
 */
final class AttributesNoiseAlignment{
	public const MIN_LOCAL_TRANSITION_END = 0;

	public function __construct(
		private int $type,
		private int $value,
	){}

	public function getType() : int { return $this->type; }

	public function getValue() : int { return $this->value; }

	public static function read(ByteBufferReader $in, int $protocolId) : self{
		$type = Byte::readUnsigned($in);
		$value = VarInt::readUnsignedInt($in);
		return new self($type, $value);
	}

	public function write(ByteBufferWriter $out, int $protocolId) : void{
		Byte::writeUnsigned($out, $this->type);
		VarInt::writeUnsignedInt($out, $this->value);
	}
}
