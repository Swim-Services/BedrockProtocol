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

namespace pocketmine\network\mcpe\protocol\types\recipe;

use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStack;
use Ramsey\Uuid\UuidInterface;
use function count;

final class ShapedRecipe extends RecipeWithTypeId{
	private string $blockName;

	/**
	 * @param RecipeIngredient[][] $input
	 * @param ItemStack[]          $output
	 * @phpstan-param list<list<RecipeIngredient>> $input
	 * @phpstan-param list<ItemStack> $output
	 */
	public function __construct(
		int $typeId,
		private string $recipeId,
		private array $input,
		private array $output,
		private UuidInterface $uuid,
		string $blockType, //TODO: rename this
		private int $priority,
		private bool $symmetric,
		private RecipeUnlockingRequirement $unlockingRequirement,
		private int $recipeNetId
	){
		parent::__construct($typeId);
		$rows = count($input);
		if($rows < 1 or $rows > 3){
			throw new \InvalidArgumentException("Expected 1, 2 or 3 input rows");
		}
		$columns = null;
		foreach($input as $rowNumber => $row){
			if($columns === null){
				$columns = count($row);
			}elseif(count($row) !== $columns){
				throw new \InvalidArgumentException("Expected each row to be $columns columns, but have " . count($row) . " in row $rowNumber");
			}
		}
		$this->blockName = $blockType;
	}

	public function getRecipeId() : string{
		return $this->recipeId;
	}

	public function getWidth() : int{
		return count($this->input[0]);
	}

	public function getHeight() : int{
		return count($this->input);
	}

	/**
	 * @return RecipeIngredient[][]
	 * @phpstan-return list<list<RecipeIngredient>>
	 */
	public function getInput() : array{
		return $this->input;
	}

	/**
	 * @return ItemStack[]
	 * @phpstan-return list<ItemStack>
	 */
	public function getOutput() : array{
		return $this->output;
	}

	public function getUuid() : UuidInterface{
		return $this->uuid;
	}

	public function getBlockName() : string{
		return $this->blockName;
	}

	public function getPriority() : int{
		return $this->priority;
	}

	public function isSymmetric() : bool{ return $this->symmetric; }

	public function getUnlockingRequirement() : RecipeUnlockingRequirement{ return $this->unlockingRequirement; }

	public function getRecipeNetId() : int{
		return $this->recipeNetId;
	}

	public static function decode(int $recipeType, PacketSerializer $in) : self{
		$recipeId = $in->getString();
		$width = $in->getVarInt();
		$height = $in->getVarInt();
		$input = [];
		for($row = 0; $row < $height; ++$row){
			for($column = 0; $column < $width; ++$column){
				$input[$row][$column] = $in->getRecipeIngredient();
			}
		}

		$output = [];
		for($k = 0, $resultCount = $in->getUnsignedVarInt(); $k < $resultCount; ++$k){
			$output[] = $in->getItemStackWithoutStackId();
		}
		$uuid = $in->getUUID();
		$block = $in->getString();
		$priority = $in->getVarInt();
		if($in->getProtocolId() >= ProtocolInfo::PROTOCOL_1_20_80){
			$symmetric = $in->getBool();

			if($in->getProtocolId() >= ProtocolInfo::PROTOCOL_1_21_0){
				$unlockingRequirement = RecipeUnlockingRequirement::read($in);
			}
		}

		$recipeNetId = $in->readRecipeNetId();

		return new self($recipeType, $recipeId, $input, $output, $uuid, $block, $priority, $symmetric ?? true, $unlockingRequirement ?? new RecipeUnlockingRequirement(null), $recipeNetId);
	}

	public function encode(PacketSerializer $out) : void{
		$out->putString($this->recipeId);
		$out->putVarInt($this->getWidth());
		$out->putVarInt($this->getHeight());
		foreach($this->input as $row){
			foreach($row as $ingredient){
				$out->putRecipeIngredient($ingredient);
			}
		}

		$out->putUnsignedVarInt(count($this->output));
		foreach($this->output as $item){
			$out->putItemStackWithoutStackId($item);
		}

		$out->putUUID($this->uuid);
		$out->putString($this->blockName);
		$out->putVarInt($this->priority);
		if($out->getProtocolId() >= ProtocolInfo::PROTOCOL_1_20_80){
			$out->putBool($this->symmetric);

			if($out->getProtocolId() >= ProtocolInfo::PROTOCOL_1_21_0){
				$this->unlockingRequirement->write($out);
			}
		}

		$out->writeRecipeNetId($this->recipeNetId);
	}
}