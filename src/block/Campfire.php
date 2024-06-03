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

use nicholass003\campfire\block\inventory\CampfireInventory;
use nicholass003\campfire\block\tile\Campfire as TileCampfire;
use nicholass003\campfire\block\utils\ExtinguishTrait;
use nicholass003\campfire\utils\CampfireFurnaceRecipeHandler;
use nicholass003\campfire\utils\CampfireFurnaceType;
use pocketmine\block\Block;
use pocketmine\block\BlockIdentifier;
use pocketmine\block\BlockTypeInfo;
use pocketmine\block\Transparent;
use pocketmine\block\utils\FacesOppositePlacingPlayerTrait;
use pocketmine\block\utils\HorizontalFacingTrait;
use pocketmine\block\utils\SupportType;
use pocketmine\block\Water;
use pocketmine\crafting\FurnaceRecipe;
use pocketmine\data\runtime\RuntimeDataDescriber;
use pocketmine\entity\Entity;
use pocketmine\entity\Living;
use pocketmine\entity\projectile\SplashPotion;
use pocketmine\event\entity\EntityDamageByBlockEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\item\FlintSteel;
use pocketmine\item\Item;
use pocketmine\item\PotionType;
use pocketmine\item\Shovel;
use pocketmine\item\VanillaItems;
use pocketmine\math\Facing;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\utils\Limits;
use pocketmine\world\BlockTransaction;
use pocketmine\world\sound\ItemFrameAddItemSound;
use function count;

class Campfire extends Transparent{
	use FacesOppositePlacingPlayerTrait;
	use HorizontalFacingTrait;
	use ExtinguishTrait;

	protected CampfireInventory $inventory;

	/** @var array<int, int> */
	protected array $cookingTimes = [];

	public function __construct(BlockIdentifier $idInfo, string $name, BlockTypeInfo $typeInfo){
		parent::__construct($idInfo, $name, $typeInfo);
	}

	protected function describeBlockOnlyState(RuntimeDataDescriber $w) : void{
		$w->horizontalFacing($this->facing);
		$w->bool($this->extinguished);
	}

	public function readStateFromWorld() : Block{
		parent::readStateFromWorld();
		$tile = $this->position->getWorld()->getTile($this->position);
		if($tile instanceof TileCampfire){
			$this->inventory = $tile->getInventory();
			$this->cookingTimes = $tile->getCookingTimes();
		}
		return $this;
	}

	public function writeStateToWorld() : void{
		parent::writeStateToWorld();
		$tile = $this->position->getWorld()->getTile($this->position);
		if($tile instanceof TileCampfire){
			$tile->setCookingTimes($this->cookingTimes);
		}
	}

	public function getLightLevel() : int{
		return $this->extinguished ? 0 : 15;
	}

	public function getDropsForCompatibleTool(Item $item) : array{
		return [
			VanillaItems::CHARCOAL()->setCount(2)
		];
	}

	public function getSupportType(int $facing) : SupportType{
		return SupportType::NONE();
	}

	public function hasEntityCollision() : bool{
		return true;
	}

	public function isAffectedBySilkTouch() : bool{
		return true;
	}

	public function getCookingTime(int $slot) : int{
		return $this->cookingTimes[$slot] ?? 0;
	}

	public function setCookingTime(int $slot, int $time) : void{
		if($slot < 0 || $slot > 3){
			throw new \InvalidArgumentException("Slot must be range in 0-3");
		}
		if($time < 0 || $time > Limits::INT32_MAX){
			throw new \InvalidArgumentException("CookingTime must be range in 0-" . Limits::INT32_MAX);
		}

		$this->cookingTimes[$slot] = $time;
	}

	public function place(BlockTransaction $tx, Item $item, Block $blockReplace, Block $blockClicked, int $face, Vector3 $clickVector, ?Player $player = null) : bool{
		if(!$this->getSide(Facing::DOWN)->getSupportType(Facing::UP)->hasCenterSupport()){
			return false;
		}
		return parent::place($tx, $item, $blockReplace, $blockClicked, $face, $clickVector, $player);
	}

	public function onInteract(Item $item, int $face, Vector3 $clickVector, ?Player $player = null, array &$returnedItems = []) : bool{
		if($player !== null){
			if($item instanceof FlintSteel){
				if($this->extinguished){
					$item->applyDamage(1);
					$this->fire($this->position);
				}
				return true;
			}
			if($item instanceof Shovel){
				if(!$this->extinguished){
					$item->applyDamage(1);
					$this->extinguish($this->position);
				}
				return true;
			}

			if(CampfireFurnaceRecipeHandler::getInstance()->match($item) !== null){
				$ingredient = clone $item;
				$ingredient->setCount(1);
				if(count($this->inventory->addItem($ingredient)) === 0){
					$item->pop();
					$this->position->getWorld()->addSound($this->position, new ItemFrameAddItemSound());
					return true;
				}
			}
		}
		return false;
	}

	public function onNearbyBlockChange() : void{
		$block = $this->getSide(Facing::UP);
		if($block instanceof Water && !$this->extinguished){
			$this->extinguish($this->position);
		}
	}

	public function onEntityInside(Entity $entity) : bool{
		if($this->extinguished){
			if($entity->isOnFire()){
				$this->fire($this->position);
				return true;
			}
			return false;
		}
		if($entity instanceof SplashPotion && $entity->getPotionType() === PotionType::WATER()){
			$this->extinguish($this->position);
			return true;
		}elseif($entity instanceof Living){
			$entity->attack(new EntityDamageByBlockEvent($this, $entity, EntityDamageEvent::CAUSE_FIRE, 1));
			$entity->setOnFire(8);
			return true;
		}
		return false;
	}

	public function onScheduledUpdate() : void{
		$world = $this->position->getWorld();
		if(!$this->extinguished){
			$items = $this->inventory->getContents();
			foreach($items as $slot => $item){
				$this->setCookingTime($slot, $this->getCookingTime($slot) + 20);
				if($this->getCookingTime($slot) >= CampfireFurnaceType::getCookDurationTicks()){
					$this->inventory->setItem($slot, VanillaItems::AIR());
					$this->setCookingTime($slot, 0);
					$result = ($recipe = CampfireFurnaceRecipeHandler::getInstance()->match($item)) instanceof FurnaceRecipe ? $recipe->getResult() : VanillaItems::AIR();
					$world->dropItem($this->position->add(0, 1, 0), $result);
				}
			}
			if(count($items) > 0){
				$world->setBlock($this->position, $this);
				$world->addSound($this->position, CampfireFurnaceType::getCookSound());
			}
			$world->scheduleDelayedBlockUpdate($this->position, 20);
		}
	}
}
