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

use Exception;
use pocketmine\data\bedrock\item\SavedItemStackData;
use pocketmine\data\SavedDataLoadingException;
use pocketmine\item\Item;
use pocketmine\nbt\NBT;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\IntArrayTag;
use pocketmine\nbt\tag\ListTag;

trait CampfireShelfTrait{

	/** @var Item[] */
	private array $items = [];
	/** @var int[] */
	private array $times = [];

	protected function loadItems(CompoundTag $tag) : void{
		/** @var ListTag $itemsTag */
		if(($itemsTag = $tag->getTag(Campfire::TAG_ITEMS)) instanceof ListTag && $itemsTag->getTagType() === NBT::TAG_Compound){

			$newItems = [];
			/** @var CompoundTag $itemNBT */
			foreach($itemsTag as $itemNBT){
				try{
					$newItems[$itemNBT->getByte(SavedItemStackData::TAG_SLOT)] = Item::nbtDeserialize($itemNBT);
				}catch(SavedDataLoadingException $e){
					\GlobalLogger::get()->logException($e);
					continue;
				}
			}
			$this->items = $newItems;
		}
		/** @var IntArrayTag $timesTag */
		if(($timesTag = $tag->getTag(Campfire::TAG_TIMES)) instanceof IntArrayTag){

			$newTimes = [];
			try{
				$newTimes = $timesTag->getValue();
			}catch(Exception $e){
				\GlobalLogger::get()->logException($e);
			}
			$this->times = $newTimes;
		}
	}

	protected function saveItems(CompoundTag $tag) : void{
		$items = [];
		$times = [];
		foreach($this->items as $index => $item){
			if($item === null){
				continue;
			}
			$items[] = $item->nbtSerialize($index);
			$tag->setTag(Campfire::ITEM_SLOTS[$index], $item->nbtSerialize());
		}
		foreach($this->times as $index => $time){
			$times[$index] = $time;
			$tag->setInt(Campfire::ITEM_TIMES[$index], $time);
		}
		$tag->setTag(Campfire::TAG_ITEMS, new ListTag($items, NBT::TAG_Compound));
		$tag->setTag(Campfire::TAG_TIMES, new IntArrayTag($times));
	}
}
