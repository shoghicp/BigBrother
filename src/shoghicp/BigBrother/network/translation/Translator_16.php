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
use shoghicp\BigBrother\DesktopPlayer;
use shoghicp\BigBrother\network\Packet;
use shoghicp\BigBrother\network\protocol\JoinGamePacket;
use shoghicp\BigBrother\network\protocol\PlayerPositionAndLookPacket;
use shoghicp\BigBrother\network\protocol\SpawnPositionPacket;
use shoghicp\BigBrother\network\protocol\STCChatPacket;

class Translator_16 implements Translator{



	public function interfaceToServer(DesktopPlayer $player, Packet $packet){
		switch($packet->pid()){
			case 0x01: //CTSChatPacket TODO: move to Info
				$pk = new MessagePacket();
				$pk->source = "";
				$pk->message = $packet->message;
				return $pk;
			default:
				return null;
		}
	}

	public function serverToInterface(DesktopPlayer $player, DataPacket $packet){
		switch($packet->pid()){
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

				$pk = new PlayerPositionAndLookPacket();
				$pk->x = $packet->x;
				$pk->y = $packet->y;
				$pk->z = $packet->z;
				$pk->yaw = $player->yaw;
				$pk->pitch = $player->pitch;
				$pk->onGround = $player->isOnGround();
				$packets[] = $pk;
				return $packets;

			case Info::MESSAGE_PACKET:
				$pk = new STCChatPacket();

				$pk->message = json_encode([
					"text" => $packet->message
				]);
				return $pk;
			default:
				return null;
		}
	}
}