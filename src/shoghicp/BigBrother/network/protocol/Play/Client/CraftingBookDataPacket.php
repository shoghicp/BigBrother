<?php
/**
 *  ______  __         ______               __    __
 * |   __ \|__|.-----.|   __ \.----..-----.|  |_ |  |--..-----..----.
 * |   __ <|  ||  _  ||   __ <|   _||  _  ||   _||     ||  -__||   _|
 * |______/|__||___  ||______/|__|  |_____||____||__|__||_____||__|
 *             |_____|
 *
 * BigBrother plugin for PocketMine-MP
 * Copyright (C) 2014-2015 shoghicp <https://github.com/shoghicp/BigBrother>
 * Copyright (C) 2016- BigBrotherTeam
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * @author BigBrotherTeam
 * @link   https://github.com/BigBrotherTeam/BigBrother
 *
 */

declare(strict_types=1);

namespace shoghicp\BigBrother\network\protocol\Play\Client;

use shoghicp\BigBrother\network\InboundPacket;

class CraftingBookDataPacket extends InboundPacket{

	/** @var int */
	public $type;
	/** @var int */
	public $recipeId = -1;
	/** @var bool */
	public $isCraftingBookOpen = false;
	/** @var bool */
	public $isFilteringCraftable = false;

	public function pid() : int{
		return self::CRAFTING_BOOK_DATA_PACKET;
	}

	protected function decode() : void{
		$this->type = $this->getVarInt();
		switch($this->type){
			case 0://Displayed Recipe
				$this->recipeId = $this->getInt();
			break;
			case 1://Crafting Book Status
				$this->isCraftingBookOpen = $this->getBool();
				$this->isFilteringCraftable = $this->getBool();
			break;
		}
	}
}
