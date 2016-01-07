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

class PlayerBlockPlacementPacket extends Packet{

	public $x;
	public $y;
	public $z;
	public $direction;
	/** @var \pocketmine\item\Item */
	public $heldItem;
	public $cursorX;
	public $cursorY;
	public $cursorZ;

	public function pid(){
		return 0x08;
	}

	public function encode(){

	}

	public function decode(){
		$this->getPosition($this->x, $this->y, $this->z);
		$this->direction = $this->getByte();
		$this->heldItem = $this->getSlot();
		$this->cursorX = $this->getByte();
		$this->cursorY = $this->getByte();
		$this->cursorZ = $this->getByte();
	}
}