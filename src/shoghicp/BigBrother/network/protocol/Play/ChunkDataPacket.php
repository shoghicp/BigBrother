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

namespace shoghicp\BigBrother\network\protocol\Play;

use shoghicp\BigBrother\network\Packet;
use shoghicp\BigBrother\utils\ConvertUtils;
use pocketmine\nbt\NBT;

class ChunkDataPacket extends Packet{

	public $chunkX;
	public $chunkZ;
	public $groundUp;
	public $primaryBitmap;
	public $payload;
	public $biomes;
	public $blockEntities = [];

	public function pid(){
		return 0x20;
	}

	public function encode(){
		$this->putInt($this->chunkX);
		$this->putInt($this->chunkZ);
		$this->putByte($this->groundUp ? 1 : 0);
		$this->putVarInt($this->primaryBitmap);
		if($this->groundUp){
			$this->putVarInt(strlen($this->payload.$this->biomes));
			$this->put($this->payload);
			$this->put($this->biomes);
		}else{
			$this->putVarInt(strlen($this->payload));
			$this->put($this->payload);
		}
		$this->putVarInt(count($this->blockEntities));

		foreach($this->blockEntities as $blockEntity){
			ConvertUtils::convertNBTData(true, $blockEntity);
			$this->put($blockEntity);
		}
	}

	public function decode(){

	}
}