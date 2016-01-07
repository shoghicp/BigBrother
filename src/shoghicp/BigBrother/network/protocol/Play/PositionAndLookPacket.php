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

class PositionAndLookPacket extends Packet{

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
		return 0x08;
	}

	public function encode(){
		$this->putDouble($this->x);
		$this->putDouble($this->y);
		$this->putDouble($this->z);
		$this->putFloat($this->yaw);
		$this->putFloat($this->pitch);
		$flags = 0;
		if($this->isRelativeX){
			$flags |= 0x01;
		}
		if($this->isRelativeY){
			$flags |= 0x02;
		}
		if($this->isRelativeZ){
			$flags |= 0x04;
		}
		if($this->isRelativePitch){
			$flags |= 0x08;
		}
		if($this->isRelativeYaw){
			$flags |= 0x10;
		}
		$this->putByte($flags);
	}

	public function decode(){

	}
}