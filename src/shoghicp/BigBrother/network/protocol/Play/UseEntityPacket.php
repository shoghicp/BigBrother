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
use shoghicp\BigBrother\utils\Binary;

class UseEntityPacket extends Packet{

	public $target;
	public $type;

	public function pid(){
		return 0x02;
	}

	public function encode(){

	}

	public function decode(){
		$this->target = $this->getVarInt();
		$this->type = $this->getVarInt();
		if($this->type === 2){
			$this->targetX = $this->getFloat();
			$this->targetY = $this->getFloat();
			$this->targetZ = $this->getFloat();
		}
	}
}