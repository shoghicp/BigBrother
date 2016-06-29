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

class PluginMessagePacket extends Packet{

	public $channel;
	public $data = [];

	public function pid(){
		return 0x17;
	}

	public function encode(){
	}

	public function decode(){
		$this->channel = $this->getString();
		switch($this->channel){
			case "REGISTER":
				$channels = bin2hex($this->getString());
				$channels = str_split($channels, 2);
				$string = "";
				foreach($channels as $num => $str){
					if($str === "00"){
						$this->data[] = hex2bin($string);
						$string = "";
					}else{
						$string .= $str;
						if(count($channels) -1 === $num){
							$this->data[] = hex2bin($string);
						}
					}
				}
			break;
			case "MC|Brand":
				$this->data = $this->getString();
			break;
		}
	}
}