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

declare(strict_types=1);

namespace shoghicp\BigBrother\network\protocol\Play\Server;

use shoghicp\BigBrother\network\OutboundPacket;
use shoghicp\BigBrother\utils\ConvertUtils;
use shoghicp\BigBrother\BigBrother;
use pocketmine\tile\Tile;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\nbt\tag\ShortTag;

class ChunkDataPacket extends OutboundPacket{

	/** @var int */
	public $chunkX;
	/** @var int */
	public $chunkZ;
	/** @var bool */
	public $groundUp;
	/** @var int */
	public $primaryBitmap;
	/** @var string */
	public $payload;
	/** @var string */
	public $biomes;
	/** @var array */
	public $blockEntities = [];

	public function pid() : int{
		return self::CHUNK_DATA_PACKET;
	}

	protected function encode() : void{
		$this->putInt($this->chunkX);
		$this->putInt($this->chunkZ);
		$this->putBool($this->groundUp);
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
			switch($blockEntity["id"]){
				case Tile::FLOWER_POT:
					$blockEntity->setTag(new ShortTag("Item", $blockEntity["item"]));
					$blockEntity->setTag(new IntTag("Data", $blockEntity["mData"]));

					$blockEntity->removeTag("item", "mdata");
				break;
				case Tile::SIGN:
					$textData = explode("\n", $blockEntity["Text"]);

					$blockEntity->setTag(new StringTag("Text1", BigBrother::toJSON($textData[0])));
					$blockEntity->setTag(new StringTag("Text2", BigBrother::toJSON($textData[1])));
					$blockEntity->setTag(new StringTag("Text3", BigBrother::toJSON($textData[2])));
					$blockEntity->setTag(new StringTag("Text4", BigBrother::toJSON($textData[3])));

					$blockEntity->removeTag("Text");
				break;
			}

			$this->put(ConvertUtils::convertNBTDataFromPEtoPC($blockEntity));
		}
	}
}
