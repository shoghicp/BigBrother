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

namespace shoghicp\BigBrother\network;

class ServerThread extends \Thread{

	/** @var int */
	protected $port;
	/** @var string */
	protected $interface;
	/** @var \ThreadedLogger */
	protected $logger;
	/** @var \ClassLoader */
	protected $loader;
	/** @var string */
	protected $data;

	/** @var array */
	public $loadPaths;

	/** @var bool */
	protected $shutdown;

	/** @var \Threaded */
	protected $externalQueue;
	/** @var \Threaded */
	protected $internalQueue;

	/** @var resource */
	protected $externalSocket;
	/** @var resource */
	protected $internalSocket;

	/**
	 * @param \ThreadedLogger $logger
	 * @param \ClassLoader    $loader
	 * @param int             $port 1-65536
	 * @param string          $interface
	 * @param string          $motd
	 * @param string|null     $icon
	 *
	 * @throws \Exception
	 */
	public function __construct(\ThreadedLogger $logger, \ClassLoader $loader, int $port, string $interface = "0.0.0.0", string $motd = "Minecraft: PE server", string $icon = null){
		$this->port = $port;
		if($port < 1 or $port > 65536){
			throw new \Exception("Invalid port range");
		}

		$this->interface = $interface;
		$this->logger = $logger;
		$this->loader = $loader;

		$this->data = serialize([
			"motd" => $motd,
			"icon" => $icon
		]);

		$loadPaths = [];
		$this->addDependency($loadPaths, new \ReflectionClass($logger));
		$this->addDependency($loadPaths, new \ReflectionClass($loader));
		$this->loadPaths = array_reverse($loadPaths);
		$this->shutdown = false;

		$this->externalQueue = new \Threaded;
		$this->internalQueue = new \Threaded;

		if(($sockets = stream_socket_pair((strtoupper(substr(PHP_OS, 0, 3)) === 'WIN' ? STREAM_PF_INET : STREAM_PF_UNIX), STREAM_SOCK_STREAM, STREAM_IPPROTO_IP)) === false){
			throw new \Exception("Could not create IPC streams. Reason: ".socket_strerror(socket_last_error()));
		}

		$this->internalSocket = $sockets[0];
		stream_set_blocking($this->internalSocket, false);
		$this->externalSocket = $sockets[1];
		stream_set_blocking($this->externalSocket, false);

		$this->start();
	}

	protected function addDependency(array &$loadPaths, \ReflectionClass $dep){
		if($dep->getFileName() !== false){
			$loadPaths[$dep->getName()] = $dep->getFileName();
		}

		if($dep->getParentClass() instanceof \ReflectionClass){
			$this->addDependency($loadPaths, $dep->getParentClass());
		}

		foreach($dep->getInterfaces() as $interface){
			$this->addDependency($loadPaths, $interface);
		}
	}

	public function isShutdown() : bool{
		return $this->shutdown === true;
	}

	public function shutdown(){
		$this->shutdown = true;
	}

	public function getPort() : int{
		return $this->port;
	}

	public function getInterface() : string{
		return $this->interface;
	}

	/**
	 * @return \ThreadedLogger
	 */
	public function getLogger() : \ThreadedLogger{
		return $this->logger;
	}

	/**
	 * @return \Threaded
	 */
	public function getExternalQueue() : \Threaded{
		return $this->externalQueue;
	}

	/**
	 * @return \Threaded
	 */
	public function getInternalQueue() : \Threaded{
		return $this->internalQueue;
	}

	/**
	 * @return resource
	 */
	public function getInternalSocket(){
		return $this->internalSocket;
	}

	public function pushMainToThreadPacket(string $str){
		$this->internalQueue[] = $str;
		@fwrite($this->externalSocket, "\xff", 1); //Notify
	}

	/**
	 * @return string|null
	 */
	public function readMainToThreadPacket(){
		return $this->internalQueue->shift();
	}

	public function pushThreadToMainPacket(string $str){
		$this->externalQueue[] = $str;
	}

	/**
	 * @return string|null
	 */
	public function readThreadToMainPacket(){
		return $this->externalQueue->shift();
	}

	public function shutdownHandler(){
		if($this->shutdown !== true){
			$this->getLogger()->emergency("[ServerThread #". \Thread::getCurrentThreadId() ."] ServerThread crashed!");
		}
	}

	public function run(){
		//Load removed dependencies, can't use require_once()
		foreach($this->loadPaths as $name => $path){
			if(!class_exists($name, false) and !interface_exists($name, false)){
				require($path);
			}
		}
		$this->loader->register();

		register_shutdown_function([$this, "shutdownHandler"]);

		$data = unserialize($this->data);
		$manager = new ServerManager($this, $this->port, $this->interface, $data["motd"], $data["icon"]);
	}
}
