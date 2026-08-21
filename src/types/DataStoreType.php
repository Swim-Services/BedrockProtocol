<?php

declare(strict_types=1);

namespace pocketmine\network\mcpe\protocol\types;

final class DataStoreType{
	public const UPDATE = 0;
	public const CHANGE = 1;
	public const REMOVAL = 2;
}
