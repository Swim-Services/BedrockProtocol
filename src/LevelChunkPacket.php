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

namespace pocketmine\network\mcpe\protocol;

use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\LE;
use pmmp\encoding\VarInt;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use pocketmine\network\mcpe\protocol\types\ChunkPosition;
use pocketmine\utils\Limits;
use function count;
use const PHP_INT_MAX;
use pocketmine\network\mcpe\protocol\types\DimensionIds;

class LevelChunkPacket extends DataPacket implements ClientboundPacket{
	public const NETWORK_ID = ProtocolInfo::LEVEL_CHUNK_PACKET;

	/**
	 * Client will request all subchunks as needed up to the top of the world
	 */
	private const CLIENT_REQUEST_FULL_COLUMN_FAKE_COUNT = Limits::UINT32_MAX;
	/**
	 * Client will request subchunks as needed up to the height written in the packet, and assume that anything above
	 * that height is air (wtf mojang ...)
	 */
	private const CLIENT_REQUEST_TRUNCATED_COLUMN_FAKE_COUNT = Limits::UINT32_MAX - 1;

	//this appears large enough for a world height of 1024 blocks - it may need to be increased in the future
	private const MAX_BLOB_HASHES = 64;

	private ChunkPosition $chunkPosition;
	/** @phpstan-var DimensionIds::* */
	private int $dimensionId;
	private int $subChunkCount;
	private ?int $clientRequestSubChunkLimit = null;
	private bool $cacheEnabled;
	/** @var int[] */
	private array $usedBlobHashes;
	private string $extraPayload;

	/**
	 * @generate-create-func
	 * @param int[] $usedBlobHashes
	 */
	public static function create(ChunkPosition $chunkPosition, int $dimensionId, int $subChunkCount, ?int $clientRequestSubChunkLimit, bool $cacheEnabled, array $usedBlobHashes, string $extraPayload) : self{
		$result = new self;
		$result->chunkPosition = $chunkPosition;
		$result->dimensionId = $dimensionId;
		$result->subChunkCount = $subChunkCount;
		$result->clientRequestSubChunkLimit = $clientRequestSubChunkLimit;
		$result->cacheEnabled = $cacheEnabled;
		$result->usedBlobHashes = $usedBlobHashes;
		$result->extraPayload = $extraPayload;
		return $result;
	}

	public function getChunkPosition() : ChunkPosition{ return $this->chunkPosition; }

	public function getDimensionId() : int{ return $this->dimensionId; }

	public function getSubChunkCount() : int{
		return $this->subChunkCount;
	}

	public function isClientSubChunkRequestsEnabled() : bool{
		return $this->clientRequestSubChunkLimit !== null;
	}

	/** @deprecated incorrect name */
	public function isClientSubChunkRequestEnabled() : bool{
		return $this->clientRequestSubChunkLimit !== null;
	}

	public function isCacheEnabled() : bool{
		return $this->cacheEnabled;
	}

	public function getClientRequestSubChunkLimit() : ?int{ return $this->clientRequestSubChunkLimit; }

	/**
	 * @return int[]
	 */
	public function getUsedBlobHashes() : array{
		return $this->usedBlobHashes;
	}

	public function getExtraPayload() : string{
		return $this->extraPayload;
	}

	protected function decodePayload(ByteBufferReader $in, int $protocolId) : void{
		$this->chunkPosition = ChunkPosition::read($in);
		if($protocolId >= ProtocolInfo::PROTOCOL_1_20_60){
			$this->dimensionId = VarInt::readSignedInt($in);
		}
		if($protocolId >= ProtocolInfo::PROTOCOL_1_26_40){
			$this->subChunkCount = VarInt::readUnsignedInt($in);
			$this->clientRequestSubChunkLimit = CommonTypes::getBool($in) ? VarInt::readSignedInt($in) : null;
		}else{
			$subChunkCountButNotReally = VarInt::readUnsignedInt($in);
			if($subChunkCountButNotReally === self::CLIENT_REQUEST_FULL_COLUMN_FAKE_COUNT){
				$this->subChunkCount = PHP_INT_MAX;
				$this->clientRequestSubChunkLimit = PHP_INT_MAX;
			}elseif($subChunkCountButNotReally === self::CLIENT_REQUEST_TRUNCATED_COLUMN_FAKE_COUNT){
				$this->subChunkCount = LE::readUnsignedShort($in);
				$this->clientRequestSubChunkLimit = $this->subChunkCount;
			}else{
				$this->subChunkCount = $subChunkCountButNotReally;
				$this->clientRequestSubChunkLimit = null;
			}
		}

		$this->cacheEnabled = CommonTypes::getBool($in);
		$this->usedBlobHashes = [];
		if($protocolId >= ProtocolInfo::PROTOCOL_1_26_40 || $this->cacheEnabled){
			$count = VarInt::readUnsignedInt($in);
			if($count > self::MAX_BLOB_HASHES){
				throw new PacketDecodeException("Expected at most " . self::MAX_BLOB_HASHES . " blob hashes, got " . $count);
			}
			for($i = 0; $i < $count; ++$i){
				$this->usedBlobHashes[] = LE::readUnsignedLong($in);
			}
		}
		$this->extraPayload = CommonTypes::getString($in);
	}

	protected function encodePayload(ByteBufferWriter $out, int $protocolId) : void{
		$this->chunkPosition->write($out);
		if($protocolId >= ProtocolInfo::PROTOCOL_1_20_60){
			VarInt::writeSignedInt($out, $this->dimensionId);
		}
		if($protocolId >= ProtocolInfo::PROTOCOL_1_26_40){
			VarInt::writeUnsignedInt($out, $this->subChunkCount);
			CommonTypes::putBool($out, $this->clientRequestSubChunkLimit !== null);
			if($this->clientRequestSubChunkLimit !== null){
				VarInt::writeSignedInt($out, $this->clientRequestSubChunkLimit);
			}
		}else{
			if($this->clientRequestSubChunkLimit !== null){
				if($this->clientRequestSubChunkLimit === PHP_INT_MAX){
					VarInt::writeUnsignedInt($out, self::CLIENT_REQUEST_FULL_COLUMN_FAKE_COUNT);
				}else{
					VarInt::writeUnsignedInt($out, self::CLIENT_REQUEST_TRUNCATED_COLUMN_FAKE_COUNT);
					LE::writeUnsignedShort($out, $this->clientRequestSubChunkLimit);
				}
			}else{
				VarInt::writeUnsignedInt($out, $this->subChunkCount);
			}
		}

		CommonTypes::putBool($out, $this->cacheEnabled);
		if($protocolId >= ProtocolInfo::PROTOCOL_1_26_40 || $this->cacheEnabled){
			VarInt::writeUnsignedInt($out, count($this->usedBlobHashes));
			foreach($this->usedBlobHashes as $hash){
				LE::writeUnsignedLong($out, $hash);
			}
		}
		CommonTypes::putString($out, $this->extraPayload);
	}

	public function handle(PacketHandlerInterface $handler) : bool{
		return $handler->handleLevelChunk($this);
	}
}
