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

use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\VarInt;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;

class FurnaceOptions {
	public const LAYOUT_NONE = 0;
	public const LAYOUT_INVENTORY_ONLY = 1;
	public const LAYOUT_DEFAULT = 2;

	public const LEFT_TAB_NONE = 0;
	public const LEFT_TAB_RECIPE_FOOD = 1;
	public const LEFT_TAB_RECIPE_ITEMS = 2;
	public const LEFT_TAB_RECIPE_BLOCKS = 3;
	public const LEFT_TAB_RECIPE_SEARCH = 4;
	public const LEFT_TAB_INVENTORY = 5;

	public function __construct(
		private int $leftFurnaceTab,
		private bool $filtering,
		private int $layout
	){

	}

	public function getLeftFurnaceTab() : int {
		return $this->leftFurnaceTab;
	}

	public function getFiltering() : bool {
		return $this->filtering;
	}

	public function getLayout() : int {
		return $this->layout;
	}

	public static function read(ByteBufferReader $in, int $protocolId) : self{
		$leftFurnaceTab = VarInt::readSignedInt($in);
		$filtering = CommonTypes::getBool($in);
		$layout = VarInt::readSignedInt($in);

		return new self(
			$leftFurnaceTab,
			$filtering,
			$layout,
		);
	}

	public function write(ByteBufferWriter $out, int $protocolId) : void{
		VarInt::writeSignedInt($out, $this->leftFurnaceTab);
		CommonTypes::putBool($out, $this->filtering);
		VarInt::writeSignedInt($out, $this->layout);
	}
}
