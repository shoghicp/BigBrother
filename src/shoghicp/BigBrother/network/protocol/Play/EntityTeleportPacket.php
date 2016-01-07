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

class EntityTeleportPacket extends Packet{

	public $eid;
	public $x;
	public $y;
	public $z;
	public $yaw;
	public $pitch;
	public $onGround = true;

	public function pid(){
		return 0x18;
	}

	public function encode(){
		$this->putVarInt($this->eid);
		$this->putInt(intval($this->x * 32));
		$this->putInt(intval($this->y * 32));
		$this->putInt(intval($this->z * 32));
		$this->putByte((int) ($this->yaw * (256 / 360)));
		$this->putByte((int) ($this->pitch * (256 / 360)));
		$this->putByte($this->onGround ? 1 : 0);
	}

	public function decode(){

	}
}