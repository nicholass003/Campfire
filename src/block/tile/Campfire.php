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

use nicholass003\campfire\utils\CampfireFurnaceRecipe;
use pocketmine\block\tile\Spawnable;
use pocketmine\item\Item;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\world\World;
use function array_keys;
use function count;
use function in_array;

class Campfire extends Spawnable{
	use CampfireShelfTrait;

	public const TAG_ITEMS = "CampfireItems"; //TAG_List
	public const TAG_TIMES = "CampfireTimes"; //TAG_IntArray

	public const ITEM_SLOTS = [
		"Item1",
		"Item2",
		"Item3",
		"Item4"
	]; //TAG_Compound

	public const ITEM_TIMES = [
		"ItemTime1",
		"ItemTime2",
		"ItemTime3",
		"ItemTime4"
	]; // TAG_Int

	public const MAX_ITEMS = 4;

	public function __construct(World $world, Vector3 $pos){
		parent::__construct($world, $pos);
		$world->scheduleDelayedBlockUpdate($pos, 1);
	}

	public function canCook(Item $item) : bool{
		return in_array($item->getTypeId(), array_keys(CampfireFurnaceRecipe::RECIPES), true);
	}

	public function getItemCookQueue() : array{
		return $this->items;
	}

	public function getItemCookResult(int $index) : ?Item{
		$result = CampfireFurnaceRecipe::matchItemOutput($this->items[$index] instanceof Item ? $this->items[$index]->getTypeId() : 0);
		return $result;
	}

	public function updateCookTime(int $index) : void{
		$this->times[$index] -= 1;
		if($this->times[$index] <= 0){
			$world = $this->position->getWorld();
			$result = $this->getItemCookResult($index);
			if($result === null){
				return;
			}
			$world->dropItem($this->position->add(0, 1, 0), $result);
			$nbt = $this->saveNBT();
			$nbt->removeTag(self::ITEM_SLOTS[$index]);
			$nbt->removeTag(self::ITEM_TIMES[$index]);
			$nbt->removeTag(self::TAG_ITEMS);
			$nbt->removeTag(self::TAG_TIMES);
			unset($this->items[$index]);
			unset($this->times[$index]);
		}
	}

	public function addItemCookQueue(Item $item) : bool{
		if(count($this->items) < self::MAX_ITEMS){
			$index = count($this->items);
			$this->items[$index] = $item;
			$this->times[$index] = 600;
			return true;
		}
		return false;
	}

	protected function addAdditionalSpawnData(CompoundTag $nbt) : void{}

	public function readSaveData(CompoundTag $nbt) : void{
		$this->loadItems($nbt);
	}

	protected function writeSaveData(CompoundTag $nbt) : void{
		$this->saveItems($nbt);
	}

	public function onUpdate() : bool{
		if($this->closed){
			return false;
		}

		$this->timings->startTiming();

		$res = false;

		for($i = 0; $i < self::MAX_ITEMS; $i++){
			if(isset($this->times[$i]) && isset($this->items[$i])){
				$this->updateCookTime($i);
				$res = true;
			}
		}
		$this->timings->stopTiming();
		return $res;
	}
}
