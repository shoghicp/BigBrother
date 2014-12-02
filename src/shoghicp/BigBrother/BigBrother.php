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

use phpseclib\Crypt\RSA;
use pocketmine\event\player\PlayerRespawnEvent;
use pocketmine\event\player\PlayerPreLoginEvent;
use shoghicp\BigBrother\network\protocol\RespawnPacket;
use shoghicp\BigBrother\network\translation\Translator;
use pocketmine\event\Listener;
use pocketmine\network\protocol\Info;
use pocketmine\plugin\PluginBase;
use shoghicp\BigBrother\network\Info as MCInfo;
use shoghicp\BigBrother\network\ProtocolInterface;
use shoghicp\BigBrother\network\ServerThread;
use shoghicp\BigBrother\network\translation\Translator_20;
use shoghicp\BigBrother\tasks\GeneratePrivateKey;

class BigBrother extends PluginBase implements Listener{

	/** @var ServerThread */
	private $thread;
	private $internalQueue;
	private $externalQueue;

	/** @var ProtocolInterface */
	private $interface;

	/** @var RSA */
	protected $rsa;

	protected $privateKey;

	protected $publicKey;

	protected $onlineMode;

	/** @var Translator */
	protected $translator;

	public function onLoad(){

		class_exists("phpseclib\\Math\\BigInteger", true);
		class_exists("phpseclib\\Crypt\\Random", true);
		class_exists("phpseclib\\Crypt\\Base", true);
		class_exists("phpseclib\\Crypt\\Rijndael", true);
		class_exists("phpseclib\\Crypt\\AES", true);
	}

	public function onEnable(){

		$this->saveDefaultConfig();
		$this->saveResource("server-icon.png", false);
		$this->reloadConfig();

		$this->onlineMode = (bool) $this->getConfig()->get("online-mode");
		if($this->onlineMode and !function_exists("mcrypt_generic_init")){
			$this->onlineMode = false;
			$this->getLogger()->notice("no mcrypt detected, online-mode has been disabled. Try using the latest PHP binaries");
		}

		if(!$this->getConfig()->exists("motd")){
			$this->getLogger()->warning("No motd has been set. The server description will be empty.");
		}

		if(Info::CURRENT_PROTOCOL === 20){
			$this->translator = new Translator_20();
		}else{
			$this->getLogger()->critical("Couldn't find a protocol translator for #".Info::CURRENT_PROTOCOL .", disabling plugin");
			$this->getPluginLoader()->disablePlugin($this);
			return;
		}

		$this->rsa = new RSA();

		$this->getServer()->getPluginManager()->registerEvents($this, $this);

		if($this->onlineMode){
			$this->getLogger()->info("Server is being started in the background");
			$task = new GeneratePrivateKey($this->getServer()->getLogger(), $this->getServer()->getLoader());
			$this->getServer()->getScheduler()->scheduleAsyncTask($task);
		}else{
			$this->enableServer();
		}
	}

	public function receiveCryptoKeys($privateKey, $publicKey){
		$this->privateKey = $privateKey;
		$this->publicKey = $publicKey;
		$this->rsa->setEncryptionMode(CRYPT_RSA_ENCRYPTION_PKCS1);
		$this->rsa->loadKey($this->privateKey);
		$this->enableServer();
	}

	protected function enableServer(){
		$this->externalQueue = new \Threaded;
		$this->internalQueue = new \Threaded;
		$port = (int) $this->getConfig()->get("port");
		$interface = $this->getConfig()->get("interface");
		$this->getLogger()->info("Starting Minecraft: PC server on ".($interface === "0.0.0.0" ? "*" : $interface).":$port version ".MCInfo::VERSION);
		$this->thread = new ServerThread($this->externalQueue, $this->internalQueue, $this->getServer()->getLogger(), $this->getServer()->getLoader(), $port, $interface, (string) $this->getConfig()->get("motd"), $this->getDataFolder() . "server-icon.png");

		$this->interface = new ProtocolInterface($this, $this->thread, $this->translator);
		$this->getServer()->addInterface($this->interface);
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

	public function onDisable(){
		//TODO: make it fully /reload compatible (remove from server)
		if($this->interface instanceof ProtocolInterface){
			$this->getServer()->removeInterface($this->interface);
			$this->thread->join();
		}
	}

	/**
	 * @param PlayerPreLoginEvent $event
	 *
	 * @priority NORMAL
	 */
	public function onPreLogin(PlayerPreLoginEvent $event){
		$player = $event->getPlayer();
		if($player instanceof DesktopPlayer){
			$threshold = $this->getConfig()->get("network-compression-threshold");
			if($threshold === false){
				$threshold = -1;
			}
			$player->bigBrother_setCompression($threshold);
		}

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
			$pk->gamemode = $player->getGamemode();
			$pk->difficulty = $player->getServer()->getDifficulty();
			$pk->levelType = "default";
			$player->putRawPacket($pk);
		}
	}

}
