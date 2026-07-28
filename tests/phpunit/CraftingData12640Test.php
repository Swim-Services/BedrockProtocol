<?php

declare(strict_types=1);

namespace pocketmine\network\mcpe\protocol;

use PHPUnit\Framework\TestCase;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pocketmine\network\mcpe\protocol\types\recipe\MultiRecipe;
use pocketmine\network\mcpe\protocol\types\recipe\RecipeUnlockingRequirement;
use pocketmine\network\mcpe\protocol\types\recipe\ShapelessRecipe;
use Ramsey\Uuid\Uuid;
use function str_ends_with;

final class CraftingData12640Test extends TestCase{

	public function testRecipeNetIdIsAnUnsignedVarInt() : void{
		$recipe = new MultiRecipe(
			CraftingDataPacket::ENTRY_MULTI,
			Uuid::fromString("00000000-0000-0000-0000-000000000001"),
			300
		);
		$out = new ByteBufferWriter();
		$recipe->encode($out, ProtocolInfo::PROTOCOL_1_26_40);

		self::assertTrue(str_ends_with($out->getData(), "\xac\x02"));
		$result = MultiRecipe::decode(
			CraftingDataPacket::ENTRY_MULTI,
			new ByteBufferReader($out->getData()),
			ProtocolInfo::PROTOCOL_1_26_40
		);
		self::assertSame(300, $result->getRecipeNetId());
	}

	public function testUnlockingIngredientsPresenceFollowsContext() : void{
		$out = new ByteBufferWriter();
		(new RecipeUnlockingRequirement(null, RecipeUnlockingRequirement::CONTEXT_NONE))
			->write($out, ProtocolInfo::PROTOCOL_1_26_40);
		self::assertSame("\x00\x01\x00", $out->getData());

		$out = new ByteBufferWriter();
		(new RecipeUnlockingRequirement([], RecipeUnlockingRequirement::CONTEXT_ALWAYS_UNLOCKED))
			->write($out, ProtocolInfo::PROTOCOL_1_26_40);
		self::assertSame("\x02\x00", $out->getData());
	}

	public function testRecipeVectorsPreserveTheirTypes() : void{
		$regular = new ShapelessRecipe(
			CraftingDataPacket::ENTRY_SHAPELESS,
			"regular",
			[],
			[],
			Uuid::fromString("00000000-0000-0000-0000-000000000001"),
			"crafting_table",
			0,
			new RecipeUnlockingRequirement([], RecipeUnlockingRequirement::CONTEXT_NONE),
			1
		);
		$chemistry = new ShapelessRecipe(
			CraftingDataPacket::ENTRY_SHAPELESS_CHEMISTRY,
			"chemistry",
			[],
			[],
			Uuid::fromString("00000000-0000-0000-0000-000000000002"),
			"chemistry_table",
			0,
			null,
			2
		);
		$packet = CraftingDataPacket::create([$chemistry, $regular], [], [], [], true);

		$out = new ByteBufferWriter();
		$packet->encode($out, ProtocolInfo::PROTOCOL_1_26_40);
		$result = new CraftingDataPacket();
		$result->decode(new ByteBufferReader($out->getData()), ProtocolInfo::PROTOCOL_1_26_40);

		$decodedRegular = $result->recipesWithTypeIds[0];
		$decodedChemistry = $result->recipesWithTypeIds[1];
		self::assertInstanceOf(ShapelessRecipe::class, $decodedRegular);
		self::assertInstanceOf(ShapelessRecipe::class, $decodedChemistry);
		self::assertSame(CraftingDataPacket::ENTRY_SHAPELESS, $decodedRegular->getTypeId());
		self::assertSame(CraftingDataPacket::ENTRY_SHAPELESS_CHEMISTRY, $decodedChemistry->getTypeId());
		self::assertNotNull($decodedRegular->getUnlockingRequirement());
		self::assertNull($decodedChemistry->getUnlockingRequirement());
		self::assertTrue($result->cleanRecipes);
	}
}
