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

namespace pocketmine\network\mcpe\protocol\serializer;

use PHPUnit\Framework\TestCase;
use pocketmine\network\mcpe\protocol\types\skin\PersonaSkinPiece;

final class LegacySkinDataConverterTest extends TestCase{
	public function testPersonaTintWireNamesMatchV2168() : void{
		$expectedNames = [
			"persona_facial_hair" => "facial_hair",
			"persona_face_accessory" => "face_accessory",
			"persona_left_leg" => "left_leg",
			"persona_right_leg" => "right_leg",
			"persona_left_arm" => "left_arm",
			"persona_right_arm" => "right_arm",
			"persona_classic_skin" => "classic_skin",
		];

		foreach($expectedNames as $legacyName => $wireName){
			$type = LegacySkinDataConverter::personaPieceTypeFromString($legacyName);
			self::assertSame($wireName, LegacySkinDataConverter::personaPieceTypeToBareString($type));
			self::assertSame($type, LegacySkinDataConverter::personaPieceTypeFromBareString($wireName));
		}
	}

	public function testSingularPersonaHandPieceTypeIsAccepted() : void{
		self::assertSame(
			PersonaSkinPiece::PIECE_TYPE_HANDS,
			LegacySkinDataConverter::personaPieceTypeFromString("persona_hand")
		);
	}

	public function testHandsPieceTypeUsesCanonicalLegacyName() : void{
		self::assertSame(
			"persona_hands",
			LegacySkinDataConverter::personaPieceTypeToString(PersonaSkinPiece::PIECE_TYPE_HANDS)
		);
	}
}
