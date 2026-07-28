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

use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\VarInt;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use function count;

final class RecipeUnlockingRequirement{
	public const CONTEXT_NONE = 0;
	public const CONTEXT_ALWAYS_UNLOCKED = 1;
	public const CONTEXT_PLAYER_IN_WATER = 2;
	public const CONTEXT_PLAYER_HAS_MANY_ITEMS = 3;

	/**
	 * @param RecipeIngredient[]|null $unlockingIngredients
	 * @phpstan-param list<RecipeIngredient>|null $unlockingIngredients
	 */
	public function __construct(
		private ?array $unlockingIngredients,
		private int $unlockingContext = self::CONTEXT_NONE,
	){}

	public function getUnlockingContext() : int{ return $this->unlockingContext; }

	/**
	 * @return RecipeIngredient[]|null
	 * @phpstan-return list<RecipeIngredient>|null
	 */
	public function getUnlockingIngredients() : ?array{ return $this->unlockingIngredients; }

	public static function read(ByteBufferReader $in, int $protocolId) : self{
		if($protocolId >= ProtocolInfo::PROTOCOL_1_26_40){
			$context = VarInt::readSignedInt($in);
			$ingredients = null;
			if(CommonTypes::getBool($in)){
				$ingredients = [];
				for($i = 0, $count = VarInt::readUnsignedInt($in); $i < $count; ++$i){
					$ingredients[] = RecipeIngredient::read($in, $protocolId);
				}
			}
			return new self($ingredients, $context);
		}
		//I don't know what the point of this structure is. It could easily have been a list<RecipeIngredient> instead.
		//It's basically just an optional list, which could have been done by an empty list wherever it's not needed.
		$unlockingContext = CommonTypes::getBool($in);
		$unlockingIngredients = null;
		if(!$unlockingContext){
			$unlockingIngredients = [];
			for($i = 0, $count = VarInt::readUnsignedInt($in); $i < $count; $i++){
				$unlockingIngredients[] = CommonTypes::getRecipeIngredient($in, $protocolId);
			}
		}

		return new self($unlockingIngredients);
	}

	public function write(ByteBufferWriter $out, int $protocolId) : void{
		if($protocolId >= ProtocolInfo::PROTOCOL_1_26_40){
			VarInt::writeSignedInt($out, $this->unlockingContext);
			CommonTypes::putBool($out, $this->unlockingIngredients !== null);
			if($this->unlockingIngredients !== null){
				VarInt::writeUnsignedInt($out, count($this->unlockingIngredients));
				foreach($this->unlockingIngredients as $ingredient){
					$ingredient->write($out, $protocolId);
				}
			}
			return;
		}
		CommonTypes::putBool($out, $this->unlockingIngredients === null);
		if($this->unlockingIngredients !== null){
			VarInt::writeUnsignedInt($out, count($this->unlockingIngredients));
			foreach($this->unlockingIngredients as $ingredient){
				CommonTypes::putRecipeIngredient($out, $ingredient, $protocolId);
			}
		}
	}
}
