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

namespace shoghicp\BigBrother\tasks;

use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;
use pocketmine\utils\Utils;
use shoghicp\BigBrother\DesktopPlayer;

class AuthenticateOnline extends AsyncTask{

	protected $clientID;
	protected $username;
	protected $hash;

	public function __construct($clientID, $username, $hash){
		$this->clientID = $clientID;
		$this->username = $username;
		$this->hash = $hash;
	}

	public function onRun(){
		$result = Utils::getURL("https://sessionserver.mojang.com/session/minecraft/hasJoined?username=".$this->username."&serverId=".$this->hash, 5);
		$this->setResult($result);
	}

	public function onCompletion(Server $server){
		foreach($server->getOnlinePlayers() as $clientID => $player){
			if($player instanceof DesktopPlayer and $clientID === $this->clientID){
				$result = json_decode($this->getResult(), true);
				if(is_array($result) and isset($result["id"])){
					$player->bigBrother_authenticate($this->username, $result["id"], $result["properties"]);
				}else{
					$player->close("", "User not premium");
				}
				break;
			}
		}
	}
}