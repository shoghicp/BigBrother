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

namespace shoghicp\BigBrother\utils;

use pocketmine\item\Item;
use pocketmine\entity\Human;
use pocketmine\entity\Projectile;
use pocketmine\nbt\NBT;
use pocketmine\nbt\tag\Tag;
use pocketmine\utils\BinaryStream;
use pocketmine\tile\Tile;
use shoghicp\BigBrother\BigBrother;

class ConvertUtils{
	public static $idlist = [
		[
			[243, 0], [3, 2] //Podzol
		],
		[
			[198, -1], [208, -1] //Grass Path
		],
		[
			[247, -1], [49, 0] //Nether Reactor core is now a obsidian
		],
		[
			[157, -1], [125, -1] //Double slab
		],
		[
			[158, -1], [126, -1] //Stairs
		],
		//******** End Rod ********//
		[
			[208, 0], [198, 0]
		],
		[
			[208, 1], [198, 1]
		],
		[
			[208, 2], [198, 3]
		],
		[
			[208, 3], [198, 2]
		],
		[
			[208, 4], [198, 4]
		],
		[
			[208, 5], [198, 5]
		],
		//*************************//
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
			[95, 0], [166, 0] //Extended Piston is now a barrier
		],
		[
			[325, 8], [326, 0] //Water bucket
		],
		[
			[325, 10], [327, 0] //Lava bucket
		],
		[
			[325, 1], [335, 0] //Milk bucket
		],
		[
			[43, 6], [43, 7] //Double Quartz Slab
		],
		[
			[43, 7], [43, 6] //Double Nether Brick Slab
		],
		[
			[44, 6], [44, 7] //Quartz Slab
		],
		[
			[44, 7], [44, 6] //Nether Brick Slab
		],
		[
			[44, 14], [44, 15] //Upper Quartz Slab
		],
		[
			[44, 15], [44, 14] //Upper Nether Brick Slab
		],
		[
			[168, 1], [168, 2] //Dark Prismarine
		],
		[
			[168, 2], [168, 1] //Prismarine Bricks
		],
		[
			[201, 1], [201, 0] //Unused Purpur Block
		],
		[
			[201, 2], [202, 0] //Pillar Purpur Block
		],
		[
			[85, 1], [188, 0] //Spruce Fence
		],
		[
			[85, 2], [189, 0] //Birch Fence
		],
		[
			[85, 3], [190, 0] //Jungle Fence
		],
		[
			[85, 4], [192, 0] //Acacia Fence
		],
		[
			[85, 5], [191, 0] //Dark Oak Fence
		],
		[
			[240, 0], [199, 0] //Chorus Plant
		],
		[
			[199, -1], [68, -1] //Item Frame is temporaly a standing sign | TODO: Convert Item Frame block to its entity. #blamemojang
		]
		/*
		[
			[PE], [PC]
		],
		*/
	];

	/*
	* $iscomputer = true is PE => PC
	* $iscomputer = false is PC => PE
	*/
	public static function convertNBTData($iscomputer, &$nbt, $convert = false){
		if($iscomputer){
			$stream = new BinaryStream();
			$stream->putByte($nbt->getType());

			if($nbt->getType() !== NBT::TAG_End){
				$stream->putShort(strlen($nbt->getName()));
				$stream->put($nbt->getName());
			}

			if($nbt->getType() === NBT::TAG_Compound){
				foreach($nbt as $tag){
					if($nbt["id"] === Tile::SIGN){
						if($tag->getType() === NBT::TAG_String){
							$convert = true;
						}else{
							$convert = false;
						}
					}else{
						$convert = false;
					}
					self::convertNBTData(true, $tag, $convert);
					$stream->buffer .= $tag;
				}

				$stream->putByte(0);
			}else{
				switch($nbt->getType()){
					case NBT::TAG_End: //No named tag
					break;
					case NBT::TAG_Byte:
						$stream->putByte($nbt->getValue());
					break;
					case NBT::TAG_Short:
						$stream->putShort($nbt->getValue());
					break;
					case NBT::TAG_Int:
						$stream->putInt($nbt->getValue());
					break;
					case NBT::TAG_Long:
						$stream->putLong($nbt->getValue());
					break;
					case NBT::TAG_Float:
						$stream->putFloat($nbt->getValue());
					break;
					case NBT::TAG_Double:
						$stream->putDouble($nbt->getValue());
					break;
					case NBT::TAG_ByteArray:
						$stream->putInt(strlen($nbt->getValue()));
						$stream->put($nbt->getValue());
					break;
					case NBT::TAG_String:
						if($convert){
							$value = BigBrother::toJSON($nbt->getValue());
							$stream->putShort(strlen($value));
							$stream->put($value);
						}else{
							$stream->putShort(strlen($nbt->getValue()));
							$stream->put($nbt->getValue());
						}
					break;
					case NBT::TAG_List:
						$id = null;
						foreach($nbt as $tag){
							if($tag instanceof Tag){
								if(!isset($id)){
									$id = $tag->getType();
								}elseif($id !== $tag->getType()){
									return false;
								}
							}
						}

						$stream->putByte($id);

						$tags = [];
						foreach($nbt as $tag){
							if($tag instanceof Tag){
								$tags[] = $tag;
							}
						}
						$stream->putInt(count($tags));

						foreach($tags as $tag){
							self::convertNBTData(true, $tag);
							$stream->buffer .= $tag;
						}
					break;
					case NBT::TAG_IntArray:
						$stream->putInt(count($nbt->getValue()));
						$stream->put(pack("N*", ...$nbt->getValue()));
					break;
				}
			}

			$nbt = $stream->getBuffer();
		}else{
			//TODO
		}
	}

	/*
	* $iscomputer = true is PE => PC
	* $iscomputer = false is PC => PE
	*/
	public static function convertItemData($iscomputer, &$item){
		if($iscomputer){
			$itemid = $item->getId();
			$itemdamage = $item->getDamage();
			$itemcount = $item->getCount();
			$itemnbt = $item->getCompoundTag();

			foreach(self::$idlist as $convertitemdata){
				if($convertitemdata[0][0] === $item->getId()){
					if($convertitemdata[0][1] === -1){
						$itemid = $convertitemdata[1][0];
						if($convertitemdata[1][1] !== -1){
							$itemdamage = $convertitemdata[1][1];
						}else{
							$itemdamage = $item->getDamage();
						}
						break;
					}elseif($convertitemdata[0][1] === $item->getDamage()){
						$itemid = $convertitemdata[1][0];
						$itemdamage = $convertitemdata[1][1];
						break;
					}
				}
			}

			$item = new ComputerItem($itemid, $itemdamage, $itemcount, $itemnbt);
		}else{
			$itemid = $item->getId();
			$itemdamage = $item->getDamage();
			$itemcount = $item->getCount();
			$itemnbt = $item->getCompoundTag();

			foreach(self::$idlist as $convertitemdata){
				if($convertitemdata[1][0] === $item->getId()){
					if($convertitemdata[1][1] === -1){
						$itemid = $convertitemdata[0][0];
						if($convertitemdata[0][1] !== -1){
							$itemdamage = $convertitemdata[0][1];
						}else{
							$itemdamage = $item->getDamage();
						}
						break;
					}elseif($convertitemdata[1][1] === $item->getDamage()){
						$itemid = $convertitemdata[0][0];
						$itemdamage = $convertitemdata[0][1];
						break;
					}
				}
			}

			$item = Item::get($itemid, $itemdamage, $itemcount, $itemnbt);
		}
	}

	/*
	* $iscomputer = true is PE => PC
	* $iscomputer = false is PC => PE
	*/
	public static function convertBlockData($iscomputer, &$blockid, &$blockdata){
		if($iscomputer){
			foreach(self::$idlist as $convertblockdata){
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
		}else{
			foreach(self::$idlist as $convertblockdata){
				if($convertblockdata[1][0] === $blockid){
					if($convertblockdata[1][1] === -1){
						$blockid = $convertblockdata[0][0];
						if($convertblockdata[0][1] !== -1){
							$blockdata = $convertblockdata[0][1];
						}
						break;
					}elseif($convertblockdata[1][1] === $blockdata){
						$blockid = $convertblockdata[0][0];
						$blockdata = $convertblockdata[0][1];
						break;
					}
				}
			}
		}
	}

	public static function convertPEToPCMetadata(array $olddata){
		$newdata = [];

		foreach($olddata as $bottom => $d){
			switch($bottom){
				case Human::DATA_FLAGS://Flags
					$flags = 0;

					if(((int) $d[1] & (1 << Human::DATA_FLAG_ONFIRE)) > 0){
						$flags |= 0x01;
					}

					if(((int) $d[1] & (1 << Human::DATA_FLAG_SNEAKING)) > 0){
						$flags |= 0x02;
					}

					if(((int) $d[1] & (1 << Human::DATA_FLAG_SPRINTING)) > 0){
						$flags |= 0x08;
					}

					if(((int) $d[1] & (1 <<  Human::DATA_FLAG_INVISIBLE)) > 0){
						//$flags |= 0x20;
					}

					if(((int) $d[1] & (1 <<  Human::DATA_FLAG_SILENT)) > 0){
						$newdata[4] = [6, true];
					}

					if(((int) $d[1] & (1 <<  Human::DATA_FLAG_IMMOBILE)) > 0){
						//$newdata[11] = [0, true];
					}

					$newdata[0] = [0, $flags];
				break;
				case Human::DATA_AIR://Air
					$newdata[1] = [1, $d[1]];
				break;
				case Human::DATA_NAMETAG://Custom name
					$newdata[2] = [3, str_replace("\n", "", $d[1])];//TODO
					$newdata[3] = [6, true];
				break;
				case Human::DATA_PLAYER_FLAGS:
				case Human::DATA_PLAYER_BED_POSITION:
				case Human::DATA_LEAD_HOLDER_EID:
				case Human::DATA_SCALE:
				case Human::DATA_MAX_AIR:
				case Projectile::DATA_SHOOTER_ID:
					//Unused
				break;
				default:
					echo "key: ".$bottom." Not implemented\n";
				break;
				//TODO: add data type
			}
		}

		$newdata["convert"] = true;

		return $newdata;
	}

}


class ComputerItem{
	public $id = 0, $damage = 0, $count = 0, $nbt = "";

	public function __construct($id = 0, $damage = 0, $count = 1, $nbt = ""){
		$this->id = $id;
		$this->damage = $damage;
		$this->count = $count;
		$this->nbt = $nbt;
	}

	public function getID(){
		return $this->id;
	}

	public function getDamage(){
		return $this->damage;
	}

	public function getCount(){
		return $this->count;
	}

	public function getCompoundTag(){
		return $this->nbt;
	}

}
