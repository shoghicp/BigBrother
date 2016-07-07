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
	protected $player;

	public function __construct($clientID, $username, $player){
		$this->clientID = $clientID;
		$this->username = $username;
		$this->player = $player;
	}

	public function onRun(){
		/*$ch = curl_init("https://api.mojang.com/profiles/minecraft");
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
		}*/
		$profile = json_decode('{"id":"c96792ac7aea4f16975e535a20a2791a","name":"Hello"}',true);//json_decode(Utils::getURL("https://api.mojang.com/users/profiles/minecraft/".$username), true);
		if(!is_array($profile)){
			return false;
		}

		$uuid = $profile["id"];
		$info = json_decode('{"id":"c96792ac7aea4f16975e535a20a2791a","name":"Hello","properties":[{"name":"textures","value":"eyJ0aW1lc3RhbXAiOjE0Njc1OTk2OTkyODQsInByb2ZpbGVJZCI6ImM5Njc5MmFjN2FlYTRmMTY5NzVlNTM1YTIwYTI3OTFhIiwicHJvZmlsZU5hbWUiOiJIZWxsbyIsInRleHR1cmVzIjp7IlNLSU4iOnsidXJsIjoiaHR0cDovL3RleHR1cmVzLm1pbmVjcmFmdC5uZXQvdGV4dHVyZS9lYzNmMDc2MTliNmFjMzczMGZkYzMxZmExZWMxY2JkMmE4ZjhkZmJkOTdkYzhhYWE4ZTI0NWJhODVhZTlmNzYifX19"}]}', true);
		if(!is_array($info)){
			return false;
		}
		$this->setResult($info);
	}

	public function onCompletion(Server $server){
		//foreach($server->getOnlinePlayers() as $clientID => $player){
			//if($player instanceof DesktopPlayer and $clientID === $this->clientID){
		echo "Cool\n";
				$result = $this->getResult();

				if(is_array($result) and isset($result["id"])){
					$this->player->bigBrother_authenticate($this->username, $result["id"], $result["properties"]);
				}else{
					$this->player->bigBrother_authenticate($this->username, "00000000000040008000000000000000", null);
				}
				//break;
			//}
		//}
	}
}