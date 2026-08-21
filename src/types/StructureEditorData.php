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

namespace pocketmine\network\mcpe\protocol\types;

use pmmp\encoding\Byte;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\VarInt;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;

class StructureEditorData{
	public const TYPE_DATA = 0;
	public const TYPE_SAVE = 1;
	public const TYPE_LOAD = 2;
	public const TYPE_CORNER = 3;
	public const TYPE_INVALID = 4;
	public const TYPE_EXPORT = 5;

	public string $structureName;
	public string $filteredStructureName;
	public string $structureDataField;
	public bool $includePlayers;
	public bool $showBoundingBox;
	public int $structureBlockType;
	public StructureSettings $structureSettings;
	public int $structureRedstoneSaveMove;
	public int $structureRedstoneSaveMode;

	public static function read(ByteBufferReader $in) : self{
		$result = new self();
		$result->structureName = CommonTypes::getString($in);
		$result->filteredStructureName = CommonTypes::getString($in);
		$result->structureDataField = CommonTypes::getString($in);
		$result->includePlayers = CommonTypes::getBool($in);
		$result->showBoundingBox = CommonTypes::getBool($in);
		$result->structureBlockType = VarInt::readSignedInt($in);
		$result->structureSettings = CommonTypes::getStructureSettings($in, \pocketmine\network\mcpe\protocol\ProtocolInfo::PROTOCOL_1_26_40);
		$result->structureRedstoneSaveMode = Byte::readUnsigned($in);
		$result->structureRedstoneSaveMove = $result->structureRedstoneSaveMode;
		return $result;
	}

	public function write(ByteBufferWriter $out) : void{
		CommonTypes::putString($out, $this->structureName);
		CommonTypes::putString($out, $this->filteredStructureName);
		CommonTypes::putString($out, $this->structureDataField);
		CommonTypes::putBool($out, $this->includePlayers);
		CommonTypes::putBool($out, $this->showBoundingBox);
		VarInt::writeSignedInt($out, $this->structureBlockType);
		CommonTypes::putStructureSettings($out, $this->structureSettings, \pocketmine\network\mcpe\protocol\ProtocolInfo::PROTOCOL_1_26_40);
		Byte::writeUnsigned($out, $this->structureRedstoneSaveMode ?? $this->structureRedstoneSaveMove);
	}
}
