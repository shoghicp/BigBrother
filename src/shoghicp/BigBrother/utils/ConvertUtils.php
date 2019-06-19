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

namespace shoghicp\BigBrother\utils;

use pocketmine\item\Item;
use pocketmine\block\Block;
use pocketmine\entity\Human;
use pocketmine\entity\projectile\Projectile;
use pocketmine\nbt\NBT;
use pocketmine\nbt\tag\ByteArrayTag;
use pocketmine\nbt\tag\ByteTag;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\DoubleTag;
use pocketmine\nbt\tag\FloatTag;
use pocketmine\nbt\tag\IntArrayTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\LongTag;
use pocketmine\nbt\tag\NamedTag;
use pocketmine\nbt\tag\ShortTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\timings\TimingsHandler;
use pocketmine\utils\BinaryStream;
use pocketmine\utils\Binary;
use pocketmine\utils\Color;
use UnexpectedValueException;

class ConvertUtils{
	/** @var TimingsHandler */
	private static $timingConvertItem;
	/** @var TimingsHandler */
	private static $timingConvertBlock;

	/** @var array */
	private static $idList = [
		//************** ITEMS ***********//
		[[325,   8], [326,   0]], //Water bucket,
		[[325,  10], [327,   0]], //Lava bucket
		[[325,   1], [335,   0]], //Milk bucket
		[[450,   0], [449,   0]], //Totem of Undying
		[[444,   0], [443,   0]], //Elytra
		[[443,   0], [422,   0]], //Minecart with Command Block
		[[333,   1], [444,   0]], //Spruce Boat
		[[333,   2], [445,   0]], //Birch Boat
		[[333,   3], [446,   0]], //Jungle Boat
		[[333,   4], [447,   0]], //Acacia Boat
		[[333,   5], [448,   0]], //Dark Oak Boat
		[[445,   5], [448,   0]], //Dark Oak Boat
		[[445,   0], [450,   0]], //Shulker Shell
		[[125,  -1], [158,  -1]], //Dropper
		[[410,  -1], [154,  -1]], //Hopper
		[[425,  -1], [416,  -1]], //Armor Stand
		[[446,  -1], [425,  -1]], //Banner
		[[466,   0], [322,   1]], //Enchanted golden apple
		//************ Discs ***********//
		//NOTE: it's the real value, no joke
		[[500,   0], [2256,  0]],
		[[501,   0], [2257,  0]],
		[[502,   0], [2258,  0]],
		[[503,   0], [2258,  0]],
		[[504,   0], [2260,  0]],
		[[505,   0], [2261,  0]],
		[[506,   0], [2262,  0]],
		[[507,   0], [2263,  0]],
		[[508,   0], [2264,  0]],
		[[509,   0], [2265,  0]],
		[[510,   0], [2266,  0]],
		[[511,   0], [2267,  0]],
		//******** Tipped Arrows *******//
		/*
		[[262,  -1], [440,  -1]], //TODO
		*/
		//*******************************//
		[[458,   0], [435,   0]], //Beetroot Seeds
		[[459,   0], [436,   0]], //Beetroot Soup
		[[460,   0], [349,   1]], //Raw Salmon
		[[461,   0], [349,   2]], //Clown fish
		[[462,   0], [350,   3]], //Puffer fish
		[[463,   0], [350,   1]], //Cooked Salmon
		[[466,   0], [422,   1]], //Enchanted Golden Apple
		//********************************//


		//************ BLOCKS *************//
		[[243,   0], [  3,   2]], //Podzol
		[[198,  -1], [208,  -1]], //Grass Path
		[[247,  -1], [ 49,   0]], //Nether Reactor core is now a obsidian
		[[157,  -1], [125,  -1]], //Double slab
		[[158,  -1], [126,  -1]], //Stairs
		//******** End Rod ********//
		[[208,   0], [198,   0]],
		[[208,   1], [198,   1]],
		[[208,   2], [198,   3]],
		[[208,   3], [198,   2]],
		[[208,   4], [198,   4]],
		[[208,   5], [198,   5]],
		//*************************//
		[[241,  -1], [ 95,  -1]], //Stained Glass
		[[182,   1], [205,   0]], //Purpur Slab
		[[181,   1], [204,   0]], //Double Purpur Slab
		[[ 95,   0], [166,   0]], //Extended Piston is now a barrier
		[[ 43,   6], [ 43,   7]], //Double Quartz Slab
		[[ 43,   7], [ 43,   6]], //Double Nether Brick Slab
		[[ 44,   6], [ 44,   7]], //Quartz Slab
		[[ 44,   7], [ 44,   6]], //Nether Brick Slab
		[[ 44,  14], [ 44,  15]], //Upper Quartz Slab
		[[ 44,  15], [ 44,  14]], //Upper Nether Brick Slab
		[[155,  -1], [155,   0]], //Quartz Block | TODO: convert meta
		[[168,   1], [168,   2]], //Dark Prismarine
		[[168,   2], [168,   1]], //Prismarine Bricks
		[[201,   1], [201,   0]], //Unused Purpur Block
		[[201,   2], [202,   0]], //Pillar Purpur Block
		[[ 85,   1], [188,   0]], //Spruce Fence
		[[ 85,   2], [189,   0]], //Birch Fence
		[[ 85,   3], [190,   0]], //Jungle Fence
		[[ 85,   4], [192,   0]], //Acacia Fence
		[[ 85,   5], [191,   0]], //Dark Oak Fence
		[[240,   0], [199,   0]], //Chorus Plant
		[[199,  -1], [ 68,  -1]], //Item Frame is temporary a standing sign | TODO: Convert Item Frame block to its entity. #blamemojang
		[[252,  -1], [255,  -1]], //Structures Block
		[[236,  -1], [251,  -1]], //Concretes
		[[237,  -1], [252,  -1]], //Concretes Powder
		//******** Glazed Terracotta ********//
		[[220,   0], [235,   0]],
		[[221,   0], [236,   0]],
		[[222,   0], [237,   0]],
		[[223,   0], [238,   0]],
		[[224,   0], [239,   0]],
		[[225,   0], [240,   0]],
		[[226,   0], [241,   0]],
		[[227,   0], [242,   0]],
		[[228,   0], [243,   0]],
		[[229,   0], [244,   0]],
		[[219,   0], [245,   0]],
		[[231,   0], [246,   0]],
		[[232,   0], [247,   0]],
		[[233,   0], [248,   0]],
		[[234,   0], [249,   0]],
		[[235,   0], [250,   0]],
		//*************************//
		[[251,  -1], [218,  -1]], //Observer
		//******** Shulker Box ********//
		//dude mojang, whyy
		[[205,  -1], [229,  -1]], //Undyed
		[[218,   0], [219,   0]],
		[[218,   1], [220,   0]],
		[[218,   2], [221,   0]],
		[[218,   3], [222,   0]],
		[[218,   4], [223,   0]],
		[[218,   5], [224,   0]],
		[[218,   6], [225,   0]],
		[[218,   7], [226,   0]],
		[[218,   8], [227,   0]],
		[[218,   9], [228,   0]],
		[[218,  10], [229,   0]],
		[[218,  11], [230,   0]],
		[[218,  12], [231,   0]],
		[[218,  13], [232,   0]],
		[[218,  14], [233,   0]],
		[[218,  15], [234,   0]],
		//*************************//
		[[188,  -1], [210,  -1]], //Repeating Command Block
		[[189,  -1], [211,  -1]], //Chain Command Block
		[[244,  -1], [207,  -1]], //Beetroot Block
		[[207,  -1], [212,  -1]], //Frosted Ice
		[[  4,  -1], [  4,  -1]], //For Stonecutter
		[[245,  -1], [  4,  -1]] //Stonecutter - To avoid problems, it's now a stone block
		//******************************//
		/*
		[[  P  E  ], [  P  C  ]],
		*/
	];

