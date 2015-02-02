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
use pocketmine\level\format\anvil\Chunk as AnvilChunk;
use pocketmine\level\format\mcregion\Chunk as McRegionChunk;
use pocketmine\level\format\leveldb\Chunk as LevelDBChunk;
use pocketmine\level\format\generic\EmptyChunkSection;
use pocketmine\level\Level;
use pocketmine\level\Position;
use pocketmine\network\protocol\Info;
use pocketmine\network\protocol\LoginPacket;
use pocketmine\network\protocol\SetEntityMotionPacket;
use pocketmine\network\protocol\SetTimePacket;
use pocketmine\network\SourceInterface;
use pocketmine\Player;
use pocketmine\scheduler\CallbackTask;
use pocketmine\Server;
use pocketmine\tile\Spawnable;
use pocketmine\utils\TextFormat;
use pocketmine\utils\Utils;
use shoghicp\BigBrother\network\Packet;
use shoghicp\BigBrother\network\protocol\ChunkDataPacket;
use shoghicp\BigBrother\network\protocol\EncryptionRequestPacket;
use shoghicp\BigBrother\network\protocol\EncryptionResponsePacket;
use shoghicp\BigBrother\network\protocol\EntityMetadataPacket;
use shoghicp\BigBrother\network\protocol\EntityTeleportPacket;
use shoghicp\BigBrother\network\protocol\KeepAlivePacket;
use shoghicp\BigBrother\network\protocol\LoginDisconnectPacket;
use shoghicp\BigBrother\network\protocol\LoginStartPacket;
use shoghicp\BigBrother\network\protocol\LoginSuccessPacket;
use shoghicp\BigBrother\network\protocol\PlayDisconnectPacket;
use shoghicp\BigBrother\network\protocol\SpawnMobPacket;
use shoghicp\BigBrother\network\protocol\SpawnPlayerPacket;
use shoghicp\BigBrother\network\ProtocolInterface;
use shoghicp\BigBrother\tasks\AuthenticateOnline;
use shoghicp\BigBrother\tasks\LevelDBToAnvil;
use shoghicp\BigBrother\tasks\McRegionToAnvil;
use shoghicp\BigBrother\tasks\OnlineProfile;
use shoghicp\BigBrother\utils\Binary;

class DesktopPlayer extends Player{

	private $bigBrother_status = 0; //0 = log in, 1 = playing
	protected $bigBrother_uuid;
	protected $bigBrother_formatedUUID;
	protected $bigBrother_properties = [];
	private $bigBrother_checkToken;
	private $bigBrother_secret;
	private $bigBrother_username;
	protected $bigBrother_titleBarID = null;
	protected $bigBrother_titleBarText;
	protected $bigBrother_titleBarLevel;
	/** @var ProtocolInterface */
	protected $interface;

	public function __construct(SourceInterface $interface, $clientID, $address, $port){
		parent::__construct($interface, $clientID, $address, $port);
		$this->setRemoveFormat(false);
	}

	public function bigBrother_updateTitleBar(){
		if($this->bigBrother_titleBarID === null){
			$this->bigBrother_titleBarID = 2147483647;

			$pk = new SpawnMobPacket();
			$pk->eid = $this->bigBrother_titleBarID;
			$pk->type = 63;
			$pk->x = $this->x;
			$pk->y = 250;
			$pk->z = $this->z;
			$pk->pitch = 0;
			$pk->yaw = 0;
			$pk->headPitch = 0;
			$pk->velocityX = 0;
			$pk->velocityY = 0;
			$pk->velocityZ = 0;
			$pk->metadata = [
				0 => ["type" => 0, "value" => 0x20],
				6 => ["type" => 3, "value" => 200 * ($this->bigBrother_titleBarLevel / 100)],
				7 => ["type" => 2, "value" => 0],
				10 => ["type" => 4, "value" => $this->bigBrother_titleBarText],
				11 => ["type" => 0, "value" => 1]
			];
			$this->putRawPacket($pk);
			$this->tasks[] = $this->getServer()->getScheduler()->scheduleDelayedRepeatingTask(new CallbackTask([$this, "bigBrother_updateTitleBar"]), 5, 20);
		}else{
			$pk = new EntityTeleportPacket();
			$pk->eid = $this->bigBrother_titleBarID;
			$pk->x = $this->x;
			$pk->y = 250;
			$pk->z = $this->z;
			$pk->yaw = 0;
			$pk->pitch = 0;
			$this->putRawPacket($pk);

			$pk = new EntityMetadataPacket();
			$pk->eid = $this->bigBrother_titleBarID;
			$pk->metadata = [
				0 => ["type" => 0, "value" => 0x20],
				6 => ["type" => 3, "value" => 200 * ($this->bigBrother_titleBarLevel / 100)],
				7 => ["type" => 2, "value" => 0],
				10 => ["type" => 4, "value" => $this->bigBrother_titleBarText],
				11 => ["type" => 0, "value" => 1]
			];
			$this->putRawPacket($pk);

		}
	}

