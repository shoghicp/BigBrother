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

class CPlayerAbilitiesPacket extends Packet{

	public $damageDisabled;
	public $canFly;
	public $isFlying = false;
	public $isCreative;

	public $flyingSpeed;
	public $walkingSpeed;

	public function pid(){
		return 0x13;
	}

	public function encode(){
	}

	public function decode(){
		$flags = base_convert($this->getByte(), 10, 2);
		if(strlen($flags) !== 8){
			$flags = str_repeat("0", 8 - strlen($flags)).$flags;
		}
		$flags = intval($flags);

		if(($flags & 0x08) !== 0){
			$this->damageDisabled = true;
		}else{
			$this->damageDisabled = false;
		}

		if(($flags & 0x04) !== 0){
			$this->canFly = true;
		}else{
			$this->canFly = false;
		}

		if(($flags & 0x02) !== 0){
			$this->isFlying = true;
		}else{
			$this->isFlying = false;
		}

		if(($flags & 0x01) !== 0){
			$this->isCreative = true;
		}else{
			$this->isCreative = false;
		}

		$this->flyingSpeed = $this->getFloat();
		$this->walkingSpeed = $this->getFloat();
	}
}