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
use pocketmine\Server;
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
use pocketmine\network\mcpe\protocol\SetLocalPlayerAsInitializedPacket;
use pocketmine\network\SourceInterface;
use pocketmine\scheduler\AsyncTask;
use pocketmine\level\Level;
use pocketmine\level\format\Chunk;
use pocketmine\timings\Timings;
use pocketmine\utils\Internet;
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
use shoghicp\BigBrother\network\ProtocolInterface;
use shoghicp\BigBrother\entity\ItemFrameBlockEntity;
use shoghicp\BigBrother\utils\Binary;
use shoghicp\BigBrother\utils\CapeUtils;
use shoghicp\BigBrother\utils\InventoryUtils;
use shoghicp\BigBrother\utils\RecipeUtils;
use shoghicp\BigBrother\utils\SkinUtils;

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
	private $inventoryUtils;
	/** @var RecipeUtils */
	private $recipeUtils;
	/** @var array */
	private $bigBrother_clientSetting = [];
	/** @var array */
	private $bigBrother_pluginMessageList = [];
	/** @var array */
	private $bigBrother_breakPosition = [];
	/** @var array */
	private $bigBrother_bossBarData = [
		"entityRuntimeId" => -1,
		"uuid" => "",
		"nameTag" => ""
	];

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
		parent::__construct($interface, $address, $port);

		$this->bigBrother_breakPosition = [new Vector3(0, 0, 0), 0];
		$this->inventoryUtils = new InventoryUtils($this);
		$this->recipeUtils = new RecipeUtils($this);
	}

	/**
	 * @return InventoryUtils
	 */
	public function getInventoryUtils() : InventoryUtils{
		return $this->inventoryUtils;
	}

	/**
	 * @return RecipeUtils
	 */
	public function getRecipeUtils() : RecipeUtils{
		return $this->recipeUtils;
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
		$dimension = 0;
		switch($level_dimension){
			case 0://Over world
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
	 * @param  string $bossBarData
	 * @return string|array
	 */
	public function bigBrother_getBossBarData(string $bossBarData = ""){
		if($bossBarData === ""){
			return $this->bigBrother_bossBarData;
		}
		return $this->bigBrother_bossBarData[$bossBarData];
	}

	/**
	 * @param string $bossBarData
	 * @param string|array $data
	 */
	public function bigBrother_setBossBarData(string $bossBarData, $data) : void{
		$this->bigBrother_bossBarData[$bossBarData] = $data;
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
			$pk->windowId = 127;//Max WindowId
			$pk->type = WindowTypes::WORKBENCH;
			$pk->x = 0;
			$pk->y = 0;
			$pk->z = 0;

			$this->dataPacket($pk);
		}
	}

	public function setLocale(string $locale) : void{
		$this->locale = $locale;
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
				/** @var ItemFrameBlockEntity $frame */
				$frame->spawnTo($this);
			}
		}
	}

	/**
	 * @param int   $chunkX
	 * @param int   $chunkZ
	 * @param Level $level
	 * @override
	 */
	protected function unloadChunk(int $chunkX, int $chunkZ, Level $level = null){
		parent::unloadChunk($chunkX, $chunkZ, $level);

		$pk = new UnloadChunkPacket();
		$pk->chunkX = $chunkX;
		$pk->chunkZ = $chunkZ;
		$this->putRawPacket($pk);

		foreach(ItemFrameBlockEntity::getItemFramesInChunk($level ?? $this->level, $chunkX, $chunkZ) as $frame){
			/** @var ItemFrameBlockEntity $frame */
			$frame->despawnFrom($this);
		}
	}

	/**
	 * @param Chunk $chunk
	 * @override
	 */
	public function onChunkUnloaded(Chunk $chunk){
		if($this->loggedIn){
			foreach(ItemFrameBlockEntity::getItemFramesInChunk($this->level, $chunk->getX(), $chunk->getZ()) as $frame){
				/** @var ItemFrameBlockEntity $frame */
				$frame->despawnFromAll();
			}
		}
	}

	/**
	 * @param LoginPacket $packet
	 * @param string $error
	 * @param bool $signedByMojang
	 * @override
	 */
	public function onVerifyCompleted(LoginPacket $packet, ?string $error, bool $signedByMojang) : void{
		parent::onVerifyCompleted($packet, null, true);

		$pk = new ResourcePackClientResponsePacket();
		$pk->status = ResourcePackClientResponsePacket::STATUS_COMPLETED;
		$this->handleDataPacket($pk);

		$pk = new RequestChunkRadiusPacket();
		$pk->radius = 8;
		$this->handleDataPacket($pk);
		
		$pk = new SetLocalPlayerAsInitializedPacket();
		$pk->entityRuntimeId = $this->getId();
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
			Level::getXZ($index, $chunkX, $chunkZ);
			$this->unloadChunk($chunkX, $chunkZ);
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

			$model = false;
			$skinImage = "";
			$capeImage = "";
			foreach($this->bigBrother_properties as $property){
				if($property["name"] === "textures"){
					$textures = json_decode(base64_decode($property["value"]), true);

					if(isset($textures["textures"]["SKIN"])){
						if(isset($textures["textures"]["SKIN"]["metadata"]["model"])){
							$model = true;
						}

						$skinImage = file_get_contents($textures["textures"]["SKIN"]["url"]);
					}

					if(isset($textures["textures"]["CAPE"])){
						$capeImage = file_get_contents($textures["textures"]["CAPE"]["url"]);
					}
				}
			}

			$pk = new LoginPacket();
			$pk->username = $this->bigBrother_username;
			$pk->protocol = Info::CURRENT_PROTOCOL;
			$pk->clientUUID = $this->bigBrother_formatedUUID;
			$pk->clientId = crc32($this->bigbrother_clientId);
			$pk->xuid = str_repeat("0", 16);
			$pk->serverAddress = "127.0.0.1:25565";
			$pk->locale = "en_US";
			$pk->skipVerification = true;
			$pk->clientData["SkinGeometry"] = "";//TODO

			if($model){
				$pk->clientData["SkinId"] = $this->bigBrother_formatedUUID."_CustomSlim";
				$pk->clientData["SkinGeometryName"] = "geometry.humanoid.customSlim";
			}else{
				$pk->clientData["SkinId"] = $this->bigBrother_formatedUUID."_Custom";
				$pk->clientData["SkinGeometryName"] = "geometry.humanoid.custom";
			}

			$skin = new SkinUtils($skinImage);
			$pk->clientData["SkinData"] = $skin->getSkinData();

			$cape = new CapeUtils($capeImage);
			$pk->clientData["CapeData"] = $cape->getCapeData();

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
			$username = $this->bigBrother_username;
			$hash = Binary::sha1("".$this->bigBrother_secret.$plugin->getASN1PublicKey());

			$this->getServer()->getAsyncPool()->submitTask(new class($this, $username, $hash) extends AsyncTask{

				/** @var string */
				private $username;
				/** @var string */
				private $hash;

				/**
				 * @param DesktopPlayer $player
				 * @param string $username
				 * @param string $hash
				 */
				public function __construct(DesktopPlayer $player, string $username, string $hash){
					self::storeLocal($player);
					$this->username = $username;
					$this->hash = $hash;
				}

				/**
				 * @override
				 */
				public function onRun(){
					$result = null;

					$response = Internet::getURL("https://sessionserver.mojang.com/session/minecraft/hasJoined?username=".$this->username."&serverId=".$this->hash, 5);
					if($response !== false){
						$result = json_decode($response, true);
					}

					$this->setResult($result);
				}

				/**
				 * @override
				 * @param $server
				 */
				public function onCompletion(Server $server){
					$result = $this->getResult();
					/** @var DesktopPlayer $player */
					$player = self::fetchLocal();
					if(is_array($result) and isset($result["id"])){
						$player->bigBrother_authenticate($result["id"], $result["properties"]);
					}else{
						$player->close("", "User not premium");
					}
				}
			});
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
				$this->getServer()->getAsyncPool()->submitTask(new class($this, $username) extends AsyncTask{

					/** @var string */
					private $username;

					/**
					 * @param DesktopPlayer $player
					 * @param string $username
					 */
					public function __construct(DesktopPlayer $player, string $username){
						self::storeLocal($player);
						$this->username = $username;
					}

					/**
					 * @override
					 */
					public function onRun(){
						$profile = null;
						$info = null;

						$response = Internet::getURL("https://api.mojang.com/users/profiles/minecraft/".$this->username);
						if($response !== false){
							$profile = json_decode($response, true);
						}

						if(!is_array($profile)){
							$this->setResult(false);
							return;
						}

						$uuid = $profile["id"];
						$response = Internet::getURL("https://sessionserver.mojang.com/session/minecraft/profile/".$uuid, 3);
						if($response !== false){
							$info = json_decode($response, true);
						}

						if($info === null or !isset($info["id"])){
							$this->setResult(false);
							return;
						}

						$this->setResult($info);
					}

					/**
					 * @override
					 * @param Server $server
					 */
					public function onCompletion(Server $server){
						$info = $this->getResult();
						if(is_array($info)){
							/** @var DesktopPlayer $player */
							$player = self::fetchLocal();
							$player->bigBrother_authenticate($info["id"], $info["properties"]);
						}
					}

				});
			}
		}
	}

	/**
	 * @param DataPacket $packet
	 * @override
	 * @throws
	 */
	public function handleDataPacket(DataPacket $packet){
		if($this->isConnected() === false){
			return;
		}

		$timings = Timings::getReceiveDataPacketTimings($packet);
		$timings->startTiming();

		$ev = new DataPacketReceiveEvent($this, $packet);
		$ev->call();
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
