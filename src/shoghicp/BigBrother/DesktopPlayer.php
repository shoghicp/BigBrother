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

namespace shoghicp\BigBrother;

use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerRespawnEvent;
use pocketmine\level\format\generic\EmptyChunkSection;
use pocketmine\level\Level;
use pocketmine\level\Position;
use pocketmine\network\protocol\Info;
use pocketmine\network\protocol\LoginPacket;
use pocketmine\network\protocol\SetTimePacket;
use pocketmine\network\SourceInterface;
use pocketmine\Player;
use pocketmine\scheduler\CallbackTask;
use pocketmine\Server;
use pocketmine\tile\Spawnable;
use shoghicp\BigBrother\network\protocol\ChunkDataPacket;
use shoghicp\BigBrother\network\protocol\KeepAlivePacket;
use shoghicp\BigBrother\network\protocol\LoginDisconnectPacket;
use shoghicp\BigBrother\network\protocol\LoginStartPacket;
use shoghicp\BigBrother\network\protocol\LoginSuccessPacket;
use shoghicp\BigBrother\network\protocol\PlayDisconnectPacket;
use shoghicp\BigBrother\network\ProtocolInterface;

class DesktopPlayer extends Player{

	private $bigBrother_status = 0; //0 = log in, 1 = playing
	private $bigBrother_uuid;
	private $bigBrother_formatedUUID;
	/** @var ProtocolInterface */
	protected $interface;

	public function __construct(SourceInterface $interface, $clientID, $address, $port){
		parent::__construct($interface, $clientID, $address, $port);
		$this->setRemoveFormat(false);
	}

	public function bigBrother_sendKeepAlive(){
		$pk = new KeepAlivePacket();
		$pk->id = mt_rand();
		$this->interface->putRawPacket($this, $pk);
	}

	public function bigBrother_getStatus(){
		return $this->bigBrother_status;
	}

	public function sendNextChunk(){
		if($this->connected === false or !isset($this->chunkLoadTask)){
			return;
		}

		if(count($this->loadQueue) === 0){
			$this->chunkLoadTask->setNextRun($this->chunkLoadTask->getNextRun() + 30);
		}else{
			$count = 0;
			$limit = (int) $this->server->getProperty("chunk-sending.per-tick", 1);
			foreach($this->loadQueue as $index => $distance){
				if($count >= $limit){
					break;
				}
				++$count;
				$X = null;
				$Z = null;
				Level::getXZ($index, $X, $Z);
				if(!$this->getLevel()->isChunkPopulated($X, $Z)){
					$this->chunkLoadTask->setNextRun($this->chunkLoadTask->getNextRun() + 30);
					return;
				}

				unset($this->loadQueue[$index]);
				$this->usedChunks[$index] = [true, 0];

				$this->getLevel()->useChunk($X, $Z, $this);
				$pk = new ChunkDataPacket();
				$pk->chunkX = $X;
				$pk->chunkZ = $Z;
				$pk->groundUp = true;
				$pk->addBitmap = 0;
				$chunk = $this->getLevel()->getChunkAt($X, $Z);
				$ids = "";
				$meta = "";
				$blockLight = "";
				$skyLight = "";
				$biomeIds = $chunk->getBiomeIdArray();
				$bitmap = 0;
				for($s = 0; $s < 8; ++$s){
					$section = $chunk->getSection($s);
					if(!($section instanceof EmptyChunkSection)){
						$bitmap |= 1 << $s;
					}
					$ids .= $section->getIdArray();
					$meta .= $section->getDataArray();
					$blockLight .= $section->getLightArray();
					$skyLight .= $section->getSkyLightArray();
				}

				$pk->payload = zlib_encode($ids . $meta . $blockLight . $skyLight . $biomeIds, ZLIB_ENCODING_DEFLATE, Level::$COMPRESSION_LEVEL);
				$pk->primaryBitmap = $bitmap;
				$this->interface->putRawPacket($this, $pk);

				foreach($chunk->getEntities() as $entity){
					if($entity !== $this){
						$entity->spawnTo($this);
					}
				}
				foreach($chunk->getTiles() as $tile){
					if($tile instanceof Spawnable){
						$tile->spawnTo($this);
					}
				}
			}
		}

		if($this->spawned === false){
			//TODO
			//$this->heal($this->data->get("health"), "spawn", true);
			$this->spawned = true;

			$this->sendSettings();
			$this->inventory->sendContents($this);
			$this->inventory->sendArmorContents($this);

			$this->blocked = false;

			$pk = new SetTimePacket();
			$pk->time = $this->getLevel()->getTime();
			$pk->started = $this->getLevel()->stopTime == false;
			$this->dataPacket($pk);

			$pos = new Position($this->x, $this->y, $this->z, $this->getLevel());
			$pos = $this->getLevel()->getSafeSpawn($pos);

			$this->server->getPluginManager()->callEvent($ev = new PlayerRespawnEvent($this, $pos));

			$this->teleport($ev->getRespawnPosition());

			$this->spawnToAll();

			$this->server->getPluginManager()->callEvent($ev = new PlayerJoinEvent($this, $this->getName() . " joined the game"));
			if(strlen(trim($ev->getJoinMessage())) > 0){
				$this->server->broadcastMessage($ev->getJoinMessage());
			}

			if($this->server->getUpdater()->hasUpdate() and $this->hasPermission(Server::BROADCAST_CHANNEL_ADMINISTRATIVE)){
				$this->server->getUpdater()->showPlayerUpdate($this);
			}
		}
	}

