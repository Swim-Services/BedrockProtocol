<?php

declare(strict_types=1);

namespace pocketmine\network\mcpe\protocol\serializer;

use pocketmine\network\mcpe\protocol\types\skin\PersonaPieceTintColor;
use pocketmine\network\mcpe\protocol\types\skin\PersonaSkinPiece;
use pocketmine\network\mcpe\protocol\types\skin\SkinData;
use function array_search;
use function array_slice;
use function array_values;
use function count;
use function hexdec;
use function ltrim;
use function sprintf;

final class LegacySkinDataConverter{
	private const PERSONA_PIECE_TYPES = [
		"persona_skeleton" => PersonaSkinPiece::PIECE_TYPE_SKELETON,
		"persona_body" => PersonaSkinPiece::PIECE_TYPE_BODY,
		"persona_skin" => PersonaSkinPiece::PIECE_TYPE_SKIN,
		"persona_bottom" => PersonaSkinPiece::PIECE_TYPE_BOTTOM,
		"persona_feet" => PersonaSkinPiece::PIECE_TYPE_FEET,
		"persona_dress" => PersonaSkinPiece::PIECE_TYPE_DRESS,
		"persona_top" => PersonaSkinPiece::PIECE_TYPE_TOP,
		"persona_high_pants" => PersonaSkinPiece::PIECE_TYPE_HIGH_PANTS,
		"persona_hands" => PersonaSkinPiece::PIECE_TYPE_HANDS,
		"persona_outerwear" => PersonaSkinPiece::PIECE_TYPE_OUTERWEAR,
		"persona_facial_hair" => PersonaSkinPiece::PIECE_TYPE_FACIAL_HAIR,
		"persona_mouth" => PersonaSkinPiece::PIECE_TYPE_MOUTH,
		"persona_eyes" => PersonaSkinPiece::PIECE_TYPE_EYES,
		"persona_hair" => PersonaSkinPiece::PIECE_TYPE_HAIR,
		"persona_hood" => PersonaSkinPiece::PIECE_TYPE_HOOD,
		"persona_back" => PersonaSkinPiece::PIECE_TYPE_BACK,
		"persona_face_accessory" => PersonaSkinPiece::PIECE_TYPE_FACE_ACCESSORY,
		"persona_head" => PersonaSkinPiece::PIECE_TYPE_HEAD,
		"persona_legs" => PersonaSkinPiece::PIECE_TYPE_LEGS,
		"persona_left_leg" => PersonaSkinPiece::PIECE_TYPE_LEFT_LEG,
		"persona_right_leg" => PersonaSkinPiece::PIECE_TYPE_RIGHT_LEG,
		"persona_arms" => PersonaSkinPiece::PIECE_TYPE_ARMS,
		"persona_left_arm" => PersonaSkinPiece::PIECE_TYPE_LEFT_ARM,
		"persona_right_arm" => PersonaSkinPiece::PIECE_TYPE_RIGHT_ARM,
		"persona_capes" => PersonaSkinPiece::PIECE_TYPE_CAPES,
		"persona_classic_skin" => PersonaSkinPiece::PIECE_TYPE_CLASSIC_SKIN,
		"persona_emote" => PersonaSkinPiece::PIECE_TYPE_EMOTE,
	];

	private function __construct(){}

	public static function armSizeFromString(string $armSize) : int{
		return match($armSize){
			"slim" => SkinData::ARM_SIZE_SLIM,
			"wide", "" => SkinData::ARM_SIZE_WIDE,
			default => throw new \InvalidArgumentException("Unknown arm size \"$armSize\""),
		};
	}

	public static function armSizeToString(int $armSize) : string{
		return match($armSize){
			SkinData::ARM_SIZE_SLIM => "slim",
			SkinData::ARM_SIZE_WIDE => "wide",
			default => throw new \InvalidArgumentException("Unknown arm size $armSize"),
		};
	}

	public static function colorFromString(string $color) : int{
		return (int) hexdec(ltrim($color, "#"));
	}

	public static function colorToString(int $color) : string{
		return sprintf("#%08X", $color & 0xffffffff);
	}

	public static function personaPieceTypeFromString(string $pieceType) : int{
		return self::PERSONA_PIECE_TYPES[$pieceType] ??
			throw new \InvalidArgumentException("Unknown persona piece type \"$pieceType\"");
	}

	public static function personaPieceTypeToString(int $pieceType) : string{
		$result = array_search($pieceType, self::PERSONA_PIECE_TYPES, true);
		return $result !== false ? $result : throw new \InvalidArgumentException("Unknown persona piece type $pieceType");
	}

	/**
	 * @param list<string> $colors
	 * @return list<int>
	 */
	public static function colorsFromStrings(array $colors) : array{
		$result = [];
		foreach(array_slice(array_values($colors), 0, PersonaPieceTintColor::COLOR_COUNT) as $color){
			$result[] = self::colorFromString($color);
		}
		while(count($result) < PersonaPieceTintColor::COLOR_COUNT){
			$result[] = 0;
		}
		return $result;
	}
}
