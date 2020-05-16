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

namespace shoghicp\BigBrother\entity;

use pocketmine\item\Item;
use pocketmine\block\Block;
use pocketmine\level\Level;
use pocketmine\level\Position;
use pocketmine\entity\Entity;
use pocketmine\tile\ItemFrame;
use pocketmine\utils\UUID;
use pocketmine\network\mcpe\protocol\MapInfoRequestPacket;
use shoghicp\BigBrother\network\protocol\Play\Server\DestroyEntitiesPacket;
use shoghicp\BigBrother\network\protocol\Play\Server\SpawnObjectPacket;
use shoghicp\BigBrother\network\protocol\Play\Server\EntityMetadataPacket;
use shoghicp\BigBrother\utils\ConvertUtils;
use shoghicp\BigBrother\DesktopPlayer;

class ItemFrameBlockEntity extends Position{
	/** @var array */
	protected static $itemFrames = [];
	/** @var array */
	protected static $itemFramesAt = [];
	/** @var array */
	protected static $itemFramesInChunk = [];

	/** @var array */
	private static $mapping = [
		0 => [ -90,  3],//EAST
		1 => [ +90,  1],//WEST
		2 => [   0,  0],//SOUTH
		3 => [-180,  2] //NORTH
	];

	/** @var int */
	private $eid;
	/** @var string */
	private $uuid;
	/** @var int */
	private $facing;
	/** @var int */
	private $yaw;

	/**
	 * @param Level $level
	 * @param int   $x
	 * @param int   $y
	 * @param int   $z
	 * @param int   $data
	 * @throws
	 */
	private function __construct(Level $level, int $x, int $y, int $z, int $data){
		parent::__construct($x, $y, $z, $level);
		$this->eid = Entity::$entityCount++;
		$this->uuid = UUID::fromRandom()->toBinary();
		$this->facing = $data;
		$this->yaw = self::$mapping[$data][0] ?? 0;
	}

	/**
	 * @return int
	 */
	public function getEntityId() : int{
		return $this->eid;
	}

	/**
	 * @return int
	 */
	public function getFacing() : int{
		return $this->facing;
	}

	/**
	 * @return bool
	 */
	public function hasItem() : bool{
		$tile = $this->getLevel()->getTile($this);
		if($tile instanceof ItemFrame){
			return $tile->hasItem();
		}

		return false;
	}

	/**
	 * @param DesktopPlayer $player
	 */
	public function spawnTo(DesktopPlayer $player){
		$pk = new SpawnObjectPacket();
		$pk->eid = $this->eid;
		$pk->uuid = $this->uuid;
		$pk->type = SpawnObjectPacket::ITEM_FRAMES;
		$pk->x = $this->x;
		$pk->y = $this->y;
		$pk->z = $this->z;
		$pk->yaw = $this->yaw;
		$pk->pitch = 0;
		$pk->data = self::$mapping[$this->facing][1];
		$pk->sendVelocity = true;
		$pk->velocityX = 0;
		$pk->velocityY = 0;
		$pk->velocityZ = 0;
		$player->putRawPacket($pk);

		$pk = new EntityMetadataPacket();
		$pk->eid = $this->eid;
		$pk->metadata = ["convert" => true];

		$tile = $this->getLevel()->getTile($this);
		if($tile instanceof ItemFrame){
			$item = $tile->hasItem() ? $tile->getItem() : Item::get(Item::AIR, 0, 0);

			if($item->getId() === Item::FILLED_MAP){
				$mapId = $item->getNamedTag()->getLong("map_uuid");
				if($mapId !== null){
					// store $mapId as meta
					$item->setDamage($mapId);

					$req  = new MapInfoRequestPacket();
					$req->mapId = $mapId;
					$player->handleDataPacket($req);
				}
			}

			ConvertUtils::convertItemData(true, $item);
			$pk->metadata[6] = [5, $item];
			$pk->metadata[7] = [1, $tile->getItemRotation()];
		}

		$player->putRawPacket($pk);
	}

	/**
	 * @param DesktopPlayer $player
	 */
	public function despawnFrom(DesktopPlayer $player) : void{
		$pk = new DestroyEntitiesPacket();
		$pk->ids []= $this->eid;
		$player->putRawPacket($pk);
	}

	public function despawnFromAll() : void{
		foreach($this->getLevel()->getChunkLoaders($this->x >> 4, $this->z >> 4) as $player){
			if($player instanceof DesktopPlayer){
				$this->despawnFrom($player);
			}
		}
		self::removeItemFrame($this);
	}

	/**
	 * @param Level $level
	 * @param int   $x
	 * @param int   $y
	 * @param int   $z
	 * @return bool
	 */
	public static function exists(Level $level, int $x, int $y, int $z) : bool{
		return isset(self::$itemFramesAt[$level->getId()][Level::blockHash($x, $y, $z)]);
	}

	/**
	 * @param Level $level
	 * @param int   $x
	 * @param int   $y
	 * @param int   $z
	 * @param int   $data
	 * @param bool  $create
	 * @return ItemFrameBlockEntity|null
	 */
	public static function getItemFrame(Level $level, int $x, int $y, int $z, int $data=0, bool $create=false) : ?ItemFrameBlockEntity{
		$entity = null;

		if(isset(self::$itemFramesAt[$level_id = $level->getId()][$index = Level::blockHash($x, $y, $z)])){
			$entity = self::$itemFramesAt[$level_id][$index];
		}elseif($create){
			$entity = new ItemFrameBlockEntity($level, $x, $y, $z, $data);
			self::$itemFrames[$level_id][$entity->eid] = $entity;
			self::$itemFramesAt[$level_id][$index] = $entity;

			if(!isset(self::$itemFramesInChunk[$level_id][$index = Level::chunkHash($x >> 4, $z >> 4)])){
				self::$itemFramesInChunk[$level_id][$index] = [];
			}
			self::$itemFramesInChunk[$level_id][$index] []= $entity;
		}

		return $entity;
	}

	/**
	 * @param Level $level
	 * @param int   $eid
	 * @return ItemFrameBlockEntity|null
	 */
	public static function getItemFrameById(Level $level, int $eid) : ?ItemFrameBlockEntity{
		return self::$itemFrames[$level->getId()][$eid] ?? null;
	}

	/**
	 * @param Block $block
	 * @param bool  $create
	 * @return ItemFrameBlockEntity|null
	 */
	public static function getItemFrameByBlock(Block $block, bool $create=false) : ?ItemFrameBlockEntity{
		return self::getItemFrame($block->getLevel(), $block->x, $block->y, $block->z, $block->getDamage(), $create);
	}

	/**
	 * @param Level $level
	 * @param int   $x
	 * @param int   $z
	 * @return array
	 */
	public static function getItemFramesInChunk(Level $level, int $x, int $z) : array{
		return self::$itemFramesInChunk[$level->getId()][Level::chunkHash($x, $z)] ?? [];
	}

	/**
	 * @param ItemFrameBlockEntity $entity
	 */
	public static function removeItemFrame(ItemFrameBlockEntity $entity) : void{
		unset(self::$itemFrames[$entity->level->getid()][$entity->eid]);
		unset(self::$itemFramesAt[$entity->level->getId()][Level::blockHash($entity->x, $entity->y, $entity->z)]);
		if(isset(self::$itemFramesInChunk[$level_id = $entity->getLevel()->getId()][$index = Level::chunkHash($entity->x >> 4, $entity->z >> 4)])){
			self::$itemFramesInChunk[$level_id][$index] = array_diff(self::$itemFramesInChunk[$level_id][$index], [$entity]);
		}
	}
}