	/** @var array */
	private static $idListIndex = [
		[/* Index for PE => PC */],
		[/* Index for PC => PE */],
	];

	/** @var array */
	private static $spawnEggList = [
		10 => "minecraft:chicken",
		11 => "minecraft:cow",
		12 => "minecraft:pig",
		13 => "minecraft:sheep",
		14 => "minecraft:wolf",
		15 => "minecraft:villager",
		16 => "minecraft:cow",
		17 => "minecraft:squid",
		18 => "minecraft:rabbit",
		19 => "minecraft:bat",
		20 => "minecraft:iron_golem",
		21 => "minecraft:snowman",
		22 => "minecraft:cat",
		23 => "minecraft:horse",
		28 => "minecraft:polar_bear",
		32 => "minecraft:zombie",
		33 => "minecraft:creeper",
		34 => "minecraft:skeleton",
		35 => "minecraft:spider",
		36 => "minecraft:zombie_pigman",
		37 => "minecraft:slime",
		38 => "minecraft:enderman",
		39 => "minecraft:silverfish",
		40 => "minecraft:spider",
		41 => "minecraft:ghast",
		42 => "minecraft:magmacube",
		43 => "minecraft:blaze",
		44 => "minecraft:zombie_village",
		45 => "minecraft:witch",
		46 => "minecraft:stray",
		47 => "minecraft:husk",
		48 => "minecraft:wither_skeleton",
		49 => "minecraft:guardian",
		50 => "minecraft:elder_guardian",
		53 => "minecraft:enderdragon",
		54 => "minecraft:shulker",
	];

	/** @var array */
	private static $reverseSpawnEggList;

