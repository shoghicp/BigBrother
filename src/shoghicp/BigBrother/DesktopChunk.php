<?php 

namespace shoghicp\BigBrother;

use pocketmine\Player;
use pocketmine\level\Level;
use shoghicp\BigBrother\utils\Binary;

class DesktopChunk{
	private $player, $chunkX, $chunkZ, $provider, $groundup, $bitmap, $biomes;

	public function __construct(Player $player, $chunkX, $chunkZ){
		$this->player = $player;
		$this->chunkX = $chunkX;
		$this->chunkZ = $chunkZ;
		$this->provider = $player->getLevel()->getProvider();
		$this->groundup = true;
		$this->bitmap = 0;
		$this->biomes = null;
		$this->data = $this->generateChunk();
	}

	public function convertPEToPCBlockData(&$blockid, &$blockdata){//TODO: Move to Class or rewrite easy
		$blockidlist = [
			[
				[243, 0], [3, 2]
			],
			[
				[198, 0], [208, 0]
			],
			[
				[247, -1], [19, 0]//Nether Reactor Core is Sponge
			],
			[
				[157, -1], [125, -1]
			],
			[
				[158, -1], [126, -1]
			],
			/*
			[
				[PE], [PC]
			],
			*/
		];

		foreach($blockidlist as $convertblockdata){
			if($convertblockdata[0][0] === $blockid){
				if($convertblockdata[0][1] === -1){
					$blockid = $convertblockdata[1][0];
					if($convertblockdata[1][1] !== -1){
						$blockdata = $convertblockdata[1][1];
					}
					break;
				}elseif($convertblockdata[0][1] === $blockdata){
					$blockid = $convertblockdata[1][0];
					$blockdata = $convertblockdata[1][1];
					break;
				}
			}
		}
		
	}

	public function generateChunk(){
		$chunk = $this->provider->getChunk($this->chunkX, $this->chunkZ, false);
		$this->biomes = $chunk->getBiomeIdArray();

		$payload = "";
		
		foreach($chunk->getSubChunks() as $num => $subChunk){
			if($subChunk->isEmpty()){
				continue;
			}

			$this->bitmap |= 0x01 << $num;

			$palette = [];
			$bitsperblock = 8;//TODO

			$chunkdata = "";
			$blocklight = "";
			$skylight = "";

			for($y = 0; $y < 16; ++$y){
				for($z = 0; $z < 16; ++$z){
					for($x = 0; $x < 16; ++$x){
						//for($x = 15; $x >= 0; --$x){
						//echo $x." : ".$y." : ".$z."\n";
						$blockid = $subChunk->getBlockId($x, $y, $z);
						$blockdata = $subChunk->getBlockData($x, $y, $z);

						//$blocklight .= $subChunk->getBlockLight($x, $y, $z);
						//$skylight .= $subChunk->getBlockSkyLight($x, $y, $z);

						$this->convertPEToPCBlockData($blockid, $blockdata);

						$block = (int) ($blockid << 4) | $blockdata;

						if(($key = array_search($block, $palette, true)) !== false){
							$chunkdata .= chr($key);//bit
						}else{
							$key = count($palette);
							$palette[$key] = $block;

							$chunkdata .= chr($key);//bit
						}
					}
				}
			}

			/*
			//Test Code (Don't use!)
			$chunkblockIds = $subChunk->getBlockIdArray();
			var_dump(strlen($chunkblockIds));
			$chunkblockData = $subChunk->getBlockDataArray();
			$shift = false;
			$dataoffset = 0;
			$blocknum = 0;//Remove it
			for($i = 0; $i < 4096; $i++){
				$blocknum++;
				if($shift){
					$blockdata = ord($chunkblockData{$dataoffset}) >> 4;

					$shift = false;
					$dataoffset++;
				}else{
					$blockdata = $chunkblockData{$dataoffset} & 0x0f;

					$shift = true;
				}

				$blockid = ord($chunkblockIds{$i});

				$this->convertPEToPCBlockData($blockid, $blockdata);

				$block = (int) ($blockid << 4) | $blockdata;

				if(($key = array_search($block, $palette, true)) !== false){
					$chunkdata .= chr($key);//bit
				}else{
					$key = count($palette);
					$palette[$key] = $block;

					$chunkdata .= chr($key);//bit
				}
			}*/

			/* Bits Per Block & Palette Length */
			$payload .= Binary::writeByte($bitsperblock).Binary::writeVarInt(count($palette));

			/* Palette */
			foreach($palette as $num => $value){
				$payload .= Binary::writeVarInt($value);
			}

			/* Data Array Length */
			$payload .= Binary::writeVarInt(strlen($chunkdata) / 8);

			/* Data Array */
			$payload .= $chunkdata;

			/* Block Light*/
			$payload .= $subChunk->getBlockLightArray();
			//$payload .= str_repeat("\xff", 4096); //Need to check this.
			//$payload .= $blocklight;

			/* Sky Light Only overworld */
			if($this->player->bigBrother_getDimension() === 0){
				//$payload .= str_repeat("\xff", 4096); //Need to check this.
				$payload .= $subChunk->getSkyLightArray();
				//$payload .= $skylight;
			}
		}

		return $payload;
	}

	public function isGroundUp(){
		return $this->groundup;
	}

	public function getBitMapData(){
		return $this->bitmap;
	}

	public function getBiomesData(){
		return $this->biomes;
	}

	public function getChunkData(){
		if(isset($this->data)){
			return $this->data;
		}
		return null;
	}

}
