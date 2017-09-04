<?php
/**
 *  ______  __         ______               __    __
 * |   __ \|__|.-----.|   __ \.----..-----.|  |_ |  |--..-----..----.
 * |   __ <|  ||  _  ||   __ <|   _||  _  ||   _||     ||  -__||   _|
 * |______/|__||___  ||______/|__|  |_____||____||__|__||_____||__|
 *             |_____|
 *
 * BigBrother plugin for PocketMine-MP
 * Copyright (C) 2014-2015 shoghicp <https://github.com/shoghicp/BigBrother>
 * Copyright (C) 2016- BigBrotherTeam
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
 *
 * @author BigBrotherTeam
 * @link   https://github.com/BigBrotherTeam/BigBrother
 *
 */

namespace shoghicp\BigBrother;

use pocketmine\Player;
use pocketmine\event\Timings;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\network\mcpe\protocol\ProtocolInfo as Info;
use pocketmine\network\mcpe\protocol\LoginPacket;
use pocketmine\network\mcpe\protocol\RequestChunkRadiusPacket;
use pocketmine\network\mcpe\protocol\ResourcePackClientResponsePacket;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\BatchPacket;
use pocketmine\network\SourceInterface;
use pocketmine\level\Level;
use pocketmine\utils\Utils;
use pocketmine\utils\UUID;
use pocketmine\utils\TextFormat;
use shoghicp\BigBrother\network\Packet;
use shoghicp\BigBrother\network\protocol\Login\EncryptionRequestPacket;
use shoghicp\BigBrother\network\protocol\Login\EncryptionResponsePacket;
use shoghicp\BigBrother\network\protocol\Login\LoginSuccessPacket;
use shoghicp\BigBrother\network\protocol\Play\Server\KeepAlivePacket;
use shoghicp\BigBrother\network\protocol\Play\Server\PlayerPositionAndLookPacket;
use shoghicp\BigBrother\network\protocol\Play\Server\PlayerListPacket;
use shoghicp\BigBrother\network\protocol\Play\Server\TitlePacket;
use shoghicp\BigBrother\network\ProtocolInterface;
use shoghicp\BigBrother\utils\Binary;
use shoghicp\BigBrother\utils\InventoryUtils;

class DesktopPlayer extends Player{

	/** @var int */
	private $bigBrother_status = 0; //0 = log in, 1 = playing
	/** @var string */
	protected $bigBrother_uuid;
	/** @var string */
	protected $bigBrother_formatedUUID;
	/** @var array */
	protected $bigBrother_properties = [];
	/** @var string */
	private $bigBrother_checkToken;
	/** @var string */
	private $bigBrother_secret;
	/** @var string */
	private $bigBrother_username;
	/** @var string */
	private $bigbrother_clientId;
	/** @var int */
	private $bigBrother_dimension;
	/** @var InventoryUtils */
	private $inventoryutils;
	/** @var array */
	protected $Settings = [];
	/** @var ProtocolInterface */
	protected $interface;
	/** @var BigBrother */
	protected $plugin;

	public function __construct(SourceInterface $interface, string $clientID, string $address, int $port, BigBrother $plugin){
		$this->plugin = $plugin;
		$this->bigbrother_clientId = $clientID;
		parent::__construct($interface, $clientID, $address, $port);
		$this->setRemoveFormat(false);// Color Code TODO: remove it?
		$this->inventoryutils = new InventoryUtils($this);
	}

	public function getInventoryUtils() : InventoryUtils{
		return $this->inventoryutils;
	}

	public function dropItemNaturally($item){
		$this->getLevel()->dropItem($this->add(0, 1.3, 0), $item, $this->getDirectionVector()->multiply(0.4), 40);
	}

	public function bigBrother_getDimension() : int{
		return $this->bigBrother_dimension;
	}

	public function bigBrother_getDimensionPEToPC(int $level_dimension) : int{
		switch($level_dimension){
			case 0://Overworld
				$dimension = 0;
			break;
			case 1://Nether
				$dimension = -1;
			break;
			case 2://The End
				$dimension = 1;
			break;
		}
		$this->bigBrother_dimension = $dimension;
		return $dimension;
	}

	public function bigBrother_getStatus() : int{
		return $this->bigBrother_status;
	}

	public function bigBrother_getProperties() : array{
		return $this->bigBrother_properties;
	}

	public function bigBrother_getUniqueId() : string{
		return $this->bigBrother_uuid;
	}

	public function bigBrother_getformatedUUID() : string{
		return $this->bigBrother_formatedUUID;
	}

	public function getSettings() : array{
		return $this->Settings;
	}

	public function getSetting(string $settingname){
		return $this->Settings[$settingname] ?? false;
	}

