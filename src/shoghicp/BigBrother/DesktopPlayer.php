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

declare(strict_types=1);

namespace shoghicp\BigBrother;

use pocketmine\Player;
use pocketmine\event\Timings;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\item\Item;
use pocketmine\inventory\CraftingGrid;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\types\WindowTypes;
use pocketmine\network\mcpe\protocol\ContainerOpenPacket;
use pocketmine\network\mcpe\protocol\ProtocolInfo as Info;
use pocketmine\network\mcpe\protocol\LoginPacket;
use pocketmine\network\mcpe\protocol\RequestChunkRadiusPacket;
use pocketmine\network\mcpe\protocol\ResourcePackClientResponsePacket;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\BatchPacket;
use pocketmine\network\SourceInterface;
use pocketmine\level\Level;
use pocketmine\level\format\Chunk;
use pocketmine\utils\Utils;
use pocketmine\utils\TextFormat;
use shoghicp\BigBrother\network\Packet;
use shoghicp\BigBrother\network\protocol\Login\EncryptionRequestPacket;
use shoghicp\BigBrother\network\protocol\Login\EncryptionResponsePacket;
use shoghicp\BigBrother\network\protocol\Login\LoginSuccessPacket;
use shoghicp\BigBrother\network\protocol\Play\Server\AdvancementsPacket;
use shoghicp\BigBrother\network\protocol\Play\Server\KeepAlivePacket;
use shoghicp\BigBrother\network\protocol\Play\Server\PlayerPositionAndLookPacket;
use shoghicp\BigBrother\network\protocol\Play\Server\TitlePacket;
use shoghicp\BigBrother\network\protocol\Play\Server\SelectAdvancementTabPacket;
use shoghicp\BigBrother\network\protocol\Play\Server\UnloadChunkPacket;
use shoghicp\BigBrother\network\protocol\Play\Server\UnlockRecipesPacket;
use shoghicp\BigBrother\network\ProtocolInterface;
use shoghicp\BigBrother\entity\ItemFrameBlockEntity;
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
	private $bigBrother_dimension = 0;
	/** @var string[] */
	private $bigBrother_entitylist = [];
	/** @var InventoryUtils */
	private $inventoryutils;
	/** @var array */
	private $bigBrother_clientSetting = [];
	/** @var array */
	private $bigBrother_pluginMessageList = [];
	/** @var array */
	private $bigBrother_breakPosition = [];

	/** @var ProtocolInterface */
	protected $interface;
	/** @var BigBrother */
	protected $plugin;

	/**
	 * @param SourceInterface $interface
	 * @param string          $clientID
	 * @param string          $address
	 * @param int             $port
	 * @param BigBrother      $plugin
	 */
	public function __construct(SourceInterface $interface, string $clientID, string $address, int $port, BigBrother $plugin){
		$this->plugin = $plugin;
		$this->bigbrother_clientId = $clientID;
		parent::__construct($interface, $clientID, $address, $port);

		$this->bigBrother_breakPosition = [new Vector3(0, 0, 0), 0];
		$this->inventoryutils = new InventoryUtils($this);
	}

	/**
	 * @return InventoryUtils
	 */
	public function getInventoryUtils() : InventoryUtils{
		return $this->inventoryutils;
	}

	/**
	 * @return int dimension
	 */
	public function bigBrother_getDimension() : int{
		return $this->bigBrother_dimension;
	}

	/**
	 * @param int $level_dimension
	 * @return int dimension of pc version converted from $level_dimension
	 */
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

	/**
	 * @param int    $eid
	 * @param string $entitytype
	 */
	public function bigBrother_addEntityList(int $eid, string $entitytype) : void{
		if(!isset($this->bigBrother_entitylist[$eid])){
			$this->bigBrother_entitylist[$eid] = $entitytype;
		}
	}

	/**
	 * @param int $eid
	 * @return string
	 */
	public function bigBrother_getEntityList(int $eid) : string{
		if(isset($this->bigBrother_entitylist[$eid])){
			return $this->bigBrother_entitylist[$eid];
		}
		return "generic";
	}

	/**
	 * @param int $eid
	 */
	public function bigBrother_removeEntityList(int $eid) : void{
		if(isset($this->bigBrother_entitylist[$eid])){
			unset($this->bigBrother_entitylist[$eid]);
		}
	}

	/**
	 * @return array
	 */
	public function bigBrother_getClientSetting() : array{
		return $this->bigBrother_clientSetting;
	}

	/**
	 * @param array $clientSetting
	 */
	public function bigBrother_setClientSetting(array $clientSetting = []) : void{
		$this->bigBrother_clientSetting = $clientSetting;
	}

	/**
	 * @return array
	 */
	public function bigBrother_getPluginMessageList() : array{
		return $this->bigBrother_pluginMessageList;
	}

	/**
	 * @param string $channel
	 * @param array  $data
	 */
	public function bigBrother_setPluginMessageList(string $channel = "", array $data = []) : void{
		$this->bigBrother_pluginMessageList[$channel] = $data;
	}

	/**
	 * @return array
	 */
	public function bigBrother_getBreakPosition() : array{
		return $this->bigBrother_breakPosition;
	}

	/**
	 * @param array $positionData
	 */
	public function bigBrother_setBreakPosition(array $positionData = []) : void{
		$this->bigBrother_breakPosition = $positionData;
	}

	/**
	 * @return int status
	 */
	public function bigBrother_getStatus() : int{
		return $this->bigBrother_status;
	}

	/**
	 * @return array properties
	 */
	public function bigBrother_getProperties() : array{
		return $this->bigBrother_properties;
	}

	/**
	 * @return string uuid
	 */
	public function bigBrother_getUniqueId() : string{
		return $this->bigBrother_uuid;
	}

	/**
	 * @return string formatted uuid
	 */
	public function bigBrother_getformatedUUID() : string{
		return $this->bigBrother_formatedUUID;
	}

	/**
	 * @param bool $first
	 */
	public function sendAdvancements(bool $first = false) : void{
		$pk = new AdvancementsPacket();
		$pk->advancements = [
			[
				"pocketmine:advancements/root",
				[
					false
				],
				[
					true,
					BigBrother::toJSON("Welcome to PocketMine-MP Server!"),
					BigBrother::toJSON("Join to PocketMine-MP Server with Minecraft"),
					Item::get(Item::GRASS),
					0,
					[
						1,
						"minecraft:textures/blocks/stone.png"
					],
					0,
					0
				],
				[
					["hasjoined"],
				],
				[
					[
						"hasjoined"
					]
				]
			]
		];
		$pk->identifiers = [];
		$pk->progress = [
			[
				"pocketmine:advancements/root",
				[
					[
						"hasjoined",
						[
							true,
							time()
						]
					]
				]
			]
		];
		$this->putRawPacket($pk);

		if($first){
			$pk = new SelectAdvancementTabPacket();
			$pk->hasTab = true;
			$pk->tabId = "pocketmine:advancements/root";
			$this->putRawPacket($pk);
		}
	}

	/**
	 * @param CraftingGrid $grid
	 * @override
	 */
	public function setCraftingGrid(CraftingGrid $grid) : void{
		parent::setCraftingGrid($grid);

		if($grid->getDefaultSize() === 9){//Open Crafting Table
			$pk = new ContainerOpenPacket();
			$pk->windowId = 255;//Max WindowId
			$pk->type = WindowTypes::WORKBENCH;
			$pk->x = 0;
			$pk->y = 0;
			$pk->z = 0;

			$this->dataPacket($pk);
		}
	}

	/**
	 * @param int $chunkX
	 * @param int $chunkZ
	 * @param BatchPacket $payload
	 * @override
	 */
	public function sendChunk(int $chunkX, int $chunkZ, BatchPacket $payload){
		parent::sendChunk($chunkX, $chunkZ, $payload);
		foreach($this->usedChunks as $index => $c){
			Level::getXZ($index, $chunkX, $chunkZ);
			foreach(ItemFrameBlockEntity::getItemFramesInChunk($this->level, $chunkX, $chunkZ) as $frame){
				$frame->spawnTo($this);
			}
		}
	}

	/**
	 * @param Level $targetLevel
	 * @return bool
	 * @override
	 */
	protected function switchLevel(Level $targetLevel) : bool{
		$oldLevel = $this->level;
		$indexes = array_keys($this->usedChunks);
		if($retval = parent::switchLevel($targetLevel)){
			foreach($indexes as $index){
				Level::getXZ($index, $chunkX, $chunkZ);
				$this->__unloadChunk($chunkX, $chunkZ, $oldLevel);
			}
		}
		return $retval;
	}

	/**
	 * @override
	 */
	protected function orderChunks(){
		$indexes = array_keys($this->usedChunks);
		if($retval = parent::orderChunks()){
			foreach(array_diff($indexes, array_keys($this->usedChunks)) as $index){
				Level::getXZ($index, $chunkX, $chunkZ);
				$this->__unloadChunk($chunkX, $chunkZ);
			}
		}
		return $retval;
	}

	/**
	 * @param int   $chunkX
	 * @param int   $chunkZ
	 * @param Level $oldLevel
	 */
	private function __unloadChunk(int $chunkX, int $chunkZ, Level $oldLevel=null){
		$pk = new UnloadChunkPacket();
		$pk->chunkX = $chunkX;
		$pk->chunkZ = $chunkZ;
		$this->putRawPacket($pk);

		foreach(ItemFrameBlockEntity::getItemFramesInChunk($oldlevel ?? $this->level, $chunkX, $chunkZ) as $frame){
			$frame->despawnFrom($this);
		}
	}

	/**
	 * @param Chunk $chunk
	 * @override
	 */
	public function onChunkUnloaded(Chunk $chunk){
		foreach(ItemFrameBlockEntity::getItemFramesInChunk($this->level, $chunk->getX(), $chunk->getZ()) as $frame){
			$frame->despawnFromAll();
		}
	}

	/**
	 * @override
	 */
	public function onVerifyCompleted(LoginPacket $packet, bool $isValid, bool $isAuthenticated) : void{
		parent::onVerifyCompleted($packet, true, true);

		$pk = new ResourcePackClientResponsePacket();
		$pk->status = ResourcePackClientResponsePacket::STATUS_COMPLETED;
		$this->handleDataPacket($pk);

		$pk = new RequestChunkRadiusPacket();
		$pk->radius = 8;
		$this->handleDataPacket($pk);

		$pk = new KeepAlivePacket();
		$pk->id = mt_rand();
		$this->putRawPacket($pk);

		$pk = new TitlePacket(); //for Set SubTitle
		$pk->actionID = TitlePacket::TYPE_SET_TITLE;
		$pk->data = TextFormat::toJSON("");
		$this->putRawPacket($pk);

		$pk = new TitlePacket();
		$pk->actionID = TitlePacket::TYPE_SET_SUB_TITLE;
		$pk->data = TextFormat::toJSON(TextFormat::YELLOW . TextFormat::BOLD . "This is a beta version of BigBrother.");
		$this->putRawPacket($pk);

		$this->sendAdvancements(true);

		/*$pk = new UnlockRecipesPacket();
		$pk->actionID = 0;
		$pk->recipes[] = 163;
		$pk->recipes[] = 438;
		$pk->recipes[] = 424;
		$pk->extraRecipes[] = 0;
		$this->putRawPacket($pk);*/
	}

	public function bigBrother_respawn() : void{
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

			$this->__unloadChunk($x, $z);
		}

		$this->usedChunks = [];
	}

	/**
	 * @param string     $uuid
	 * @param array|null $onlineModeData
	 */
	public function bigBrother_authenticate(string $uuid, ?array $onlineModeData = null) : void{
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

			$skin = "";
			$skindata = null;
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
			$pk->clientData["SkinGeometryName"] = "";//TODO
			$pk->clientData["SkinGeometry"] = "";//TODO
			$pk->clientData["CapeData"] = "";//TODO
			if($skin === ""){
				if($this->plugin->getConfig()->get("skin-slim")){
					$pk->clientData["SkinId"] = "Standard_Custom";
				}else{
					$pk->clientData["SkinId"] = "Standard_CustomSlim";
				}
				$pk->clientData["SkinData"] = base64_encode(file_get_contents($this->plugin->getDataFolder().$this->plugin->getConfig()->get("skin-yml")));
			}else{
				if($skindata !== null && !isset($skindata["textures"]["SKIN"]["metadata"]["model"])){
					$pk->clientData["SkinId"] = "Standard_Custom";
				}else{
					$pk->clientData["SkinId"] = "Standard_CustomSlim";
				}
				$pk->clientData["SkinData"] = base64_encode($skin);
			}
			$pk->chainData = ["chain" => []];
			$pk->clientDataJwt = "eyJ4NXUiOiJNSFl3RUFZSEtvWkl6ajBDQVFZRks0RUVBQ0lEWWdBRThFTGtpeHlMY3dsWnJ5VVFjdTFUdlBPbUkyQjd2WDgzbmRuV1JVYVhtNzR3RmZhNWZcL2x3UU5UZnJMVkhhMlBtZW5wR0k2SmhJTVVKYVdacmptTWo5ME5vS05GU05CdUtkbThyWWlYc2ZhejNLMzZ4XC8xVTI2SHBHMFp4S1wvVjFWIn0.W10.QUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFB";
			$this->handleDataPacket($pk);
		}
	}

	/**
	 * @param BigBrother $plugin
	 * @param EncryptionResponsePacket $packet
	 */
	public function bigBrother_processAuthentication(BigBrother $plugin, EncryptionResponsePacket $packet) : void{
		$this->bigBrother_secret = $plugin->decryptBinary($packet->sharedSecret);
		$token = $plugin->decryptBinary($packet->verifyToken);
		$this->interface->enableEncryption($this, $this->bigBrother_secret);
		if($token !== $this->bigBrother_checkToken){
			$this->close("", "Invalid check token");
		}else{
			$this->getAuthenticateOnline($this->bigBrother_username, Binary::sha1("".$this->bigBrother_secret.$plugin->getASN1PublicKey()));
		}
	}

	/**
	 * @param BigBrother $plugin
	 * @param string $username
	 * @param bool $onlineMode
	 */
	public function bigBrother_handleAuthentication(BigBrother $plugin, string $username, bool $onlineMode = false) : void{
		if($this->bigBrother_status === 0){
			$this->bigBrother_username = $username;
			if($onlineMode){
				$pk = new EncryptionRequestPacket();
				$pk->serverID = "";
				$pk->publicKey = $plugin->getASN1PublicKey();
				$pk->verifyToken = $this->bigBrother_checkToken = str_repeat("\x00", 4);
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
	 * @return array|bool profile data if success else false
	 */
	public function getProfile(string $username){
		$profile = null;
		$info = null;

		$response = Utils::getURL("https://api.mojang.com/users/profiles/minecraft/".$username);
		if($response !== false){
			$profile = json_decode($response, true);
		}

		if(!is_array($profile)){
			return false;
		}

		$uuid = $profile["id"];
		$response = Utils::getURL("https://sessionserver.mojang.com/session/minecraft/profile/".$uuid, 3);
		if($response !== false){
			$info = json_decode($response, true);
		}

		if($info === null or !isset($info["id"])){
			return false;
		}

		return $info;
	}

	/**
	 * @param string $username
	 * @param string $hash
	 */
	public function getAuthenticateOnline(string $username, string $hash) : void{
		$result = null;

		$response = Utils::getURL("https://sessionserver.mojang.com/session/minecraft/hasJoined?username=".$username."&serverId=".$hash, 5);
		if($response !== false){
			$result = json_decode($response, true);
		}

		if(is_array($result) and isset($result["id"])){
			$this->bigBrother_authenticate($result["id"], $result["properties"]);
		}else{
			$this->close("", "User not premium");
		}
	}

	/**
	 * @param string $url
	 * @return string sking image
	 */
	public function getSkinImage(string $url) : string{
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
				$skin = "";
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
		return "";
	}

	/**
	 * @param DataPacket $packet
	 * @override
	 */
	public function handleDataPacket(DataPacket $packet){
		if($this->isConnected() === false){
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

	/**
	 * @param Packet $packet
	 */
	public function putRawPacket(Packet $packet) : void{
		$this->interface->putRawPacket($this, $packet);
	}
}
