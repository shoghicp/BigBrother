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

namespace shoghicp\BigBrother\network;

use pocketmine\network\protocol\DataPacket;
use pocketmine\network\SourceInterface;
use pocketmine\Player;
use shoghicp\BigBrother\BigBrother;
use shoghicp\BigBrother\DesktopPlayer;
use shoghicp\BigBrother\network\protocol\CTSChatPacket;
use shoghicp\BigBrother\network\protocol\LoginStartPacket;
use shoghicp\BigBrother\network\translation\Translator;
use shoghicp\BigBrother\utils\Binary;

class ProtocolInterface implements SourceInterface{

	/** @var BigBrother */
	protected $plugin;
	/** @var Translator */
	protected $translator;
	/** @var ServerThread */
	protected $thread;
	/** @var resource */
	protected $fp;

	/** @var \SplObjectStorage<DesktopPlayer> */
	protected $sessions;

	/** @var DesktopPlayer[] */
	protected $sessionsPlayers = [];

	/** @var DesktopPlayer[] */
	protected $identifiers = [];

	protected $identifier = 0;

	public function __construct(BigBrother $plugin, ServerThread $thread, Translator $translator){
		$this->plugin = $plugin;
		$this->translator = $translator;
		$this->thread = $thread;
		$this->fp = $this->thread->getExternalIPC();
		$this->sessions = new \SplObjectStorage();
	}

	public function emergencyShutdown(){
		fwrite($this->fp, Binary::writeInt(1) . chr(ServerManager::PACKET_EMERGENCY_SHUTDOWN));
	}

	public function shutdown(){
		foreach($this->sessionsPlayers as $player){
			$player->close($player->getName() . " has left the game", $this->plugin->getServer()->getProperty("settings.shutdown-message", "Server closed"));
		}
		fwrite($this->fp, Binary::writeInt(1) . chr(ServerManager::PACKET_SHUTDOWN));
	}

	public function setName($name){

	}

	public function close(Player $player, $reason = "unknown reason"){
		if(isset($this->sessions[$player])){
			$identifier = $this->sessions[$player];
			$this->sessions->detach($player);
			unset($this->sessionsPlayers[$identifier]);
			$player->close($player->getName() . " has left the game", "Connection closed");
		}else{
			return;
		}
		fwrite($this->fp, Binary::writeInt(5) . chr(ServerManager::PACKET_CLOSE_SESSION) . Binary::writeInt($identifier));
	}

	protected function sendPacket($target, Packet $packet){
		$data = chr(ServerManager::PACKET_SEND_PACKET) . Binary::writeInt($target) . $packet->write();
		fwrite($this->fp, Binary::writeInt(strlen($data)) . $data);
	}

	public function putRawPacket(DesktopPlayer $player, Packet $packet){
		if(isset($this->sessions[$player])){
			$target = $this->sessions[$player];
			$this->sendPacket($target, $packet);
		}
	}

	public function putPacket(Player $player, DataPacket $packet, $needACK = false, $immediate = true){
		$id = 0;
		if($needACK){
			$id = $this->identifier++;
			$this->identifiers[$id] = $player;
		}
		$packets = $this->translator->serverToInterface($player, $packet);
		if($packets !== null and $this->sessions->contains($player)){
			$target = $this->sessions[$player];
			if(is_array($packets)){
				foreach($packets as $packet){
					$this->sendPacket($target, $packet);
				}
			}else{
				$this->sendPacket($target, $packets);
			}
		}

		return $id;
	}

	protected function receivePacket(DesktopPlayer $player, Packet $packet){
		$packets = $this->translator->interfaceToServer($player, $packet);
		if($packets !== null){
			if(is_array($packets)){
				foreach($packets as $packet){
					$player->handleDataPacket($packet);
				}
			}else{
				$player->handleDataPacket($packets);
			}
		}
	}

	public function readStream($len){
		$buffer = "";

		while(strlen($buffer) < $len){
			$buffer .= (string) fread($this->fp, $len - strlen($buffer));
		}

		return $buffer;
	}

	protected function handlePacket(DesktopPlayer $player, $payload){
		$pid = ord($payload{0});
		$offset = 1;

		$status = $player->bigBrother_getStatus();

		if($status === 1){
			if($pid === 0x01){
				$pk = new CTSChatPacket();
				$pk->read($payload, $offset);

			}else{
				return;
			}
			$this->receivePacket($player, $pk);
		}elseif($status === 0){
			if($pid === 0x00){
				$pk = new LoginStartPacket();
				$pk->read($payload, $offset);
				$player->bigBrother_handleAuthentication($pk);
			}else{
				$player->close($player->getName() . " has left the game", "Unexpected packet $pid");
			}
		}
	}

	public function process(){
		if(count($this->identifiers) > 0){
			foreach($this->identifiers as $id => $player){
				$player->handleACK($id);
			}
		}

		$len = fread($this->fp, 4);
		if($len === false or $len === ""){
			return;
		}

		$buffer = $this->readStream(Binary::readInt($len));

		$offset = 1;
		$pid = ord($buffer{0});

		if($pid === ServerManager::PACKET_SEND_PACKET){
			$id = Binary::readInt(substr($buffer, $offset, 4));
			$offset += 4;
			if(isset($this->sessionsPlayers[$id])){
				$payload = substr($buffer, $offset);
				$this->handlePacket($this->sessionsPlayers[$id], $payload);
			}
		}elseif($pid === ServerManager::PACKET_OPEN_SESSION){
			$id = Binary::readInt(substr($buffer, $offset, 4));
			$offset += 4;
			if(isset($this->sessionsPlayers[$id])){
				return;
			}
			$len = ord($buffer{$offset++});
			$address = substr($buffer, $offset, $len);
			$offset += $len;
			$port = Binary::readShort(substr($buffer, $offset, 2), false);

			$identifier = "$id:$address:$port";

			$player = new DesktopPlayer($this, $identifier, $address, $port);
			$this->sessions->attach($player, $id);
			$this->sessionsPlayers[$id] = $player;
			$this->plugin->getServer()->addPlayer($identifier, $player);
		}elseif($pid === ServerManager::PACKET_CLOSE_SESSION){
			$id = Binary::readInt(substr($buffer, $offset, 4));
			if(!isset($this->sessionsPlayers[$id])){
				return;
			}
			$this->close($this->sessionsPlayers[$id]);
		}

	}
}