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

use pocketmine\level\format\leveldb\Chunk;
use pocketmine\level\Level;
use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;
use pocketmine\utils\Binary;
use shoghicp\BigBrother\DesktopPlayer;

class LevelDBToAnvil extends AsyncTask{

	protected $playerName;

	protected $chunkX;
	protected $chunkZ;

	public $blockIds;
	public $blockData;
	public $blockSkyLight;
	public $blockLight;

	protected $biomeIds;
	protected $compressionLevel;


	public function __construct(DesktopPlayer $player, Chunk $chunk){
		$this->playerName = $player->getName();

		$this->chunkX = $chunk->getX();
		$this->chunkZ = $chunk->getZ();

		$this->blockIds = $chunk->getBlockIdArray();
		$this->blockData = $chunk->getBlockDataArray();
		$this->blockSkyLight = $chunk->getBlockSkyLightArray();
		$this->blockLight = $chunk->getBlockLightArray();

		$this->biomeIds = $chunk->getBiomeIdArray();

		$this->compressionLevel = Level::$COMPRESSION_LEVEL;
	}

	public function onRun(){
		$ids = ["", "", "", "", "", "", "", ""];
		$blockLight = $skyLight = [[], [], [], [], [], [], [], []];

		//Complexity: O(MG)
		for($Y = 0; $Y < 8; ++$Y){
			for($y = 0; $y < 16; ++$y){
				$offset = ($Y << 4) + $y;
				for($z = 0; $z < 16; ++$z){
					for($x = 0; $x < 16; ++$x){
						$index = ($x << 11) + ($z << 7) + $offset;
						$halfIndex = ($x << 10) + ($z << 6) + ($offset >> 1);
						if(($y & 1) === 0){
							$data = ord($this->blockData[$halfIndex]) & 0x0F;
							$bLight = ord($this->blockLight[$halfIndex]) & 0x0F;
							//$sLight = ord($this->blockSkyLight[$halfIndex]) & 0x0F;
						}else{
							$data = ord($this->blockData[$halfIndex]) >> 4;
							$bLight = ord($this->blockLight[$halfIndex]) >> 4;
							//$sLight = ord($this->blockSkyLight[$halfIndex]) >> 4;
						}
						$ids[$Y] .= pack("v", (ord($this->blockIds[$index]) << 4) | $data);

						$blockLight[$Y][] = $bLight;
						//$skyLight[$Y][] = $sLight;
					}
				}
			}
		}

		foreach($blockLight as $Y => $data){
			$final = "";
			$len = count($data);
			for($i = 0; $i < $len; $i += 2){
				$final .= chr(($data[$i + 1] << 4) | $data[$i]);
			}
			$blockLight[$Y] = $final;
		}

		/*
		foreach($skyLight as $Y => $data){
			$final = "";
			$len = count($data);
			for($i = 0; $i < $len; $i += 2){
				$final .= chr(($data[$i + 1] << 4) | $data[$i]);
			}
			$skyLight[$Y] = $final;
		}
		*/

		$skyLight = [$half = str_repeat("\xff", 4096), $half, $half, $half, $half, $half, $half, $half];

		$this->setResult(implode($ids) . implode($blockLight) . implode($skyLight) . $this->biomeIds);
	}

	public function onCompletion(Server $server){
		$player = $server->getPlayerExact($this->playerName);
		if($player instanceof DesktopPlayer){
			if(($payload = $this->getResult()) !== null){
				$player->bigBrother_sendChunk($this->chunkX, $this->chunkZ, $payload);
			}
		}
	}
}