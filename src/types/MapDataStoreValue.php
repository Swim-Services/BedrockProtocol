<?php

declare(strict_types=1);

namespace pocketmine\network\mcpe\protocol\types;

use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\VarInt;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use function count;

final class MapDataStoreValue extends DataStoreValue{
	/**
	 * @param array<string, DataStoreValue|null> $values
	 */
	public function __construct(private readonly array $values){}

	/** @return array<string, DataStoreValue|null> */
	public function getValues() : array{ return $this->values; }

	public function getTypeId() : int{
		throw new \LogicException("Map data store values do not have a protocol 1.26.40 type ID");
	}

	public function write(ByteBufferWriter $out) : void{
		VarInt::writeUnsignedInt($out, count($this->values));
		foreach($this->values as $key => $value){
			CommonTypes::putString($out, $key);
			DataStoreValue::writeWithType($out, $value, ProtocolInfo::PROTOCOL_1_26_30);
		}
	}

	public static function read(ByteBufferReader $in) : self{
		$values = [];
		for($i = 0, $count = VarInt::readUnsignedInt($in); $i < $count; ++$i){
			$values[CommonTypes::getString($in)] = DataStoreValue::readWithType($in, ProtocolInfo::PROTOCOL_1_26_30);
		}
		return new self($values);
	}
}
