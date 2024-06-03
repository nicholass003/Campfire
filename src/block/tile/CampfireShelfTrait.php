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

namespace nicholass003\campfire\block\tile;

use nicholass003\campfire\block\inventory\CampfireInventory;
use pocketmine\item\Item;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\IntTag;

trait CampfireShelfTrait{

	/** @var array<int, int> */
	private array $cookingTimes = [];

	public const ITEM_SLOTS = "Item"; //TAG_Compound
	public const ITEM_TIMES = "ItemTime"; // TAG_Int

	public const MAX_ITEMS = 4;

	protected CampfireInventory $inventory;

	protected function readData(CompoundTag $nbt) : void{
		$items = [];
		$listeners = $this->inventory->getListeners()->toArray();
		$this->inventory->getListeners()->remove(...$listeners);

		for($slot = 1; $slot <= self::MAX_ITEMS; $slot++){
			if(($tag = $nbt->getTag(self::ITEM_SLOTS . $slot)) instanceof CompoundTag){
				$items[$slot - 1] = Item::nbtDeserialize($tag);
			}
			if(($tag = $nbt->getTag(self::ITEM_TIMES . $slot)) instanceof IntTag){
				$this->cookingTimes[$slot - 1] = $tag->getValue();
			}
		}

		$this->inventory->setContents($items);
		$this->inventory->getListeners()->add(...$listeners);
	}

	protected function writeData(CompoundTag $nbt) : void{
		for($slot = 1; $slot <= self::MAX_ITEMS; $slot++){
			$item = $this->inventory->getItem($slot - 1);
			if(!$item->isNull()){
				$nbt->setTag(self::ITEM_SLOTS . $slot, $item->nbtSerialize($slot));
			}

			$cookingTime = $this->cookingTimes[$slot - 1] ?? 0;
			if($cookingTime !== 0){
				$nbt->setInt(self::ITEM_TIMES . $slot, $cookingTime);
			}
		}
	}
}
