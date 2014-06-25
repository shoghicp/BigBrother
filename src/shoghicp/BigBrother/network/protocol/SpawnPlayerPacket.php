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

namespace shoghicp\BigBrother\network\protocol;

use shoghicp\BigBrother\network\Packet;
use shoghicp\BigBrother\utils\Binary;

class SpawnPlayerPacket extends Packet{

	public $eid;
	public $uuid;
	public $name;
	public $data = [];
	public $x;
	public $y;
	public $z;
	public $yaw;
	public $pitch;
	public $item;
	public $metadata;

	public function pid(){
		return 0x0c;
	}

	public function encode(){
		$this->putVarInt($this->eid);
		$this->putString($this->uuid);
		$this->putString($this->name);
		$this->putVarInt(count($this->data));
		foreach($this->data as $ob){
			$this->putString($ob["name"]);
			$this->putString($ob["value"]);
			$this->putString($ob["signature"]);
		}
		$this->putInt(intval($this->x * 32));
		$this->putInt(intval($this->y * 32));
		$this->putInt(intval($this->z * 32));
		$this->putByte((int) ($this->yaw * (256 / 360)));
		$this->putByte((int) ($this->pitch * (256 / 360)));
		$this->putShort($this->item);
		$this->put(Binary::writeMetadata($this->metadata));
	}

	public function decode(){

	}
}