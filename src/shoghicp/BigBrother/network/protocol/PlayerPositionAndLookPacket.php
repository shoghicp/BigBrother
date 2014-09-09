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

class PlayerPositionAndLookPacket extends Packet{

	public $x;
	public $y;
	public $z;
	public $yaw;
	public $pitch;
	public $isRelativeX;
	public $isRelativeY;
	public $isRelativeZ;
	public $isRelativeYaw;
	public $isRelativePitch;

	public function pid(){
		return 0x06;
	}

	public function encode(){

	}

	public function decode(){
		$this->x = $this->getDouble();
		$this->y = $this->getDouble();
		$this->z = $this->getDouble();
		$this->yaw = $this->getFloat();
		$this->pitch = $this->getFloat();
		$flags = $this->getByte();
		$this->isRelativeX = ($flags & 0x01) > 0;
		$this->isRelativeY = ($flags & 0x02) > 0;
		$this->isRelativeZ = ($flags & 0x04) > 0;
		$this->isRelativePitch = ($flags & 0x08) > 0;
		$this->isRelativeYaw = ($flags & 0x10) > 0;
	}
}