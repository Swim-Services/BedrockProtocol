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

namespace pocketmine\network\mcpe\protocol\types\inventory;

use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;

final class CreativeGroupEntry {
	public function __construct(
		private int $category,
		private string $name,
		private ItemStack $iconItem
	){}

	public function getCategory() : int{ return $this->category; }

	public function getName() : string{ return $this->name; }

	public function getIconItem() : ItemStack{ return $this->iconItem; }

	public static function read(PacketSerializer $in) : self{
		$category = $in->getLInt();
		$name = $in->getString();
		$iconItem = $in->getItemStackWithoutStackId();
		return new self($category, $name, $iconItem);
	}

	public function write(PacketSerializer $out) : void{
		$out->putLInt($this->category);
		$out->putString($this->name);
		$out->putItemStackWithoutStackId($this->iconItem);
	}

}
