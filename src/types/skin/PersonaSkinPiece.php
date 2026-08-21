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

namespace pocketmine\network\mcpe\protocol\types\skin;

use Ramsey\Uuid\UuidInterface;

final class PersonaSkinPiece{

	//Mojang's real wire enum has an unlisted/reserved value at 0 (confirmed empirically: comparing the same
	//persona piece UUIDs between a login-derived skin and a real PlayerSkinPacket wire capture showed every
	//single piece type value on the wire is exactly one higher than these constants previously assumed), so
	//these all start at 1 rather than 0 to match the real wire encoding.
	public const PIECE_TYPE_SKELETON = 1;
	public const PIECE_TYPE_BODY = 2;
	public const PIECE_TYPE_SKIN = 3;
	public const PIECE_TYPE_BOTTOM = 4;
	public const PIECE_TYPE_FEET = 5;
	public const PIECE_TYPE_DRESS = 6;
	public const PIECE_TYPE_TOP = 7;
	public const PIECE_TYPE_HIGH_PANTS = 8;
	public const PIECE_TYPE_HANDS = 9;
	public const PIECE_TYPE_OUTERWEAR = 10;
	public const PIECE_TYPE_FACIAL_HAIR = 11;
	public const PIECE_TYPE_MOUTH = 12;
	public const PIECE_TYPE_EYES = 13;
	public const PIECE_TYPE_HAIR = 14;
	public const PIECE_TYPE_HOOD = 15;
	public const PIECE_TYPE_BACK = 16;
	public const PIECE_TYPE_FACE_ACCESSORY = 17;
	public const PIECE_TYPE_HEAD = 18;
	public const PIECE_TYPE_LEGS = 19;
	public const PIECE_TYPE_LEFT_LEG = 20;
	public const PIECE_TYPE_RIGHT_LEG = 21;
	public const PIECE_TYPE_ARMS = 22;
	public const PIECE_TYPE_LEFT_ARM = 23;
	public const PIECE_TYPE_RIGHT_ARM = 24;
	public const PIECE_TYPE_CAPES = 25;
	public const PIECE_TYPE_CLASSIC_SKIN = 26;
	public const PIECE_TYPE_EMOTE = 27;

	public function __construct(
		private string $pieceId,
		private int $pieceType,
		private UuidInterface $packId,
		private bool $isDefaultPiece,
		private string $productId
	){}

	public function getPieceId() : string{
		return $this->pieceId;
	}

	public function getPieceType() : int{
		return $this->pieceType;
	}

	public function getPackId() : UuidInterface{
		return $this->packId;
	}

	public function isDefaultPiece() : bool{
		return $this->isDefaultPiece;
	}

	public function getProductId() : string{
		return $this->productId;
	}
}
