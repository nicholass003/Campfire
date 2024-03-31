<?php

/*
 * Copyright (c) 2024 - present nicholass003
 *        _      _           _                ___   ___ ____
 *       (_)    | |         | |              / _ \ / _ \___ \
 *  _ __  _  ___| |__   ___ | | __ _ ___ ___| | | | | | |__) |
 * | '_ \| |/ __| '_ \ / _ \| |/ _` / __/ __| | | | | | |__ <
 * | | | | | (__| | | | (_) | | (_| \__ \__ \ |_| | |_| |__) |
 * |_| |_|_|\___|_| |_|\___/|_|\__,_|___/___/\___/ \___/____/
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author  nicholass003
 * @link    https://github.com/nicholass003/
 *
 *
 */

declare(strict_types=1);

namespace nicholass003\campfire\utils;

use pocketmine\data\bedrock\item\ItemTypeNames;
use pocketmine\item\Item;
use pocketmine\item\ItemTypeIds;
use pocketmine\item\StringToItemParser;

class CampfireFurnaceRecipe{

	public const DROPS = [
		ItemTypeIds::RAW_BEEF => ItemTypeNames::BEEF,
		ItemTypeIds::RAW_CHICKEN => ItemTypeNames::CHICKEN,
		ItemTypeIds::RAW_RABBIT => ItemTypeNames::RABBIT,
		ItemTypeIds::RAW_PORKCHOP => ItemTypeNames::PORKCHOP,
		ItemTypeIds::RAW_MUTTON => ItemTypeNames::MUTTON,
		ItemTypeIds::RAW_FISH => ItemTypeNames::TROPICAL_FISH,
		ItemTypeIds::RAW_SALMON => ItemTypeNames::SALMON,
		ItemTypeIds::POTATO => ItemTypeNames::POTATO
	];

	public const RECIPES = [
		ItemTypeIds::RAW_BEEF => ItemTypeNames::COOKED_BEEF,
		ItemTypeIds::RAW_CHICKEN => ItemTypeNames::COOKED_CHICKEN,
		ItemTypeIds::RAW_RABBIT => ItemTypeNames::COOKED_RABBIT,
		ItemTypeIds::RAW_PORKCHOP => ItemTypeNames::COOKED_PORKCHOP,
		ItemTypeIds::RAW_MUTTON => ItemTypeNames::COOKED_MUTTON,
		ItemTypeIds::RAW_FISH => ItemTypeNames::COOKED_COD,
		ItemTypeIds::RAW_SALMON => ItemTypeNames::COOKED_SALMON,
		ItemTypeIds::POTATO => ItemTypeNames::BAKED_POTATO
	];

	public static function matchItemDrop(int $itemTypeId) : ?Item{
		if($itemTypeId === 0) return null;
		return StringToItemParser::getInstance()->parse(self::DROPS[$itemTypeId]);
	}

	public static function matchItemOutput(int $itemTypeId) : ?Item{
		if($itemTypeId === 0) return null;
		return StringToItemParser::getInstance()->parse(self::RECIPES[$itemTypeId]);
	}
}
