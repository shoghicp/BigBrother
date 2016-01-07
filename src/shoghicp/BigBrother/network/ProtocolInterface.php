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
use shoghicp\BigBrother\network\Info; //Computer Edition
use shoghicp\BigBrother\network\Packet;
use shoghicp\BigBrother\network\protocol\Login\EncryptionResponsePacket;
use shoghicp\BigBrother\network\protocol\Login\LoginStartPacket;
use shoghicp\BigBrother\network\protocol\Play\AnimatePacket;
use shoghicp\BigBrother\network\protocol\Play\ClientSettingsPacket;
use shoghicp\BigBrother\network\protocol\Play\ClientStatusPacket;
use shoghicp\BigBrother\network\protocol\Play\CreativeInventoryActionPacket;
use shoghicp\BigBrother\network\protocol\Play\CPlayerAbilitiesPacket;
use shoghicp\BigBrother\network\protocol\Play\CTSChatPacket;
use shoghicp\BigBrother\network\protocol\Play\CTSCloseWindowPacket;
use shoghicp\BigBrother\network\protocol\Play\HeldItemChangePacket;
use shoghicp\BigBrother\network\protocol\Play\KeepAlivePacket;
use shoghicp\BigBrother\network\protocol\Play\PlayerArmSwingPacket;
use shoghicp\BigBrother\network\protocol\Play\PlayerBlockPlacementPacket;
use shoghicp\BigBrother\network\protocol\Play\PlayerDiggingPacket;
use shoghicp\BigBrother\network\protocol\Play\PlayerLookPacket;
use shoghicp\BigBrother\network\protocol\Play\PlayerPacket;
use shoghicp\BigBrother\network\protocol\Play\PlayerPositionAndLookPacket;
use shoghicp\BigBrother\network\protocol\Play\PlayerPositionPacket;
use shoghicp\BigBrother\network\protocol\Play\PluginMessagePacket;
use shoghicp\BigBrother\network\protocol\Play\ResourcePackStatusPacket;
use shoghicp\BigBrother\network\protocol\Play\CTabCompletePacket;
use shoghicp\BigBrother\network\protocol\Play\UseEntityPacket;
use shoghicp\BigBrother\network\translation\Translator;
use shoghicp\BigBrother\utils\Binary;

class ProtocolInterface implements SourceInterface{

	/** @var BigBrother */
	protected $plugin;
	/** @var Translator */
	protected $translator;
	/** @var ServerThread */
	protected $thread;

	/** @var \SplObjectStorage<DesktopPlayer> */
	protected $sessions;

	/** @var DesktopPlayer[] */
	protected $sessionsPlayers = [];

	/** @var DesktopPlayer[] */
	protected $identifiers = [];

	protected $identifier = 0;

	public function __construct(BigBrother $plugin, $server, Translator $translator){
		$this->plugin = $plugin;
		$this->server = $server;
		$this->translator = $translator;
		$this->thread = new ServerThread($server->getLogger(), $server->getLoader(), $plugin->getPort(), $plugin->getIp(), $plugin->getMotd(), $plugin->getDataFolder()."server-icon.png");
		$this->sessions = new \SplObjectStorage();
	}

	public function emergencyShutdown(){
		$this->thread->pushMainToThreadPacket(chr(ServerManager::PACKET_EMERGENCY_SHUTDOWN));
	}

	public function shutdown(){
        $this->thread->pushMainToThreadPacket(chr(ServerManager::PACKET_SHUTDOWN));
	}

	public function setName($name){
		$info = $this->plugin->getServer()->getQueryInformation();
		$value = [
			"MaxPlayers" => $info->getMaxPlayerCount(),
			"OnlinePlayers" => $info->getPlayerCount(),
		];
		$buffer = chr(ServerManager::PACKET_SET_OPTION).chr(strlen("name"))."name".json_encode($value);
        $this->thread->pushMainToThreadPacket($buffer);
	}

	public function closeSession($identifier){
		if(isset($this->sessionsPlayers[$identifier])){
			$player = $this->sessionsPlayers[$identifier];
			unset($this->sessionsPlayers[$identifier]);
			$player->close($player->getLeaveMessage(), "Connection closed");
		}
	}

	public function close(Player $player, $reason = "unknown reason"){
		if(isset($this->sessions[$player])){
			$identifier = $this->sessions[$player];
			$this->sessions->detach($player);
			unset($this->identifiers[$identifier]);
			$this->thread->pushMainToThreadPacket(chr(ServerManager::PACKET_CLOSE_SESSION) . Binary::writeInt($identifier));
		}else{
			return;
		}
	}

	protected function sendPacket($target, Packet $packet){
		$data = chr(ServerManager::PACKET_SEND_PACKET) . Binary::writeInt($target) . $packet->write();
		$this->thread->pushMainToThreadPacket($data);
	}

	public function setCompression(DesktopPlayer $player, $threshold){
		if(isset($this->sessions[$player])){
			$target = $this->sessions[$player];
			$data = chr(ServerManager::PACKET_SET_COMPRESSION) . Binary::writeInt($target) . Binary::writeInt($threshold);
			$this->thread->pushMainToThreadPacket($data);
		}
	}

