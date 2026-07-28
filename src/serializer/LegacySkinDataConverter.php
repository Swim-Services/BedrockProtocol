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
use function strtolower;

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

	/**
	 * On >= 1.26.40, PieceTintColors' key is (unlike PersonaSkinPiece's own PieceType, which stayed a raw
	 * uint32) serialized as a length-prefixed string containing the *bare* lowercased enum name, with no
	 * "persona_" prefix - confirmed empirically from a live packet capture ("hair", "eyes").
	 */
	private const PERSONA_PIECE_TYPES_BARE = [
		"skeleton" => PersonaSkinPiece::PIECE_TYPE_SKELETON,
		"body" => PersonaSkinPiece::PIECE_TYPE_BODY,
		"skin" => PersonaSkinPiece::PIECE_TYPE_SKIN,
		"bottom" => PersonaSkinPiece::PIECE_TYPE_BOTTOM,
		"feet" => PersonaSkinPiece::PIECE_TYPE_FEET,
		"dress" => PersonaSkinPiece::PIECE_TYPE_DRESS,
		"top" => PersonaSkinPiece::PIECE_TYPE_TOP,
		"high_pants" => PersonaSkinPiece::PIECE_TYPE_HIGH_PANTS,
		"hands" => PersonaSkinPiece::PIECE_TYPE_HANDS,
		"outerwear" => PersonaSkinPiece::PIECE_TYPE_OUTERWEAR,
		"facialhair" => PersonaSkinPiece::PIECE_TYPE_FACIAL_HAIR,
		"mouth" => PersonaSkinPiece::PIECE_TYPE_MOUTH,
		"eyes" => PersonaSkinPiece::PIECE_TYPE_EYES,
		"hair" => PersonaSkinPiece::PIECE_TYPE_HAIR,
		"hood" => PersonaSkinPiece::PIECE_TYPE_HOOD,
		"back" => PersonaSkinPiece::PIECE_TYPE_BACK,
		"faceaccessory" => PersonaSkinPiece::PIECE_TYPE_FACE_ACCESSORY,
		"head" => PersonaSkinPiece::PIECE_TYPE_HEAD,
		"legs" => PersonaSkinPiece::PIECE_TYPE_LEGS,
		"leftleg" => PersonaSkinPiece::PIECE_TYPE_LEFT_LEG,
		"rightleg" => PersonaSkinPiece::PIECE_TYPE_RIGHT_LEG,
		"arms" => PersonaSkinPiece::PIECE_TYPE_ARMS,
		"leftarm" => PersonaSkinPiece::PIECE_TYPE_LEFT_ARM,
		"rightarm" => PersonaSkinPiece::PIECE_TYPE_RIGHT_ARM,
		"capes" => PersonaSkinPiece::PIECE_TYPE_CAPES,
		"classicskin" => PersonaSkinPiece::PIECE_TYPE_CLASSIC_SKIN,
		"emote" => PersonaSkinPiece::PIECE_TYPE_EMOTE,
	];

	private function __construct(){}

	public static function armSizeFromString(string $armSize) : int{
		//real clients send this capitalized ("Slim"/"Wide"), matching the wire enum's naming - match
		//case-insensitively so we're not relying on an unconfirmed exact casing convention.
		return match(strtolower($armSize)){
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
		$value = (int) hexdec(ltrim($color, "#"));
		//hexdec() gives the unsigned 32-bit interpretation, but colors are stored/transmitted as signed int32
		//everywhere else (wire encoding, NBT IntTag), so wrap values above the signed range down into it.
		if($value > 0x7fffffff){
			$value -= 0x100000000;
		}
		return $value;
	}

	public static function colorToString(int $color) : string{
		return sprintf("#%08X", $color & 0xffffffff);
	}

	public static function personaPieceTypeFromString(string $pieceType) : int{
		return self::PERSONA_PIECE_TYPES[strtolower($pieceType)] ??
			throw new \InvalidArgumentException("Unknown persona piece type \"$pieceType\"");
	}

	public static function personaPieceTypeToString(int $pieceType) : string{
		$result = array_search($pieceType, self::PERSONA_PIECE_TYPES, true);
		return $result !== false ? $result : throw new \InvalidArgumentException("Unknown persona piece type $pieceType");
	}

	public static function personaPieceTypeFromBareString(string $pieceType) : int{
		return self::PERSONA_PIECE_TYPES_BARE[strtolower($pieceType)] ??
			throw new \InvalidArgumentException("Unknown persona piece type \"$pieceType\"");
	}

	public static function personaPieceTypeToBareString(int $pieceType) : string{
		$result = array_search($pieceType, self::PERSONA_PIECE_TYPES_BARE, true);
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
