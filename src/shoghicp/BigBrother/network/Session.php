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

use pocketmine\utils\TextFormat;
use shoghicp\BigBrother\network\protocol\Login\LoginDisconnectPacket;
use shoghicp\BigBrother\network\protocol\Login\PingPacket;
use shoghicp\BigBrother\utils\AES;
use shoghicp\BigBrother\utils\Binary;

use shoghicp\BigBrother\BigBrother;

class Session{
	/** @var ServerManager */
	private $manager;
	private $identifier;
	private $socket;
	private $status = 0;
	protected $address;
	protected $port;
	/** @var AES */
	protected $aes;
	protected $hasCrypto = false;

	private $threshold = null;

	public function __construct(ServerManager $manager, $identifier, $socket){
		$this->manager = $manager;
		$this->identifier = $identifier;
		$this->socket = $socket;
		$addr = stream_socket_get_name($this->socket, true);
		$final = strrpos($addr, ":");
		$this->port = (int) substr($addr, $final + 1);
		$this->address = substr($addr, 0, $final);
	}

	public function setCompression($threshold){
		$this->writeRaw(Binary::writeVarInt(0x46) . Binary::writeVarInt($threshold >= 0 ? $threshold : -1));
		$this->threshold = $threshold === -1 ? null : $threshold;
	}

	public function write($data){
		if($this->hasCrypto){
			@fwrite($this->socket, $this->aes->encrypt($data));
		}else{
			@fwrite($this->socket, $data);
		}
	}

	public function read($len){
		if($this->hasCrypto){
			$data = @fread($this->socket, $len);
			if(strlen($data) > 0){
				return $this->aes->decrypt($data);
			}else{
				return $data;
			}
		}else{
			return @fread($this->socket, $len);
		}
	}

	public function getAddress(){
		return $this->address;
	}

	public function getPort(){
		return $this->port;
	}

	public function enableEncryption($secret){
		$this->aes = new AES(128, "CFB", 8);
		$this->aes->setKey($secret);
		$this->aes->setIV($secret);
		$this->aes->init();

		$this->hasCrypto = true;
	}

	public function writePacket(Packet $packet){
		$data = $packet->write();
		if($this->threshold === null){
			$this->write(Binary::writeVarInt(strlen($data)) . $data);
		}else{
			$dataLength = strlen($data);
			if($dataLength >= $this->threshold){
				$data = zlib_encode($data, ZLIB_ENCODING_DEFLATE, 7);
			}else{
				$dataLength = 0;
			}

			$data = Binary::writeVarInt($dataLength) . $data;
			$this->write(Binary::writeVarInt(strlen($data)) . $data);
		}
	}

	public function writeRaw($data){
		if($this->threshold === null){
			$this->write(Binary::writeVarInt(strlen($data)) . $data);
		}else{
			$dataLength = strlen($data);
			if($dataLength >= $this->threshold){
				$data = zlib_encode($data, ZLIB_ENCODING_DEFLATE, 7);
			}else{
				$dataLength = 0;
			}

			$data = Binary::writeVarInt($dataLength) . $data;
			$this->write(Binary::writeVarInt(strlen($data)) . $data);
		}
	}

	public function process(){
		$length = Binary::readVarIntSession($this);
		if($length === false or $this->status === -1){
			$this->close("Connection closed");
			return;
		}elseif($length <= 0 or $length > 131070){
			$this->close("Invalid length");
			return;
		}

		$offset = 0;

		$buffer = $this->read($length);

		if($this->threshold !== null){
			$dataLength = Binary::readVarInt($buffer, $offset);
			if($dataLength !== 0){
				if($dataLength < $this->threshold){
					$this->close("Invalid compression threshold");
				}else{
					$buffer = zlib_decode(substr($buffer, $offset));
					$offset = 0;
				}
			}else{
				$buffer = substr($buffer, $offset);
				$offset = 0;
			}
		}

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
				$data = [
					"version" => [
						"name" => Info::VERSION,
						"protocol" => Info::PROTOCOL
					],
					"players" => [
						"max" => $this->manager->getServerData()["MaxPlayers"],
						"online" => $this->manager->getServerData()["OnlinePlayers"],
						"sample" => $sample,
					],
					"description" => json_decode(TextFormat::toJSON($this->manager->description))
				];
				if($this->manager->favicon !== null){
					$data["favicon"] = $this->manager->favicon;
				}
				$data = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

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
						$packet->reson = BigBrother::toJSON(TextFormat::BOLD . "Outdated client!".TextFormat::RESET."\n\nPlease use ".Info::VERSION);
						$this->writePacket($packet);
					}elseif($protocol > Info::PROTOCOL){
						$packet = new LoginDisconnectPacket();
						$packet->reson = BigBrother::toJSON(TextFormat::BOLD . "Outdated server!".TextFormat::RESET."\n\nI'm using ".Info::VERSION);
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