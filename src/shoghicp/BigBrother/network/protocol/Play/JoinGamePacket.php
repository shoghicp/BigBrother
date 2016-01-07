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

class JoinGamePacket extends Packet{

	public $eid;
	public $gamemode;
	public $dimension;
	public $difficulty;
	public $maxPlayers;
	public $levelType;
	public $reducedDebugInfo = false;

	public function pid(){
		return 0x01;
	}

	public function encode(){
		$this->putInt($this->eid);
		$this->putByte($this->gamemode);
		$this->putByte($this->dimension);
		$this->putByte($this->difficulty);
		$this->putByte($this->maxPlayers);
		$this->putString($this->levelType);
		$this->putByte($this->reducedDebugInfo ? 1 : 0);
	}

	public function decode(){

	}
}