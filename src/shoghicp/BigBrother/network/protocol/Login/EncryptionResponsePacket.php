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

namespace shoghicp\BigBrother\network\protocol\Login;

use shoghicp\BigBrother\network\Packet;

class EncryptionResponsePacket extends Packet{

	public $sharedSecret;
	public $verifyToken;

	public function pid(){
		return 0x01;
	}

	public function encode(){

	}

	public function decode(){
		$this->sharedSecret = $this->get($this->getVarInt());
		$this->verifyToken = $this->get($this->getVarInt());
	}
}