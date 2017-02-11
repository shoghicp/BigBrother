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

			$palette = [];
			$bitsperblock = 8;//TODO

			$chunkdata = "";

			for($y = 0; $y < 16; ++$y){
				for($z = 0; $z < 16; ++$z){
					for($x = 0; $x < 16; ++$x){
						$blockid = $subChunk->getBlockId($x, $y, $z);
						$blockdata = $subChunk->getBlockData($x, $y, $z);


						$block = ($blockid << 4) | $blockdata;

						if(($key = array_search($block, $palette)) !== false){
							$chunkdata .= chr($key);
						}else{
							$key = count($palette);
							$palette[$key] = $block;

							$chunkdata .= chr($key);
							//var_dump(chr($key));
						}
					}
				}
			}









			/*				Bits Per Block		 Palette Length*/
			$payload .= Binary::writeByte($bitsperblock).Binary::writeVarInt(count($palette));

			foreach($palette as $num => $value){
				$payload .= Binary::writeVarInt($value);
			}

			$payload .= Binary::writeVarInt(strlen($chunkdata) / 8);

			$payload .= $chunkdata;

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