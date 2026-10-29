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

namespace pocketmine\network\mcpe\protocol\types\inventory\stackrequest;

use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\network\mcpe\protocol\PacketDecodeException;

final class ItemStackRequestActionType{

	private function __construct(){
		//NOOP
	}

	public const TAKE = 0;
	public const PLACE = 1;
	public const SWAP = 2;
	public const DROP = 3;
	public const DESTROY = 4;
	public const CRAFTING_CONSUME_INPUT = 5;
	public const CRAFTING_CREATE_SPECIFIC_RESULT = 6;
	public const PLACE_INTO_BUNDLE = 7;
	public const TAKE_FROM_BUNDLE = 8;
	public const LAB_TABLE_COMBINE = 9;
	public const BEACON_PAYMENT = 10;
	public const MINE_BLOCK = 11;
	public const CRAFTING_RECIPE = 12;
	public const CRAFTING_RECIPE_AUTO = 13; //recipe book?
	public const CREATIVE_CREATE = 14;
	public const CRAFTING_RECIPE_OPTIONAL = 15; //anvil/cartography table rename
	public const CRAFTING_GRINDSTONE = 16;
	public const CRAFTING_LOOM = 17;
	public const CRAFTING_NON_IMPLEMENTED_DEPRECATED_ASK_TY_LAING = 18;
	public const CRAFTING_RESULTS_DEPRECATED_ASK_TY_LAING = 19; //no idea what this is for

	// Internal ID: 18 and 19 retain their legacy meanings for older clients.
	public const CRAFTING_RESERVED = 20;

	public static function toModernTypeId(int $legacyTypeId, int $protocolId = ProtocolInfo::CURRENT_PROTOCOL) : int{
		if($legacyTypeId === self::CRAFTING_RESERVED){
			if($protocolId < ProtocolInfo::PROTOCOL_1_26_60){
				throw new \InvalidArgumentException("Reserved crafting action requires protocol 1.26.60");
			}
			return 16;
		}
		if($legacyTypeId === self::PLACE_INTO_BUNDLE || $legacyTypeId === self::TAKE_FROM_BUNDLE){
			throw new \InvalidArgumentException("Bundle actions are not supported by protocol 1.26.40");
		}
		return ($legacyTypeId >= self::LAB_TABLE_COMBINE ? $legacyTypeId - 2 : $legacyTypeId)
			+ ($protocolId >= ProtocolInfo::PROTOCOL_1_26_60 && $legacyTypeId >= self::CRAFTING_NON_IMPLEMENTED_DEPRECATED_ASK_TY_LAING ? 1 : 0);
	}

	public static function fromModernTypeId(int $modernTypeId, int $protocolId = ProtocolInfo::CURRENT_PROTOCOL) : int{
		if($modernTypeId < 0 || $modernTypeId > ($protocolId >= ProtocolInfo::PROTOCOL_1_26_60 ? 18 : 17)){
			throw new PacketDecodeException("Unhandled item stack request action variant $modernTypeId");
		}
		// 1.26.60 inserts CraftReservedAction before CraftNonImplemented/CraftResults.
		// https://github.com/Mojang/bedrock-protocol-docs/blob/v1.26.60/json/ItemStackRequest.json
		if($protocolId >= ProtocolInfo::PROTOCOL_1_26_60){
			if($modernTypeId === 16){
				return self::CRAFTING_RESERVED;
			}
			if($modernTypeId > 16){
				--$modernTypeId;
			}
		}
		return $modernTypeId >= 7 ? $modernTypeId + 2 : $modernTypeId;
	}
}
