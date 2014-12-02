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

namespace shoghicp\BigBrother\network\translation;

use pocketmine\item\Item;
use pocketmine\network\protocol\ContainerClosePacket;
use pocketmine\network\protocol\DataPacket;
use pocketmine\network\protocol\Info;
use pocketmine\network\protocol\InteractPacket;
use pocketmine\network\protocol\MessagePacket;
use pocketmine\network\protocol\MovePlayerPacket;
use pocketmine\network\protocol\RemoveBlockPacket;
use pocketmine\network\protocol\RespawnPacket;
use pocketmine\network\protocol\UseItemPacket;
use pocketmine\utils\TextFormat;
use shoghicp\BigBrother\DesktopPlayer;
use shoghicp\BigBrother\network\Packet;
use shoghicp\BigBrother\network\protocol\BlockChangePacket;
use shoghicp\BigBrother\network\protocol\ChangeGameStatePacket;
use shoghicp\BigBrother\network\protocol\DestroyEntitiesPacket;
use shoghicp\BigBrother\network\protocol\EntityHeadLookPacket;
use shoghicp\BigBrother\network\protocol\EntityMetadataPacket;
use shoghicp\BigBrother\network\protocol\EntityTeleportPacket;
use shoghicp\BigBrother\network\protocol\EntityVelocityPacket;
use shoghicp\BigBrother\network\protocol\JoinGamePacket;
use shoghicp\BigBrother\network\protocol\OpenWindowPacket;
use shoghicp\BigBrother\network\protocol\PlayerAbilitiesPacket;
use shoghicp\BigBrother\network\protocol\PositionAndLookPacket;
use shoghicp\BigBrother\network\protocol\SetSlotPacket;
use shoghicp\BigBrother\network\protocol\SpawnObjectPacket;
use shoghicp\BigBrother\network\protocol\SpawnPlayerPacket;
use shoghicp\BigBrother\network\protocol\SpawnPositionPacket;
use shoghicp\BigBrother\network\protocol\STCChatPacket;
use shoghicp\BigBrother\network\protocol\STCCloseWindowPacket;
use shoghicp\BigBrother\network\protocol\TimeUpdatePacket;
use shoghicp\BigBrother\network\protocol\UpdateHealthPacket;
use shoghicp\BigBrother\network\protocol\WindowItemsPacket;
use shoghicp\BigBrother\utils\Binary;

class Translator_20 implements Translator{



	public function interfaceToServer(DesktopPlayer $player, Packet $packet){
		switch($packet->pid()){
			// TODO: move to Info

			case 0x01: //CTSChatPacket
				$pk = new MessagePacket();
				$pk->source = "";
				$pk->message = $packet->message;
				return $pk;

			case 0x02: //UseEntityPacket
				$pk = new InteractPacket();
				$pk->target = $packet->target;
				$pk->action = $packet->mouse;
				return $pk;

			case 0x04: //PlayerPositionPacket
				$pk = new MovePlayerPacket();
				$pk->x = $packet->x;
				$pk->y = $packet->y;
				$pk->z = $packet->z;
				$pk->yaw = $player->yaw;
				$pk->bodyYaw = $player->yaw;
				$pk->pitch = $player->pitch;
				return $pk;

			case 0x05: //PlayerLookPacket
				$pk = new MovePlayerPacket();
				$pk->x = $player->x;
				$pk->y = $player->y;
				$pk->z = $player->z;
				$pk->yaw = $packet->yaw;
				$pk->bodyYaw = $packet->yaw;
				$pk->pitch = $packet->pitch;
				return $pk;

			case 0x06: //PlayerPositionAndLookPacket
				$pk = new MovePlayerPacket();
				$pk->x = $packet->x;
				$pk->y = $packet->y;
				$pk->z = $packet->z;
				$pk->yaw = $packet->yaw;
				$pk->bodyYaw = $packet->yaw;
				$pk->pitch = $packet->pitch;
				return $pk;

			case 0x07: //PlayerDiggingPacket
				if($packet->status === 2 or ($player->getGamemode() === 1 and $packet->status === 0)){ //Finished digging
					$pk = new RemoveBlockPacket();
					$pk->eid = 0;
					$pk->x = $packet->x;
					$pk->y = $packet->y;
					$pk->z = $packet->z;
					return $pk;
				}
				return null;

			case 0x08; //PlayerBlockPlacementPacket
				$pk = new UseItemPacket();
				$pk->x = $packet->x;
				$pk->y = $packet->y;
				$pk->z = $packet->z;
				$pk->face = $packet->direction;
				$pk->item = $packet->heldItem->getID();
				$pk->meta = $packet->heldItem->getDamage();
				$pk->eid = 0;
				$pk->fx = $packet->cursorX / 16;
				$pk->fy = $packet->cursorY / 16;
				$pk->fz = $packet->cursorZ / 16;
				return $pk;

			case 0x0d: //CTSCloseWindowPacket
				$pk = new ContainerClosePacket();
				$pk->windowid = $packet->windowID;
				return $pk;

			case 0x16: //ClientStatusPacket
				if($packet->actionID === 0){
					$pk = new RespawnPacket();
					$pk->eid = 0;
					$pk->x = $player->getSpawn()->getX();
					$pk->y = $player->getSpawn()->getX();
					$pk->z = $player->getSpawn()->getX();
					return $pk;
				}
				return null;

			default:
				return null;
		}
	}