	public function bigBrother_setTitleBar($text, $level = 100){
		if($level > 100){
			$level = 100;
		}elseif($level < 0){
			$level = 0;
		}

		$this->bigBrother_titleBarText = $text;
		$this->bigBrother_titleBarLevel = $level;
		$this->bigBrother_updateTitleBar();
	}

	public function bigBrother_sendKeepAlive(){
		$pk = new KeepAlivePacket();
		$pk->id = mt_rand();
		$this->putRawPacket($pk);
	}

	public function bigBrother_getStatus(){
		return $this->bigBrother_status;
	}

	public function bigBrother_sendChunk($x, $z, $payload){
		$pk = new ChunkDataPacket();
		$pk->chunkX = $x;
		$pk->chunkZ = $z;
		$pk->groundUp = true;

		$pk->payload = $payload;
		$pk->primaryBitmap = 0xff;
		$this->putRawPacket($pk);
	}

	public function sendChunk($x, $z, $payload){

	}

	protected function sendNextChunk(){
		if($this->connected === false){
			return;
		}

		$count = 0;
		foreach($this->loadQueue as $index => $distance){
			if($count >= $this->chunksPerTick){
				break;
			}

			Level::getXZ($index, $X, $Z);
			if(!$this->level->isChunkPopulated($X, $Z)){
				$this->level->generateChunk($X, $Z);
				if($this->spawned){
					continue;
				}else{
					break;
				}
			}

			++$count;

			unset($this->loadQueue[$index]);
			$this->usedChunks[$index] = true;

			$this->level->useChunk($X, $Z, $this);
			$chunk = $this->level->getChunk($X, $Z);
			if($chunk instanceof AnvilChunk){
				$this->kick("Playing on Anvil worlds is not yet implemented");
				//TODO!
				/*$pk = new ChunkDataPacket();
				$pk->chunkX = $X;
				$pk->chunkZ = $Z;
				$pk->groundUp = true;
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
					}else{
						continue;
					}
					$ids .= $section->getIdArray();
					$meta .= $section->getDataArray();
					$blockLight .= $section->getLightArray();
					$skyLight .= $section->getSkyLightArray();
				}

				$pk->payload = zlib_encode($ids . $meta . $blockLight . $skyLight . $biomeIds, ZLIB_ENCODING_DEFLATE, Level::$COMPRESSION_LEVEL);
				$pk->primaryBitmap = $bitmap;
				$this->putRawPacket($pk);*/
			}elseif($chunk instanceof McRegionChunk){
				$task = new McRegionToAnvil($this, $chunk);
				$this->server->getScheduler()->scheduleAsyncTask($task);
			}elseif($chunk instanceof LevelDBChunk){
				$task = new LevelDBToAnvil($this, $chunk);
				$this->server->getScheduler()->scheduleAsyncTask($task);
			}

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

		if(count($this->usedChunks) >= 4 and $this->spawned === false){

			$this->bigBrother_setTitleBar(TextFormat::YELLOW . TextFormat::BOLD . "This is a beta version of BigBrother.", 0);

			$this->spawned = true;

			$pk = new SetTimePacket();
			$pk->time = $this->level->getTime();
			$pk->started = $this->level->stopTime == false;
			$this->dataPacket($pk);

			$pos = $this->level->getSafeSpawn($this);

			$this->server->getPluginManager()->callEvent($ev = new PlayerRespawnEvent($this, $pos));

			$this->teleport($ev->getRespawnPosition());

			$this->sendSettings();
			$this->inventory->sendContents($this);
			$this->inventory->sendArmorContents($this);

			$this->server->getPluginManager()->callEvent($ev = new PlayerJoinEvent($this, TextFormat::YELLOW . $this->getName() . " joined the game"));
			if(strlen(trim($ev->getJoinMessage())) > 0){
				$this->server->broadcastMessage($ev->getJoinMessage());
			}

			$this->spawnToAll();

			if($this->server->getUpdater()->hasUpdate() and $this->hasPermission(Server::BROADCAST_CHANNEL_ADMINISTRATIVE)){
				$this->server->getUpdater()->showPlayerUpdate($this);
			}
		}
	}

	public function spawnTo(Player $player){
		if($player instanceof DesktopPlayer){
			if($this !== $player and $this->spawned === true and $player->getLevel() === $this->getLevel() and $player->canSee($this)){
				$this->hasSpawned[$player->getID()] = $player;
				$pk = new SpawnPlayerPacket();
				if($player->getRemoveFormat()){
					$pk->name = TextFormat::clean($this->nameTag);
				}else{
					$pk->name = $this->nameTag;
				}
				$pk->eid = $this->getID();
				$pk->uuid = $this->bigBrother_formatedUUID;
				$pk->x = $this->x;
				$pk->z = $this->y;
				$pk->y = $this->z;
				$pk->yaw = $this->yaw;
				$pk->pitch = $this->pitch;
				$pk->item = $this->inventory->getItemInHand()->getID();
				$pk->metadata = $this->getData();
				$pk->data = $this->bigBrother_properties;
				$player->putRawPacket($pk);

				$pk = new EntityTeleportPacket();
				$pk->eid = $this->getID();
				$pk->x = $this->x;
				$pk->z = $this->y;
				$pk->y = $this->z;
				$pk->yaw = $this->yaw;
				$pk->pitch = $this->pitch;
				$player->putRawPacket($pk);

				$pk = new SetEntityMotionPacket();
				$pk->eid = $this->getID();
				$pk->speedX = $this->motionX;
				$pk->speedY = $this->motionY;
				$pk->speedZ = $this->motionZ;
				$player->dataPacket($pk);

				$this->inventory->sendHeldItem($player);

				$this->inventory->sendArmorContents($player);
			}
		}else{
			parent::spawnTo($player);
		}
	}

	public function bigBrother_authenticate($username, $uuid, $onlineModeData = null){
		if($this->bigBrother_status === 0){
			$this->bigBrother_uuid = $uuid;
			$this->bigBrother_formatedUUID = Binary::UUIDtoString($this->bigBrother_uuid);

			$pk = new LoginSuccessPacket();
			$pk->uuid = $this->bigBrother_formatedUUID;
			$pk->name = $this->username;
			$this->putRawPacket($pk);
			$this->bigBrother_status = 1;
			if($onlineModeData !== null and is_array($onlineModeData)){
				$this->bigBrother_properties = $onlineModeData;
			}

			$this->tasks[] = $this->server->getScheduler()->scheduleDelayedRepeatingTask(new CallbackTask([$this, "bigBrother_sendKeepAlive"]), 180, 2);
			$this->server->getScheduler()->scheduleDelayedTask(new CallbackTask([$this, "bigBrother_authenticationCallback"], [$username]), 1);
		}
	}

	public function bigBrother_processAuthentication(BigBrother $plugin, EncryptionResponsePacket $packet){
		$this->bigBrother_secret = $plugin->decryptBinary($packet->sharedSecret);
		$token = $plugin->decryptBinary($packet->verifyToken);
		$this->interface->enableEncryption($this, $this->bigBrother_secret);
		if($token !== $this->bigBrother_checkToken){
			$this->close("", "Invalid check token");
		}else{
			$task = new AuthenticateOnline($this->clientID, $this->bigBrother_username, Binary::sha1("" . $this->bigBrother_secret . $plugin->getASN1PublicKey()));
			$this->server->getScheduler()->scheduleAsyncTask($task);
		}
	}

	public function bigBrother_authenticationCallback($username){
		$pk = new LoginPacket();
		$pk->username = $username;
		$pk->clientId = crc32($this->clientID);
		$pk->protocol1 = Info::CURRENT_PROTOCOL;
		$pk->protocol2 = Info::CURRENT_PROTOCOL;
		$pk->loginData = "";
		$this->handleDataPacket($pk);
	}

	public function bigBrother_handleAuthentication(BigBrother $plugin, $username, $onlineMode){
		if($this->bigBrother_status === 0){
			$this->bigBrother_username = $username;
			if($onlineMode === true){
				$pk = new EncryptionRequestPacket();
				$pk->serverID = "";
				$pk->publicKey = $plugin->getASN1PublicKey();
				$pk->verifyToken = $this->bigBrother_checkToken = Utils::getRandomBytes(4, false, true, $pk->publicKey);
				$this->putRawPacket($pk);
			}else{
				$task = new OnlineProfile($this->clientID, $this->bigBrother_username);
				$this->server->getScheduler()->scheduleAsyncTask($task);
			}
		}

	}

	public function close($message = "", $reason = "generic reason"){
		if($this->bigBrother_status === 0){
			$pk = new LoginDisconnectPacket();
			$pk->reason = TextFormat::toJSON($reason === "" ? "You have been disconnected." : $reason);
			$this->putRawPacket($pk);
		}else{
			$pk = new PlayDisconnectPacket();
			$pk->reason = TextFormat::toJSON($reason === "" ? "You have been disconnected." : $reason);
			$this->putRawPacket($pk);
		}
		parent::close($message, $reason);
	}

	public function bigBrother_setCompression($threshold){
		$this->interface->setCompression($this, $threshold);
	}

	public function putRawPacket(Packet $packet){
		$this->interface->putRawPacket($this, $packet);
	}
}
