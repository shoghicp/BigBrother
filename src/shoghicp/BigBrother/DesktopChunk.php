<?php 

namespace shoghicp\BigBrother;

use pocketmine\Player;
use pocketmine\level\Level;

class DesktopChunk{
	private $player, $chunkX, $chunkZ, $provider;

	public function __construct(Player $player, $chunkX, $chunkZ){
		$this->player = $player;
		$this->chunkX = $chunkX;
		$this->chunkZ = $chunkZ;
		$this->provider = $provider = $player->getLevel()->getProvider();
		$this->data = $this->generateChunk();
	}

	public function generateChunk(){
		$chunk = $this->provider->getChunk($this->chunkX, $this->chunkZ, false);
		$chunkblockIds = $chunk->getBlockIdArray();
		$chunkblockData = $chunk->getBlockDataArray();
		$chunkblockSkyLight = $chunk->getBlockSkyLightArray();
		$chunkblockLight = $chunk->getBlockLightArray();

		$chunkbiomeIds = $chunk->getBiomeIdArray();

		$compressionLevel = Level::$COMPRESSION_LEVEL;

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
							$data = ord($chunkblockData[$halfIndex]) & 0x0F;
							$bLight = ord($chunkblockLight[$halfIndex]) & 0x0F;

							//$sLight = ord($blockSkyLight[$halfIndex]) & 0x0F;
						}else{
							$data = ord($chunkblockData[$halfIndex]) >> 4;
							$bLight = ord($chunkblockLight[$halfIndex]) >> 4;
							//$sLight = ord($blockSkyLight[$halfIndex]) >> 4;
						}
						$ids[$Y] .= pack("v", (ord($chunkblockIds[$index]) << 4) | $data);

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

		$payload = implode($ids) . implode($blockLight) . implode($skyLight) . $chunkbiomeIds;

		return $payload;
	}

	public function getData(){
		if(isset($this->data)){
			return $this->data;
		}
		return null;
	}

}