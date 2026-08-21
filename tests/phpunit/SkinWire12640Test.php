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

namespace pocketmine\network\mcpe\protocol;

use PHPUnit\Framework\TestCase;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pocketmine\network\mcpe\protocol\types\skin\PersonaPieceTintColor;
use pocketmine\network\mcpe\protocol\types\skin\SkinData;
use pocketmine\network\mcpe\protocol\types\skin\SkinImage;
use Ramsey\Uuid\Uuid;
use function str_contains;
use function str_ends_with;

final class SkinWire12640Test extends TestCase{
	public function testPlayerSkinWireFormat() : void{
		$skin = new SkinData(
			skinId: "skin-id",
			playFabId: "playfab",
			resourcePatch: "{}",
			skinImage: new SkinImage(1, 1, "\0\0\0\0"),
			geometryDataEngineVersion: "1.26.40",
			pieceTintColors: [new PersonaPieceTintColor("persona_face_accessory", [0, 1, -1, 0x12345678])],
			trustedSkinFlag: SkinData::TRUSTED_SKIN_FLAG_TRUE,
			profileHash: "HASH",
		);

		$packet = PlayerSkinPacket::create(Uuid::fromString(Uuid::NIL), "OLD", "NEW", $skin);
		$out = new ByteBufferWriter();
		$packet->encode($out, ProtocolInfo::PROTOCOL_1_26_40);
		$wire = $out->getData();

		self::assertTrue(str_contains($wire, "\x0eface_accessory"));
		self::assertTrue(str_ends_with($wire, "\x04true\x04HASH\x03NEW\x03OLD"));

		$decoded = new PlayerSkinPacket();
		$decoded->decode(new ByteBufferReader($wire), ProtocolInfo::PROTOCOL_1_26_40);
		self::assertSame("NEW", $decoded->newSkinName);
		self::assertSame("OLD", $decoded->oldSkinName);
		self::assertSame(SkinData::TRUSTED_SKIN_FLAG_TRUE, $decoded->skin->getTrustedSkinFlag());
		self::assertSame("persona_face_accessory", $decoded->skin->getPieceTintColors()[0]->getPieceType());
	}
}
