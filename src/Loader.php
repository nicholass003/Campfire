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

use nicholass003\campfire\crafting\ExtraFurnaceType;
use pocketmine\crafting\CraftingManager;
use pocketmine\crafting\CraftingManagerFromDataHelper;
use pocketmine\crafting\json\FurnaceRecipeData;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\SingletonTrait;
use Symfony\Component\Filesystem\Path;

class Loader extends PluginBase{
	use SingletonTrait;

	private ?CraftingManager $craftingManager;

	protected function onLoad() : void{

	}

	protected function onEnable() : void{
		self::setInstance($this);
	}

	private static function makeCraftingManager(string $directoryPath) : CraftingManager{
		$result = new CraftingManager();
		foreach(CraftingManagerFromDataHelper::loadJsonArrayOfObjectsFile(Path::join($directoryPath, 'smelting.json'), FurnaceRecipeData::class) as $recipe){
			$furnaceType = match($recipe->block){
				"campfire" => ExtraFurnaceType::CAMPFIRE(),
				default => null
			};
			if($furnaceType === null){
				continue;
			}
			$output = CraftingManagerFromDataHelper::deserializeItemStack($recipe->output);
			if($output === null){
				continue;
			}
		}
		return $result;
	}

	public function getCraftingManager() : CraftingManager{
		return $this->craftingManager;
	}
}
