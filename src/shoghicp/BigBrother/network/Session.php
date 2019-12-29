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

namespace shoghicp\BigBrother\network;

use phpseclib\Crypt\AES;
use pocketmine\utils\TextFormat;
use shoghicp\BigBrother\network\protocol\Login\LoginDisconnectPacket;
use shoghicp\BigBrother\network\protocol\Status\PingPacket;
use shoghicp\BigBrother\utils\Binary;

class Session{
	/** @var ServerManager */
	private $manager;
	/** @var int */
	private $identifier;
	/** @var resource */
	private $socket;
	/** @var int */
	private $status = 0;
	/** @var string */
	protected $address;
	/** @var int */
	protected $port;
	/** @var AES */
	protected $aes;
	/** @var bool */
	protected $encryptionEnabled = false;

	/** @var ?int */
	private $threshold = null;

	/**
	 * @param ServerManager $manager
	 * @param int           $identifier
	 * @param resource      $socket
	 */
	public function __construct(ServerManager $manager, int $identifier, $socket){
		$this->manager = $manager;
		$this->identifier = $identifier;
		$this->socket = $socket;
		$addr = stream_socket_get_name($this->socket, true);
		$final = strrpos($addr, ":");
		$this->port = (int) substr($addr, $final + 1);
		$this->address = substr($addr, 0, $final);
	}

	/**
	 * @param int $threshold
	 */
	public function setCompression(int $threshold) : void{
		$this->writeRaw(Binary::writeComputerVarInt(0x03) . Binary::writeComputerVarInt($threshold >= 0 ? $threshold : -1));
		$this->threshold = $threshold === -1 ? null : $threshold;
	}

	/**
	 * @param string $data
	 */
	public function write(string $data) : void{
		if($this->encryptionEnabled){
			@fwrite($this->socket, $this->aes->encrypt($data));
		}else{
			@fwrite($this->socket, $data);
		}
	}

	/**
	 * @param int $len
	 * @return string data read from socket
	 */
	public function read(int $len) : string{
		if($this->encryptionEnabled){
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

	/**
	 * @return string address
	 */
	public function getAddress() : string{
		return $this->address;
	}

	/**
	 * @return int port
	 */
	public function getPort() : int{
		return $this->port;
	}

	/**
	 * @param string $secret
	 */
	public function enableEncryption(string $secret) : void{
		$this->aes = new AES(AES::MODE_CFB8);
		$this->aes->enableContinuousBuffer();
		$this->aes->setKey($secret);
		$this->aes->setIV($secret);

		$this->encryptionEnabled = true;
	}

	/**
	 * @param Packet $packet
	 */
	public function writePacket(Packet $packet) : void{
		$this->writeRaw($packet->write());
	}

	/**
	 * @param string $data
	 */
	public function writeRaw(string $data) : void{
		if($this->threshold === null){
			$this->write(Binary::writeComputerVarInt(strlen($data)) . $data);
		}else{
			$dataLength = strlen($data);
			if($dataLength >= $this->threshold){
				$data = zlib_encode($data, ZLIB_ENCODING_DEFLATE, 7);
			}else{
				$dataLength = 0;
			}

			$data = Binary::writeComputerVarInt($dataLength) . $data;
			$this->write(Binary::writeComputerVarInt(strlen($data)) . $data);
		}
	}

	public function process() : void{
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
			$dataLength = Binary::readComputerVarInt($buffer, $offset);
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
			$pid = Binary::readComputerVarInt($buffer, $offset);
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
						"name" => ServerManager::VERSION,
						"protocol" => ServerManager::PROTOCOL
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

				$data = Binary::writeComputerVarInt(0x00) . Binary::writeComputerVarInt(strlen($data)) . $data;
				$this->writeRaw($data);
			}elseif($pid === 0x01){
				$packet = new PingPacket();
				$packet->read($buffer, $offset);
				$this->writePacket($packet);
				$this->status = -1;
			}
		}elseif($this->status === 0){
			$pid = Binary::readComputerVarInt($buffer, $offset);
			if($pid === 0x00){
				$protocol = Binary::readComputerVarInt($buffer, $offset);
				$len = Binary::readComputerVarInt($buffer, $offset);
				$hostname = substr($buffer, $offset, $len);
				$offset += $len;
				$serverPort = Binary::readShort(substr($buffer, $offset, 2));
				$offset += 2;
				$nextState = Binary::readComputerVarInt($buffer, $offset);

				if($nextState === 1){
					$this->status = 1;
				}elseif($nextState === 2){
					$this->status = -1;
					if($protocol < ServerManager::PROTOCOL){
						$packet = new LoginDisconnectPacket();
						$packet->reason = json_encode(["translate" => "multiplayer.disconnect.outdated_client", "with" => [["text" => ServerManager::VERSION]]]);
						$this->writePacket($packet);
					}elseif($protocol > ServerManager::PROTOCOL){
						$packet = new LoginDisconnectPacket();
						$packet->reason = json_encode(["translate" => "multiplayer.disconnect.outdated_server", "with" => [["text" => ServerManager::VERSION]]]);
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

	/**
	 * @return int identifier
	 */
	public function getID() : int{
		return $this->identifier;
	}

	/**
	 * @param string $reason
	 */
	public function close(string $reason = "") : void{
		$this->manager->close($this);
	}
}
