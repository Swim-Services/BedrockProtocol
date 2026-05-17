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

namespace pocketmine\network\mcpe\protocol\types\inventory;

use pmmp\encoding\Byte;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\DataDecodeException;
use pmmp\encoding\VarInt;
use pocketmine\network\mcpe\protocol\PacketDecodeException;
use function count;
use function dechex;
use function ord;
use function str_pad;
use function strlen;
use function var_dump;
use const STR_PAD_LEFT;

abstract class TransactionData{
	/** @var NetworkInventoryAction[] */
	protected array $actions = [];

	/**
	 * @return NetworkInventoryAction[]
	 */
	final public function getActions() : array{
		return $this->actions;
	}

	abstract public function getTypeId() : int;

	/**
	 * @throws DataDecodeException
	 * @throws PacketDecodeException
	 */
	final public function decode(ByteBufferReader $in, int $protocolId, bool $new = false) : void{
		if ($new) {
			//var_dump(Byte::readUnsigned($in));
			Byte::readUnsigned($in);
		}
		$actionCount = VarInt::readUnsignedInt($in);
		for($i = 0; $i < $actionCount; ++$i){
			$this->actions[] = (new NetworkInventoryAction())->read($in, $new);
		}

		$this->decodeData($in, $protocolId, $new);
	}

	/**
	 * @throws DataDecodeException
	 * @throws PacketDecodeException
	 */
	abstract protected function decodeData(ByteBufferReader $in, int $protocolId, bool $new) : void;

	final public function encode(ByteBufferWriter $out, int $protocolId, bool $new) : void{
		VarInt::writeUnsignedInt($out, count($this->actions));
		foreach($this->actions as $action){
			$action->write($out, $protocolId);
		}
		$this->encodeData($out, $protocolId, $new);
	}

	abstract protected function encodeData(ByteBufferWriter $out, int $protocolId, bool $new) : void;

	static function hex_dump($string)
{
  for ($i = 0; $i < strlen($string); $i++) {
	echo str_pad(dechex(ord($string[$i])), 2, '0', STR_PAD_LEFT) . " ";
	}
	echo "\n";
}

}
