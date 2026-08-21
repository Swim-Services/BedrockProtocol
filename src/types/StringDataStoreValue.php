<?php

declare(strict_types=1);

namespace pocketmine\network\mcpe\protocol\types;

use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;

final class StringDataStoreValue extends DataStoreValue{
	public const ID = DataStoreValueType::STRING;
	public function __construct(private readonly string $value){}
	public function getValue() : string{ return $this->value; }
	public function getTypeId() : int{ return self::ID; }
	public function write(ByteBufferWriter $out) : void{ CommonTypes::putString($out, $this->value); }
	public static function read(ByteBufferReader $in) : self{ return new self(CommonTypes::getString($in)); }
}
