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

	public function generateChunk(){
		$chunk = $this->provider->getChunk($this->chunkX, $this->chunkZ, false);
		$this->biomes = $chunk->getBiomeIdArray();

		$payload = "";

		$subChunkCount = $chunk->getSubChunkSendCount();
		foreach($chunk->getSubChunks() as $num => $subChunk){
			if($subChunk->isEmpty()){
				continue;
			}

			$this->bitmap |= 0x01 << $num;









			/*$chunkdata = "";
			$chunkblockData = $subChunk->getBlockIdArray();

			/*				Bits Per Block		 Palette Length*/
			$payload .= Binary::writeByte(12).Binary::writeVarInt(0);

			/*$chunkblockIds = $chunk->getBlockIdArray();
			$chunkblockData = $subChunk->getBlockDataArray();
			$shift = false;
			$dataoffset = 0;
			for($i = 0; $i < 4096; $i++){
				$chunkdata .= $chunkblockIds{$i};
				if($shift){
					//$chunkdata .= $chunkblockData{$dataoffset} >> 4;
					$test = $chunkblockData{$dataoffset} >> 4;
					/*echo base_convert($chunkblockData{$dataoffset}, 10, 2)."\n";
					echo base_convert($test, 10, 2)."\n";*//*
					$chunkdata .= $test;

					$shift = false;
					$dataoffset++;
				}else{
					$chunkdata .= $chunkblockData{$dataoffset} << 4;
					$shift = true;
				}
				//echo "chunkblockData: ".$dataoffset."\n";
				//$p
			}

			$payload .= Binary::writeVarInt(strlen($chunkdata) / 8);

			echo "chunkData: ".strlen($chunkdata)."\n";

			$payload .= $chunkdata;*/

			$payload .= Binary::writeVarInt(512);

			$payload .= str_repeat("\x01", 4092);
			

			$payload .= $subChunk->getBlockLightArray();

			if($this->player->bigBrother_getDimension() === 0){
				$payload .= $subChunk->getSkyLightArray();
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