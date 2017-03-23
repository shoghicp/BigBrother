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

class PlayerListPacket extends Packet{

	const TYPE_ADD = 0;
	const TYPE_UPDATE_NAME = 3;
	const TYPE_REMOVE = 4;

	public $actionID;
	public $players = [];

	public function pid(){
		return 0x2d;
	}

	public function clean(){
		$this->players = [];
		return parent::clean();
	}

	public function encode(){
		$this->putVarInt($this->actionID);
		$this->putVarInt(count($this->players));
		foreach($this->players as $player){
			switch($this->actionID){
				case self::TYPE_ADD:
					$this->put($player[0]);//UUID
					$this->putString($player[1]); //PlayerName
					$this->putVarInt(count($player[2])); //Count Peropetry

					foreach($player[2] as $peropetrydata){
						$this->putString($peropetrydata["name"]); //Name
						$this->putString($peropetrydata["value"]); //Value
						if(isset($peropetrydata["signature"])){
							$this->putByte(1); //Is Signed
							$this->putString($peropetrydata["signature"]); //Peropetry
						}else{
							$this->putByte(0); //Is Signed
						}
					}

					$this->putVarInt($player[3]); //Gamemode
					$this->putVarInt($player[4]); //Ping
					$this->putByte($player[5] ? 1 : 0); //has Display name
					if($player[5] === true){
						$this->putString($player[6]); //Display name
					}
					break;
				case self::TYPE_UPDATE_NAME:
					$this->put($player[0]);//UUID
					$this->putByte($player[1] ? 1 : 0); //has Display name
					$this->putString($player[2]);//Display name
					break;
				case self::TYPE_REMOVE:
					$this->put($player[0]);//UUID
					break;
				default:
					echo "PlayerListPacket: ".$this->actionID."\n";
					break;
			}
		}
	}

	public function decode(){

	}
}