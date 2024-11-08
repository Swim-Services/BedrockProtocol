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

use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;
use function count;

class CameraAimAssistCategoriesDefinition{
	/**
	 * @param CameraAimAssistCategoryDefinition[] $categories
	 */
	public function __construct(
		private string $id,
		private array $categories,
	){}

	public function getId() : string{ return $this->id; }

	/** @return CameraAimAssistCategoryDefinition[] */
	public function getCategories() : array{ return $this->categories; }

	public static function read(PacketSerializer $in) : self{
		$id = $in->getString();
		$categoriesCount = $in->getUnsignedVarInt();
		$categories = [];
		while($categoriesCount-- > 0){
			$categories[] = CameraAimAssistCategoryDefinition::read($in);
		}
		return new self($id, $categories);
	}

	public function write(PacketSerializer $out) : void{
		$out->putString($this->id);
		$out->putUnsignedVarInt(count($this->categories));
		foreach($this->categories as $category) {
			$category->write($out);
		}
	}
}
