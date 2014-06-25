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
	 * no payload
	 */
	const PACKET_SHUTDOWN = 0xfe;

	/*
	 * no payload
	 */
	const PACKET_EMERGENCY_SHUTDOWN = 0xff;

	protected $fp;
	protected $socket;
	protected $identifier = 0;
	protected $sockets = [];
	/** @var Session[] */
	protected $sessions = [];
	/** @var \Logger */
	protected $logger;
	protected $shutdown = false;

	public $players = 0;
	public $maxPlayers = 20;
	/** @var string[] */
	public $sample = [];
	public $description = "§bPocketMine-MP server using §6§lBigBrother§r§b plugin\n§aConnect to Minecraft: PE servers from PC clients";
	public $favicon = "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAEAAAABACAYAAACqaXHeAAAABmJLR0QA/wD/AP+gvaeTAAAACXBIWXMAAA6mAAAOpgGH3KlvAAAAB3RJTUUH3gIJDS8ZnYfI6wAAA3VJREFUeNrtW8tOFEEUrXZmGNhNjAHjEwGBoLige1AGE2cH+AMakRATTYyJfoGsiF9gogY1ajQaP0AJiTEYYOTRDZEEA0p4iGhmVhM3MsBMu+WcxZ30wlXf2t2u6pqq03XOvbeqxhoaGvLNnpLL5faaJpvNgl0oFIxUtra2zP8slZWVYn08Hge7pqYG7OrqarD3mZAXBSDsAEQ3NjbgQT6fF1/YTCbxgY8Yth1uB9tbnQTbQooaK2KBXfpbBNuuTWF/axn8glURsE/MfQmkYUoBBSDsGlCO84nuG8hxCzk3s4QcN0dPI4djRPriDpju9ymwncbz4njs2g7ShM843q7rqGkjT0SNUwooAGHXAPaLvzrPol9eRY7ajWmwk61p8QdmFsawfQty3ElfE993R59h3GC2MQzZLtEnjYK5cqoV7MOzrq4ABUAB2KMBnL/7fgztGHLKHGxAO7eKnP34FBGO+di+tIsaMfoSNSLdh5yP4Teyj3XSJ4xQ/5hLGMo1eL5KAQUg7BrAD9ra+oL1QJxzGilWXx6jXGCXNAL9updBv+8XyM9HUKPcxXHab8ApWRVxXQEKgAIQQAO8iRfod+s6KH/PiPm9cxL99F3npjiAnv3yAIf/LOKDQ01or2Fsb6d6xf7WP33QFaAAKACCBjCnjY+xPO/jWxURMdbnkkjIA+ItSj5XsFkDOPbfRM3w1nA/44CuAAVAARA1wK7vFP3u++5mscN37Ocz8gBaWtDOUHs+S/RmXmOccKsf45DhBXzf2tYVoAAoAEHiAPLj7tgrsYOBkSUxbkilmkWOc0mlSEPuU25wu7fMlDBO8Yu+rgAFQAEIoAFz2WlEqCoqdjDY1STWX3z4HOx7C5RrGLx/MDCPnB08YwWqd5dx/E4DnkWuT03oClAAFAApDohifu/vxOQ4YF72sxy7k5suW3oe4PvetByXOBf6dQUoAApAAA3g+/d+CTXATl7B/QCDsbg7/gZ7pC1CpwPfdyffBhogx/JO+1UaD51l/v6GmrGC9wiP0HyVAgpA2DWA/2NT581gizL3BZzjeDfY+zErtz93CfN7cxkb0L5+sqk92Izo/kH9Vw/rab5KAQUg7BqQoMM6vk+ff3ynjKPms8OimAtwXMFl9ife+fHpb4p2Ud7nzw8/Eut5vkoBBSDkxdL/DisFFIBQl3+nqxY3IYpcCgAAAABJRU5ErkJggg==";

	public function __construct(ServerThread $thread, $port, $interface){
		$this->logger = $thread->getLogger();
		$this->fp = $thread->getInternalIPC();
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

	protected function processPacket(){
		$len = fread($this->fp, 4);
		if($len === false){
			$this->logger->critical("[BigBrother] Invalid internal ICP stream");
			exit(1);
		}

		$len = Binary::readInt($len);
		$pid = ord(fgetc($this->fp));
		if($len > 1){
			$buffer = fread($this->fp, $len - 1);
		}else{
			$buffer = "";
		}
		if($pid === self::PACKET_SEND_PACKET){
			$id = Binary::readInt(substr($buffer, 0, 4));
			$data = substr($buffer, 4);

			if(!isset($this->sessions[$id])){
				$this->closeSession($id);
				return;
			}
			$this->sessions[$id]->writeRaw($data);
		}elseif($pid === self::PACKET_ENABLE_ENCRYPTION){
			$id = Binary::readInt(substr($buffer, 0, 4));
			$secret = substr($buffer, 4);

			if(!isset($this->sessions[$id])){
				$this->closeSession($id);
				return;
			}
			$this->sessions[$id]->enableEncryption($secret);
		}elseif($pid === self::PACKET_CLOSE_SESSION){
			$id = Binary::readInt(substr($buffer, 0, 4));
			if(isset($this->sessions[$id])){
				$this->close($this->sessions[$id]);
			}
		}elseif($pid === self::PACKET_SHUTDOWN){
			$this->shutdown = true;
			foreach($this->sessions as $session){
				$session->close();
			}
		}elseif($pid === self::PACKET_EMERGENCY_SHUTDOWN){
			$this->shutdown = true;
		}
	}

	public function sendPacket($id, $buffer){
		fwrite($this->fp, Binary::writeInt(strlen($buffer) + 5) . chr(self::PACKET_SEND_PACKET) . Binary::writeInt($id) . $buffer);
	}

	public function openSession(Session $session){
		$data = chr(self::PACKET_OPEN_SESSION) . Binary::writeInt($session->getID()) . chr(strlen($session->getAddress())) . $session->getAddress() . Binary::writeShort($session->getPort());
		fwrite($this->fp, Binary::writeInt(strlen($data)) . $data);
	}

	protected function closeSession($id){
		fwrite($this->fp, Binary::writeInt(5) . chr(self::PACKET_CLOSE_SESSION) . Binary::writeInt($id));
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
						$this->processPacket();
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
		$this->closeSession($identifier);
	}
}