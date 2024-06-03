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

namespace nicholass003\campfire;

use nicholass003\campfire\block\Campfire;
use nicholass003\campfire\block\tile\Campfire as TileCampfire;
use nicholass003\campfire\utils\CampfireFurnaceRecipeHandler;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerLoginEvent;
use function var_dump;

class EventListener implements Listener{

	public function onPlayerLogin(PlayerLoginEvent $event) : void{
		$player = $event->getPlayer();
		if(!$event->isCancelled()){
			$player->getNetworkSession()->sendDataPacket(CampfireFurnaceRecipeHandler::getInstance()->getCache());
		}
	}

	public function onPlayerInteract(PlayerInteractEvent $event) : void{
		$player = $event->getPlayer();
		$block = $event->getBlock();
		if($block instanceof Campfire){
			$tile = $player->getWorld()->getTile($block->getPosition());
			if($tile instanceof TileCampfire){
				var_dump($tile->saveNBT());
			}
		}
	}
}
