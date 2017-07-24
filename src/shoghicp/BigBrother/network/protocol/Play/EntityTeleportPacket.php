<?php
/**
 *  ______  __         ______               __    __
 * |   __ \|__|.-----.|   __ \.----..-----.|  |_ |  |--..-----..----.
 * |   __ <|  ||  _  ||   __ <|   _||  _  ||   _||     ||  -__||   _|
 * |______/|__||___  ||______/|__|  |_____||____||__|__||_____||__|
 *             |_____|
 *
 * BigBrother plugin for PocketMine-MP
 * Copyright (C) 2014-2015 shoghicp <https://github.com/shoghicp/BigBrother>
 * Copyright (C) 2016- BigBrotherTeam
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
 *
 * @author BigBrotherTeam
 * @link   https://github.com/BigBrotherTeam/BigBrother
 *
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
		return 0x4b;
	}

	public function encode(){
		assert($this->yaw >= 0 and $this->yaw < 360);
		assert($this->pitch >= 0 and $this->pitch < 360);

		$this->putVarInt($this->eid);
		$this->putDouble($this->x);
		$this->putDouble($this->y);
		$this->putDouble($this->z);
		$this->putByte((int)round($this->yaw * 256 / 360));//TODO make sure
		$this->putByte((int)round($this->pitch * 256 / 360));//TODO make sure
		$this->putByte($this->onGround ? 1 : 0);
	}

	public function decode(){

	}
}
