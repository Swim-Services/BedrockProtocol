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

use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;
use pocketmine\network\mcpe\protocol\types\camera\CameraAimAssistCategoriesDefinition;
use pocketmine\network\mcpe\protocol\types\camera\CameraAimAssistPresetDefinition;
use function count;

class CameraAimAssistPresetsPacket extends DataPacket implements ClientboundPacket{
	public const NETWORK_ID = ProtocolInfo::CAMERA_AIM_ASSIST_PRESETS_PACKET;

	/** @var CameraAimAssistCategoriesDefinition[] */
	private array $categories;
	/** @var CameraAimAssistPresetDefinition[] */
	private array $presets;

	/**
	 * @param CameraAimAssistCategoriesDefinition[] $categories
	 * @param CameraAimAssistPresetDefinition[] $presets
	 * @generate-create-func
	 */
	public static function create(array $categories, array $presets) : self{
		$result = new self;
		$result->categories = $categories;
		$result->presets = $presets;
		return $result;
	}

	protected function decodePayload(PacketSerializer $in) : void{
		$categoriesCount = $in->getUnsignedVarInt();
		while ($categoriesCount-- > 0) {
			$this->categories[] = CameraAimAssistCategoriesDefinition::read($in);
		}

		$presetsCount = $in->getUnsignedVarInt();
		while ($presetsCount-- > 0) {
			$this->presets[] = CameraAimAssistPresetDefinition::read($in);
		}
	}
	protected function encodePayload(PacketSerializer $out) : void{
		$out->putUnsignedVarInt(count($this->categories));
		foreach($this->categories as $category) {
			$category->write($out);
		}

		$out->putUnsignedVarInt(count($this->presets));
		foreach($this->presets as $preset) {
			$preset->write($out);
		}
	}
	public function handle(PacketHandlerInterface $handler) : bool{
		return $handler->handleCameraAimAssistPresets($this);
	}
}
