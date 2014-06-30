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

class OnlineProfile extends AsyncTask{

	protected $clientID;
	protected $username;

	public function __construct($clientID, $username){
		$this->clientID = $clientID;
		$this->username = $username;
	}

	public function onRun(){
		$ch = curl_init("https://api.mojang.com/profiles/minecraft");
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
		curl_setopt($ch, CURLOPT_FORBID_REUSE, 1);
		curl_setopt($ch, CURLOPT_FRESH_CONNECT, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([$this->username]));
		curl_setopt($ch, CURLOPT_AUTOREFERER, true);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array("User-Agent: Mozilla/5.0 (Windows NT 6.1; WOW64; rv:12.0) Gecko/20100101 Firefox/12.0 PocketMine-MP", "Content-Type: application/json"));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_TIMEOUT, 3);
		$ret = json_decode(curl_exec($ch), true);
		curl_close($ch);

		if(!is_array($ret) or ($profile = array_shift($ret)) === null){
			return;
		}

		$uuid = $profile["id"];

		$info = json_decode(Utils::getURL("https://sessionserver.mojang.com/session/minecraft/profile/$uuid", 3), true);

		if(!is_array($info)){
			return;
		}

		$this->setResult($info);
	}

	public function onCompletion(Server $server){
		foreach($server->getOnlinePlayers() as $clientID => $player){
			if($player instanceof DesktopPlayer and $clientID === $this->clientID){
				$result = $this->getResult();
				if(is_array($result) and isset($result["id"])){
					$player->bigBrother_authenticate($this->username, $result["id"], $result["properties"]);
				}else{
					$player->bigBrother_authenticate($this->username, "00000000000040008000000000000000", null);
				}
				break;
			}
		}
	}
}