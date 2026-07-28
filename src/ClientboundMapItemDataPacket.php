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

use pmmp\encoding\Byte;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\LE;
use pmmp\encoding\VarInt;
use pocketmine\color\Color;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use pocketmine\network\mcpe\protocol\types\BlockPosition;
use pocketmine\network\mcpe\protocol\types\DimensionIds;
use pocketmine\network\mcpe\protocol\types\MapDecoration;
use pocketmine\network\mcpe\protocol\types\MapImage;
use pocketmine\network\mcpe\protocol\types\MapTrackedObject;
use pocketmine\utils\Binary;
use function count;

class ClientboundMapItemDataPacket extends DataPacket implements ClientboundPacket{
	public const NETWORK_ID = ProtocolInfo::CLIENTBOUND_MAP_ITEM_DATA_PACKET;

	public const BITFLAG_TEXTURE_UPDATE = 0x02;
	public const BITFLAG_DECORATION_UPDATE = 0x04;
	public const BITFLAG_MAP_CREATION = 0x08;

	public int $mapId;
	public int $type;
	public int $dimensionId = DimensionIds::OVERWORLD;
	public bool $isLocked = false;
	public BlockPosition $origin;

	/** @var list<int>|null */
	public ?array $parentMapIds = null;
	public ?int $scale = null;

	/** @var list<MapTrackedObject>|null */
	public ?array $trackedEntities = null;
	/** @var list<MapDecoration>|null */
	public ?array $decorations = null;

	public ?int $xOffset = null;
	public ?int $yOffset = null;
	public ?MapImage $colors = null;

	public ?int $width = null;
	public ?int $height = null;
	/** @var list<Color>|null */
	public ?array $pixels = null;

