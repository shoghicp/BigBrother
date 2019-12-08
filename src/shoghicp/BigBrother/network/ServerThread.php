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

use ClassLoader;
use Exception;
use ReflectionClass;
use Thread;
use Threaded;
use ThreadedLogger;

class ServerThread extends Thread{

	/** @var int */
	protected $port;
	/** @var string */
	protected $interface;
	/** @var ThreadedLogger */
	protected $logger;
	/** @var ClassLoader */
	protected $loader;
	/** @var string */
	protected $data;

	/** @var array */
	public $loadPaths;

	/** @var bool */
	protected $shutdown;

	/** @var Threaded */
	protected $externalQueue;
	/** @var Threaded */
	protected $internalQueue;

	/** @var resource */
	protected $externalSocket;
	/** @var resource */
	protected $internalSocket;

	/**
	 * @param ThreadedLogger $logger
	 * @param ClassLoader    $loader
	 * @param int             $port 1-65536
	 * @param string          $interface
	 * @param string          $motd
	 * @param string|null     $icon
	 * @param bool            $autoStart
	 * @throws Exception
	 */
	public function __construct(ThreadedLogger $logger, ClassLoader $loader, int $port, string $interface = "0.0.0.0", string $motd = "Minecraft: PE server", string $icon = null, bool $autoStart = true){
		$this->port = $port;
		if($port < 1 or $port > 65536){
			throw new Exception("Invalid port range");
		}

		$this->interface = $interface;
		$this->logger = $logger;
		$this->loader = $loader;

		$this->data = serialize([
			"motd" => $motd,
			"icon" => $icon
		]);

		$loadPaths = [];
		$this->addDependency($loadPaths, new ReflectionClass($logger));
		$this->addDependency($loadPaths, new ReflectionClass($loader));
		$this->loadPaths = array_reverse($loadPaths);
		$this->shutdown = false;

		$this->externalQueue = new Threaded;
		$this->internalQueue = new Threaded;

		if(($sockets = stream_socket_pair((strtoupper(substr(PHP_OS, 0, 3)) === 'WIN' ? STREAM_PF_INET : STREAM_PF_UNIX), STREAM_SOCK_STREAM, STREAM_IPPROTO_IP)) === false){
			throw new Exception("Could not create IPC streams. Reason: ".socket_strerror(socket_last_error()));
		}

		$this->internalSocket = $sockets[0];
		stream_set_blocking($this->internalSocket, false);
		$this->externalSocket = $sockets[1];
		stream_set_blocking($this->externalSocket, false);

		if($autoStart){
			$this->start();
		}
	}

	/**
	 * @param array            &$loadPaths
	 * @param ReflectionClass $dep
	 */
	protected function addDependency(array &$loadPaths, ReflectionClass $dep){
		if($dep->getFileName() !== false){
			$loadPaths[$dep->getName()] = $dep->getFileName();
		}

		if($dep->getParentClass() instanceof ReflectionClass){
			$this->addDependency($loadPaths, $dep->getParentClass());
		}

		foreach($dep->getInterfaces() as $interface){
			$this->addDependency($loadPaths, $interface);
		}
	}

	/**
	 * @return bool true if this thread state is shutdown
	 */
	public function isShutdown() : bool{
		return $this->shutdown === true;
	}

	public function shutdown(){
		$this->shutdown = true;
	}

	/**
	 * @return int port
	 */
	public function getPort() : int{
		return $this->port;
	}

	/**
	 * @return string interface
	 */
	public function getInterface() : string{
		return $this->interface;
	}

	/**
	 * @return ThreadedLogger logger
	 */
	public function getLogger() : ThreadedLogger{
		return $this->logger;
	}

	/**
	 * @return Threaded external queue
	 */
	public function getExternalQueue() : Threaded{
		return $this->externalQueue;
	}

	/**
	 * @return Threaded internal queue
	 */
	public function getInternalQueue() : Threaded{
		return $this->internalQueue;
	}

	/**
	 * @return resource internal socket
	 */
	public function getInternalSocket(){
		return $this->internalSocket;
	}

	/**
	 * @param string $str
	 */
	public function pushMainToThreadPacket(string $str) : void{
		$this->internalQueue[] = $str;
		@fwrite($this->externalSocket, "\xff", 1); //Notify
	}

	/**
	 * @return string|null
	 */
	public function readMainToThreadPacket() : ?string{
		return $this->internalQueue->shift();
	}

	/**
	 * @param string $str
	 */
	public function pushThreadToMainPacket(string $str) : void{
		$this->externalQueue[] = $str;
	}

	/**
	 * @return string|null
	 */
	public function readThreadToMainPacket() : ?string{
		return $this->externalQueue->shift();
	}

	public function shutdownHandler() : void{
		if($this->shutdown !== true){
			$this->getLogger()->emergency("[ServerThread #". Thread::getCurrentThreadId() ."] ServerThread crashed!");
		}
	}

	/**
	 * @override
	 */
	public function run(){
		//Load removed dependencies, can't use require_once()
		foreach($this->loadPaths as $name => $path){
			if(!class_exists($name, false) and !interface_exists($name, false)){
				/** @noinspection PhpIncludeInspection */
				require $path;
			}
		}
		$this->loader->register();

		register_shutdown_function([$this, "shutdownHandler"]);

		$data = unserialize($this->data);
		new ServerManager($this, $this->port, $this->interface, $data["motd"], $data["icon"]);
	}

	public function setGarbage(){
	}
}
