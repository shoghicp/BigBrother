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

class SpawnMobPacket extends Packet{

	public $eid;
	public $uuid;
	public $type;
	public $x;
	public $y;
	public $z;
	public $yaw;
	public $pitch;
	public $headPitch;
	public $velocityX;
	public $velocityY;
	public $velocityZ;
	public $metadata;

	public function pid(){
		return 0x03;
	}

	public function encode(){
		$this->putVarInt($this->eid);
		$this->put($this->uuid);
		$this->putVarInt($this->type);
		$this->putDouble($this->x);
		$this->putDouble($this->y);
		$this->putDouble($this->z);
		$this->putByte((int) ($this->yaw * (256 / 360)));//TODO
		$this->putByte((int) ($this->pitch * (256 / 360)));//TODO
		$this->putByte((int) ($this->headPitch * (256 / 360)));//TODO
		$this->putShort($this->velocityX * 8000);
		$this->putShort($this->velocityY * 8000);
		$this->putShort($this->velocityZ * 8000);
		$this->put(Binary::writeMetadata($this->metadata));
	}

	public function decode(){

	}
}