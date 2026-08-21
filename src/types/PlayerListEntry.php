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

namespace pocketmine\network\mcpe\protocol\types;

use pocketmine\color\Color;
use pocketmine\network\mcpe\protocol\types\skin\SkinData;
use Ramsey\Uuid\UuidInterface;

class PlayerListEntry{

	public const ACTION_ADD = 0;
	public const ACTION_REMOVE = 1;

	/**
	 * This is encoded per entry starting with 1.26.40. Null makes
	 * PlayerListPacket fall back to its packet-level type for compatibility.
	 */
	public ?int $action = null;
	public UuidInterface $uuid;
	public int $actorUniqueId;
	public string $username;
	public ?SkinData $skinData = null;
	public string $xboxUserId;
	public string $platformChatId = "";
	public int $buildPlatform = DeviceOS::UNKNOWN;
	public bool $isTeacher = false;
	public bool $isHost = false;
	public bool $isSubClient = false;
	public ?Color $color = null;

	public static function createRemovalEntry(UuidInterface $uuid) : PlayerListEntry{
		$entry = new PlayerListEntry();
		$entry->action = self::ACTION_REMOVE;
		$entry->uuid = $uuid;

		return $entry;
	}

	public static function createAdditionEntry(
		UuidInterface $uuid,
		int $actorUniqueId,
		string $username,
		SkinData $skinData,
		string $xboxUserId = "",
		string $platformChatId = "",
		int $buildPlatform = -1,
		bool $isTeacher = false,
		bool $isHost = false,
		bool $isSubClient = false,
		?Color $color = null
	) : PlayerListEntry{
		$entry = new PlayerListEntry();
		$entry->action = self::ACTION_ADD;
		$entry->uuid = $uuid;
		$entry->actorUniqueId = $actorUniqueId;
		$entry->username = $username;
		$entry->skinData = $skinData;
		$entry->xboxUserId = $xboxUserId;
		$entry->platformChatId = $platformChatId;
		$entry->buildPlatform = $buildPlatform;
		$entry->isTeacher = $isTeacher;
		$entry->isHost = $isHost;
		$entry->isSubClient = $isSubClient;
		$entry->color = $color;

		return $entry;
	}
}