	protected function decodePayload(ByteBufferReader $in, int $protocolId) : void{
		$this->mapId = CommonTypes::getActorUniqueId($in);
		if($protocolId < ProtocolInfo::PROTOCOL_1_26_40){
			$this->type = VarInt::readUnsignedInt($in);
		}
		$this->dimensionId = Byte::readUnsigned($in);
		$this->isLocked = CommonTypes::getBool($in);
		if($protocolId >= ProtocolInfo::PROTOCOL_1_26_40 || $protocolId >= ProtocolInfo::PROTOCOL_1_19_20){
			$this->origin = CommonTypes::getBlockPosition($in);
		}
		if($protocolId >= ProtocolInfo::PROTOCOL_1_26_40){
			$this->parentMapIds = CommonTypes::readOptional($in, static function(ByteBufferReader $in) : array{
				$result = [];
				for($i = 0, $count = VarInt::readUnsignedInt($in); $i < $count; ++$i){
					$result[] = CommonTypes::getActorUniqueId($in);
				}
				return $result;
			});
			/** @var int|null $scale */
			$scale = CommonTypes::readOptional($in, Byte::readUnsigned(...));
			$this->scale = $scale;
			$this->trackedEntities = CommonTypes::readOptional($in, static function(ByteBufferReader $in) : array{
				$result = [];
				for($i = 0, $count = VarInt::readUnsignedInt($in); $i < $count; ++$i){
					$result[] = MapTrackedObject::read($in);
				}
				return $result;
			});
			$this->decorations = CommonTypes::readOptional($in, static function(ByteBufferReader $in) : array{
				$result = [];
				for($i = 0, $count = VarInt::readUnsignedInt($in); $i < $count; ++$i){
					$result[] = new MapDecoration(
						Byte::readUnsigned($in),
						Byte::readUnsigned($in),
						Byte::readUnsigned($in),
						Byte::readUnsigned($in),
						CommonTypes::getString($in),
						Color::fromRGBA(Binary::flipIntEndianness(LE::readUnsignedInt($in)))
					);
				}
				return $result;
			});
			/** @var int|null $width */
			$width = CommonTypes::readOptional($in, VarInt::readSignedInt(...));
			$this->width = $width;
			/** @var int|null $height */
			$height = CommonTypes::readOptional($in, VarInt::readSignedInt(...));
			$this->height = $height;
			/** @var int|null $xOffset */
			$xOffset = CommonTypes::readOptional($in, VarInt::readSignedInt(...));
			$this->xOffset = $xOffset;
			/** @var int|null $yOffset */
			$yOffset = CommonTypes::readOptional($in, VarInt::readSignedInt(...));
			$this->yOffset = $yOffset;
			$this->pixels = CommonTypes::readOptional($in, static function(ByteBufferReader $in) : array{
				$result = [];
				for($i = 0, $count = VarInt::readUnsignedInt($in); $i < $count; ++$i){
					$result[] = Color::fromRGBA(Binary::flipIntEndianness(LE::readUnsignedInt($in)));
				}
				return $result;
			});
		}

		if($protocolId < ProtocolInfo::PROTOCOL_1_26_40 && ($this->type & self::BITFLAG_MAP_CREATION) !== 0){
			$this->parentMapIds = [];
			$count = VarInt::readUnsignedInt($in);
			for($i = 0; $i < $count; ++$i){
				$this->parentMapIds[] = CommonTypes::getActorUniqueId($in);
			}
		}

		if($protocolId < ProtocolInfo::PROTOCOL_1_26_40 && ($this->type & (self::BITFLAG_MAP_CREATION | self::BITFLAG_DECORATION_UPDATE | self::BITFLAG_TEXTURE_UPDATE)) !== 0){ //Decoration bitflag or colour bitflag
			$this->scale = Byte::readUnsigned($in);
		}

		if($protocolId < ProtocolInfo::PROTOCOL_1_26_40 && ($this->type & self::BITFLAG_DECORATION_UPDATE) !== 0){
			$this->trackedEntities = [];
			for($i = 0, $count = VarInt::readUnsignedInt($in); $i < $count; ++$i){
				$object = new MapTrackedObject();
				$object->type = LE::readUnsignedInt($in);
				if($object->type === MapTrackedObject::TYPE_BLOCK){
					$object->blockPosition = CommonTypes::getBlockPosition($in, $protocolId >= ProtocolInfo::PROTOCOL_1_26_10);
				}elseif($object->type === MapTrackedObject::TYPE_ENTITY){
					$object->actorUniqueId = CommonTypes::getActorUniqueId($in);
				}else{
					throw new PacketDecodeException("Unknown map object type $object->type");
				}
				$this->trackedEntities[] = $object;
			}

			$this->decorations = [];
			for($i = 0, $count = VarInt::readUnsignedInt($in); $i < $count; ++$i){
				$icon = Byte::readUnsigned($in);
				$rotation = Byte::readUnsigned($in);
				$xOffset = Byte::readUnsigned($in);
				$yOffset = Byte::readUnsigned($in);
				$label = CommonTypes::getString($in);
				$color = Color::fromRGBA(Binary::flipIntEndianness(VarInt::readUnsignedInt($in)));
				$this->decorations[] = new MapDecoration($icon, $rotation, $xOffset, $yOffset, $label, $color);
			}
		}

		if($protocolId < ProtocolInfo::PROTOCOL_1_26_40 && ($this->type & self::BITFLAG_TEXTURE_UPDATE) !== 0){
			$width = VarInt::readSignedInt($in);
			$height = VarInt::readSignedInt($in);
			$this->xOffset = VarInt::readSignedInt($in);
			$this->yOffset = VarInt::readSignedInt($in);

			$count = VarInt::readUnsignedInt($in);
			if($count !== $width * $height){
				throw new PacketDecodeException("Expected colour count of " . ($height * $width) . " (height $height * width $width), got $count");
			}

			$this->colors = MapImage::decode($in, $height, $width);
		}
	}