	public function setSetting(array $settings){
		$this->Settings = array_merge($this->Settings, $settings);
	}

	public function removeSetting(string $settingname){
		if(isset($this->Settings[$settingname])){
			unset($this->Settings[$settingname]);
		}
	}

	public function cleanSetting(string $settingname){
		unset($this->Settings[$settingname]);
	}

	public function bigBrother_respawn(){
		$pk = new PlayerPositionAndLookPacket();
		$pk->x = $this->getX();
		$pk->y = $this->getY();
		$pk->z = $this->getZ();
		$pk->yaw = 0;
		$pk->pitch = 0;
		$pk->flags = 0;
		$this->putRawPacket($pk);

		foreach($this->usedChunks as $index => $d){//reset chunks
			Level::getXZ($index, $x, $z);

			foreach($this->level->getChunkEntities($x, $z) as $entity){
				if($entity !== $this){
					$entity->despawnFrom($this);
				}
			}

			unset($this->usedChunks[$index]);
			$this->level->unregisterChunkLoader($this, $x, $z);
			unset($this->loadQueue[$index]);
		}

		$this->usedChunks = [];
	}

	public function bigBrother_authenticate(string $uuid, array $onlineModeData = null){
		if($this->bigBrother_status === 0){
			$this->bigBrother_uuid = $uuid;
			$this->bigBrother_formatedUUID = Binary::UUIDtoString($this->bigBrother_uuid);

			$this->interface->setCompression($this);

			$pk = new LoginSuccessPacket();
			$pk->uuid = $this->bigBrother_formatedUUID;
			$pk->name = $this->bigBrother_username;
			$this->putRawPacket($pk);

			$this->bigBrother_status = 1;

			if($onlineModeData !== null){
				$this->bigBrother_properties = $onlineModeData;
			}

			$skin = false;
			foreach($this->bigBrother_properties as $property){
				if($property["name"] === "textures"){
					$skindata = json_decode(base64_decode($property["value"]), true);
					if(isset($skindata["textures"]["SKIN"]["url"])){
						$skin = $this->getSkinImage($skindata["textures"]["SKIN"]["url"]);
					}
				}
			}

			$pk = new LoginPacket();
			$pk->username = $this->bigBrother_username;
			$pk->protocol = Info::CURRENT_PROTOCOL;
			$pk->clientUUID = $this->bigBrother_formatedUUID;
			$pk->clientId = crc32($this->bigbrother_clientId);
			$pk->serverAddress = "127.0.0.1:25565";
			if($skin === null or $skin === false){
				if($this->plugin->getConfig()->get("skin-slim")){
					$pk->skinId = "Standard_Custom";
				}else{
					$pk->skinId = "Standard_CustomSlim";
				}
				$pk->skin = file_get_contents($this->plugin->getDataFolder().$this->plugin->getConfig()->get("skin-yml"));
			}else{
				if(!isset($skindata["textures"]["SKIN"]["metadata"]["model"])){
					$pk->skinId = "Standard_Custom";
				}else{
					$pk->skinId = "Standard_CustomSlim";
				}
				$pk->skin = $skin;
			}
			$this->handleDataPacket($pk);

			BigBrother::addPlayerList($this);

			$pk = new RequestChunkRadiusPacket();//for PocketMine-MP
			$pk->radius = 8;
			$this->handleDataPacket($pk);

			$pk = new ResourcePackClientResponsePacket();
			$pk->status = ResourcePackClientResponsePacket::STATUS_COMPLETED;
			$this->handleDataPacket($pk);

			$pk = new KeepAlivePacket();
			$pk->id = mt_rand();
			$this->putRawPacket($pk);

			$pk = new PlayerListPacket();
			$pk->actionID = PlayerListPacket::TYPE_ADD;
			$pk->players[] = [
				UUID::fromString($this->bigBrother_formatedUUID)->toBinary(),
				$this->bigBrother_username,
				$this->bigBrother_properties,
				$this->getGamemode(),
				0,
				true,
				BigBrother::toJSON($this->bigBrother_username)
			];
			$this->putRawPacket($pk);

			$playerlist = [];
			$playerlist[UUID::fromString($this->bigBrother_formatedUUID)->toString()] = $this->bigBrother_username;
			$this->setSetting(["PlayerList" => $playerlist]);

			$pk = new TitlePacket(); //for Set SubTitle
			$pk->actionID = TitlePacket::TYPE_SET_TITLE;
			$pk->data = TextFormat::toJSON("");
			$this->putRawPacket($pk);

			$pk = new TitlePacket();
			$pk->actionID = TitlePacket::TYPE_SET_SUB_TITLE;
			$pk->data = TextFormat::toJSON(TextFormat::YELLOW . TextFormat::BOLD . "This is a beta version of BigBrother.");
			$this->putRawPacket($pk);
		}
	}