	// TODO this color table is not up-to-date (please update me!!)
	/** @var array */
	private static $colorTable = [
		0x04 => [0x59, 0x7D, 0x27], // Grass
		0x05 => [0x6D, 0x99, 0x30], // Grass
		0x06 => [0x7F, 0xB2, 0x38], // Grass
		0x07 => [0x6D, 0x99, 0x30], // Grass
		0x08 => [0xAE, 0xA4, 0x73], // Sand
		0x09 => [0xD5, 0xC9, 0x8C], // Sand
		0x0A => [0xF7, 0xE9, 0xA3], // Sand
		0x0B => [0xD5, 0xC9, 0x8C], // Sand
		0x0C => [0x75, 0x75, 0x75], // Cloth
		0x0D => [0x90, 0x90, 0x90], // Cloth
		0x0E => [0xA7, 0xA7, 0xA7], // Cloth
		0x0F => [0x90, 0x90, 0x90], // Cloth
		0x10 => [0xB4, 0x00, 0x00], // Fire
		0x11 => [0xDC, 0x00, 0x00], // Fire
		0x12 => [0xFF, 0x00, 0x00], // Fire
		0x13 => [0xDC, 0x00, 0x00], // Find
		0x14 => [0x70, 0x70, 0xB4], // Ice
		0x15 => [0x8A, 0x8A, 0xDC], // Ice
		0x16 => [0xA0, 0xA0, 0xFF], // Ice
		0x17 => [0x8A, 0x8A, 0xDC], // Ice
		0x18 => [0x75, 0x75, 0x75], // Iron
		0x19 => [0x90, 0x90, 0x90], // Iron
		0x1A => [0xA7, 0xA7, 0xA7], // Iron
		0x1B => [0x90, 0x90, 0x90], // Iron
		0x1C => [0x00, 0x57, 0x00], // Foliage
		0x1D => [0x00, 0x6A, 0x00], // Foliage
		0x1E => [0x00, 0x7C, 0x00], // Foliage
		0x1F => [0x00, 0x6A, 0x00], // Foliage
		0x20 => [0xB4, 0xB4, 0xB4], // Snow
		0x21 => [0xDC, 0xDC, 0xDC], // Snow
		0x22 => [0xFF, 0xFF, 0xFF], // Snow
		0x23 => [0xDC, 0xDC, 0xDC], // Snow
		0x24 => [0x73, 0x76, 0x81], // Clay
		0x25 => [0x8D, 0x90, 0x9E], // Clay
		0x26 => [0xA4, 0xA8, 0xB8], // Clay
		0x27 => [0x8D, 0x90, 0x9E], // Clay
		0x28 => [0x81, 0x4A, 0x21], // Dirt
		0x29 => [0x9D, 0x5B, 0x28], // Dirt
		0x2A => [0xB7, 0x6A, 0x2F], // Dirt
		0x2B => [0x9D, 0x5B, 0x28], // Dirt
		0x2C => [0x4F, 0x4F, 0x4F], // Stone
		0x2D => [0x60, 0x60, 0x60], // Stone
		0x2E => [0x70, 0x70, 0x70], // Stone
		0x2F => [0x60, 0x60, 0x60], // Stone
		0x30 => [0x2D, 0x2D, 0xB4], // Water
		0x31 => [0x37, 0x37, 0xDC], // Water
		0x32 => [0x40, 0x40, 0xFF], // Water
		0x33 => [0x37, 0x37, 0xDC], // Water
		0x34 => [0x49, 0x3A, 0x23], // Wood
		0x35 => [0x59, 0x47, 0x2B], // Wood
		0x36 => [0x68, 0x53, 0x32], // Wood
		0x37 => [0x59, 0x47, 0x2B], // Wood
		0x38 => [0xB4, 0xB1, 0xAC], // Quartz, Sea Lantern, Birch Log
		0x39 => [0xDC, 0xD9, 0xD3], // Quartz, Sea Lantern, Birch Log
		0x3A => [0xFF, 0xFC, 0xF5], // Quartz, Sea Lantern, Birch Log
		0x3B => [0x87, 0x85, 0x81], // Quartz, Sea Lantern, Birch Log
		0x3C => [0x98, 0x59, 0x24], // Orange Wool/Glass/Stained Clay, Pumpkin, Hardened Clay, Acacia Plank
		0x3D => [0xBA, 0x6D, 0x2C], // Orange Wool/Glass/Stained Clay, Pumpkin, Hardened Clay, Acacia Plank
		0x3E => [0xD8, 0x7F, 0x33], // Orange Wool/Glass/Stained Clay, Pumpkin, Hardened Clay, Acacia Plank
		0x3F => [0x72, 0x43, 0x1B], // Orange Wool/Glass/Stained Clay, Pumpkin, Hardened Clay, Acacia Plank
		0x40 => [0x7D, 0x35, 0x98], // Magenta Wool/Glass/Stained Clay
		0x41 => [0x99, 0x41, 0xBA], // Magenta Wool/Glass/Stained Clay
		0x42 => [0xB2, 0x4C, 0xD8], // Magenta Wool/Glass/Stained Clay
		0x43 => [0x5E, 0x28, 0x72], // Magenta Wool/Glass/Stained Clay
		0x44 => [0x48, 0x6C, 0x98], // Light Blue Wool/Glass/Stained Clay
		0x45 => [0x58, 0x84, 0xBA], // Light Blue Wool/Glass/Stained Clay
		0x46 => [0x66, 0x99, 0xD8], // Light Blue Wool/Glass/Stained Clay
		0x47 => [0x36, 0x51, 0x72], // Light Blue Wool/Glass/Stained Clay
		0x48 => [0xA1, 0xA1, 0x24], // Yellow Wool/Glass/Stained Clay, Sponge, Hay Bale
		0x49 => [0xC5, 0xC5, 0x2C], // Yellow Wool/Glass/Stained Clay, Sponge, Hay Bale
		0x4A => [0xE5, 0xE5, 0x33], // Yellow Wool/Glass/Stained Clay, Sponge, Hay Bale
		0x4B => [0x79, 0x79, 0x1B], // Yellow Wool/Glass/Stained Clay, Sponge, Hay Bale
		0x4C => [0x59, 0x90, 0x11], // Lime Wool/Glass/Stained Clay, Melon
		0x4D => [0x6D, 0xB0, 0x15], // Lime Wool/Glass/Stained Clay, Melon
		0x4E => [0x7F, 0xCC, 0x19], // Lime Wool/Glass/Stained Clay, Melon
		0x4F => [0x43, 0x6C, 0x0D], // Lime Wool/Glass/Stained Clay, Melon
		0x50 => [0xAA, 0x59, 0x74], // Pink Wool/Glass/Stained Clay
		0x51 => [0xD0, 0x6D, 0x8E], // Pink Wool/Glass/Stained Clay
		0x52 => [0xF2, 0x7F, 0xA5], // Pink Wool/Glass/Stained Clay
		0x53 => [0x80, 0x43, 0x57], // Pink Wool/Glass/Stained Clay
		0x54 => [0x35, 0x35, 0x35], // Grey Wool/Glass/Stained Clay
		0x55 => [0x41, 0x41, 0x41], // Grey Wool/Glass/Stained Clay
		0x56 => [0x4C, 0x4C, 0x4C], // Grey Wool/Glass/Stained Clay
		0x57 => [0x28, 0x28, 0x28], // Grey Wool/Glass/Stained Clay
		0x58 => [0x6C, 0x6C, 0x6C], // Light Grey Wool/Glass/Stained Clay
		0x59 => [0x84, 0x84, 0x84], // Light Grey Wool/Glass/Stained Clay
		0x5A => [0x99, 0x99, 0x99], // Light Grey Wool/Glass/Stained Clay
		0x5B => [0x51, 0x51, 0x51], // Light Grey Wool/Glass/Stained Clay
		0x5C => [0x35, 0x59, 0x6C], // Cyan Wool/Glass/Stained Clay
		0x5D => [0x41, 0x6D, 0x84], // Cyan Wool/Glass/Stained Clay
		0x5E => [0x4C, 0x7F, 0x99], // Cyan Wool/Glass/Stained Clay
		0x5F => [0x28, 0x43, 0x51], // Cyan Wool/Glass/Stained Clay
		0x60 => [0x59, 0x2C, 0x7D], // Purple Wool/Glass/Stained Clay, Mycelium
		0x61 => [0x6D, 0x36, 0x99], // Purple Wool/Glass/Stained Clay, Mycelium
		0x62 => [0x7F, 0x3F, 0xB2], // Purple Wool/Glass/Stained Clay, Mycelium
		0x63 => [0x43, 0x21, 0x5E], // Purple Wool/Glass/Stained Clay, Mycelium
		0x64 => [0x24, 0x35, 0x7D], // Blue Wool/Glass/Stained Clay
		0x65 => [0x2C, 0x41, 0x99], // Blue Wool/Glass/Stained Clay
		0x66 => [0x33, 0x4C, 0xB2], // Blue Wool/Glass/Stained Clay
		0x67 => [0x1B, 0x28, 0x5E], // Blue Wool/Glass/Stained Clay
		0x68 => [0x48, 0x35, 0x24], // Brown Wool/Glass/Stained Clay, Soul Sand, Dark Oak Plank
		0x69 => [0x58, 0x41, 0x2C], // Brown Wool/Glass/Stained Clay, Soul Sand, Dark Oak Plank
		0x6A => [0x66, 0x4C, 0x33], // Brown Wool/Glass/Stained Clay, Soul Sand, Dark Oak Plank
		0x6B => [0x36, 0x28, 0x1B], // Brown Wool/Glass/Stained Clay, Soul Sand, Dark Oak Plank
		0x6C => [0x48, 0x59, 0x24], // Green Wool/Glass/Stained Clay, End Portal Frame
		0x6D => [0x58, 0x6D, 0x2C], // Green Wool/Glass/Stained Clay, End Portal Frame
		0x6E => [0x66, 0x7F, 0x33], // Green Wool/Glass/Stained Clay, End Portal Frame
		0x6F => [0x36, 0x43, 0x1B], // Green Wool/Glass/Stained Clay, End Portal Frame
		0x70 => [0x6C, 0x24, 0x24], // Red Wool/Glass/Stained Clay, Huge Red Mushroom, Brick, Enchanting Table
		0x71 => [0x84, 0x2C, 0x2C], // Red Wool/Glass/Stained Clay, Huge Red Mushroom, Brick, Enchanting Table
		0x72 => [0x99, 0x33, 0x33], // Red Wool/Glass/Stained Clay, Huge Red Mushroom, Brick, Enchanting Table
		0x73 => [0x51, 0x1B, 0x1B], // Red Wool/Glass/Stained Clay, Huge Red Mushroom, Brick, Enchanting Table
		0x74 => [0x11, 0x11, 0x11], // Black Wool/Glass/Stained Clay, Dragon Egg, Block of Coal, Obsidian
		0x75 => [0x15, 0x15, 0x15], // Black Wool/Glass/Stained Clay, Dragon Egg, Block of Coal, Obsidian
		0x76 => [0x19, 0x19, 0x19], // Black Wool/Glass/Stained Clay, Dragon Egg, Block of Coal, Obsidian
		0x77 => [0x0D, 0x0D, 0x0D], // Black Wool/Glass/Stained Clay, Dragon Egg, Block of Coal, Obsidian
		0x78 => [0xB0, 0xA8, 0x36], // Block of Gold, Weighted Pressure Plate (Light)
		0x79 => [0xD7, 0xCD, 0x42], // Block of Gold, Weighted Pressure Plate (Light)
		0x7A => [0xFA, 0xEE, 0x4D], // Block of Gold, Weighted Pressure Plate (Light)
		0x7B => [0x84, 0x7E, 0x28], // Block of Gold, Weighted Pressure Plate (Light)
		0x7C => [0x40, 0x9A, 0x96], // Block of Diamond, Prismarine, Prismarine Bricks, Dark Prismarine, Beacon
		0x7D => [0x4F, 0xBC, 0xB7], // Block of Diamond, Prismarine, Prismarine Bricks, Dark Prismarine, Beacon
		0x7E => [0x5C, 0xDB, 0xD5], // Block of Diamond, Prismarine, Prismarine Bricks, Dark Prismarine, Beacon
		0x7F => [0x30, 0x73, 0x70], // Block of Diamond, Prismarine, Prismarine Bricks, Dark Prismarine, Beacon
		0x80 => [0x34, 0x5A, 0xB4], // Lapis Lazuli Block
		0x81 => [0x3F, 0x6E, 0xDC], // Lapis Lazuli Block
		0x82 => [0x4A, 0x80, 0xFF], // Lapis Lazuli Block
		0x83 => [0x27, 0x43, 0x87], // Lapis Lazuli Block
		0x84 => [0x00, 0x99, 0x28], // Block of Emerald
		0x85 => [0x00, 0xBB, 0x32], // Block of Emerald
		0x86 => [0x00, 0xD9, 0x3A], // Block of Emerald
		0x87 => [0x00, 0x72, 0x1E], // Block of Emerald
		0x88 => [0x5A, 0x3B, 0x22], // Podzol, Spruce Plank
		0x89 => [0x6E, 0x49, 0x29], // Podzol, Spruce Plank
		0x8A => [0x7F, 0x55, 0x30], // Podzol, Spruce Plank
		0x8B => [0x43, 0x2C, 0x19], // Podzol, Spruce Plank
		0x8C => [0x4F, 0x01, 0x00], // Netherrack, Quartz Ore, Nether Wart, Nether Brick Items
		0x8D => [0x60, 0x01, 0x00], // Netherrack, Quartz Ore, Nether Wart, Nether Brick Items
		0x8E => [0x70, 0x02, 0x00], // Netherrack, Quartz Ore, Nether Wart, Nether Brick Items
		0x8F => [0x3B, 0x01, 0x00], // Netherrack, Quartz Ore, Nether Wart, Nether Brick Items
	];