	protected function encodePayload(ByteBufferWriter $out, int $protocolId) : void{
		CommonTypes::putActorUniqueId($out, $this->mapId);
		$type = 0;
		if($protocolId < ProtocolInfo::PROTOCOL_1_26_40){
			if(($parentMapIdsCount = count($this->parentMapIds ?? [])) > 0){
				$type |= self::BITFLAG_MAP_CREATION;
			}
			if(($decorationCount = count($this->decorations ?? [])) > 0){
				$type |= self::BITFLAG_DECORATION_UPDATE;
			}
			if($this->colors !== null){
				$type |= self::BITFLAG_TEXTURE_UPDATE;
			}
			VarInt::writeUnsignedInt($out, $type);
		}
		Byte::writeUnsigned($out, $this->dimensionId);
		CommonTypes::putBool($out, $this->isLocked);
		if($protocolId >= ProtocolInfo::PROTOCOL_1_26_40 || $protocolId >= ProtocolInfo::PROTOCOL_1_19_20){
			CommonTypes::putBlockPosition($out, $this->origin);
		}
		if($protocolId >= ProtocolInfo::PROTOCOL_1_26_40){
			CommonTypes::writeOptional($out, $this->parentMapIds, static function(ByteBufferWriter $out, array $ids) : void{
				VarInt::writeUnsignedInt($out, count($ids));
				foreach($ids as $id){
					CommonTypes::putActorUniqueId($out, $id);
				}
			});
			CommonTypes::writeOptional($out, $this->scale, Byte::writeUnsigned(...));
			CommonTypes::writeOptional($out, $this->trackedEntities, static function(ByteBufferWriter $out, array $entities) : void{
				VarInt::writeUnsignedInt($out, count($entities));
				foreach($entities as $entity){
					$entity->write($out);
				}
			});
			CommonTypes::writeOptional($out, $this->decorations, static function(ByteBufferWriter $out, array $decorations) : void{
				VarInt::writeUnsignedInt($out, count($decorations));
				foreach($decorations as $decoration){
					Byte::writeUnsigned($out, $decoration->getIcon());
					Byte::writeUnsigned($out, $decoration->getRotation());
					Byte::writeUnsigned($out, $decoration->getXOffset());
					Byte::writeUnsigned($out, $decoration->getYOffset());
					CommonTypes::putString($out, $decoration->getLabel());
					LE::writeUnsignedInt($out, Binary::flipIntEndianness($decoration->getColor()->toRGBA()));
				}
			});
			CommonTypes::writeOptional($out, $this->width, VarInt::writeSignedInt(...));
			CommonTypes::writeOptional($out, $this->height, VarInt::writeSignedInt(...));
			CommonTypes::writeOptional($out, $this->xOffset, VarInt::writeSignedInt(...));
			CommonTypes::writeOptional($out, $this->yOffset, VarInt::writeSignedInt(...));
			CommonTypes::writeOptional($out, $this->pixels, static function(ByteBufferWriter $out, array $pixels) : void{
				VarInt::writeUnsignedInt($out, count($pixels));
				foreach($pixels as $pixel){
					LE::writeUnsignedInt($out, Binary::flipIntEndianness($pixel->toRGBA()));
				}
			});
		}

		if($protocolId < ProtocolInfo::PROTOCOL_1_26_40 && ($type & self::BITFLAG_MAP_CREATION) !== 0){
			VarInt::writeUnsignedInt($out, $parentMapIdsCount);
			foreach($this->parentMapIds ?? [] as $parentMapId){
				CommonTypes::putActorUniqueId($out, $parentMapId);
			}
		}

		if($protocolId < ProtocolInfo::PROTOCOL_1_26_40 && ($type & (self::BITFLAG_MAP_CREATION | self::BITFLAG_TEXTURE_UPDATE | self::BITFLAG_DECORATION_UPDATE)) !== 0){
			Byte::writeUnsigned($out, $this->scale ?? throw new \InvalidArgumentException("Scale must be set for a legacy map update"));
		}

		if($protocolId < ProtocolInfo::PROTOCOL_1_26_40 && ($type & self::BITFLAG_DECORATION_UPDATE) !== 0){
			VarInt::writeUnsignedInt($out, count($this->trackedEntities ?? []));
			foreach($this->trackedEntities ?? [] as $object){
				LE::writeUnsignedInt($out, $object->type);
				if($object->type === MapTrackedObject::TYPE_BLOCK){
					CommonTypes::putBlockPosition($out, $object->blockPosition ?? throw new \InvalidArgumentException("Missing block position"), $protocolId >= ProtocolInfo::PROTOCOL_1_26_10);
				}elseif($object->type === MapTrackedObject::TYPE_ENTITY){
					CommonTypes::putActorUniqueId($out, $object->actorUniqueId ?? throw new \InvalidArgumentException("Missing actor unique ID"));
				}else{
					throw new \InvalidArgumentException("Unknown map object type $object->type");
				}
			}

			VarInt::writeUnsignedInt($out, $decorationCount);
			foreach($this->decorations ?? [] as $decoration){
				Byte::writeUnsigned($out, $decoration->getIcon());
				Byte::writeUnsigned($out, $decoration->getRotation());
				Byte::writeUnsigned($out, $decoration->getXOffset());
				Byte::writeUnsigned($out, $decoration->getYOffset());
				CommonTypes::putString($out, $decoration->getLabel());
				VarInt::writeUnsignedInt($out, Binary::flipIntEndianness($decoration->getColor()->toRGBA()));
			}
		}

		if($protocolId < ProtocolInfo::PROTOCOL_1_26_40 && $this->colors !== null){
			VarInt::writeSignedInt($out, $this->colors->getWidth());
			VarInt::writeSignedInt($out, $this->colors->getHeight());
			VarInt::writeSignedInt($out, $this->xOffset ?? 0);
			VarInt::writeSignedInt($out, $this->yOffset ?? 0);

			VarInt::writeUnsignedInt($out, $this->colors->getWidth() * $this->colors->getHeight()); //list count, but we handle it as a 2D array... thanks for the confusion mojang

			$this->colors->encode($out);
		}
	}

	public function handle(PacketHandlerInterface $handler) : bool{
		return $handler->handleClientboundMapItemData($this);
	}
}
