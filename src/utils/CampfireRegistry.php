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

namespace nicholass003\campfire\utils;

use nicholass003\campfire\block\Campfire;
use nicholass003\campfire\block\ExtraVanillaBlocks;
use nicholass003\campfire\block\SoulCampfire;
use nicholass003\campfire\block\tile\Campfire as TileCampfire;
use pocketmine\block\RuntimeBlockStateRegistry;
use pocketmine\block\tile\TileFactory;
use pocketmine\data\bedrock\block\BlockStateNames;
use pocketmine\data\bedrock\block\BlockTypeNames;
use pocketmine\data\bedrock\block\convert\BlockStateReader;
use pocketmine\data\bedrock\block\convert\BlockStateWriter;
use pocketmine\inventory\CreativeInventory;
use pocketmine\item\StringToItemParser;
use pocketmine\world\format\io\GlobalBlockStateHandlers;

class CampfireRegistry{

	private function construct(){}

	public static function register() : void{
		self::registerCampfire();
		self::mapBlockStateToObjectDeserializer();
		self::mapBlockObjectToStateSerializer();
		self::registerTile();
	}

	private static function registerCampfire() : void{
		self::registerBlock(ExtraVanillaBlocks::CAMPFIRE(), ["campfire"]);
		self::registerBlock(ExtraVanillaBlocks::SOUL_CAMPFIRE(), ["soul_campfire"]);
	}

	private static function mapBlockStateToObjectDeserializer() : void{
		GlobalBlockStateHandlers::getDeserializer()->map(BlockTypeNames::CAMPFIRE, function(BlockStateReader $in) : Campfire{
			return ExtraVanillaBlocks::CAMPFIRE()
					->setFacing($in->readCardinalHorizontalFacing())
					->setExtinguished($in->readBool(BlockStateNames::EXTINGUISHED));
		});
		GlobalBlockStateHandlers::getDeserializer()->map(BlockTypeNames::SOUL_CAMPFIRE, function(BlockStateReader $in) : SoulCampfire{
			return ExtraVanillaBlocks::SOUL_CAMPFIRE()
					->setFacing($in->readCardinalHorizontalFacing())
					->setExtinguished($in->readBool(BlockStateNames::EXTINGUISHED));
		});
	}

	private static function mapBlockObjectToStateSerializer() : void{
		GlobalBlockStateHandlers::getSerializer()->map(ExtraVanillaBlocks::CAMPFIRE(),
			fn(Campfire $block) => self::encodeCampfire($block, BlockStateWriter::create(BlockTypeNames::CAMPFIRE))
		);
		GlobalBlockStateHandlers::getSerializer()->map(ExtraVanillaBlocks::SOUL_CAMPFIRE(),
			fn(SoulCampfire $block) => self::encodeCampfire($block, BlockStateWriter::create(BlockTypeNames::SOUL_CAMPFIRE))
		);
	}

	private static function encodeCampfire(Campfire|SoulCampfire $block, BlockStateWriter $out) : BlockStateWriter{
		return $out
			->writeCardinalHorizontalFacing($block->getFacing())
			->writeBool(BlockStateNames::EXTINGUISHED, $block->isExtinguished());
	}

	private static function registerTile() : void{
		$tileFactory = TileFactory::getInstance();
		$tileFactory->register(TileCampfire::class, ["Campfire", "minecraft:campfire"]);
	}

	private static function registerBlock(Campfire|SoulCampfire $block, array $stringToItemParserNames) : void{
		RuntimeBlockStateRegistry::getInstance()->register($block);
		foreach($stringToItemParserNames as $name){
			StringToItemParser::getInstance()->registerBlock($name, fn() => clone $block);
		}
		CreativeInventory::getInstance()->add($block->asItem());
	}
}
