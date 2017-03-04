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

use shoghicp\BigBrother\utils\Binary;

class ServerManager{

	/*
	 * Internal Packet:
	 * int32 (length without this field)
	 * byte (packet ID)
	 * payload
	 */

	/*
	 * SEND_PACKET payload:
	 * int32 (session identifier)
	 * packet (binary payload)
	 */
	const PACKET_SEND_PACKET = 0x01;

	/*
	 * OPEN_SESSION payload:
	 * int32 (session identifier)
	 * byte (address length)
	 * byte[] (address)
	 * short (port)
	 */
	const PACKET_OPEN_SESSION = 0x02;

	/*
	 * CLOSE_SESSION payload:
	 * int32 (session identifier)
	 */
	const PACKET_CLOSE_SESSION = 0x03;

	/*
	 * ENABLE_ENCRYPTION payload:
	 * int32 (session identifier)
	 * byte[] (secret)
	 */
	const PACKET_ENABLE_ENCRYPTION = 0x04;

	/*
	 * ENABLE_ENCRYPTION payload:
	 * int32 (session identifier)
	 * int (threshold)
	 */
	const PACKET_SET_COMPRESSION = 0x05;

	const PACKET_SET_OPTION = 0x06;

	/*
	 * no payload
	 */
	const PACKET_SHUTDOWN = 0xfe;

	/*
	 * no payload
	 */
	const PACKET_EMERGENCY_SHUTDOWN = 0xff;

	/** @var ServerThread */
	protected $thread;
	protected $fp;
	protected $socket;
	protected $identifier = 0;
	protected $sockets = [];
	/** @var Session[] */
	protected $sessions = [];
	/** @var \Logger */
	protected $logger;
	protected $shutdown = false;

	/** @var string[] */
	public $sample = [];
	public $description;
	public $favicon;
	public $serverdata = [
		"MaxPlayers" => 20,
		"OnlinePlayers" => 0,
	];

	public function __construct(ServerThread $thread, $port, $interface, $description = "", $favicon = null){
		$this->thread = $thread;
		$this->description = $description;
		if($favicon === null or ($image = file_get_contents($favicon)) == ""){
			$this->favicon = null;
		}else{
			$this->favicon = "data:image/png;base64,".base64_encode($image);
		}

		$this->logger = $this->thread->getLogger();
		$this->fp = $this->thread->getInternalSocket();

		if($interface === ""){
			$interface = "0.0.0.0";
		}

		$this->socket = stream_socket_server("tcp://$interface:$port", $errno, $errstr, STREAM_SERVER_LISTEN | STREAM_SERVER_BIND);
		if(!$this->socket){
			$this->logger->critical("[BigBrother] **** FAILED TO BIND TO " . $interface . ":" . $port . "!", true, true, 0);
			$this->logger->critical("[BigBrother] Perhaps a server is already running on that port?", true, true, 0);
			exit(1);
		}

		$this->sockets[-1] = $this->socket;
		$this->sockets[0] = $this->fp;

		$this->process();
	}

	public function getServerData(){
		return $this->serverdata;
	}

	public function shutdown(){
		$this->thread->shutdown();
		usleep(50000); //Sleep for 1 tick
		//$this->thread->kill();
	}

	protected function processPacket(){
		@fread($this->fp, 1);
		if(strlen($packet = $this->thread->readMainToThreadPacket()) > 0){
			$pid = ord($packet{0});

			$buffer = substr($packet, 1);

			if($pid === self::PACKET_SEND_PACKET){
				$id = Binary::readInt(substr($buffer, 0, 4));
				$data = substr($buffer, 4);

				if(!isset($this->sessions[$id])){
					$this->closeSession($id, 0);
					return true;
				}
				$this->sessions[$id]->writeRaw($data);
			}elseif($pid === self::PACKET_ENABLE_ENCRYPTION){
				$id = Binary::readInt(substr($buffer, 0, 4));
				$secret = substr($buffer, 4);

				if(!isset($this->sessions[$id])){
					$this->closeSession($id, 0);
					return true;
				}
				$this->sessions[$id]->enableEncryption($secret);
			}elseif($pid === self::PACKET_SET_COMPRESSION){
				$id = Binary::readInt(substr($buffer, 0, 4));
				$threshold = Binary::readInt(substr($buffer, 4, 4));

				if(!isset($this->sessions[$id])){
					$this->closeSession($id, 0);
					return true;
				}
				$this->sessions[$id]->setCompression($threshold);
			}elseif($pid === self::PACKET_SET_OPTION){
				$offset = 1;
				$len = ord($packet{$offset++});
                $name = substr($packet, $offset, $len);
                $offset += $len;
                $value = substr($packet, $offset);
                switch($name){
                	case "name":
                		$this->serverdata = json_decode($value, true);
                	break;
                }
			}elseif($pid === self::PACKET_CLOSE_SESSION){
				$id = Binary::readInt(substr($buffer, 0, 4));
				if(isset($this->sessions[$id])){
					$this->close($this->sessions[$id]);
				}else{
					$this->closeSession($id, 1);
				}
			}elseif($pid === self::PACKET_SHUTDOWN){
				foreach($this->sessions as $session){
					$session->close();
				}

				$this->shutdown();
				stream_socket_shutdown($this->socket, STREAM_SHUT_RDWR);
				$this->shutdown = true;
			}elseif($pid === self::PACKET_EMERGENCY_SHUTDOWN){
				$this->shutdown = true;
			}

			return true;
		}

		return false;
	}

	public function sendPacket($id, $buffer){
		$this->thread->pushThreadToMainPacket(chr(self::PACKET_SEND_PACKET) . Binary::writeInt($id) . $buffer);
	}

	public function openSession(Session $session){
		$data = chr(self::PACKET_OPEN_SESSION) . Binary::writeInt($session->getID()) . chr(strlen($session->getAddress())) . $session->getAddress() . Binary::writeShort($session->getPort());
		$this->thread->pushThreadToMainPacket($data);
	}

	protected function closeSession($id, $flag){
		$this->thread->pushThreadToMainPacket(chr(self::PACKET_CLOSE_SESSION) . Binary::writeInt($id).Binary::writeInt($flag));
	}

	private function process(){
		while($this->shutdown !== true){
			$sockets = $this->sockets;
			$write = null;
			$except = null;
			if(@stream_select($sockets, $write, $except, null) > 0){
				if(isset($sockets[-1])){
					unset($sockets[-1]);
					if($connection = stream_socket_accept($this->socket, 0)){
						$this->identifier++;
						$this->sockets[$this->identifier] = $connection;
						$this->sessions[$this->identifier] = new Session($this, $this->identifier, $connection);
					}
				}elseif(isset($sockets[0])){
					if($sockets[0] !== $this->fp){
						$this->findSocket($sockets[0]);
					}else{
						while($this->processPacket()){}
					}
					unset($sockets[0]);
				}

				foreach($sockets as $identifier => $socket){
					if(isset($this->sessions[$identifier]) and $this->sockets[$identifier] === $socket){
						$this->sessions[$identifier]->process();
					}else{
						$this->findSocket($socket);
					}
				}
			}
		}
	}

	protected function findSocket($s){
		foreach($this->sockets as $identifier => $socket){
			if($identifier > 0 and $socket === $s){
				$this->sessions[$identifier]->process();
				break;
			}
		}
	}

	public function close(Session $session){
		$identifier = $session->getID();
		fclose($this->sockets[$identifier]);
		unset($this->sockets[$identifier]);
		unset($this->sessions[$identifier]);
		$this->closeSession($identifier, 0);
	}

}