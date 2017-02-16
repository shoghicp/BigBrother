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

class EntityPropertiesPacket extends Packet{

	public $eid;
	public $entries = [];

	public function pid(){
		return 0x4a;
	}

	public function encode(){
		$this->putVarInt($this->eid);
		$this->putInt(count($this->entries));
		foreach($this->entries as $entry){
			$this->putString($entry[0]);
			$this->putDouble($entry[1]);
			$this->putVarInt(0);//TODO: Modifiers
		}
	}

	public function decode(){

	}
}