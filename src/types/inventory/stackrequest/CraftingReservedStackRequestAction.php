<?php

declare(strict_types=1);

namespace pocketmine\network\mcpe\protocol\types\inventory\stackrequest;

use pmmp\encoding\Byte;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use pocketmine\network\mcpe\protocol\types\GetTypeIdFromConstTrait;

/** Reserved by the 1.26.60 protocol; gameplay execution is not implemented. */
final class CraftingReservedStackRequestAction extends ItemStackRequestAction{
	use GetTypeIdFromConstTrait;

	public const ID = ItemStackRequestActionType::CRAFTING_RESERVED;

	public function __construct(private string $reservedId, private int $repetitions){}

	public function getReservedId() : string{ return $this->reservedId; }
	public function getRepetitions() : int{ return $this->repetitions; }

	public static function read(ByteBufferReader $in, int $protocolId) : self{
		return new self(CommonTypes::getString($in), Byte::readUnsigned($in));
	}

	public function write(ByteBufferWriter $out, int $protocolId) : void{
		CommonTypes::putString($out, $this->reservedId);
		Byte::writeUnsigned($out, $this->repetitions);
	}
}
