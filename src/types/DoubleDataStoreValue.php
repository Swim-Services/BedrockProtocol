<?php

declare(strict_types=1);

namespace pocketmine\network\mcpe\protocol\types;

use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\LE;

final class DoubleDataStoreValue extends DataStoreValue{
	public const ID = DataStoreValueType::DOUBLE;
	public function __construct(private readonly float $value){}
	public function getValue() : float{ return $this->value; }
	public function getTypeId() : int{ return self::ID; }
	public function write(ByteBufferWriter $out) : void{ LE::writeDouble($out, $this->value); }
	public static function read(ByteBufferReader $in) : self{ return new self(LE::readDouble($in)); }
}
