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

use nicholass003\campfire\block\Campfire as BlockCampfire;
use nicholass003\campfire\block\inventory\CampfireInventory;
use pocketmine\block\tile\Container;
use pocketmine\block\tile\ContainerTrait;
use pocketmine\block\tile\Spawnable;
use pocketmine\inventory\CallbackInventoryListener;
use pocketmine\inventory\Inventory;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\world\World;

class Campfire extends Spawnable implements Container{
	use ContainerTrait;
	use CampfireShelfTrait;

	public function __construct(World $world, Vector3 $pos){
		parent::__construct($world, $pos);
		$this->inventory = new CampfireInventory($this->position);
		$this->inventory->getListeners()->add(CallbackInventoryListener::onAnyChange(
			static function(Inventory $unused) use($world, $pos) : void{
				$block = $world->getBlock($pos);
				if($block instanceof BlockCampfire){
					$world->setBlock($pos, $block);
				}
			}
		));
	}

	public function getInventory() : Inventory{
		return $this->inventory;
	}

	public function getRealInventory() : Inventory{
		return $this->inventory;
	}

	public function getCookingTimes() : array{
		return $this->cookingTimes;
	}

	public function setCookingTimes(array $cookingTimes) : void{
		$this->cookingTimes = $cookingTimes;
	}

	public function readSaveData(CompoundTag $nbt) : void{
		$this->readData($nbt);
	}

	protected function writeSaveData(CompoundTag $nbt) : void{
		$this->writeData($nbt);
	}

	protected function addAdditionalSpawnData(CompoundTag $nbt) : void{
		for($slot = 1; $slot <= self::MAX_ITEMS; $slot++){
			$item = $this->inventory->getItem($slot - 1);
			if(!$item->isNull()){
				$nbt->setTag(self::ITEM_SLOTS . $slot, $item->nbtSerialize());
			}
		}
	}
}