	public function bigBrother_processAuthentication(BigBrother $plugin, EncryptionResponsePacket $packet){
		$this->bigBrother_secret = $plugin->decryptBinary($packet->sharedSecret);
		$token = $plugin->decryptBinary($packet->verifyToken);
		$this->interface->enableEncryption($this, $this->bigBrother_secret);
		if($token !== $this->bigBrother_checkToken){
			$this->close("", "Invalid check token");
		}else{
			$this->getAuthenticateOnline($this->bigBrother_username, Binary::sha1("".$this->bigBrother_secret.$plugin->getASN1PublicKey()));
		}
	}

	public function bigBrother_handleAuthentication(BigBrother $plugin, string $username, bool $onlineMode = false){
		if($this->bigBrother_status === 0){
			$this->bigBrother_username = $username;
			if($onlineMode){
				$pk = new EncryptionRequestPacket();
				$pk->serverID = "";
				$pk->publicKey = $plugin->getASN1PublicKey();
				$pk->verifyToken = $this->bigBrother_checkToken = str_repeat("\x00", 4);//for PocketMine-MP  Random Bytes :(
				$this->putRawPacket($pk);
			}else{
				$info = $this->getProfile($username);
				if(is_array($info)){
					$this->bigBrother_authenticate($info["id"], $info["properties"]);
				}
			}
		}
	}

	/**
	 * @param string $username
	 * @return array|bool|null
	 */
	public function getProfile(string $username){
		$profile = json_decode(Utils::getURL("https://api.mojang.com/users/profiles/minecraft/".$username), true);
		if(!is_array($profile)){
			return false;
		}

		$uuid = $profile["id"];
		$info = json_decode(Utils::getURL("https://sessionserver.mojang.com/session/minecraft/profile/".$uuid."", 3), true);
		if(!isset($info["id"])){
			return false;
		}
		return $info;
	}

	public function getAuthenticateOnline(string $username, string $hash){
		$result = json_decode(Utils::getURL("https://sessionserver.mojang.com/session/minecraft/hasJoined?username=".$username."&serverId=".$hash, 5), true);
		if(is_array($result) and isset($result["id"])){
			$this->bigBrother_authenticate($result["id"], $result["properties"]);
		}else{
			$this->close("", "User not premium");
		}
	}

	/**
	 * @param string $url
	 * @return string|bool|null
	 */
	public function getSkinImage(string $url){
		if(extension_loaded("gd")){
			$image = imagecreatefrompng($url);

			if($image !== false){
				$width = imagesx($image);
				$height = imagesy($image);
				$colors = [];
				for($y = 0; $y < $height; $y++){
					$y_array = [];
					for($x = 0; $x < $width; $x++){
						$rgb = imagecolorat($image, $x, $y);
						$r = ($rgb >> 16) & 0xFF;
						$g = ($rgb >> 8) & 0xFF;
						$b = $rgb & 0xFF;
						$alpha = imagecolorsforindex($image, $rgb)["alpha"];
						$x_array = [$r, $g, $b, $alpha];
						$y_array[] = $x_array;
					}
					$colors[] = $y_array;
				}
				$skin = null;
				foreach($colors as $width){
					foreach($width as $height){
						$alpha = 0;
						if($height[0] === 255 and $height[1] === 255 and $height[2] === 255){
							$height[0] = 0;
							$height[1] = 0;
							$height[2] = 0;
							if($height[3] === 127){
								$alpha = 255;
							}else{
								$alpha = 0;
							}
						}else{
							if($height[3] === 127){
								$alpha = 0;
							}else{
								$alpha = 255;
							}
						}
						$skin = $skin.chr($height[0]).chr($height[1]).chr($height[2]).chr($alpha);
					}
				}
				imagedestroy($image);
				return $skin;
			}
		}
		return false;
	}

	/**
	 * @override
	 */
	public function handleDataPacket(DataPacket $packet){
		if($this->connected === false){
			return;
		}

		$timings = Timings::getReceiveDataPacketTimings($packet);
		$timings->startTiming();

		$this->getServer()->getPluginManager()->callEvent($ev = new DataPacketReceiveEvent($this, $packet));
		if(!$ev->isCancelled() and !$packet->handle($this->sessionAdapter)){
			$this->getServer()->getLogger()->debug("Unhandled " . $packet->getName() . " received from " . $this->getName() . ": 0x" . bin2hex($packet->buffer));
		}

		$timings->stopTiming();
	}

	public function putRawPacket(Packet $packet){
		$this->interface->putRawPacket($this, $packet);
	}
}
