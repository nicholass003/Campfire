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

use pocketmine\crafting\CraftingManagerFromDataHelper;
use pocketmine\crafting\ExactRecipeIngredient;
use pocketmine\crafting\FurnaceRecipe;
use pocketmine\crafting\json\FurnaceRecipeData;
use pocketmine\crafting\json\RecipeIngredientData;
use pocketmine\crafting\MetaWildcardRecipeIngredient;
use pocketmine\crafting\RecipeIngredient;
use pocketmine\crafting\TagWildcardRecipeIngredient;
use pocketmine\data\bedrock\block\BlockStateData;
use pocketmine\data\bedrock\item\BlockItemIdMap;
use pocketmine\data\bedrock\item\ItemTypeDeserializeException;
use pocketmine\data\bedrock\item\SavedItemData;
use pocketmine\data\bedrock\item\SavedItemStackData;
use pocketmine\data\SavedDataLoadingException;
use pocketmine\errorhandler\ErrorToExceptionHandler;
use pocketmine\item\Item;
use pocketmine\nbt\LittleEndianNbtSerializer;
use pocketmine\network\mcpe\convert\TypeConverter;
use pocketmine\network\mcpe\protocol\CraftingDataPacket;
use pocketmine\network\mcpe\protocol\types\recipe\FurnaceRecipe as ProtocolFurnaceRecipe;
use pocketmine\network\mcpe\protocol\types\recipe\FurnaceRecipeBlockName;
use pocketmine\network\mcpe\protocol\types\recipe\IntIdMetaItemDescriptor;
use pocketmine\utils\AssumptionFailedError;
use pocketmine\utils\SingletonTrait;
use pocketmine\world\format\io\GlobalItemDataHandlers;
use Symfony\Component\Filesystem\Path;
use function base64_decode;

class CampfireFurnaceRecipeHandler{
	use SingletonTrait;

	/** @var FurnaceRecipe[] */
	protected array $furnaceRecipes = [];

	public function makeCampfireFurnaceRecipe(string $directoryPath) : CampfireFurnaceRecipeHandler{
		$directoryPath = Path::join(\pocketmine\BEDROCK_DATA_PATH, "recipes");
		foreach(CraftingManagerFromDataHelper::loadJsonArrayOfObjectsFile(Path::join($directoryPath, 'smelting.json'), FurnaceRecipeData::class) as $recipe){
			$output = CraftingManagerFromDataHelper::deserializeItemStack($recipe->output);
			if($output === null){
				continue;
			}
			$input = self::deserializeIngredient($recipe->input);
			if($input === null){
				continue;
			}
			$this->register(new FurnaceRecipe(
				$output,
				$input
			));
		}
		return $this;
	}

	public function getAll() : array{
		return $this->furnaceRecipes;
	}

	public function register(FurnaceRecipe $recipe) : void{
		$this->furnaceRecipes[] = $recipe;
	}

	public function match(Item $input) : ?FurnaceRecipe{
		foreach($this->furnaceRecipes as $recipe){
			if($recipe->getInput()->accepts($input)){
				return $recipe;
			}
		}
		return null;
	}

	public function getCache() : CraftingDataPacket{
		$recipesWithTypeIds = [];
		foreach($this->furnaceRecipes as $recipe){
			$input = TypeConverter::getInstance()->coreRecipeIngredientToNet($recipe->getInput())->getDescriptor();
			if(!$input instanceof IntIdMetaItemDescriptor){
				throw new AssumptionFailedError();
			}
			$recipesWithTypeIds[] = new ProtocolFurnaceRecipe(
				CraftingDataPacket::ENTRY_FURNACE_DATA,
				$input->getId(),
				$input->getMeta(),
				TypeConverter::getInstance()->coreItemStackToNet($recipe->getResult()),
				FurnaceRecipeBlockName::CAMPFIRE
			);
		}
		return CraftingDataPacket::create($recipesWithTypeIds, [], [], [], false);
	}

	//private method from pocketmine]\crafting\CraftingManagerFromDataHelper
	private static function deserializeIngredient(RecipeIngredientData $data) : ?RecipeIngredient{
		if(isset($data->count) && $data->count !== 1){
			//every case we've seen so far where this isn't the case, it's been a bug and the count was ignored anyway
			//e.g. gold blocks crafted from 9 ingots, but each input item individually had a count of 9
			throw new SavedDataLoadingException("Recipe inputs should have a count of exactly 1");
		}
		if(isset($data->tag)){
			return new TagWildcardRecipeIngredient($data->tag);
		}

		$meta = $data->meta ?? null;
		if($meta === RecipeIngredientData::WILDCARD_META_VALUE){
			//this could be an unimplemented item, but it doesn't really matter, since the item shouldn't be able to
			//be obtained anyway - filtering unknown items is only really important for outputs, to prevent players
			//obtaining them
			return new MetaWildcardRecipeIngredient($data->name);
		}

		$itemStack = self::deserializeItemStackFromFields(
			$data->name,
			$meta,
			$data->count ?? null,
			$data->block_states ?? null,
			null,
			[],
			[]
		);
		if($itemStack === null){
			//probably unknown item
			return null;
		}
		return new ExactRecipeIngredient($itemStack);
	}

	/**
	 * @param string[] $canPlaceOn
	 * @param string[] $canDestroy
	 */
	private static function deserializeItemStackFromFields(string $name, ?int $meta, ?int $count, ?string $blockStatesRaw, ?string $nbtRaw, array $canPlaceOn, array $canDestroy) : ?Item{
		$meta ??= 0;
		$count ??= 1;

		$blockName = BlockItemIdMap::getInstance()->lookupBlockId($name);
		if($blockName !== null){
			if($meta !== 0){
				throw new SavedDataLoadingException("Meta should not be specified for blockitems");
			}
			$blockStatesTag = $blockStatesRaw === null ?
				[] :
				(new LittleEndianNbtSerializer())
					->read(ErrorToExceptionHandler::trapAndRemoveFalse(fn() => base64_decode($blockStatesRaw, true)))
					->mustGetCompoundTag()
					->getValue();
			$blockStateData = BlockStateData::current($blockName, $blockStatesTag);
		}else{
			$blockStateData = null;
		}

		$nbt = $nbtRaw === null ? null : (new LittleEndianNbtSerializer())
			->read(ErrorToExceptionHandler::trapAndRemoveFalse(fn() => base64_decode($nbtRaw, true)))
			->mustGetCompoundTag();

		$itemStackData = new SavedItemStackData(
			new SavedItemData(
				$name,
				$meta,
				$blockStateData,
				$nbt
			),
			$count,
			null,
			null,
			$canPlaceOn,
			$canDestroy,
		);

		try{
			return GlobalItemDataHandlers::getDeserializer()->deserializeStack($itemStackData);
		}catch(ItemTypeDeserializeException){
			//probably unknown item
			return null;
		}
	}
}