	public static function init() : void{
		self::$timingConvertItem = new TimingsHandler("BigBrother - Convert Item Data");
		self::$timingConvertBlock = new TimingsHandler("BigBrother - Convert Block Data");

		//reset all index
		self::$idListIndex = [
			[/* PE => PC */],
			[/* PC => PE */]
		];

		foreach(self::$idList as $entry){
			//append index (PE => PC)
			if(isset(self::$idListIndex[0][$entry[0][0]])){
				self::$idListIndex[0][$entry[0][0]][] = $entry;
			}else{
				self::$idListIndex[0][$entry[0][0]] = [$entry];
			}

			//append index (PC => PE)
			if(isset(self::$idListIndex[1][$entry[1][0]])){
				self::$idListIndex[1][$entry[1][0]][] = $entry;
			}else{
				self::$idListIndex[1][$entry[1][0]] = [$entry];
			}
		}

		self::$reverseSpawnEggList = array_flip(self::$spawnEggList);

	}

	/**
	 * @param NamedTag  $nbt
	 * @param bool $isListTag
	 * @return string converted nbt tag data
	 */
	public static function convertNBTDataFromPEtoPC(NamedTag $nbt, $isListTag = false) : string{
		$stream = new BinaryStream();

		if(!$isListTag){
			$stream->putByte($nbt->getType());

			if($nbt instanceof NamedTag){
				$stream->putShort(strlen($nbt->getName()));
				$stream->put($nbt->getName());
			}
		}

		switch($nbt->getType()){
			case NBT::TAG_Compound:
				assert($nbt instanceof CompoundTag);
				foreach($nbt as $tag){
					$stream->put(self::convertNBTDataFromPEtoPC($tag));
				}

				$stream->putByte(0);
			break;
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
				$stream->put(Binary::writeDouble($nbt->getValue()));
			break;
			case NBT::TAG_ByteArray:
				$stream->putInt(strlen($nbt->getValue()));
				$stream->put($nbt->getValue());
			break;
			case NBT::TAG_String:
				$stream->putShort(strlen($nbt->getValue()));
				$stream->put($nbt->getValue());
			break;
			case NBT::TAG_List:
				assert($nbt instanceof ListTag);

				$count = count($nbt);
				$type = $nbt->getTagType();

				foreach($nbt as $tag){
					if($tag instanceof NamedTag){
						if($type !== $tag->getType()){
							throw new UnexpectedValueException("ListTag must consists of tags which types are the same");
						}
					}
				}

				$stream->putByte($type);
				$stream->putInt($count);

				foreach($nbt as $tag){
					$stream->put(self::convertNBTDataFromPEtoPC($tag, true));
				}
			break;
			case NBT::TAG_IntArray:
				$stream->putInt(count($nbt->getValue()));
				$stream->put(pack("N*", ...$nbt->getValue()));
			break;
		}



		return $stream->getBuffer();
	}

