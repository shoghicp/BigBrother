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

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerLoginEvent;
use pocketmine\network\protocol\Info;
use pocketmine\plugin\PluginBase;
use shoghicp\BigBrother\network\ProtocolInterface;
use shoghicp\BigBrother\network\ServerThread;
use shoghicp\BigBrother\network\translation\Translator_16;

class BigBrother extends PluginBase implements Listener{

	/** @var ServerThread */
	private $thread;

	/** @var ProtocolInterface */
	private $interface;

	public function onEnable(){
		$this->getServer()->getLoader()->add("phpseclib", [
			$this->getFile() . "src"
		]);

		$this->saveDefaultConfig();
		$this->reloadConfig();

		if(Info::CURRENT_PROTOCOL === 16){
			$translator = new Translator_16();
		}else{
			$this->getLogger()->critical("Couldn't find a protocol translator for #".Info::CURRENT_PROTOCOL .", disabling plugin");
			$this->getPluginLoader()->disablePlugin($this);
			return;
		}

		$this->thread = new ServerThread($this->getServer()->getLogger(), $this->getServer()->getLoader(), (int) $this->getConfig()->get("port"), $this->getConfig()->get("interface"));
		$this->interface = new ProtocolInterface($this, $this->thread, $translator);
		$this->getServer()->addInterface($this->interface);
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
	}

	public function onDisable(){
		//TODO: make it fully /reload compatible (remove from server)
		$this->interface->shutdown();
		$this->thread->join();
	}

	/**
	 * @param PlayerLoginEvent $event
	 *
	 * @priority MONITOR
	 */
	public function onLogin(PlayerLoginEvent $event){
		$player = $event->getPlayer();
		if($player instanceof DesktopPlayer){
			if(!$event->isCancelled()){
				$player->bigBrother_authenticate();
			}
		}
	}

}