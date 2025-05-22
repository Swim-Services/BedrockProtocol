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

use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;
use pocketmine\network\mcpe\protocol\types\biome\BiomeDefinitionData;
use pocketmine\network\mcpe\protocol\types\biome\BiomeDefinitionEntry;
use pocketmine\network\mcpe\protocol\types\CacheableNbt;
use function array_map;
use function count;

class BiomeDefinitionListPacket extends DataPacket implements ClientboundPacket{
	public const NETWORK_ID = ProtocolInfo::BIOME_DEFINITION_LIST_PACKET;

	/**
	 * @var BiomeDefinitionData[]
	 * @phpstan-var list<BiomeDefinitionData>
	 */
	private ?array $definitionData;
	/**
	 * @var string[]
	 * @phpstan-var list<string>
	 */
	private ?array $strings = [];

	/** @phpstan-var CacheableNbt<CompoundTag> */
	private ?CacheableNbt $legacyDefinitions;

	/**
	 * @generate-create-func
	 * @param BiomeDefinitionData[] $definitionData
	 * @param string[]              $strings
	 * @phpstan-param list<BiomeDefinitionData> $definitionData
	 * @phpstan-param list<string>              $strings
	 * @phpstan-param CacheableNbt<CompoundTag> $legacyDefinitions
	 */
	private static function internalCreate(?array $definitionData, ?array $strings, ?CacheableNbt $legacyDefinitions) : self{
		$result = new self;
		$result->definitionData = $definitionData;
		$result->strings = $strings;
		$result->legacyDefinitions = $legacyDefinitions;
		return $result;
	}

	/**
	 * @param BiomeDefinitionData[] $definitionData
	 * @param string[] 				$strings
	 * @phpstan-param list<BiomeDefinitionData> $definitionData
	 * @phpstan-param list<string>            	$strings
	 */
	public static function create(array $definitionData, array $strings) : self{
		return self::internalCreate($definitionData, $strings,null);
	}

	/**
	 * @phpstan-param CacheableNbt<CompoundTag> $definitions
	 */
	public static function createLegacy(CacheableNbt $definitions) : self{
		return self::internalCreate(null, null, $definitions);
	}

	/**
	 * @phpstan-param list<BiomeDefinitionEntry> $definitions
	 */
	public static function fromDefinitions(array $definitions) : self{
		/**
		 * @var int[]                      $stringIndexLookup
		 * @phpstan-var array<string, int> $stringIndexLookup
		 */
		$stringIndexLookup = [];
		$strings = [];
		$addString = function(string $string) use (&$stringIndexLookup, &$strings) : int{
			if(isset($stringIndexLookup[$string])){
				return $stringIndexLookup[$string];
			}

			$stringIndexLookup[$string] = count($stringIndexLookup);
			$strings[] = $string;
			return $stringIndexLookup[$string];
		};

		$definitionData = array_map(fn(BiomeDefinitionEntry $entry) => new BiomeDefinitionData(
			$addString($entry->getBiomeName()),
			$entry->getId(),
			$entry->getTemperature(),
			$entry->getDownfall(),
			$entry->getRedSporeDensity(),
			$entry->getBlueSporeDensity(),
			$entry->getAshDensity(),
			$entry->getWhiteAshDensity(),
			$entry->getDepth(),
			$entry->getScale(),
			$entry->getMapWaterColor(),
			$entry->hasRain(),
			$entry->getTags() === null ? null : array_map($addString, $entry->getTags()),
			$entry->getChunkGenData(),
		), $definitions);

		return self::create($definitionData, $strings);
	}

	/**
	 * @throws PacketDecodeException
	 */
	private function locateString(int $index) : string{
		return $this->strings[$index] ?? throw new PacketDecodeException("Unknown string index $index");
	}

	/**
	 * Returns biome definition data with all string indexes resolved to actual strings.
	 *
	 * @return BiomeDefinitionEntry[]
	 * @phpstan-return list<BiomeDefinitionEntry>
	 *
	 * @throws PacketDecodeException
	 */
	public function buildDefinitionsFromData() : array{
		return array_map(fn(BiomeDefinitionData $data) => new BiomeDefinitionEntry(
			$this->locateString($data->getNameIndex()),
			$data->getId(),
			$data->getTemperature(),
			$data->getDownfall(),
			$data->getRedSporeDensity(),
			$data->getBlueSporeDensity(),
			$data->getAshDensity(),
			$data->getWhiteAshDensity(),
			$data->getDepth(),
			$data->getScale(),
			$data->getMapWaterColor(),
			$data->hasRain(),
			($tagIndexes = $data->getTagIndexes()) === null ? null : array_map($this->locateString(...), $tagIndexes),
			$data->getChunkGenData(),
		), $this->definitionData ?? throw new PacketDecodeException("No definition data available"));
	}

	protected function decodePayload(PacketSerializer $in) : void{
		if($in->getProtocolId() < ProtocolInfo::PROTOCOL_1_21_80){
			$this->legacyDefinitions = new CacheableNbt($in->getNbtCompoundRoot());
			$this->definitionData = null;
			$this->strings = null;
			return;
		}

		$this->legacyDefinitions = null;
		for($i = 0, $count = $in->getUnsignedVarInt(); $i < $count; ++$i){
			$this->definitionData[] = BiomeDefinitionData::read($in);
		}

		for($i = 0, $count = $in->getUnsignedVarInt(); $i < $count; ++$i){
			$this->strings[] = $in->getString();
		}
	}

	protected function encodePayload(PacketSerializer $out) : void{
		if($out->getProtocolId() < ProtocolInfo::PROTOCOL_1_21_80){
			if($this->legacyDefinitions === null){
				throw new \LogicException("Legacy definitions not set");
			}
			$out->put($this->legacyDefinitions->getEncodedNbt());
			return;
		}

		if($this->definitionData === null || $this->strings === null){
			throw new \LogicException("Definition data not set");
		}

		$out->putUnsignedVarInt(count($this->definitionData));
		foreach($this->definitionData as $data){
			$data->write($out);
		}

		$out->putUnsignedVarInt(count($this->strings));
		foreach($this->strings as $string){
			$out->putString($string);
		}
	}

	public function handle(PacketHandlerInterface $handler) : bool{
		return $handler->handleBiomeDefinitionList($this);
	}
}
