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

namespace shoghicp\BigBrother\network\protocol;

use shoghicp\BigBrother\network\Packet;
use shoghicp\BigBrother\utils\Binary;

class EntityVelocityPacket extends Packet{

	public $eid;
	public $velocityX;
	public $velocityY;
	public $velocityZ;

	public function pid(){
		return 0x12;
	}

	public function encode(){
		$this->put(Binary::writeVarInt($this->eid));
		$this->putShort($this->velocityX * 8000);
		$this->putShort($this->velocityY * 8000);
		$this->putShort($this->velocityZ * 8000);
	}

	public function decode(){

	}
}