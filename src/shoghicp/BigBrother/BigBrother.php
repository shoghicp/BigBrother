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

use pocketmine\plugin\PluginBase;

use phpseclib\Crypt\RSA;
use pocketmine\network\protocol\Info;
use pocketmine\network\protocol\PlayerActionPacket;
use shoghicp\BigBrother\network\Info as MCInfo;
use shoghicp\BigBrother\network\ProtocolInterface;
use shoghicp\BigBrother\network\translation\Translator;
use shoghicp\BigBrother\network\translation\Translator_101;
use shoghicp\BigBrother\network\protocol\Play\RespawnPacket;
use shoghicp\BigBrother\network\protocol\Play\ResourcePackSendPacket;
use pocketmine\utils\TextFormat;
use pocketmine\block\Block;
use pocketmine\math\Vector3;
use pocketmine\tile\Sign;
use pocketmine\Achievement;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerRespawnEvent;

class BigBrother extends PluginBase implements Listener{

	/** @var ProtocolInterface */
	private $interface;

	/** @var RSA */
	protected $rsa;

	protected $privateKey;

	protected $publicKey;

	protected $onlineMode;

	/** @var Translator */
	protected $translator;

	public function onEnable(){
		$this->saveDefaultConfig();
		$this->saveResource("server-icon.png", false);
		$this->saveResource("steve.yml", false);
		$this->saveResource("alex.yml", false);
		$this->reloadConfig();

		$this->onlineMode = (bool) $this->getConfig()->get("online-mode");
		if($this->onlineMode and !function_exists("mcrypt_generic_init")){
			$this->onlineMode = false;
			$this->getLogger()->notice("no mcrypt detected, online-mode has been disabled. Try using the latest PHP binaries");
		}

		if(!$this->getConfig()->exists("motd")){
			$this->getLogger()->warning("No motd has been set. The server description will be empty.");
			return;
		}

		if(Info::CURRENT_PROTOCOL === 101){
			$this->translator = new Translator_101();

			$this->rsa = new RSA();

			$this->getServer()->getPluginManager()->registerEvents($this, $this);

			Achievement::add("openInventory", "Taking Inventory"); //this for DesktopPlayer

			if($this->onlineMode){
				$this->getLogger()->info("Server is being started in the background");
				$this->getLogger()->info("Generating keypair");
				$this->rsa->setPrivateKeyFormat(CRYPT_RSA_PRIVATE_FORMAT_PKCS1);
				$this->rsa->setPublicKeyFormat(CRYPT_RSA_PUBLIC_FORMAT_PKCS1);
				$keys = $this->rsa->createKey(1024);
				$this->privateKey = $keys["privatekey"];
				$this->publicKey = $keys["publickey"];
				$this->rsa->setEncryptionMode(CRYPT_RSA_ENCRYPTION_PKCS1);
				$this->rsa->loadKey($this->privateKey);
			}
			
			$this->getLogger()->info("Starting Minecraft: PC server on ".($this->getIp() === "0.0.0.0" ? "*" : $this->getIp()).":".$this->getPort()." version ".MCInfo::VERSION);

			$disable = true;
			foreach($this->getServer()->getNetwork()->getInterfaces() as $interface){
				if($interface instanceof ProtocolInterface){
					$disable = false;
				}
			}
			if($disable){
				$this->interface = new ProtocolInterface($this, $this->getServer(), $this->translator, $this->getConfig()->get("network-compression-threshold"));
				$this->getServer()->getNetwork()->registerInterface($this->interface);
			}
		}else{
			$this->getLogger()->critical("Couldn't find a protocol translator for #".Info::CURRENT_PROTOCOL .", disabling plugin");
			$this->getPluginLoader()->disablePlugin($this);
		}
	}

	public function getIp(){
		return $this->getConfig()->get("interface");
	}

	public function getPort(){
		return (int) $this->getConfig()->get("port");
	}

	public function getMotd(){
		return (string) $this->getConfig()->get("motd");
	}

	public function getResourcePackURL(){
		return (string) $this->getConfig()->get("resourcepackurl");
	}

	/**
	 * @return bool
	 */
	public function isOnlineMode(){
		return $this->onlineMode;
	}

	public function getASN1PublicKey(){
		$key = explode("\n", $this->publicKey);
		array_pop($key);
		array_shift($key);
		return base64_decode(implode(array_map("trim", $key)));
	}

	public function decryptBinary($secret){
		return $this->rsa->decrypt($secret);
	}

	/**
	 * @param PlayerRespawnEvent $event
	 *
	 * @priority NORMAL
	 */
	public function onRespawn(PlayerRespawnEvent $event){
		$player = $event->getPlayer();
		if($player instanceof DesktopPlayer){
			$pk = new RespawnPacket();
			$pk->dimension = 0;
			$pk->difficulty = $player->getServer()->getDifficulty();
			$pk->gamemode = $player->getGamemode();
			$pk->levelType = "default";
			$player->putRawPacket($pk);
		}
	}

	public static function toJSON($message, $type = 1, $parameters = null){
		$result = TextFormat::toJSON($message);
		if($type === 2 and is_array($parameters)){
			$result = json_decode($result, true);
			unset($result["text"]);
			$result["translate"] = TextFormat::clean($message);
			foreach($parameters as $num => $parameter){
				$parameters[$num] = TextFormat::clean($parameter);//TODO
			}
			$result["with"] = $parameters;
			$result = json_encode($result, JSON_UNESCAPED_SLASHES);
		}
		return $result;
	}
}