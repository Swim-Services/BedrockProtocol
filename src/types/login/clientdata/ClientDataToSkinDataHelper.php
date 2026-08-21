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

namespace pocketmine\network\mcpe\protocol\types\login\clientdata;

use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\network\mcpe\protocol\serializer\LegacySkinDataConverter;
use pocketmine\network\mcpe\protocol\types\skin\PersonaPieceTintColor;
use pocketmine\network\mcpe\protocol\types\skin\PersonaSkinPiece;
use pocketmine\network\mcpe\protocol\types\skin\SkinAnimation;
use pocketmine\network\mcpe\protocol\types\skin\SkinData;
use pocketmine\network\mcpe\protocol\types\skin\SkinImage;
use Ramsey\Uuid\Uuid;
use function array_map;
use function array_values;
use function base64_decode;

final class ClientDataToSkinDataHelper{
	/**
	 * @throws \InvalidArgumentException
	 */
	private static function safeB64Decode(string $base64, string $context) : string{
		$result = base64_decode($base64, true);
		if($result === false){
			throw new \InvalidArgumentException("$context: Malformed base64, cannot be decoded");
		}
		return $result;
	}

	/**
	 * @throws \InvalidArgumentException
	 */
	public static function fromClientData(ClientData $clientData, int $protocolId = ProtocolInfo::CURRENT_PROTOCOL) : SkinData{
		/** @var SkinAnimation[] $animations */
		$animations = [];
		foreach($clientData->AnimatedImageData as $k => $animation){
			$animations[] = new SkinAnimation(
				new SkinImage(
					$animation->ImageHeight,
					$animation->ImageWidth,
					self::safeB64Decode($animation->Image, "AnimatedImageData.$k.Image")
				),
				$animation->Type,
				$animation->Frames,
				$animation->AnimationExpression
			);
		}
		return new SkinData(
			$clientData->SkinId,
			$clientData->PlayFabId ?? "",
			self::safeB64Decode($clientData->SkinResourcePatch, "SkinResourcePatch"),
			new SkinImage($clientData->SkinImageHeight, $clientData->SkinImageWidth, self::safeB64Decode($clientData->SkinData, "SkinData")),
			$animations,
			new SkinImage($clientData->CapeImageHeight, $clientData->CapeImageWidth, self::safeB64Decode($clientData->CapeData, "CapeData")),
			self::safeB64Decode($clientData->SkinGeometryData, "SkinGeometryData"),
			self::safeB64Decode($clientData->SkinGeometryDataEngineVersion ?? "", "SkinGeometryDataEngineVersion"), //yes, they actually base64'd the version!
			self::safeB64Decode($clientData->SkinAnimationData, "SkinAnimationData"),
			$clientData->CapeId,
			//ClientData has no distinct "FullId" field, but real clients always send FullID == ID for a
			//PlayerSkinPacket, so mirror that instead of minting an unrelated random UUID (which some clients
			//appear to reject for persona skins presented at login).
			$clientData->SkinId,
			LegacySkinDataConverter::armSizeFromString($clientData->ArmSize),
			LegacySkinDataConverter::colorFromString($clientData->SkinColor),
			array_map(function(ClientDataPersonaSkinPiece $piece) : PersonaSkinPiece{
				return new PersonaSkinPiece(
					$piece->PieceId,
					LegacySkinDataConverter::personaPieceTypeFromString($piece->PieceType),
					//PackId is empty for persona pieces that don't belong to any purchased content pack (the
					//common case for default pieces), so it's not always a valid UUID string.
					Uuid::fromString(Uuid::isValid($piece->PackId) ? $piece->PackId : Uuid::NIL),
					$piece->IsDefault,
					$piece->ProductId
				);
			}, $clientData->PersonaPieces),
			array_map(function(ClientDataPersonaPieceTintColor $tint) : PersonaPieceTintColor{
				return new PersonaPieceTintColor($tint->PieceType, LegacySkinDataConverter::colorsFromStrings(array_values($tint->Colors)));
			}, $clientData->PieceTintColors),
			true,
			$clientData->PremiumSkin,
			$clientData->PersonaSkin,
			$clientData->CapeOnClassicSkin,
			true, //assume this is true? there's no field for it ...
			$clientData->OverrideSkin ?? true,
			self::trustedSkinFlagFromClientData($clientData),
			$clientData->ProfileHash,
		);
	}

	/**
	 * $clientData->TrustedSkin only exists on >= PROTOCOL_1_19_20; older clients won't have sent it at all, in
	 * which case there's nothing to derive a trust state from and we fall back to unset.
	 */
	private static function trustedSkinFlagFromClientData(ClientData $clientData) : string{
		$trustedSkin = $clientData->TrustedSkin ?? null;
		if($trustedSkin === null){
			return SkinData::TRUSTED_SKIN_FLAG_UNSET;
		}
		return $trustedSkin ? SkinData::TRUSTED_SKIN_FLAG_TRUE : SkinData::TRUSTED_SKIN_FLAG_FALSE;
	}
}