	public function serverToInterface(DesktopPlayer $player, DataPacket $packet){
		switch($packet->pid()){

			case Info::UPDATE_BLOCK_PACKET:
				$pk = new BlockChangePacket();
				$pk->x = $packet->x;
				$pk->y = $packet->y;
				$pk->z = $packet->z;
				$pk->blockId = $packet->block;
				$pk->blockMeta = $packet->meta;
				return $pk;

			case Info::START_GAME_PACKET:
				$packets = [];

				$pk = new JoinGamePacket();
				$pk->eid = $packet->eid;
				$pk->gamemode = $player->getGamemode();
				$pk->dimension = 0;
				$pk->difficulty = $player->getServer()->getDifficulty();
				$pk->maxPlayers = $player->getServer()->getMaxPlayers();
				$pk->levelType = "default";
				$packets[] = $pk;

				$pk = new PlayerAbilitiesPacket();
				$pk->flyingSpeed = 0.05;
				$pk->walkingSpeed = 0.1;
				$pk->canFly = ($player->getGamemode() & 0x01) > 0;
				$pk->damageDisabled = ($player->getGamemode() & 0x01) > 0;
				$pk->isFlying = false;
				$pk->isCreative = ($player->getGamemode() & 0x01) > 0;
				if($player->spawned === true){
					$packets = [$pk];

					$pk = new ChangeGameStatePacket();
					$pk->reason = 3;
					$pk->value = $player->getGamemode();
					$packets[] = $pk;
					return $packets;
				}else{
					$packets[] = $pk;
				}

				$pk = new SpawnPositionPacket();
				$pk->spawnX = $packet->spawnX;
				$pk->spawnY = $packet->spawnY;
				$pk->spawnZ = $packet->spawnZ;
				$packets[] = $pk;

				$pk = new PositionAndLookPacket();
				$pk->x = $packet->x;
				$pk->y = $packet->y;
				$pk->z = $packet->z;
				$pk->yaw = $player->yaw;
				$pk->pitch = $player->pitch;
				$pk->onGround = $player->isOnGround();
				$packets[] = $pk;
				return $packets;

			case Info::SET_HEALTH_PACKET:
				$pk = new UpdateHealthPacket();
				$pk->health = $packet->health;
				$pk->food = 20;
				$pk->saturation = 5;
				return $pk;

			case Info::MESSAGE_PACKET:
				$pk = new STCChatPacket();

				$pk->message = TextFormat::toJSON($packet->message);
				return $pk;

			case Info::SET_TIME_PACKET:
				$pk = new TimeUpdatePacket();
				$pk->age = $packet->time;
				$pk->time = $packet->time; //TODO: calculate offset from MCPE
				return $pk;

			case Info::SET_SPAWN_POSITION_PACKET:
				$pk = new SpawnPositionPacket();
				$pk->spawnX = $packet->x;
				$pk->spawnY = $packet->y;
				$pk->spawnZ = $packet->z;
				return $pk;

			case Info::REMOVE_ENTITY_PACKET:
			case Info::REMOVE_PLAYER_PACKET:
				$pk = new DestroyEntitiesPacket();
				$pk->ids[] = $packet->eid;
				return $pk;

			case Info::MOVE_PLAYER_PACKET:
				if($packet->eid === 0){
					$pk = new PositionAndLookPacket();
					$pk->x = $packet->x;
					$pk->y = $packet->y;
					$pk->z = $packet->z;
					$pk->yaw = $packet->yaw;
					$pk->pitch = $packet->pitch;
					$pk->onGround = $player->isOnGround();
					return $pk;
				}else{
					$packets = [];
					$pk = new EntityTeleportPacket();
					$pk->eid = $packet->eid;
					$pk->x = $packet->x;
					$pk->y = $packet->y;
					$pk->z = $packet->z;
					$pk->yaw = $packet->yaw;
					$pk->pitch = $packet->pitch;
					$packets[] = $pk;

					$pk = new EntityHeadLookPacket();
					$pk->eid = $packet->eid;
					$pk->yaw = $packet->yaw;
					$packets[] = $pk;
					return $packets;
				}

			case Info::MOVE_ENTITY_PACKET:
				$packets = [];
				foreach($packet->entities as $d){
					$pk = new EntityTeleportPacket();
					$pk->eid = $d[0];
					$pk->x = $d[1];
					$pk->y = $d[2];
					$pk->z = $d[3];
					$pk->yaw = $d[4];
					$pk->pitch = $d[5];
					$packets[] = $pk;

					$pk = new EntityHeadLookPacket();
					$pk->eid = $d[0];
					$pk->yaw = $d[4];
					$packets[] = $pk;
				}
				return $packets;

			case Info::SET_ENTITY_MOTION_PACKET:
				$packets = [];
				foreach($packet->entities as $d){
					$pk = new EntityVelocityPacket();
					$pk->eid = $d[0];
					$pk->velocityX = $d[1];
					$pk->velocityY = $d[2];
					$pk->velocityZ = $d[3];
					$packets[] = $pk;
				}
				return $packets;

			/*
			case Info::CONTAINER_CLOSE_PACKET:
				$pk = new STCCloseWindowPacket();
				$pk->windowID = $packet->windowid;
				return $pk;

			case Info::CONTAINER_OPEN_PACKET:
				$pk = new OpenWindowPacket();
				$pk->windowID = $packet->windowid;
				$pk->inventoryType = $packet->type;
				$pk->windowTitle = "";
				$pk->slots = $packet->slots;
				return $pk;

			case Info::CONTAINER_SET_SLOT_PACKET:
				$pk = new SetSlotPacket();
				$pk->windowID = $packet->windowid;
				if($pk->windowID === 0){
					$pk->slot = $packet->slot + 9;
				}elseif($pk->windowID === 0x78){
					$pk->windowID = 0;
					$pk->slot = $packet->slot + 5;
				}else{
					$pk->slot = $packet->slot;
				}
				$pk->item = $packet->item;
				return $pk;

			case Info::CONTAINER_SET_CONTENT_PACKET:
				$pk = new WindowItemsPacket();
				$pk->windowID = $packet->windowid;
				if($pk->windowID === 0 or $pk->windowID === 0x78){
					$pk->windowID = 0;
					for($i = 0; $i < 5; ++$i){
						$pk->items[] = Item::get(Item::AIR, 0, 0);
					}
					$pk->items[] = $player->getInventory()->getHelmet();
					$pk->items[] = $player->getInventory()->getChestplate();
					$pk->items[] = $player->getInventory()->getLeggings();
					$pk->items[] = $player->getInventory()->getBoots();
					$slots = $player->getInventory()->getSize();
					for($i = 0; $i < $slots; ++$i){
						$pk->items[] = $player->getInventory()->getItem($i);
					}
				}else{
					$pk->items = $packet->slots;
				}

				return $pk;
			*/

			case Info::ADD_ITEM_ENTITY_PACKET:
				$packets = [];
				$pk = new SpawnObjectPacket();
				$pk->eid = $packet->eid;
				$pk->type = 2;
				$pk->x = $packet->x;
				$pk->y = $packet->y;
				$pk->z = $packet->z;
				$pk->yaw = $packet->yaw;
				$pk->pitch = $packet->pitch;
				$packets[] = $pk;

				$pk = new EntityMetadataPacket();
				$pk->eid = $packet->eid;
				$pk->metadata = $pk->metadata = [
					0 => ["type" => 0, "value" => 0],
					10 => ["type" => 5, "value" => $packet->item],
				];
				$packets[] = $pk;

				return $packets;


			case Info::ADD_PLAYER_PACKET:
				$packets = [];
				$pk = new SpawnPlayerPacket();
				$pk->name = $packet->username;
				$pk->eid = $packet->eid;
				$pk->uuid = Binary::UUIDtoString("00000000000030008000000000000000");
				$pk->x = $packet->x;
				$pk->z = $packet->y;
				$pk->y = $packet->z;
				$pk->yaw = $packet->yaw;
				$pk->pitch = $packet->pitch;
				$pk->item = 0;
				$pk->metadata = $packet->metadata;
				$packets[] = $pk;

				$pk = new EntityTeleportPacket();
				$pk->eid = $packet->eid;
				$pk->x = $packet->x;
				$pk->y = $packet->y;
				$pk->z = $packet->z;
				$pk->yaw = $packet->yaw;
				$pk->pitch = $packet->pitch;
				$packets[] = $pk;
				return $packets;

			default:
				return null;
		}
	}
}