	public function bigBrother_authenticate(){
		if($this->bigBrother_status === 0 and $this->loggedIn === true){
			$this->bigBrother_uuid = "00000000000000000000000000000000";
			$this->bigBrother_formatedUUID = substr($this->bigBrother_uuid, 0, 8) ."-". substr($this->bigBrother_uuid, 8, 4) ."-". substr($this->bigBrother_uuid, 12, 4) ."-". substr($this->bigBrother_uuid, 16, 4) ."-". substr($this->bigBrother_uuid, 20);

			$pk = new LoginSuccessPacket();
			$pk->uuid = $this->bigBrother_formatedUUID;
			$pk->name = $this->username;
			$this->interface->putRawPacket($this, $pk);
			$this->bigBrother_status = 1;

			$this->tasks[] = $this->server->getScheduler()->scheduleRepeatingTask(new CallbackTask([$this, "bigBrother_sendKeepAlive"]), 180);
		}
	}

	public function bigBrother_handleAuthentication(LoginStartPacket $packet){
		if($this->bigBrother_status === 0){
			$pk = new LoginPacket();
			$pk->username = $packet->name;
			$pk->clientId = crc32($this->clientID);
			$pk->protocol1 = Info::CURRENT_PROTOCOL;
			$pk->protocol2 = Info::CURRENT_PROTOCOL;
			$pk->loginData = "";
			$this->handleDataPacket($pk);
		}

		/*//Login start
		$packet = new LoginStartPacket();
		$packet->read($buffer);
		$this->username = $packet->name;
		//TODO: authentication
		//TODO: async task
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
		$ret = json_decode(curl_exec($ch), false);
		curl_close($ch);

		if(!is_array($ret) or ($profile = array_shift($ret)) === null){
			$this->status = -1;
			$pk = new LoginDisconnectPacket();
			$pk->reason = "{\"text\":\"§lInvalid player name!\"}";
			$this->writePacket($pk);
			return;
		}

		//$this->uuid = $profile->id;
		$this->formattedUUID = substr($this->uuid, 0, 8) ."-". substr($this->uuid, 8, 4) ."-". substr($this->uuid, 12, 4) ."-". substr($this->uuid, 16, 4) ."-". substr($this->uuid, 20);
		//$this->username = $profile->name;

		$pk = new LoginSuccessPacket();
		$pk->uuid = $this->formattedUUID;
		$pk->name = $this->username;
		$this->writePacket($pk);
		usleep(50000); //TODO: remove this

		//TODO
		//From here on, everything is done by the translator on the main thread :)
		//$this->status = 3;
		$this->status = -1;
		$pk = new PlayDisconnectPacket();
		$pk->reason = json_encode([
			"text" => TextFormat::BOLD . "You logged in correctly!".TextFormat::RESET."\n\n".TextFormat::BOLD."Name: ".TextFormat::RESET . $this->username."\n".TextFormat::BOLD."UUID: ".TextFormat::RESET . $this->formattedUUID."\n\n".TextFormat::GOLD."TODO: ".TextFormat::RESET."Implement translator"
		], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
		$this->writePacket($pk);*/
	}

	public function close($message = "", $reason = "generic reason"){
		if($this->bigBrother_status === 0){
			$pk = new LoginDisconnectPacket();
			$pk->reason = json_encode([
				"text" => $reason === "" ? "You have been disconnected." : $reason
			]);
			$this->interface->putRawPacket($this, $pk);
		}else{
			$pk = new PlayDisconnectPacket();
			$pk->reason = json_encode([
				"text" => $reason === "" ? "You have been disconnected." : $reason
			]);
			$this->interface->putRawPacket($this, $pk);
		}
		parent::close($message, $reason);
	}
}