	/**
	 * @param string $buffer
	 * @param bool 	 $isListTag
	 * @param int 	 $listTagId
	 * @return CompoundTag|NamedTag|null
	 */
	public static function convertNBTDataFromPCtoPE(string $buffer, $isListTag = false, $listTagId = NBT::TAG_End) : ?NamedTag{
		$stream = new BinaryStream($buffer);
		$nbt = null;

		$name = "";
		if($isListTag){
			$type = $listTagId;
		}else{
			$type = $stream->getByte();
			if($type !== NBT::TAG_End){
				$name = $stream->get($stream->getShort());
			}
		}

		switch($type){
			case NBT::TAG_End://unused
				$nbt = null;
			break;
			case NBT::TAG_Byte:
				$nbt = new ByteTag($name, $stream->getByte());
			break;
			case NBT::TAG_Short:
				$nbt = new ShortTag($name, $stream->getShort());
			break;
			case NBT::TAG_Int:
				$nbt = new IntTag($name, $stream->getInt());
			break;
			case NBT::TAG_Long:
				$nbt = new LongTag($name, $stream->getLong());
			break;
			case NBT::TAG_Float:
				$nbt = new FloatTag($name, $stream->getFloat());
			break;
			case NBT::TAG_Double:
				$nbt = new DoubleTag($name, Binary::readDouble($stream->get(8)));
			break;
			case NBT::TAG_ByteArray:
				$nbt = new ByteArrayTag($name, $stream->get($stream->getInt()));
			break;
			case NBT::TAG_String:
				$nbt = new StringTag($name, $stream->get($stream->getShort()));
			break;
			case NBT::TAG_List:
				$id = $stream->getByte();
				$count = $stream->getInt();

				$tags = [];
				for($i = 0; $i < $count and !$stream->feof(); $i++){
					$tag = self::convertNBTDataFromPCtoPE(substr($buffer, $stream->getOffset()), true, $id);
					if($tag instanceof NamedTag){
						$stream->offset += strlen(self::convertNBTDataFromPEtoPC($tag, true));
					}else{
						$stream->offset += 1;
					}

					if($tag instanceof NamedTag){
						$tags[] = $tag;
					}
				}

				$nbt = new ListTag($name, $tags, $id);
			break;
			case NBT::TAG_Compound:
				$tags = [];
				do{
					$tag = self::convertNBTDataFromPCtoPE(substr($buffer, $stream->getOffset()));
					if($tag instanceof NamedTag){
						$stream->offset += strlen(self::convertNBTDataFromPEtoPC($tag));
					}else{
						$stream->offset += 1;
					}

					if($tag instanceof NamedTag){
						$tags[] = $tag;
					}
				}while($tag !== null and !$stream->feof());

				$nbt = new CompoundTag($name, $tags);
			break;
			case NBT::TAG_IntArray:
				$nbt = new IntArrayTag($name, unpack("N*", $stream->get($stream->getInt()*4)));
			break;
		}

		return $nbt;
	}

