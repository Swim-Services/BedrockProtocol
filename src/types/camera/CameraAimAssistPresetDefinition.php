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

class CameraAimAssistPresetDefinition{
	/**
	 * @param string[] $exclusionList
	 * @param string[] $liquidTargetingList
	 * @param array<string, string> $itemSettings
	 */
	public function __construct(
		private string $id,
		private string $categories,
		private array $exclusionList,
		private array $liquidTargetingList,
		private array $itemSettings,
		private ?string $defaultItemSettings,
		private ?string $handSettings,
	){}

	public function getId() : string{ return $this->id; }

	public function getCategories() : string{ return $this->categories; }

	/** @return string[] */
	public function getExclusionList() : array{ return $this->exclusionList; }

	/** @return string[] */
	public function getLiquidTargetingList() : array{ return $this->liquidTargetingList; }

	/** @return array<string, string> */
	public function getItemSettings() : array{ return $this->itemSettings; }

	public function getDefaultItemSettings() : string{ return $this->defaultItemSettings; }

	public function getHandSettings() : string{ return $this->handSettings; }

	public static function read(PacketSerializer $in) : self{
		$id = $in->getString();
		$categories = $in->getString();

		$exclusionListCount = $in->getUnsignedVarInt();
		$exclusionList = [];
		while($exclusionListCount-- > 0){
			$exclusionList[] = $in->getString();
		}

		$liquidTargetingListCount = $in->getUnsignedVarInt();
		$liquidTargetingList = [];
		while($liquidTargetingListCount-- > 0){
			$liquidTargetingList[] = $in->getString();
		}

		$itemSettingsCount = $in->getUnsignedVarInt();
		$itemSettings = [];
		while($itemSettingsCount-- > 0){
			$itemId = $in->getString();
			$category = $in->getString();
			$itemSettings[$itemId] = $category;
		}

		$defaultItemSettings = $in->readOptional($in->getString(...));
		$handSettings = $in->readOptional($in->getString(...));

		return new self($id, $categories, $exclusionList, $liquidTargetingList, $itemSettings, $defaultItemSettings, $handSettings);
	}

	public function write(PacketSerializer $out) : void{
		$out->putString($this->id);
		$out->putString($this->categories);

		$out->putUnsignedVarInt(count($this->exclusionList));
		foreach($this->exclusionList as $blockId) {
			$out->putString($blockId);
		}

		$out->putUnsignedVarInt(count($this->liquidTargetingList));
		foreach($this->liquidTargetingList as $itemId) {
			$out->putString($itemId);
		}

		$out->putUnsignedVarInt(count($this->itemSettings));
		foreach($this->itemSettings as $itemId => $category) {
			$out->putString($itemId);
			$out->putString($category);
		}

		$out->writeOptional($this->defaultItemSettings, $out->putString(...));
		$out->writeOptional($this->handSettings, $out->putString(...));
	}
}
