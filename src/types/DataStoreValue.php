<?php

declare(strict_types=1);

namespace pocketmine\network\mcpe\protocol\types;

use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\LE;
use pmmp\encoding\VarInt;
use pocketmine\network\mcpe\protocol\PacketDecodeException;
use pocketmine\network\mcpe\protocol\ProtocolInfo;

abstract class DataStoreValue{
	abstract public function getTypeId() : int;
	abstract public function write(ByteBufferWriter $out) : void;

	public static function readWithType(ByteBufferReader $in, int $protocolId) : ?self{
		if($protocolId >= ProtocolInfo::PROTOCOL_1_26_40){
			return match(VarInt::readUnsignedInt($in)){
				DataStoreValueType::DOUBLE => DoubleDataStoreValue::read($in),
				DataStoreValueType::BOOL => BoolDataStoreValue::read($in),
				DataStoreValueType::STRING => StringDataStoreValue::read($in),
				default => throw new PacketDecodeException("Unknown data store value type"),
			};
		}

		return match(LE::readUnsignedInt($in)){
			0 => null,
			1 => BoolDataStoreValue::read($in),
			2 => LongDataStoreValue::read($in),
			3 => DoubleDataStoreValue::read($in),
			4 => StringDataStoreValue::read($in),
			5 => ListDataStoreValue::read($in),
			6 => MapDataStoreValue::read($in),
			default => throw new PacketDecodeException("Unknown legacy data store value type"),
		};
	}

	public static function writeWithType(ByteBufferWriter $out, ?self $value, int $protocolId) : void{
		if($protocolId >= ProtocolInfo::PROTOCOL_1_26_40){
			if($value === null || $value instanceof LongDataStoreValue || $value instanceof ListDataStoreValue || $value instanceof MapDataStoreValue){
				throw new \InvalidArgumentException("This data store value is only supported by legacy protocols");
			}
			VarInt::writeUnsignedInt($out, $value->getTypeId());
		}else{
			LE::writeUnsignedInt($out, match(true){
				$value === null => 0,
				$value instanceof BoolDataStoreValue => 1,
				$value instanceof LongDataStoreValue => 2,
				$value instanceof DoubleDataStoreValue => 3,
				$value instanceof StringDataStoreValue => 4,
				$value instanceof ListDataStoreValue => 5,
				$value instanceof MapDataStoreValue => 6,
				default => throw new \InvalidArgumentException("Unknown data store value type"),
			});
		}
		$value?->write($out);
	}
}