	/**
	 * Convert item data from PE => PC when $isComputer is set to true,
	 * else convert item data opposite way.
	 *
	 * @param bool $isComputer
	 * @param Item &$item
	 */
	public static function convertItemData(bool $isComputer, Item &$item) : void{
		self::$timingConvertItem->startTiming();

		$itemId = $item->getId();
		$itemDamage = $item->getDamage();
		$itemCount = $item->getCount();
		$itemNBT = clone $item->getNamedTag();

		switch($itemId){
			case Item::PUMPKIN:
			case Item::JACK_O_LANTERN:
				$itemDamage = 0;
			break;
			case Item::WRITABLE_BOOK:
				if($isComputer){
					$listTag = [];
					$photoListTag = [];
					foreach($itemNBT["pages"] as $pageNumber => $pageTags){
						if($pageTags instanceof CompoundTag){
							foreach($pageTags as $name => $tag){
								if($tag instanceof StringTag){
									switch($tag->getName()){
										case "text":
											$listTag[] = new StringTag("", $tag->getValue());
										break;
										case "photoname":
											$photoListTag[] = new StringTag("", $tag->getValue());
										break;
									}
								}
							}
						}
					}

					$itemNBT->removeTag("pages");
					$itemNBT->setTag(new ListTag("pages", $listTag));
					$itemNBT->setTag(new ListTag("photoname", $photoListTag));
				}else{
					$listTag = [];
					foreach($itemNBT["pages"] as $pageNumber => $tag){
						if($tag instanceof StringTag){
							$tag->setName("text");

							$value = "";
							if(isset($itemNBT["photoname"][$pageNumber])){
								$value = $itemNBT["photoname"][$pageNumber];
							}
							$photoNameTag = new StringTag("photoname", $value);

							$listTag[] = new CompoundTag("", [
								$tag,
								$photoNameTag,
							]);
						}
					}

					$itemNBT->removeTag("pages");
					if($itemNBT->hasTag("photoname")){
						$itemNBT->removeTag("photoname");
					}

					$itemNBT->setTag(new ListTag("pages", $listTag));
				}
			break;
			case Item::WRITTEN_BOOK:
				if($isComputer){
					$listTag = [];
					$photoListTag = [];
					foreach($itemNBT["pages"] as $pageNumber => $pageTags){
						if($pageTags instanceof CompoundTag){
							foreach($pageTags as $name => $tag){
								if($tag instanceof StringTag){
									switch($tag->getName()){
										case "text":
											$listTag[] = new StringTag("", $tag->getValue());
										break;
										case "photoname":
											$photoListTag[] = new StringTag("", $tag->getValue());
										break;
									}
								}
							}
						}
					}

					$itemNBT->removeTag("pages");
					$itemNBT->setTag(new ListTag("pages", $listTag));
					$itemNBT->setTag(new ListTag("photoname", $photoListTag));
				}else{
					$listTag = [];
					foreach($itemNBT["pages"] as $pageNumber => $tag){
						if($tag instanceof StringTag){
							$tag->setName("text");

							$value = "";
							if(isset($itemNBT["photoname"][$pageNumber])){
								$value = $itemNBT["photoname"][$pageNumber];
							}
							$photoNameTag = new StringTag("photoname", $value);

							$listTag[] = new CompoundTag("", [
								$tag,
								$photoNameTag,
							]);
						}
					}

					$itemNBT->removeTag("pages");
					if($itemNBT->hasTag("photoname")){
						$itemNBT->removeTag("photoname");
					}

					$itemNBT->setTag(new ListTag("pages", $listTag));
				}
			break;
			case Item::SPAWN_EGG:
				if($isComputer){
					if($type = self::$spawnEggList[$itemDamage] ?? ""){
						$itemNBT = new CompoundTag("", [
							new CompoundTag("EntityTag", [
								new StringTag("id", $type),
							])
						]);
					}
				}else{
					$entityTag = "";
					if($itemNBT !== ""){
						if($itemNBT->hasTag("EntityTag")){
							$entityTag = $itemNBT["EntityTag"]["id"];
						}
					}

					$itemDamage = self::$reverseSpawnEggList[$entityTag] ?? 0;
				}
			break;
			default:
				if($isComputer){
					$src = 0; $dst = 1;
				}else{
					$src = 1; $dst = 0;
				}

				foreach(self::$idListIndex[$src][$itemId] ?? [] as $convertItemData){
					if($convertItemData[$src][1] === -1){
						$itemId = $convertItemData[$dst][0];
						if($convertItemData[$dst][1] === -1){
							$itemDamage = $item->getDamage();
						}else{
							$itemDamage = $convertItemData[$dst][1];
						}
						break;
					}elseif($convertItemData[$src][1] === $item->getDamage()){
						$itemId = $convertItemData[$dst][0];
						$itemDamage = $convertItemData[$dst][1];
						break;
					}
				}
			break;
		}

		if($isComputer){
			$item = new ComputerItem($itemId, $itemDamage, $itemCount, $itemNBT);
		}else{
			$item = Item::get($itemId, $itemDamage, $itemCount, $itemNBT);
		}

		self::$timingConvertItem->stopTiming();
	}

