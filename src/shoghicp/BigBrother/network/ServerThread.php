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

class ServerThread extends \Thread{

	protected $port;
	protected $interface;
	/** @var \ThreadedLogger */
	protected $logger;
	protected $loader;

	public $loadPaths = [];

	protected $shutdown;

	protected $externalSocket;
	protected $internalSocket;

	/**
	 * @param \ThreadedLogger $logger
	 * @param \SplAutoloader  $loader
	 * @param int             $port 1-65536
	 * @param string          $interface
	 *
	 * @throws \Exception
	 */
	public function __construct(\ThreadedLogger $logger, \SplAutoloader $loader, $port, $interface = "0.0.0.0"){
		$this->port = (int) $port;
		if($port < 1 or $port > 65536){
			throw new \Exception("Invalid port range");
		}

		$this->interface = $interface;
		$this->logger = $logger;
		$this->loader = $loader;
		$loadPaths = [];
		$this->addDependency($loadPaths, new \ReflectionClass($logger));
		$this->addDependency($loadPaths, new \ReflectionClass($loader));
		$this->loadPaths = array_reverse($loadPaths);
		$this->shutdown = false;

		$sockets = [];
		if(($sockets = stream_socket_pair((strtoupper(substr(PHP_OS, 0, 3)) === 'WIN' ? STREAM_PF_INET : STREAM_PF_UNIX), STREAM_SOCK_STREAM, STREAM_IPPROTO_IP)) === false){
			throw new \Exception("Could not create IPC streams. Reason: ".socket_strerror(socket_last_error()));
		}

		$this->internalSocket = $sockets[0];
		stream_set_blocking($this->internalSocket, 1);
		$this->externalSocket = $sockets[1];
		stream_set_blocking($this->externalSocket, 0);
		@stream_set_write_buffer($this->externalSocket, 1024 * 1024 * 8);

		$this->start(PTHREADS_INHERIT_ALL & ~PTHREADS_INHERIT_CLASSES);
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

	public function isShutdown(){
		return $this->shutdown === true;
	}

	public function shutdown(){
		$this->lock();
		$this->shutdown = true;
		socket_close($this->internalSocket);
		$this->unlock();
	}

	public function getPort(){
		return $this->port;
	}

	public function getInterface(){
		return $this->interface;
	}

	/**
	 * @return \ThreadedLogger
	 */
	public function getLogger(){
		return $this->logger;
	}

	public function getExternalIPC(){
		return $this->externalSocket;
	}

	public function getInternalIPC(){
		return $this->internalSocket;
	}

	public function run(){
		//Load removed dependencies, can't use require_once()
		foreach($this->loadPaths as $name => $path){
			if(!class_exists($name, false) and !interface_exists($name, false)){
				require($path);
			}
		}
		$this->loader->register();

		$manager = new ServerManager($this, $this->port, $this->interface);
	}
}