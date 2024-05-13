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

namespace nicholass003\campfire\block;

use nicholass003\campfire\block\tile\Campfire as TileCampfire;
use nicholass003\campfire\block\utils\ExtinguishTrait;
use nicholass003\campfire\utils\CampfireFurnaceRecipe;
use nicholass003\campfire\utils\CampfireFurnaceType;
use pocketmine\block\BlockIdentifier;
use pocketmine\block\BlockTypeInfo;
use pocketmine\block\Transparent;
use pocketmine\block\utils\FacesOppositePlacingPlayerTrait;
use pocketmine\block\utils\HorizontalFacingTrait;
use pocketmine\data\runtime\RuntimeDataDescriber;
use pocketmine\item\Item;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\world\sound\ItemFrameAddItemSound;

class Campfire extends Transparent{
	use FacesOppositePlacingPlayerTrait;
	use HorizontalFacingTrait;
	use ExtinguishTrait;

	public function __construct(BlockIdentifier $idInfo, string $name, BlockTypeInfo $typeInfo){
		parent::__construct($idInfo, $name, $typeInfo);
	}

	protected function describeBlockOnlyState(RuntimeDataDescriber $w) : void{
		$w->horizontalFacing($this->facing);
		$w->bool($this->extinguished);
	}

	public function getLightLevel() : int{
		return $this->extinguished ? 0 : 15;
	}

	public function getDropsForCompatibleTool(Item $item) : array{
		$tile = $this->position->getWorld()->getTile($this->position);
		$drops = [];
		$drops[] = $this->asItem();
		if($tile instanceof TileCampfire){
			foreach($tile->getItemCookQueue() as $slot => $id){
				$item = CampfireFurnaceRecipe::matchItemDrop($id);
				if($item !== null){
					$drops[] = $item;
				}
			}
		}
		return $drops;
	}

	public function onInteract(Item $item, int $face, Vector3 $clickVector, ?Player $player = null, array &$returnedItems = []) : bool{
		$world = $this->position->getWorld();
		$tile = $world->getTile($this->position);
		if($tile instanceof TileCampfire){
			if($tile->canCook($item)){
				if($tile->addItemCookQueue($item)){
					$item->pop();
					$this->position->getWorld()->addSound($clickVector, new ItemFrameAddItemSound());
					$world->scheduleDelayedBlockUpdate($this->position, 1);
					$block = $world->getBlock($this->position);
					if($block instanceof Campfire){
						$world->setBlock($this->position, $block);
					}
				}
			}
		}
		return true;
	}

	public function onScheduledUpdate() : void{
		$world = $this->position->getWorld();
		$tile = $world->getTile($this->position);
		if($tile instanceof TileCampfire && $tile->onUpdate()){
			$world->addSound($this->position, CampfireFurnaceType::getCookSound());
			$world->scheduleDelayedBlockUpdate($this->position, 1);
		}
	}
}