	/**
	 * Convert block data from PE => PC when $isComputer is set to true,
	 * else convert block data opposite way.
	 *
	 * @param bool $isComputer
	 * @param int  &$blockId to convert
	 * @param int  &$blockData to convert
	 */
	public static function convertBlockData(bool $isComputer, int &$blockId, int &$blockData) : void{
		self::$timingConvertBlock->startTiming();

		switch($blockId){
			case Block::WOODEN_TRAPDOOR:
			case Block::IRON_TRAPDOOR:
				self::convertTrapdoor($blockData);
			break;
			case Block::STONE_BUTTON:
			case Block::WOODEN_BUTTON:
				self::convertButton($blockData);
			break;
			default:
				if($isComputer){
					$src = 0; $dst = 1;
				}else{
					$src = 1; $dst = 0;
				}

				foreach(self::$idListIndex[$src][$blockId] ?? [] as $convertBlockData){
					if($convertBlockData[$src][1] === -1){
						$blockId = $convertBlockData[$dst][0];
						if($convertBlockData[$dst][1] !== -1){
							$blockData = $convertBlockData[$dst][1];
						}
						break;
					}elseif($convertBlockData[$src][1] === $blockData){
						$blockId = $convertBlockData[$dst][0];
						$blockData = $convertBlockData[$dst][1];
						break;
					}
				}
			break;
		}

		self::$timingConvertBlock->stopTiming();
	}

