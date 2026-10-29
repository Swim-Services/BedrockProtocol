<?php

declare(strict_types=1);

// php tests/integration/crafting-requests.php /path/to/PocketMine-MP/vendor/autoload.php
require $argv[1] ?? dirname(__DIR__, 3) . '/PocketMine-MP/vendor/autoload.php';

use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pocketmine\network\mcpe\protocol\ItemStackRequestPacket;
use pocketmine\network\mcpe\protocol\PacketDecodeException;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\network\mcpe\protocol\types\inventory\stackrequest\CraftingReservedStackRequestAction;
use pocketmine\network\mcpe\protocol\types\inventory\stackrequest\CraftRecipeStackRequestAction;
use pocketmine\network\mcpe\protocol\types\inventory\stackrequest\CraftRecipeAutoStackRequestAction;
use pocketmine\network\mcpe\protocol\types\inventory\stackrequest\DeprecatedCraftingResultsStackRequestAction;
use pocketmine\network\mcpe\protocol\types\inventory\stackrequest\ItemStackRequestActionType;

function check(bool $ok, string $message) : void{
	if(!$ok){ throw new RuntimeException($message); }
}

// Build wire fixtures independently of the action mapper/writer. Mojang's
// v1.26.60 ItemStackRequest.json inserts variant 16 (enum byte 18); CraftResults
// consequently moves from variant 17/enum 19 to variant 18/enum 20.
// https://github.com/Mojang/bedrock-protocol-docs/blob/v1.26.60/json/ItemStackRequest.json
foreach([ProtocolInfo::PROTOCOL_1_26_60, ProtocolInfo::PROTOCOL_1_26_50, ProtocolInfo::PROTOCOL_1_26_40] as $protocol){
	foreach(['minecraft:oak_planks', 'minecraft:birch_planks', 'minecraft:crafting_table', 'minecraft:stick'] as $name){
		foreach([false, true] as $auto){
			$modern = $protocol >= ProtocolInfo::PROTOCOL_1_26_60;
			$craftPayload = $auto
				? "\x0b\x0d\x01\x01\x01\x01\x01\x11minecraft:oak_log\x00\x01\x00"
				: "\x0a\x0c\x01\x01";
			// One request (-1), recipe 1 crafting once, then one result with count 4.
			$body = "\x01\x01\x02" . $craftPayload . ($modern ? "\x12\x14" : "\x11\x13")
				. "\x01\x01\x01" . chr(strlen($name)) . $name . "\x00\x04\x00\x00\x00\x01"
				. "\x00" . pack('V', 0xffffffff);
			// Packet header is unsigned-varint 147.
			$wire = "\x93\x01" . $body;
			$reader = new ByteBufferReader($wire);
			$packet = new ItemStackRequestPacket();
			$packet->decode($reader, $protocol);
			check($reader->getUnreadLength() === 0, 'Crafting fixture must be consumed completely');
			$request = $packet->getRequests()[0];
			check($request->getRequestId() === -1 && $request->getFilterStringCause() === -1, 'Request framing corrupted');
			[$craft, $results] = $request->getActions();
			check(($auto ? $craft instanceof CraftRecipeAutoStackRequestAction : $craft instanceof CraftRecipeStackRequestAction) && $craft->getRecipeId() === 1 && $craft->getRepetitions() === 1, 'Wrong recipe action');
			if($auto){
				check(count($craft->getIngredients()) === 1 && $craft->getIngredients()[0]->getDescriptor()->getName() === 'minecraft:oak_log', 'Recipe-book ingredient corrupted');
			}
			check($results instanceof DeprecatedCraftingResultsStackRequestAction && $results->getIterations() === 1, 'Wrong result action');
			$result = $results->getResults()[0];
			check($result->getIngredient()->getCount() === 4 && $result->getIngredient()->getDescriptor()->getName() === $name, 'Craft result corrupted');
			$writer = new ByteBufferWriter();
			$packet->encode($writer, $protocol);
			check($writer->getData() === $wire, 'Crafting re-encode must match independent wire fixture');
		}
	}
	// Every existing variant keeps its meaning; only the two final ones shift.
	for($variant = 0; $variant <= 17; ++$variant){
		$wireVariant = $variant + ($protocol >= ProtocolInfo::PROTOCOL_1_26_60 && $variant >= 16 ? 1 : 0);
		$internal = $variant >= 7 ? $variant + 2 : $variant;
		check(ItemStackRequestActionType::fromModernTypeId($wireVariant, $protocol) === $internal, 'Action decoded as wrong type');
		check(ItemStackRequestActionType::toModernTypeId($internal, $protocol) === $wireVariant, 'Action encoded as wrong type');
	}
	try{
		ItemStackRequestActionType::fromModernTypeId(19, $protocol);
		throw new RuntimeException('Unknown action variant accepted');
	}catch(PacketDecodeException){}
}

// Reserved action has a real payload, not a no-op/deprecated crafting action.
$wire = "\x93\x01\x01\x01\x01\x10\x12\x04test\x02\x00" . pack('V', 0xffffffff);
$reader = new ByteBufferReader($wire);
$packet = new ItemStackRequestPacket();
$packet->decode($reader, ProtocolInfo::PROTOCOL_1_26_60);
$action = $packet->getRequests()[0]->getActions()[0];
check($reader->getUnreadLength() === 0 && $action instanceof CraftingReservedStackRequestAction && $action->getReservedId() === 'test' && $action->getRepetitions() === 2, 'Reserved payload corrupted');
$writer = new ByteBufferWriter();
$packet->encode($writer, ProtocolInfo::PROTOCOL_1_26_60);
check($writer->getData() === $wire, 'Reserved action wire mismatch');
try{
	$packet->encode(new ByteBufferWriter(), ProtocolInfo::PROTOCOL_1_26_50);
	throw new RuntimeException('Reserved action encoded for old client');
}catch(InvalidArgumentException){}

echo "Crafting request fixtures and all action mappings passed for 1.26.40, 1.26.50 and 1.26.60.\n";
