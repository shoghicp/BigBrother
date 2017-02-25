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
				[243, 0], [3, 2] //Podzol
			],
			[
				[198, -1], [208, -1] //Grass Path
			],
			[
				[247, -1], [19, 0] //Nether Reactor Core is Sponge
			],
			[
				[157, -1], [125, -1] //Double slab
			],
			[
				[158, -1], [126, -1] //Stairs
			],
			[
				[208, 0], [198, 0] //End Rod
			],
			[
				[241, -1], [95, -1] //Stained Glass
			],
			[
				[182, 1], [205, 0] //Purpur Slab
			],
			[
				[181, 1], [204, 0] //Double Purpur Slab
			],
			[
				[95, 0], [166, 0] //Double Purpur Slab
			]
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

			//TODO: blocklight and skylight
			$blocklight = $subChunk->getBlockLightArray();
			$skylight = $subChunk->getSkyLightArray();

			for($y = 0; $y < 16; ++$y){
				for($z = 0; $z < 16; ++$z){
					$data = "";
					$chunklight = "";
					$chunkskylight = "";
					$shift = false;

					for($x = 0; $x < 16; ++$x){
						$blockid = $subChunk->getBlockId($x, $y, $z);
						$blockdata = $subChunk->getBlockData($x, $y, $z);
						//$lightdata = $subChunk->getBlockLight($x, $y, $z);
						//$skylightdata = $subChunk->getBlockSkyLight($x, $y, $z);

						$this->convertPEToPCBlockData($blockid, $blockdata);
						$block = (int) ($blockid << 4) | $blockdata;

						if(($key = array_search($block, $palette, true)) !== false){
							$data .= chr($key);//bit
						}else{
							$key = count($palette);
							$palette[$key] = $block;

							$data .= chr($key);//bit
						}

						/*if(!$shift){
							$bak_light = $lightdata;
							$bak_skylight = $skylightdata;
							$shift = true;
						}else{
							$chunklight .= (chr($bak_light << 4) | $lightdata);
							$chunkskylight .= chr(($bak_skylight << 4) | $skylightdata);
							$shift = false;
						}*/

						if($x === 7 or $x === 15){//Reset ChunkData
							$chunkdata .= strrev($data);
							/*$blocklight .= strrev($chunklight);
							$skylight .= strrev($chunkskylight);*/
							$data = "";
							/*$chunklight = "";
							$chunkskylight = "";*/
						}
					}
				}
			}

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
			$payload .= $blocklight;

			/* Sky Light Only overworld */
			if($this->player->bigBrother_getDimension() === 0){
				$payload .= $skylight;
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
