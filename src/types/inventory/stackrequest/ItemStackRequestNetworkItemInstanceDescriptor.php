<?php

declare(strict_types=1);

namespace pocketmine\network\mcpe\protocol\types\inventory\stackrequest;

use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\VarInt;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use pocketmine\network\mcpe\protocol\types\recipe\RecipeIngredient;

final class ItemStackRequestNetworkItemInstanceDescriptor{
	public function __construct(private RecipeIngredient $ingredient, private int $blockRuntimeId, private string $rawExtraData){}
	public function getIngredient() : RecipeIngredient{ return $this->ingredient; }
	public function getBlockRuntimeId() : int{ return $this->blockRuntimeId; }
	public function getRawExtraData() : string{ return $this->rawExtraData; }
	public static function read(ByteBufferReader $in) : self{
		return new self(
			CommonTypes::getRecipeIngredient($in, ProtocolInfo::PROTOCOL_1_26_40),
			VarInt::readUnsignedInt($in),
			CommonTypes::getString($in)
		);
	}
	public function write(ByteBufferWriter $out) : void{
		CommonTypes::putRecipeIngredient($out, $this->ingredient, ProtocolInfo::PROTOCOL_1_26_40);
		VarInt::writeUnsignedInt($out, $this->blockRuntimeId);
		CommonTypes::putString($out, $this->rawExtraData);
	}
}
