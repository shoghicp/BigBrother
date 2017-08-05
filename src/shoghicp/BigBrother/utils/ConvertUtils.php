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

namespace shoghicp\BigBrother\utils;

use pocketmine\item\Item;
use pocketmine\block\Block;
use pocketmine\entity\Human;
use pocketmine\entity\Projectile;
use pocketmine\event\TimingsHandler;
use pocketmine\nbt\NBT;
use pocketmine\nbt\tag\Tag;
use pocketmine\utils\BinaryStream;
use pocketmine\tile\Tile;
use shoghicp\BigBrother\BigBrother;

class ConvertUtils{
	private static $timingConvertItem;
	private static $timingConvertBlock;

	private static $idlist = [
		//************** ITEMS ***********//
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
			[450, 0], [449, 0] //Totem of Undying
		],
		[
			[444, 0], [443, 0] //Elytra
		],
		[
			[443, 0], [422, 0] //Minecart with Command Block
		],
		[
			[333, 1], [444, 0] //Spruce Boat
		],
		[
			[333, 2], [445, 0] //Birch Boat
		],
		[
			[333, 3], [446, 0] //Jungle Boat
		],
		[
			[333, 4], [447, 0] //Acacia Boat
		],
		[
			[333, 5], [448, 0] //Dark Oak Boat
		],
		[
			[445, 5], [448, 0] //Dark Oak Boat
		],
		[
			[445, 0], [450, 0] //Shulker Shell
		],
		[
			[125, -1], [158, -1] //Dropper
		],
		[
			[410, -1], [154, -1] //Hopper
		],
		//******** Tipped Arrows *******//
		/*[
			[262, -1], [440, -1] //TODO: Fix that
		],*/
		//*******************************//
		[
			[458, 0], [435, 0] //Beetroot Seeds
		],
		[
			[459, 0], [436, 0] //Beetroot Soup
		],
		[
			[460, 0], [349, 1] //Raw Salmon
		],
		[
			[461, 0], [349, 2] //Clownfish
		],
		[
			[462, 0], [350, 3] //Pufferfish
		],
		[
			[463, 0], [350, 1] //Cooked Salmon
		],
		[
			[466, 0], [422, 1] //Enchanted Golden Apple
		],
		//********************************//


