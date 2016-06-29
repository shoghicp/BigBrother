<?php

/*
 * BigBrother plugin for PocketMine-MP
 * Copyright (C) 2014 shoghicp <https://github.com/shoghicp/BigBrother>
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
*/

namespace shoghicp\BigBrother\network\protocol\Play;

use shoghicp\BigBrother\network\Packet;

class OpenWindowPacket extends Packet{

	public $windowID;
	public $inventoryType;
	public $windowTitle;
	public $slots;
	public $useTitle = false;
	public $entityId;

	public function pid(){
		return 0x2d;
	}

	public function encode(){
		$this->putByte($this->windowID);
		$type = "";
		switch($this->inventoryType){
			case 0:
				$type = "minecraft:chest";
				break;
			case 1:
				$type = "minecraft:crafting_table";
				break;
			case 2:
				$type = "minecraft:furnace";
				break;
			//TODO: http://wiki.vg/Inventory#Windows
		}
		$this->putString($type);
		$this->putString($this->windowTitle);
		$this->putByte($this->slots);
		$this->putByte($this->useTitle ? 1 : 0);
		if($this->windowID === 11){
			$this->putInt($this->entityId);
		}
	}

	public function decode(){

	}
}