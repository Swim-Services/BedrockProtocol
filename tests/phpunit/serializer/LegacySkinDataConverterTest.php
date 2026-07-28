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
