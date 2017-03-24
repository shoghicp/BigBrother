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

class NamedSoundEffectPacket extends Packet{

	public $name;
	public $category;
	public $x;
	public $y;
	public $z;
	public $volume;
	public $pitch;

	public function pid(){
		return 0x19;
	}

	public function encode(){
		$this->putString($this->name);
		$this->putVarInt($this->category);
		$this->putInt($this->x * 8);
		$this->putInt($this->y * 8);
		$this->putInt($this->z * 8);
		$this->putFloat($this->volume);
		$this->putFloat($this->pitch);
	}

	public function decode(){

	}
}