		//************ BLOCKS *************//
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
		],
		[
			[236, -1], [252, -1] //Concretes
		],
		//******** Glazed Terracota ********//
		[
			[220, 0], [235, 0]
		],
		[
			[221, 0], [236, 0]
		],
		[
			[222, 0], [237, 0]
		],
		[
			[223, 0], [238, 0]
		],
		[
			[224, 0], [239, 0]
		],
		[
			[225, 0], [240, 0]
		],
		[
			[226, 0], [241, 0]
		],
		[
			[227, 0], [242, 0]
		],
		[
			[228, 0], [243, 0]
		],
		[
			[229, 0], [244, 0]
		],
		[
			[219, 0], [245, 0]
		],
		[
			[231, 0], [246, 0]
		],
		[
			[232, 0], [247, 0]
		],
		[
			[233, 0], [248, 0]
		],
		[
			[234, 0], [249, 0]
		],
		[
			[235, 0], [250, 0]
		],
		//*************************//
		[
			[251, -1], [218, -1] //Observer
		],
		//******** Shulker Box ********//
		//dude mojang, whyy
		[
			[218, 0], [219, 0]
		],
		[
			[218, 1], [220, 0]
		],
		[
			[218, 2], [221, 0]
		],
		[
			[218, 3], [222, 0]
		],
		[
			[218, 4], [223, 0]
		],
		[
			[218, 5], [224, 0]
		],
		[
			[218, 6], [225, 0]
		],
		[
			[218, 7], [226, 0]
		],
		[
			[218, 8], [227, 0]
		],
		[
			[218, 9], [228, 0]
		],
		[
			[218, 10], [229, 0]
		],
		[
			[218, 11], [230, 0]
		],
		[
			[218, 12], [231, 0]
		],
		[
			[218, 13], [232, 0]
		],
		[
			[218, 14], [233, 0]
		],
		[
			[218, 15], [234, 0]
		],
		//*************************//
		[
			[188, -1], [210, -1] //Repeating Command Block
		],
		[
			[189, -1], [211, -1] //Chain Command Block
		],
		[
			[244, -1], [207, -1] //Beetroot Block
		],
		[
			[207, -1], [212, -1] //Frosted Ice
		],
		[
			[245, -1], [61, -1] //Stonecutter - To avoid problems, it's now a simple furnace
		],
		//******************************//
		/*
		[
			[PE], [PC]
		],
		*/
	];
	private static $idlistIndex = [
		[/* Index for PE => PC */],
		[/* Index for PC => PE */],
	];


	public static function init(){
		self::$timingConvertItem = new TimingsHandler("BigBrother - Convert Item Data");
		self::$timingConvertBlock = new TimingsHandler("BigBrother - Convert Block Data");

		foreach(self::$idlist as $entry){
			//append index (PE => PC)
			if(isset(self::$idlistIndex[0][$entry[0][0]])){
				self::$idlistIndex[0][$entry[0][0]][] = $entry;
			}else{
				self::$idlistIndex[0][$entry[0][0]] = [$entry];
			}

			//append index (PC => PE)
			if(isset(self::$idlistIndex[1][$entry[1][0]])){
				self::$idlistIndex[1][$entry[1][0]][] = $entry;
			}else{
				self::$idlistIndex[1][$entry[1][0]] = [$entry];
			}
		}
	}

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
						$stream->put(pack("d", $nbt->getValue()));
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
		self::$timingConvertItem->startTiming();

		$itemid = $item->getId();
		$itemdamage = $item->getDamage();
		$itemcount = $item->getCount();
		$itemnbt = $item->getCompoundTag();

		switch($itemid){
			case Item::PUMPKIN:
			case Item::JACK_O_LANTERN:
				$itemdamage = 0;
			break;
			default:
				if($iscomputer){
					$src = 0; $dst = 1;
				}else{
					$src = 1; $dst = 0;
				}

				foreach(self::$idlistIndex[$src][$itemid] ?? [] as $convertitemdata){
					if($convertitemdata[$src][1] === -1){
						$itemid = $convertitemdata[$dst][0];
						if($convertitemdata[$dst][1] === -1){
							$itemdamage = $item->getDamage();
						}else{
							$itemdamage = $convertitemdata[$dst][1];
						}
						break;
					}elseif($convertitemdata[$src][1] === $item->getDamage()){
						$itemid = $convertitemdata[$dst][0];
						$itemdamage = $convertitemdata[$dst][1];
						break;
					}
				}
			break;
		}

		if($iscomputer){
			$item = new ComputerItem($itemid, $itemdamage, $itemcount, $itemnbt);
		}else{
			$item = Item::get($itemid, $itemdamage, $itemcount, $itemnbt);
		}

		self::$timingConvertItem->stopTiming();
	}

	/*
	 * $iscomputer = true is PE => PC
	 * $iscomputer = false is PC => PE
	 */
	public static function convertBlockData($iscomputer, &$blockid, &$blockdata){
		self::$timingConvertBlock->startTiming();

		switch($blockid){
			case Block::WOODEN_TRAPDOOR:
			case Block::IRON_TRAPDOOR:
				self::convertTrapdoor($iscomputer, $blockid, $blockdata);
			break;
			default:
				if($iscomputer){
					$src = 0; $dst = 1;
				}else{
					$src = 1; $dst = 0;
				}

				foreach(self::$idlistIndex[$src][$blockid] ?? [] as $convertblockdata){
					if($convertblockdata[$src][1] === -1){
						$blockid = $convertblockdata[$dst][0];
						if($convertblockdata[$dst][1] !== -1){
							$blockdata = $convertblockdata[$dst][1];
						}
						break;
					}elseif($convertblockdata[$src][1] === $blockdata){
						$blockid = $convertblockdata[$dst][0];
						$blockdata = $convertblockdata[$dst][1];
						break;
					}
				}
			break;
		}

		self::$timingConvertBlock->stopTiming();
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
				case Human::DATA_FUSE_LENGTH:
					$newdata[6] = [1, $d[1]];
				break;
				case Human::DATA_PLAYER_FLAGS:
				case Human::DATA_PLAYER_BED_POSITION:
				case Human::DATA_LEAD_HOLDER_EID:
				case Human::DATA_SCALE:
				case Human::DATA_MAX_AIR:
				case Human::DATA_OWNER_EID:
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

	/*
	 * Blame Mojang!! :-@
	 * Why Mojang change the order of flag bits?
	 * Why Mojang change the directions??
	 *
	 * #blamemojang
	 */
	private static function convertTrapdoor(bool $iscomputer, int &$blockid, int &$blockdata){
		//swap bits
		$blockdata ^= (($blockdata & 0x04) << 1);
		$blockdata ^= (($blockdata & 0x08) >> 1);
		$blockdata ^= (($blockdata & 0x04) << 1);

		//swap directions
		$directions = [
			0 => 3,
			1 => 2,
			2 => 1,
			3 => 0
		];

		$blockdata = (($blockdata >> 2) << 2) | $directions[$blockdata & 0x03];
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
