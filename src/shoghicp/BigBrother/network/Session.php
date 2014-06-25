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

use shoghicp\BigBrother\network\protocol\LoginDisconnectPacket;
use shoghicp\BigBrother\network\protocol\PingPacket;
use shoghicp\BigBrother\utils\Binary;

class Session{
	/** @var ServerManager */
	private $manager;
	private $identifier;
	private $socket;
	private $status = 0;
	protected $address;
	protected $port;

	public function __construct(ServerManager $manager, $identifier, $socket){
		$this->manager = $manager;
		$this->identifier = $identifier;
		$this->socket = $socket;
		$addr = stream_socket_get_name($this->socket, true);
		$final = strrpos($addr, ":");
		$this->port = (int) substr($addr, $final + 1);
		$this->address = substr($addr, 0, $final);

	}

	public function getAddress(){
		return $this->address;
	}

	public function getPort(){
		return $this->port;
	}
	public function writePacket(Packet $packet){
		$data = $packet->write();
		@fwrite($this->socket, Binary::writeVarInt(strlen($data)) . $data);
	}

	public function writeRaw($data){
		@fwrite($this->socket, Binary::writeVarInt(strlen($data)) . $data);
	}

	public function process(){
		$length = Binary::readVarIntStream($this->socket);
		if($length === false or $this->status === -1){
			$this->close("Connection closed");
			return;
		}elseif($length <= 0 or $length > 131070){
			$this->close("Invalid length");
			return;
		}

		$offset = 0;

		$buffer = fread($this->socket, $length);

		if($this->status === 2){ //Login
			$this->manager->sendPacket($this->identifier, $buffer);
		}elseif($this->status === 1){
			$pid = Binary::readVarInt($buffer, $offset);
			if($pid === 0x00){
				$sample = [];
				foreach($this->manager->sample as $id => $name){
					$sample[] = [
						"name" => $name,
						"id" => $id
					];
				}
				$data = json_encode([
					"version" => [
						"name" => Info::VERSION,
						"protocol" => Info::PROTOCOL
					],
					"players" => [
						"max" => $this->manager->maxPlayers,
						"online" => $this->manager->players,
						"sample" => $sample,
					],
					"description" => $this->manager->description,
					"favicon" => $this->manager->favicon
				], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

				$data = Binary::writeVarInt(0x00) . Binary::writeVarInt(strlen($data)) . $data;
				$this->writeRaw($data);
			}elseif($pid === 0x01){
				$packet = new PingPacket();
				$packet->read($buffer, $offset);
				$this->writePacket($packet);
				$this->status = -1;
			}
		}elseif($this->status === 0){
			$pid = Binary::readVarInt($buffer, $offset);
			if($pid === 0x00){
				$protocol = Binary::readVarInt($buffer, $offset);
				$len = Binary::readVarInt($buffer, $offset);
				$hostname = substr($buffer, $offset, $len);
				$offset += $len;
				$serverPort = Binary::readShort(substr($buffer, $offset, 2));
				$offset += 2;
				$nextState = Binary::readVarInt($buffer, $offset);

				if($nextState === 1){
					$this->status = 1;
				}elseif($nextState === 2){
					$this->status = -1;
					if($protocol < Info::PROTOCOL){
						$packet = new LoginDisconnectPacket();
						$packet->reason = "{\"text\":\"§lOutdated client!§r\\n\\nPlease use ".Info::VERSION."\"}";
						$this->writePacket($packet);
					}elseif($protocol > Info::PROTOCOL){
						$packet = new LoginDisconnectPacket();
						$packet->reason = "{\"text\":\"§lOutdated server!§r\\n\\nI'm using ".Info::VERSION."\"}";
						$this->writePacket($packet);
					}else{
						$this->manager->openSession($this);
						$this->status = 2;
					}
				}else{
					$this->close();
				}
			}else{
				$this->close("Unexpected packet $pid");
			}
		}

	}

	public function getID(){
		return $this->identifier;
	}

	public function close($reason = ""){
		$this->manager->close($this);
	}

}