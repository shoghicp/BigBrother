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

class PlayerPositionPacket extends Packet{

	public $x;
	public $y;
	public $z;
	public $onGround;

	public function pid(){
		return 0x04;
	}

	public function encode(){

	}

	public function decode(){
		$this->x = $this->getDouble();
		$this->y = $this->getDouble();
		$this->z = $this->getDouble();
		$this->onGround = $this->getByte() > 0;
	}
}