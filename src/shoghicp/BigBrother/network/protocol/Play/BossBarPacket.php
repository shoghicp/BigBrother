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

class BossBarPacket extends Packet{

	const TYPE_ADD = 0;
	const TYPE_REMOVE = 1;
	const TYPE_UPDATE_HEALTH = 2;
	const TYPE_UPDATE_TITLE = 3;

	public $uuid;
	public $actionID;

	public function pid(){
		return 0x0c;
	}

	public function encode(){
		$this->put($this->uuid);
		$this->putVarInt($this->actionID);
		switch($this->actionID){
			case self::TYPE_ADD:
				$this->putString($this->title);//Chat format
				$this->putFloat($this->health);
				$this->putVarInt($this->color);
				$this->putVarInt($this->division);
				$this->putByte($this->flags);
			break;
			case self::TYPE_REMOVE:
			break;
			case self::TYPE_UPDATE_HEALTH:
				$this->putFloat($this->health);
			break;
			case self::TYPE_UPDATE_TITLE:
				$this->putString($this->title);//Chat format
			break;
			//TODO: addtype
		}
	}

	public function decode(){
	}
}