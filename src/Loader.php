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

use nicholass003\campfire\utils\CampfireFurnaceRecipeHandler;
use nicholass003\campfire\utils\CampfireRegistry;
use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\AsyncTask;
use pocketmine\utils\SingletonTrait;
use Symfony\Component\Filesystem\Path;

class Loader extends PluginBase{
	use SingletonTrait;

	protected function onLoad() : void{
		CampfireRegistry::register();
	}

	protected function onEnable() : void{
		self::setInstance($this);

		$this->getServer()->getAsyncPool()->addWorkerStartHook(function(int $worker) : void{
			$this->getServer()->getAsyncPool()->submitTaskToWorker(new class extends AsyncTask{
				public function onRun() : void{
					CampfireRegistry::register();
				}
			}, $worker);
		});

		$this->getServer()->getPluginManager()->registerEvents(new EventListener(), $this);
		CampfireFurnaceRecipeHandler::getInstance()->makeCampfireFurnaceRecipe(Path::join(\pocketmine\BEDROCK_DATA_PATH, "recipes"));
	}
}
