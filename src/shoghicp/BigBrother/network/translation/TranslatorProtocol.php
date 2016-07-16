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
use shoghicp\BigBrother\network\protocol\RespawnPacket;
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
use shoghicp\BigBrother\network\protocol\PlayerListPacket;
use shoghicp\BigBrother\network\protocol\EntityEquipmentPacket;
use shoghicp\BigBrother\utils\Binary;

class TranslatorProtocol implements Translator{



	public function interfaceToServer(DesktopPlayer $player, Packet $packet){
		if($packet->pid() === 4 || $packet->pid() === 5||$packet->pid() === 6){
			
		}else{
			//echo "[Receive] 0x".$packet->pid()."\n";
		}
		switch($packet->pid()){
			// TODO: move to Info
			case 0x00: //KeepAlivePacket
				$pk->id = mt_rand();
				$player->putRawPacket($pk);
				return null;

			case 0x01: //ChatPacket
				$pk = new TextPacket();
				$pk->type = 1;
				$pk->source = "";
				$pk->message = $packet->message;
				return $pk;

			case 0x02: //UseEntityPacket
				$pk = new InteractPacket();
				$pk->target = $packet->target;
				$pk->action = $packet->mouse;
				return $pk;

			case 0x03: //PlayerPacket
				$player->setSetting(["onGround" => $packet->onGround]);
				return null;

			case 0x04: //PlayerPositonPacket
				$pk = new MovePlayerPacket();
				$pk->x = $packet->x;
				$pk->y = $packet->y + $player->getEyeHeight();
				$pk->z = $packet->z;
				$pk->yaw = $player->yaw;
				$pk->bodyYaw = $player->yaw;
				$pk->pitch = $player->pitch;
				return $pk;

			case 0x05: //PlayerLookPacket
				$pk = new MovePlayerPacket();
				$pk->x = $player->x;
				$pk->y = $player->y + $player->getEyeHeight();
				$pk->z = $player->z;
				$pk->yaw = $packet->yaw;
				$pk->bodyYaw = $packet->yaw;
				$pk->pitch = $packet->pitch;
				return $pk;

			case 0x06: //PlayerPositionAndLookPacket
				$pk = new MovePlayerPacket();
				$pk->x = $packet->x;
				$pk->y = $packet->y + $player->getEyeHeight();
				$pk->z = $packet->z;
				$pk->yaw = $packet->yaw;
				$pk->bodyYaw = $packet->yaw;
				$pk->pitch = $packet->pitch;
				return $pk;
				
			case 0x07: //PlayerDiggingPacket
				switch($packet->status){
					case 0:
						if($player->getGamemode() === 1){
							$pk = new RemoveBlockPacket();
							$pk->eid = 0;
							$pk->x = $packet->x;
							$pk->y = $packet->y;
							$pk->z = $packet->z;
							return $pk;
						}else{
							$pk = new PlayerActionPacket();
							$pk->eid = 0;
							$pk->action = PlayerActionPacket::ACTION_START_BREAK;
							$pk->x = $packet->x;
							$pk->y = $packet->y;
							$pk->z = $packet->z;
							$pk->face = $packet->face;
							return $pk;
						}
					break;
					case 1:
						$pk = new PlayerActionPacket();
						$pk->eid = 0;
						$pk->action = PlayerActionPacket::ACTION_ABORT_BREAK;
						$pk->x = $packet->x;
						$pk->y = $packet->y;
						$pk->z = $packet->z;
						$pk->face = $packet->face;
						return $pk;
					break;
					case 2:
						if($player->getGamemode() !== 1){
							$packets = [];
							$pk = new PlayerActionPacket();
							$pk->eid = 0;
							$pk->action = PlayerActionPacket::ACTION_STOP_BREAK;
							$pk->x = $packet->x;
							$pk->y = $packet->y;
							$pk->z = $packet->z;
							$pk->face = $packet->face;
							$packets[] = $pk;

							$pk = new RemoveBlockPacket();
							$pk->eid = 0;
							$pk->x = $packet->x;
							$pk->y = $packet->y;
							$pk->z = $packet->z;
							$packets[] = $pk;
							return $packets;
						}else{
							//echo "PlayerDiggingPacket: ".$packet->status."\n";
						}
					break;
					default:
						//echo "PlayerDiggingPacket: ".$packet->status."\n";
					break;
				}

				return null;

			case 0x08; //PlayerBlockPlacementPacket
				//echo "PlayerBlockPlacementPacket: ".$packet->direction."\n";

				if($packet->direction !== 255){
					$pk = new UseItemPacket();
					$pk->x = $packet->x;
					$pk->y = $packet->y;
					$pk->z = $packet->z;
					$pk->face = $packet->direction;
					$pk->item = $packet->heldItem;
					$pk->fx = $packet->cursorX / 16;
					$pk->fy = $packet->cursorY / 16;
					$pk->fz = $packet->cursorZ / 16;
					$pk->posX = $player->getX();
					$pk->posY = $player->getY();
					$pk->posZ = $player->getZ();
					return $pk;
				}else{

				}

				return null;

			case 0x09: //HeldItemChangePacket
				$item = $player->getInventory()->getItem($packet->selectedSlot);
				$olditem = $player->getInventory()->getItem($player->getInventory()->getHeldItemIndex());

				if($item->getId() !== 0 or $item->getId() === 0 and $olditem->getId() !== 0){
					$pk = new MobEquipmentPacket();
					$pk->eid = 0;
					$pk->item = $item;
					if($item->getId() === 0){
						$pk->slot = 255;
					}else{
						$pk->slot = Item::getCreativeItemIndex($item) + 9;
					}
					$pk->selectedSlot = $packet->selectedSlot;
					return $pk;
				}

				return null;

			case 0x0a: //PlayerArmSwingPacket
				$pk = new AnimatePacket();
				$pk->action = 1;
				$pk->eid = 0;
				return $pk;

			case 0x0b: //AnimatePacket
				switch($packet->actionID){
					case 0:
						if(!$player->getSetting("isFlying")){
							$pk = new PlayerActionPacket();
							$pk->eid = $packet->eid;
							$pk->action = PlayerActionPacket::ACTION_START_SNEAK;
							$pk->x = $player->getX();
							$pk->y = $player->getY();
							$pk->z = $player->getZ();
							$pk->face = 0;
							return $pk;
						}
					break;
					case 1:
						if(!$player->getSetting("isFlying")){
							$pk = new PlayerActionPacket();
							$pk->eid = $packet->eid;
							$pk->action = PlayerActionPacket::ACTION_STOP_SNEAK;
							$pk->x = $player->getX();
							$pk->y = $player->getY();
							$pk->z = $player->getZ();
							$pk->face = 0;
							return $pk;
						}
					break;
					case 2:
						$pk = new PlayerActionPacket();
						$pk->eid = $packet->eid;
						$pk->action = PlayerActionPacket::ACTION_STOP_SLEEPING;
						$pk->x = $player->getX();
						$pk->y = $player->getY();
						$pk->z = $player->getZ();
						$pk->face = 0;
						return $pk;
					break;
					case 3:
						$pk = new PlayerActionPacket();
						$pk->eid = $packet->eid;
						$pk->action = PlayerActionPacket::ACTION_START_SPRINT;
						$pk->x = $player->getX();
						$pk->y = $player->getY();
						$pk->z = $player->getZ();
						$pk->face = 0;
						return $pk;
					break;
					case 4:
						$pk = new PlayerActionPacket();
						$pk->eid = $packet->eid;
						$pk->action = PlayerActionPacket::ACTION_STOP_SPRINT;
						$pk->x = $player->getX();
						$pk->y = $player->getY();
						$pk->z = $player->getZ();
						$pk->face = 0;
						return $pk;
					break;
					default:
						///echo "[AnimatePacket] ".$packet->actionID."\n";//Debug Code
					break;
				}
				return null;

			case 0x0d: //CTSCloseWindowPacket
				if($packet->windowID !== 0x00){
					$pk = new ContainerClosePacket();
					$pk->windowid = $packet->windowID;
					return $pk;
				}

			case 0x10: //CreativeInventoryActionPacket
				//echo "Slot: ".$packet->slot."\n";
				//echo "ItemId: ".$packet->item->getId()." : ".$packet->item->getDamage()."\n";

				/*if($packet->slot === 65535){
					$pk = new DropItemPacket();
					$pk->type = 0;
					$pk->item = $packet->item;
					return $pk;
				}else{
					$pk = new ContainerSetSlotPacket();
					$pk->windowid = 0;
					$pk->slot = $packet->slot;
					$pk->item = $packet->item;
					return $pk;
				}*/

				return null;

			case 0x13: //CPlayerAbilitiesPacket
				$player->setSetting(["isFlying" => $packet->isFlying]);
				return null;

			case 0x14: //CTabCompletePacket
				/*$pk = new STabComletePacket();

				foreach($player->getServer()->getCommandMap()->getCommands() as $command){
					if($command->testPermissionSilent($player)){
						$pk->matches[] = $command->getName();
					}
				}

				foreach($player->getServer()->getOnlinePlayers() as $packetplayer){
					$pk->matches[] = $packetplayer->getName();
				}

				//TODO

				//echo $packet->text."\n";

				return $pk;*/
				return null;

			case 0x15: //ClientSettingsPacket
				$player->setSetting([
					"Lang" => $packet->lang,
					"View" => $packet->view,
					"ChatMode" => $packet->chatmode,
					"ChatColor" => $packet->chatcolor,
					"SkinSettings" => $packet->skinsetting,
				]);

				return null;

			case 0x16: //ClientStatusPacket
				switch($packet->actionID){
					case 0:
						$pk = new PlayerActionPacket();
						$pk->eid = 0;
						$pk->action = PlayerActionPacket::ACTION_RESPAWN;
						$pk->x = 0;
						$pk->y = 0;
						$pk->z = 0;
						$pk->face = 0;
						return $pk;
					break;
					case 1:
						$statistic = [];
						$statistic[] = ["achievement.openInventory", 1];//
						foreach($player->achievements as $achievement => $count){
							$statistic[] = ["achievement.".$achievement, $count];
						}

						//stat
						//https://gist.github.com/thinkofdeath/a1842c21a0cf2e1fb5e0

						$pk = new StatisticsPacket();
						$pk->count = count($statistic);//TODO stat
						$pk->statistic = $statistic;
						$player->putRawPacket($pk);
					break;
					case 2:
						//$player->awardAchievement("openInventory"); this for DesktopPlayer
						//Achievement::broadcast($player, "openInventory");//Debug
					break;
				}
				return null;

			case 0x17: //PluginMessagePacket
				switch($packet->channel){
					case "REGISTER"://Mods Register
						$player->setSetting(["Channels" => $packet->data]);
					break;
					case "MC|Brand": //ServerType
						$player->setSetting(["ServerType" => $packet->data]);
					break;
					default:
						//echo "PluginChannel: ".$packet->channel."\n";
					break;
				}
				return null;

			case 0x19: //ResourcePackStatusPacket
				$player->setSetting(["ResourceStatus" => $packet->status, "ResourceHash" => $packet->hash]);
				return null;

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

			case Info::SET_TIME_PACKET:
				$pk = new TimeUpdatePacket();
				$pk->age = $packet->time;
				$pk->time = $packet->time; //TODO: calculate offset from MCPE
				return $pk;

			case Info::REMOVE_ENTITY_PACKET:
				$pk = new DestroyEntitiesPacket();
				$pk->ids[] = $packet->eid;
				return $pk;
			/*case Info::REMOVE_PLAYER_PACKET:
				$pk = new PlayerListPacket();
				$pk->actionID = PlayerListPacket::TYPE_REMOVE;
				$pk->players[] = [
					$packet->clientId->toBinary()
				];
				$packets[] = $pk;
				$pk = new DestroyEntitiesPacket();
				$pk->ids[] = $packet->eid;
				$packets[] = $pk;
				return $packets;*/

			case Info::SET_SPAWN_POSITION_PACKET:
				$pk = new SpawnPositionPacket();
				$pk->spawnX = $packet->x;
				$pk->spawnY = $packet->y;
				$pk->spawnZ = $packet->z;
				return $pk;
				break;

			case Info::SET_HEALTH_PACKET:
				$pk = new UpdateHealthPacket();
				$pk->health = $packet->health;
				$pk->food = 20;
				$pk->saturation = 5;
				return $pk;

			case Info::MOVE_ENTITY_PACKET:
			case Info::MOVE_PLAYER_PACKET:
				if($packet->eid === 0){
					$pk = new PositionAndLookPacket();
					$pk->x = $packet->x;
					$pk->y = $packet->y - $player->getEyeHeight();
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
					$pk->y = $packet->y - $player->getEyeHeight();
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

			case Info::ADD_PLAYER_PACKET:
				$packets = [];
				$packetplayer = $player->getServer()->getPlayerExact($packet->username);
				$pk = new PlayerListPacket();
				$pk->actionID = PlayerListPacket::TYPE_ADD;
				$pk->players[] = [
					$packetplayer->getUniqueId()->toBinary(),
					$packetplayer->getName(),
					[],
					$packetplayer->getGamemode(),
					0,
					false,
				];
		
				$packets[] = $pk;
				$pk = new SpawnPlayerPacket();
				$pk->eid = $packet->eid;
				$pk->uuid = $packetplayer->getUniqueId()->toBinary();
				$pk->x = $packet->x;
				$pk->z = $packet->z;
				$pk->y = $packet->y;
				$pk->yaw = $packet->yaw;
				$pk->pitch = $packet->pitch;
				$pk->item = $packetplayer->getInventory()->getItemInHand()->getId();
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

			case Info::ADD_ITEM_ENTITY_PACKET:
				$packets = [];
				$pk = new SpawnObjectPacket();
				$pk->eid = $packet->eid;
				$pk->type = 2;
				$pk->x = $player->x;
				$pk->y = $player->y;
				$pk->z = $player->z;
				$pk->yaw = $player->yaw;
				$pk->pitch = $player->pitch;
				$packets[] = $pk;

				$pk = new EntityMetadataPacket();
				$pk->eid = $packet->eid;
				$pk->metadata = $pk->metadata = [
					0 => ["type" => 0, "value" => 0],
					10 => ["type" => 5, "value" => $packet->item],
				];
				$packets[] = $pk;

				return $packets;

			case Info::UPDATE_BLOCK_PACKET:
				$pk = new BlockChangePacket();
				$count = count($packet->records) - 1;
				$pk->x = $packet->records[$count][0];
				$pk->y = $packet->records[$count][2];
				$pk->z = $packet->records[$count][1];
				$pk->blockId = $packet->records[$count][3];
				$pk->blockMeta = $packet->records[$count][4];
				return $pk;

			case Info::MOB_EQUIPMENT_PACKET:
				$pk = new EntityEquipmentPacket();
				$pk->eid = $packet->eid;
				$pk->slot = $packet->slot;
				$pk->item = $packet->item;
				return $pk;

			
			case Info::MOB_ARMOR_EQUIPMENT_PACKET:
				$packets = [];
				foreach($packet->slots as $num => $item){
					$pk = new EntityEquipmentPacket();
					$pk->eid = $packet->eid;
					$pk->slot = $num + 1;
					$pk->item = $item;
					$packets[] = $pk;
				}
				return $packets;

			case Info::SET_ENTITY_DATA_PACKET:
				$pk = new EntityMetadataPacket();
				$pk->eid = $packet->eid;
				$pk->metadata = $packet->metadata;
				return $pk;

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

			case Info::ANIMATE_PACKET:
				switch($packet->action){
					case 1:
						$pk = new CAnimatePacket();
						$pk->actionID = 0;
						$pk->eid = $packet->eid;
						return $pk;
					break;
					case 3: //LeaveBed
						$pk = new CAnimatePacket();
						$pk->actionID = 2;
						$pk->eid = $packet->eid;
						return $pk;
					break;
					default:
						//echo "AnimatePacket: ".$packet->action."\n";
					break;
				}	
				return null;

			case Info::RESPAWN_PACKET:
				$pk = new RespawnPacket();
				$pk->dimension = 0;
				$pk->difficulty = $player->getServer()->getDifficulty();
				$pk->gamemode = $player->getGamemode();
				$pk->levelType = "default";
				return $pk;

			case Info::CRAFTING_DATA_PACKET:
				$player->setSetting(["Recipes" => $packet->entries, "cleanRecipes" => $packet->cleanRecipes]);
				return null;

			/*case Info::SET_DIFFICULTY_PACKET:
				$pk = new ServerDifficultyPacket();
				$pk->difficulty = $packet->difficulty;
				return $pk;*/

			case Info::SET_PLAYER_GAMETYPE_PACKET:
				$packets  = [];
				$pk = new PlayerAbilitiesPacket();
				$pk->flyingSpeed = 0.05;
				$pk->walkingSpeed = 0.1;
				$pk->canFly = ($player->getGamemode() & 0x01) > 0;
				$pk->damageDisabled = ($player->getGamemode() & 0x01) > 0;
				$pk->isFlying = false;
				$pk->isCreative = ($player->getGamemode() & 0x01) > 0;
				$packets[] = $pk;
				$pk = new ChangeGameStatePacket();
				$pk->reason = 3;
				$pk->value = $player->getGamemode();
				$packets[] = $pk;
				return $packets;

			case Info::INTERACT_PACKET:
			case Info::PLAY_STATUS_PACKET:
			case Info::BATCH_PACKET:
			case Info::TEXT_PACKET:
				return null;

			case Info::LOGIN_PACKET:
			case Info::EXPLODE_PACKET:
			case Info::PLAYER_LIST_PACKET:
			case Info::FULL_CHUNK_DATA_PACKET:
			case Info::SERVER_TO_CLIENT_HANDSHAKE_PACKET:
			case Info::CLIENT_TO_SERVER_HANDSHAKE_PACKET:
			case Info::DISCONNECT_PACKET:
			case Info::SET_TIME_PACKET:
			case Info::ADD_ENTITY_PACKET:
			case Info::ADD_ITEM_ENTITY_PACKET:
			case Info::TAKE_ITEM_ENTITY_PACKET:
			case Info::RIDER_JUMP_PACKET:
			case Info::REMOVE_BLOCK_PACKET:
			case Info::ADD_PAINTING_PACKET:
			case Info::LEVEL_EVENT_PACKET:
			case Info::BLOCK_EVENT_PACKET:
			case Info::ENTITY_EVENT_PACKET:
			case Info::MOB_EFFECT_PACKET:
			case Info::UPDATE_ATTRIBUTES_PACKET:
			case Info::USE_ITEM_PACKET:
			case Info::PLAYER_ACTION_PACKET:
			case Info::HURT_ARMOR_PACKET:
			case Info::SET_ENTITY_LINK_PACKET:
			case Info::SET_HEALTH_PACKET:
			case Info::SET_SPAWN_POSITION_PACKET:
			case Info::DROP_ITEM_PACKET:
			case Info::CONTAINER_OPEN_PACKET:
			case Info::CONTAINER_CLOSE_PACKET:
			case Info::CONTAINER_SET_SLOT_PACKET:
			case Info::CONTAINER_SET_DATA_PACKET:
			case Info::CONTAINER_SET_CONTENT_PACKET:
			case Info::CRAFTING_EVENT_PACKET:
			case Info::ADVENTURE_SETTINGS_PACKET:
			case Info::BLOCK_ENTITY_DATA_PACKET:
			case Info::PLAYER_INPUT_PACKET:
			case Info::SET_DIFFICULTY_PACKET:
			case Info::CHANGE_DIMENSION_PACKET:
			case Info::TELEMETRY_EVENT_PACKET:
			case Info::SPAWN_EXPERIENCE_ORB_PACKET:
			case Info::CLIENTBOUND_MAP_ITEM_DATA_PACKET:
			case Info::MAP_INFO_REQUEST_PACKET:
			case Info::REQUEST_CHUNK_RADIUS_PACKET:
			case Info::CHUNK_RADIUS_UPDATED_PACKET:
			case Info::ITEM_FRAME_DROP_ITEM_PACKET:
			case Info::REPLACE_SELECTED_ITEM_PACKET:
			case Info::ADD_ITEM_PACKET:
				//return null;

			default:
				echo "[Send] 0x".$packet->pid()."\n";
				return null;
		}
	}
}