	public function enableEncryption(DesktopPlayer $player, $secret){
		if(isset($this->sessions[$player])){
			$target = $this->sessions[$player];
			$data = chr(ServerManager::PACKET_ENABLE_ENCRYPTION) . Binary::writeInt($target) . $secret;
			$this->thread->pushMainToThreadPacket($data);
		}
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

	protected function handlePacket(DesktopPlayer $player, $payload){
		$pid = ord($payload{0});
		$offset = 1;

		$status = $player->bigBrother_getStatus();

		if($status === 1){
			switch($pid){
				case 0x00:
					$pk = new KeepAlivePacket();
					break;
				case 0x01:
					$pk = new CTSChatPacket();
					break;
				case 0x02:
					$pk = new UseEntityPacket();
					break;
				case 0x03:
					$pk = new PlayerPacket();
					break;
				case 0x04:
					$pk = new PlayerPositionPacket();
					break;
				case 0x05:
					$pk = new PlayerLookPacket();
					break;
				case 0x06:
					$pk = new PlayerPositionAndLookPacket();
					break;
				case 0x07:
					$pk = new PlayerDiggingPacket();
					break;
				case 0x08:
					$pk = new PlayerBlockPlacementPacket();
					break;
				case 0x09:
					$pk = new HeldItemChangePacket();
					break;
				case 0x0a:
					$pk = new PlayerArmSwingPacket();
					break;
				case 0x0b:
					$pk = new AnimatePacket();
					break;
				/*case 0x0c:
					//
					break;*/
				case 0x0d:
					$pk = new CTSCloseWindowPacket();
					break;
				/*case 0x0e:
					break;
				case 0x0f:

					break;*/
				case 0x10:
					$pk = new CreativeInventoryActionPacket();
				break;
				/*case 0x11:

					break;
				case 0x12:

					break;*/
				case 0x13:
					$pk = new CPlayerAbilitiesPacket();
					break;
				case 0x14:
					$pk = new CTabCompletePacket();
					break;
				case 0x15:
					$pk = new ClientSettingsPacket();
					break;
				case 0x16:
					$pk = new ClientStatusPacket();
					break;
				case 0x17:
					$pk = new PluginMessagePacket();
					break;
				/*case 0x18:
					//
					break;*/
				case 0x19:
					$pk = new ResourcePackStatusPacket();
					break;
				default:
					echo "[Receive] 0x".bin2hex(chr($pid))."\n"; //Debug
					return;
			}

			$pk->read($payload, $offset);
			$this->receivePacket($player, $pk);
		}elseif($status === 0){
			if($pid === 0x00){
				echo "LoginStart\n";
				$pk = new LoginStartPacket();
				$pk->read($payload, $offset);
				$player->bigBrother_handleAuthentication($this->plugin, $pk->name, $this->plugin->isOnlineMode());
			}elseif($pid === 0x01 and $this->plugin->isOnlineMode()){
				$pk = new EncryptionResponsePacket();
				$pk->read($payload, $offset);
				$player->bigBrother_processAuthentication($this->plugin, $pk);
			}else{
				$player->close($player->getLeaveMessage(), "Unexpected packet $pid");
			}
		}
	}

	public function process(){
		if(count($this->identifiers) > 0){
			foreach($this->identifiers as $id => $player){
				$player->handleACK($id);
			}
		}

		while(strlen($buffer = $this->thread->readThreadToMainPacket()) > 0){
			$offset = 1;
			$pid = ord($buffer{0});

			if($pid === ServerManager::PACKET_SEND_PACKET){
				$id = Binary::readInt(substr($buffer, $offset, 4));
				$offset += 4;
				if(isset($this->sessionsPlayers[$id])){
					$payload = substr($buffer, $offset);
					try{
						$this->handlePacket($this->sessionsPlayers[$id], $payload);

					}catch(\Exception $e){
						if(\pocketmine\DEBUG > 1){
							$logger = $this->server->getLogger();
							if($logger instanceof MainLogger){
								$logger->debug("DesktopPacket 0x" . bin2hex($payload));
								$logger->logException($e);
							}
						}
					}
				}
			}elseif($pid === ServerManager::PACKET_OPEN_SESSION){
				$id = Binary::readInt(substr($buffer, $offset, 4));
				$offset += 4;
				if(isset($this->sessionsPlayers[$id])){
					continue;
				}
				$len = ord($buffer{$offset++});
				$address = substr($buffer, $offset, $len);
				$offset += $len;
				$port = Binary::readShort(substr($buffer, $offset, 2));

				$identifier = "$id:$address:$port";

				$player = new DesktopPlayer($this, $identifier, $address, $port, $this->plugin);
				$this->sessions->attach($player, $id);
				$this->sessionsPlayers[$id] = $player;
				$this->plugin->getServer()->addPlayer($identifier, $player);
			}elseif($pid === ServerManager::PACKET_CLOSE_SESSION){
				$id = Binary::readInt(substr($buffer, $offset, 4));
				$offset += 4;
				$flag = Binary::readInt(substr($buffer, $offset, 4));

				if(isset($this->sessionsPlayers[$id])){
					if($flag === 0){
						$this->close($this->sessionsPlayers[$id]);
					}else{
						$this->closeSession($id);
					}
				}
			}

		}

		return true;
	}

}