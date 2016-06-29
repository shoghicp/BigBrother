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

class SpawnObjectPacket extends Packet{

	public $eid;
	public $type;
	public $x;
	public $y;
	public $z;
	public $yaw;
	public $pitch;
	public $data = "";

	public function pid(){
		return 0x0e;
	}

	public function encode(){
		$this->putVarInt($this->eid);
		$this->putByte($this->type);
		$this->putInt(intval($this->x * 32));
		$this->putInt(intval($this->y * 32));
		$this->putInt(intval($this->z * 32));
		$this->putByte(($this->yaw / 360) << 8);
		$this->putByte(($this->pitch / 360) << 8);
		$this->putInt(0); //TODO: extra data
	}

	public function decode(){

	}
}