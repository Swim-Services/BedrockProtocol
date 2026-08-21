<?php

declare(strict_types=1);

namespace pocketmine\network\mcpe\protocol\types;

use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;

final class BoolDataStoreValue extends DataStoreValue{
	public const ID = DataStoreValueType::BOOL;
	public function __construct(private readonly bool $value){}
	public function getValue() : bool{ return $this->value; }
	public function getTypeId() : int{ return self::ID; }
	public function write(ByteBufferWriter $out) : void{ CommonTypes::putBool($out, $this->value); }
	public static function read(ByteBufferReader $in) : self{ return new self(CommonTypes::getBool($in)); }
}
