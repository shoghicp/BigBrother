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

namespace shoghicp\BigBrother\tasks;

use phpseclib\Crypt\RSA;
use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;
use shoghicp\BigBrother\BigBrother;

class GeneratePrivateKey extends AsyncTask{

	/** @var \ThreadedLogger */
	protected $logger;
	protected $loader;
	/** @var array */
	protected $loadPaths;

	public function __construct(\ThreadedLogger $logger, \ClassLoader $loader){
		$this->logger = $logger;
		$this->loader = $loader;
		$loadPaths = [];
		$this->addDependency($loadPaths, new \ReflectionClass($logger));
		$this->addDependency($loadPaths, new \ReflectionClass($loader));
		$this->loadPaths = array_reverse($loadPaths);
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

	public function onRun(){
		foreach($this->loadPaths as $name => $path){
			if(!class_exists($name, false) and !interface_exists($name, false)){
				require($path);
			}
		}
		$this->loader->register(true);

		$rsa = new RSA();
		$this->logger->info("[BigBrother] Generating keypair");
		$rsa->setPrivateKeyFormat(CRYPT_RSA_PRIVATE_FORMAT_PKCS1);
		$rsa->setPublicKeyFormat(CRYPT_RSA_PUBLIC_FORMAT_PKCS1);
		$keys = $rsa->createKey(1024);
		$this->setResult($keys);
	}

	public function onCompletion(Server $server){
		$plugin = $server->getPluginManager()->getPlugin("BigBrother");
		if($plugin instanceof BigBrother){
			if($plugin->isEnabled()){
				$result = $this->getResult();
				$plugin->receiveCryptoKeys($result["privatekey"], $result["publickey"]);
			}
		}
	}
}