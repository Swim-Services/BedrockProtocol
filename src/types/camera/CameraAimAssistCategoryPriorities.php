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

class CameraAimAssistCategoryPriorities{
	/**
	 * @param array<string, int> $entities
	 * @param array<string, int> $blocks
	 */
	public function __construct(
		private array $entities,
		private array $blocks,
		private ?int $entityDefault,
		private ?int $blockDefault,
	){}

	/** @return array<string, int> */
	public function getEntities() : array{ return $this->entities; }

		/** @return array<string, int> */
	public function getBlocks() : array{ return $this->blocks; }

	public function getEntityDefault() : ?int{ return $this->entityDefault; }

	public function getBlockDefault() : ?int{ return $this->blockDefault; }

	public static function read(PacketSerializer $in) : self{
		$entitiesCount = $in->getUnsignedVarInt();
		$entities = [];
		while($entitiesCount-- > 0){
			$itemId = $in->getString();
			$priority = $in->getInt();
			$entities[$itemId] = $priority;
		}
		$blocksCount = $in->getUnsignedVarInt();
		$blocks = [];
		while($blocksCount-- > 0){
			$blockId = $in->getString();
			$priority = $in->getInt();
			$blocks[$blockId] = $priority;
		}
		$entityDefault = $in->readOptional($in->getInt(...));
		$blockDefault = $in->readOptional($in->getInt(...));
		return new self($entities, $blocks, $entityDefault, $blockDefault);
	}

	public function write(PacketSerializer $out) : void{
		$out->putUnsignedVarInt(count($this->entities));
		foreach($this->entities as $itemId => $priority) {
			$out->putString($itemId);
			$out->putInt($priority);
		}
		foreach($this->blocks as $blockId => $priority) {
			$out->putString($blockId);
			$out->putInt($priority);
		}
		$out->writeOptional($this->entityDefault, $out->putInt(...));
		$out->writeOptional($this->blockDefault, $out->putInt(...));
	}
}