	/**
	 * @param array $oldData
	 * @return array converted
	 */
	public static function convertPEToPCMetadata(array $oldData) : array{
		$newData = [];

		foreach($oldData as $bottom => $d){
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

					if(((int) $d[1] & (1 << Human::DATA_FLAG_INVISIBLE)) > 0){
						$flags |= 0x20;
					}

					if(((int) $d[1] & (1 << Human::DATA_FLAG_CAN_SHOW_NAMETAG)) > 0){
						$newData[3] = [6, true];
					}

					if(((int) $d[1] & (1 << Human::DATA_FLAG_ALWAYS_SHOW_NAMETAG)) > 0){
						$newData[3] = [6, true];
					}

					/*if(((int) $d[1] & (1 << Human::DATA_FLAG_IMMOBILE)) > 0){//TODO
						//$newData[11] = [0, true];
					}*/

					if(((int) $d[1] & (1 << Human::DATA_FLAG_SILENT)) > 0){
						$newData[4] = [6, true];
					}

					$newData[0] = [0, $flags];
				break;
				case Human::DATA_AIR://Air
					$newData[1] = [1, $d[1]];
				break;
				case Human::DATA_NAMETAG://Custom name
					$newData[2] = [3, str_replace("\n", "", $d[1])];//TODO
				break;
				case Human::DATA_FUSE_LENGTH://TNT
					$newData[6] = [1, $d[1]];
				break;
				case Human::DATA_POTION_COLOR:
					$newData[8] = [1, $d[1]];
				break;
				case Human::DATA_POTION_AMBIENT:
					$newData[9] = [6, $d[1] ? true : false];
				break;
				case Human::DATA_VARIANT:
				case Human::DATA_PLAYER_FLAGS:
				case Human::DATA_PLAYER_BED_POSITION:
				case Human::DATA_LEAD_HOLDER_EID:
				case Human::DATA_SCALE:
				case Human::DATA_MAX_AIR:
				case Human::DATA_OWNER_EID:
				case Human::DATA_BOUNDING_BOX_WIDTH:
				case Human::DATA_BOUNDING_BOX_HEIGHT:
				case Human::DATA_ALWAYS_SHOW_NAMETAG://TODO: sendPacket?
				case Projectile::DATA_SHOOTER_ID:
					//Unused
				break;
				default:
					echo "key: ".$bottom." Not implemented\n";
				break;
				//TODO: add data type
			}
		}

		$newData["convert"] = true;

		return $newData;
	}

	/**
	 * Find nearest color defined in self::$colorTable for each pixel in $colors
	 *
	 * @param Color[][] $target
	 * @return string
	 */
	public static function convertColorsToPC($colors, $width, $height) : string{
		$ret = "";

		for($y=0; $y<$height; ++$y){
			for($x=0; $x<$width; ++$x){
				$min = PHP_INT_MAX;
				$ind = 0x00;

				$r = $colors[$y][$x]->getR();
				$g = $colors[$y][$x]->getG();
				$b = $colors[$y][$x]->getB();
				$a = $colors[$y][$x]->getA();

				if($a >= 128){
					foreach(self::$colorTable as $index => $rgb){
						$squared = ($r -$rgb[0])**2 + ($g-$rgb[1])**2 + ($b-$rgb[2])**2;
						if($squared < $min){
							$ind = $index;
							$min = $squared;
						}
					}
				}

				$ret .= chr($ind);
			}
		}

		return $ret;
	}

	/**
	 * Blame Mojang!! :-@
	 * Why Mojang change the order of flag bits?
	 * Why Mojang change the directions??
	 *
	 * @param int &$blockData
	 *
	 * #blamemojang
	 */
	private static function convertTrapdoor(int &$blockData) : void{
		//swap bits
		$blockData ^= (($blockData & 0x04) << 1);
		$blockData ^= (($blockData & 0x08) >> 1);
		$blockData ^= (($blockData & 0x04) << 1);

		//swap directions
		$directions = [
			0 => 3,
			1 => 2,
			2 => 1,
			3 => 0
		];

		$blockData = (($blockData >> 2) << 2) | $directions[$blockData & 0x03];
	}

	/**
	 * Blame Mojang!! :-@
	 * Why Mojang change the directions??
	 *
	 * @param int &$blockData
	 *
	 * #blamemojang
	 */
	private static function convertButton(int &$blockData) : void{
		$directions = [
			0 => 0, // Button on block bottom facing down
			1 => 5, // Button on block top facing up
			2 => 4, // Button on block side facing north
			3 => 3, // Button on block side facing south
			4 => 2, // Button on block side facing west
			5 => 1, // Button on block side facing east
		];

		$blockData = ($blockData & 0x08) | $directions[$blockData & 0x07];
	}

}


class ComputerItem extends Item{
	/**
	 * @param int                $id
	 * @param int                $meta
	 * @param int                $count
	 * @param CompoundTag|string $tag
	 */
	public function __construct(int $id = 0, int $meta = 0, int $count = 1, $tag = ""){
		parent::__construct($id, $meta);
		$this->setCount($count);
		$this->setCompoundTag($tag);
	}
}
