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

namespace nicholass003\campfire\block\utils;

use nicholass003\campfire\block\Campfire;
use pocketmine\world\Position;
use pocketmine\world\sound\FireExtinguishSound;
use pocketmine\world\sound\FlintSteelSound;

trait ExtinguishTrait{
	protected bool $extinguished = false;

	public function isExtinguished() : bool{
		return $this->extinguished === true;
	}

	public function setExtinguished(bool $value) : self{
		$this->extinguished = $value;
		return $this;
	}

	public function extinguish(Position $pos) : void{
		$pos->getWorld()->addSound($pos, new FireExtinguishSound());
		$campfire = $pos->getWorld()->getBlock($pos);
		if(!$campfire instanceof Campfire){
			return;
		}
		$pos->getWorld()->setBlock($pos, $campfire->setExtinguished(true));
		$pos->getWorld()->scheduleDelayedBlockUpdate($pos, 20);
	}

	public function fire(Position $pos) : void{
		$pos->getWorld()->addSound($pos, new FlintSteelSound());
		$campfire = $pos->getWorld()->getBlock($pos);
		if(!$campfire instanceof Campfire){
			return;
		}
		$pos->getWorld()->setBlock($pos, $campfire->setExtinguished(false));
		$pos->getWorld()->scheduleDelayedBlockUpdate($pos, 20);
	}
}
