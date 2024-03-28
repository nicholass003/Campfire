<?php

/**
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
 */

declare(strict_types=1);

namespace nicholass003\campfire\crafting;

use nicholass003\campfire\sound\CampfireSound;
use pocketmine\utils\LegacyEnumShimTrait;
use pocketmine\world\sound\Sound;
use function spl_object_id;

/**
 * TODO: These tags need to be removed once we get rid of LegacyEnumShimTrait (PM6)
 *  These are retained for backwards compatibility only.
 *
 * @method static FurnaceType CAMPFIRE()
 *
 * @phpstan-type TMetadata array{0: int, 1: Sound}
 */

enum ExtraFurnaceType{
	use LegacyEnumShimTrait;

	case CAMPFIRE;

	/**
	 * @phpstan-return TMetadata
	 */
	private function getMetadata() : array{
		/** @phpstan-var array<int, TMetadata> $cache */
		static $cache = [];
		
		return $cache[spl_object_id($this)] ??= match($this){
			self::CAMPFIRE => [600, new CampfireSound()]
		};
	}

	public function getCookDurationTicks() : int{ return $this->getMetadata()[0]; }

	public function getCookSound() : Sound{ return $this->getMetadata()[1]; }
}