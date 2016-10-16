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

namespace shoghicp\BigBrother\network\protocol\Play\Server;

use shoghicp\BigBrother\network\Packet;

class PlayerPositionAndLookPacket extends Packet{

	public $x;
	public $y;
	public $z;
	public $yaw;
	public $pitch;
	public $flags;
	public $teleportId;

	public function pid(){
		return 0x2e;
	}

	public function encode(){
		$this->putDouble($this->x);
		$this->putDouble($this->y);
		$this->putDouble($this->z);
		$this->putFloat($this->yaw);
		$this->putFloat($this->pitch);
		$this->putByte($this->flags);
		$this->putVarInt($this->teleportId);
	}

	public function decode(){
		
	}
}