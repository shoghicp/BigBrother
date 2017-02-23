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

class BlockChangePacket extends Packet{

	public $x;
	public $y;
	public $z;
	public $blockId;
	public $blockMeta;

	public function pid(){
		return 0x0b;
	}

	public function encode(){
		$this->putPosition($this->x, $this->y, $this->z);
		$this->putVarInt(($this->blockId << 4) | ($this->blockMeta & 15));
	}

	public function decode(){

	}
}