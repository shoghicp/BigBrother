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

use pocketmine\network\protocol\DataPacket;
use pocketmine\network\protocol\Info;
use pocketmine\network\protocol\MessagePacket;
use pocketmine\network\protocol\MovePlayerPacket;
use pocketmine\utils\TextFormat;
use shoghicp\BigBrother\DesktopPlayer;
use shoghicp\BigBrother\network\Packet;
use shoghicp\BigBrother\network\protocol\BlockChangePacket;
use shoghicp\BigBrother\network\protocol\DestroyEntitiesPacket;
use shoghicp\BigBrother\network\protocol\EntityHeadLookPacket;
use shoghicp\BigBrother\network\protocol\EntityTeleportPacket;
use shoghicp\BigBrother\network\protocol\JoinGamePacket;
use shoghicp\BigBrother\network\protocol\PlayerPositionAndLookPacket;
use shoghicp\BigBrother\network\protocol\PositionAndLookPacket;
use shoghicp\BigBrother\network\protocol\SpawnPlayerPacket;
use shoghicp\BigBrother\network\protocol\SpawnPositionPacket;
use shoghicp\BigBrother\network\protocol\STCChatPacket;
use shoghicp\BigBrother\network\protocol\TimeUpdatePacket;
use shoghicp\BigBrother\utils\Binary;

class Translator_16 implements Translator{



	public function interfaceToServer(DesktopPlayer $player, Packet $packet){
		switch($packet->pid()){
			// TODO: move to Info

			case 0x01: //CTSChatPacket
				$pk = new MessagePacket();
				$pk->source = "";
				$pk->message = $packet->message;
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

				$pk = new SpawnPositionPacket();
				$pk->spawnX = $packet->spawnX;
				$pk->spawnY = $packet->spawnY;
				$pk->spawnZ = $packet->spawnZ;
				$packets[] = $pk;

				$pk = new PositionAndLookPacket();
				$pk->x = $packet->x;
				$pk->y = $packet->y + $player->height;
				$pk->z = $packet->z;
				$pk->yaw = $player->yaw;
				$pk->pitch = $player->pitch;
				$pk->onGround = $player->isOnGround();
				$packets[] = $pk;
				return $packets;

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
					$pk->y = $packet->y + $player->height;
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

			case Info::MOVE_ENTITY_PACKET_POSROT:
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