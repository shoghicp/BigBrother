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

class UseBedPacket extends Packet{

	public $eid;
	public $bedX;
	public $bedY;
	public $bedZ;

	public function pid(){
		return 0x2f;
	}

	public function encode(){
		$this->putVarInt($this->eid);
		$this->putPosition($this->bedX, $this->bedY, $this->bedZ);
	}

	public function decode(){
	}
}