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

use pocketmine\Achievement;
use pocketmine\Player;
use pocketmine\entity\Entity;
use pocketmine\item\Item;
use pocketmine\Block\Block;
use pocketmine\level\Level;
use pocketmine\network\protocol\AddEntityPacket;
use pocketmine\network\protocol\AddItemEntityPacket;
use pocketmine\network\protocol\AddPaintingPacket;
use pocketmine\network\protocol\AddPlayerPacket;
use pocketmine\network\protocol\AdventureSettingsPacket;
use pocketmine\network\protocol\AnimatePacket;
use pocketmine\network\protocol\BatchPacket;
use pocketmine\network\protocol\ChunkRadiusUpdatedPacket;
use pocketmine\network\protocol\ContainerClosePacket;
use pocketmine\network\protocol\ContainerOpenPacket;
use pocketmine\network\protocol\ContainerSetContentPacket;
use pocketmine\network\protocol\ContainerSetDataPacket;
use pocketmine\network\protocol\ContainerSetSlotPacket;
use pocketmine\network\protocol\CraftingDataPacket;
use pocketmine\network\protocol\CraftingEventPacket;
use pocketmine\network\protocol\ChangeDimensionPacket;
use pocketmine\network\protocol\DataPacket;
use pocketmine\network\protocol\DropItemPacket;
use pocketmine\network\protocol\FullChunkDataPacket;
use pocketmine\network\protocol\ItemFrameDropItemPacket;
use pocketmine\network\protocol\RequestChunkRadiusPacket;
use pocketmine\network\protocol\SetEntityLinkPacket;
use pocketmine\network\protocol\BlockEntityDataPacket;
use pocketmine\network\protocol\EntityEventPacket;
use pocketmine\network\protocol\ExplodePacket;
use pocketmine\network\protocol\HurtArmorPacket;
use pocketmine\network\protocol\Info;
use pocketmine\network\protocol\InteractPacket;
use pocketmine\network\protocol\LevelEventPacket;
use pocketmine\network\protocol\DisconnectPacket;
use pocketmine\network\protocol\LoginPacket;
use pocketmine\network\protocol\PlayStatusPacket;
use pocketmine\network\protocol\TextPacket;
use pocketmine\network\protocol\MoveEntityPacket;
use pocketmine\network\protocol\MovePlayerPacket;
use pocketmine\network\protocol\PlayerActionPacket;
use pocketmine\network\protocol\MobArmorEquipmentPacket;
use pocketmine\network\protocol\MobEquipmentPacket;
use pocketmine\network\protocol\RemoveBlockPacket;
use pocketmine\network\protocol\RemoveEntityPacket;
use pocketmine\network\protocol\RemovePlayerPacket;
use pocketmine\network\protocol\RespawnPacket;
use pocketmine\network\protocol\SetDifficultyPacket;
use pocketmine\network\protocol\SetEntityDataPacket;
use pocketmine\network\protocol\SetEntityMotionPacket;
use pocketmine\network\protocol\SetHealthPacket;
use pocketmine\network\protocol\SetPlayerGameTypePacket;
use pocketmine\network\protocol\SetSpawnPositionPacket;
use pocketmine\network\protocol\SetTimePacket;
use pocketmine\network\protocol\StartGamePacket;
use pocketmine\network\protocol\TakeItemEntityPacket;
use pocketmine\network\protocol\BlockEventPacket;
use pocketmine\network\protocol\UpdateBlockPacket;
use pocketmine\network\protocol\UseItemPacket;
use pocketmine\network\protocol\PlayerInputPacket;
use pocketmine\math\Vector3;
use pocketmine\nbt\NBT;
use pocketmine\tile\Tile;
use pocketmine\utils\TextFormat;
use shoghicp\BigBrother\BigBrother;
use shoghicp\BigBrother\DesktopPlayer;
use shoghicp\BigBrother\network\Info as CInfo; //Computer Edition
use shoghicp\BigBrother\network\Packet;
use shoghicp\BigBrother\network\protocol\Login\LoginDisconnectPacket;
use shoghicp\BigBrother\network\protocol\Play\AnimatePacket as CAnimatePacket;
use shoghicp\BigBrother\network\protocol\Play\BlockChangePacket;
use shoghicp\BigBrother\network\protocol\Play\ChangeGameStatePacket;
use shoghicp\BigBrother\network\protocol\Play\DestroyEntitiesPacket;
use shoghicp\BigBrother\network\protocol\Play\EntityEquipmentPacket;
use shoghicp\BigBrother\network\protocol\Play\EntityHeadLookPacket;
use shoghicp\BigBrother\network\protocol\Play\EntityMetadataPacket;
use shoghicp\BigBrother\network\protocol\Play\EntityTeleportPacket;
use shoghicp\BigBrother\network\protocol\Play\EntityVelocityPacket;
use shoghicp\BigBrother\network\protocol\Play\JoinGamePacket;
use shoghicp\BigBrother\network\protocol\Play\OpenWindowPacket;
use shoghicp\BigBrother\network\protocol\Play\PlayDisconnectPacket;
use shoghicp\BigBrother\network\protocol\Play\PlayerAbilitiesPacket;
use shoghicp\BigBrother\network\protocol\Play\PlayerListPacket;
use shoghicp\BigBrother\network\protocol\Play\PositionAndLookPacket;
use shoghicp\BigBrother\network\protocol\Play\STabComletePacket;
use shoghicp\BigBrother\network\protocol\Play\ScoreboardObjectivePacket;
use shoghicp\BigBrother\network\protocol\Play\ServerDifficultyPacket;
use shoghicp\BigBrother\network\protocol\Play\SetSlotPacket;
use shoghicp\BigBrother\network\protocol\Play\SpawnObjectPacket;
use shoghicp\BigBrother\network\protocol\Play\SpawnPlayerPacket;
use shoghicp\BigBrother\network\protocol\Play\SpawnPositionPacket;
use shoghicp\BigBrother\network\protocol\Play\StatisticsPacket;
use shoghicp\BigBrother\network\protocol\Play\RespawnPacket as CRespawnPacket;
use shoghicp\BigBrother\network\protocol\Play\STCChatPacket;
use shoghicp\BigBrother\network\protocol\Play\STCCloseWindowPacket;
use shoghicp\BigBrother\network\protocol\Play\TimeUpdatePacket;
use shoghicp\BigBrother\network\protocol\Play\UpdateHealthPacket;
use shoghicp\BigBrother\network\protocol\Play\UpdateSignPacket;
use shoghicp\BigBrother\network\protocol\Play\UseBedPacket;
use shoghicp\BigBrother\network\protocol\Play\WindowItemsPacket;
use shoghicp\BigBrother\utils\Binary;

class TranslatorProtocol implements Translator{

	public function interfaceToServer(DesktopPlayer $player, Packet $packet){
		if($packet->pid() !== 0x00 and $packet->pid() !== 0x03 and $packet->pid() !== 0x04 and $packet->pid() !== 0x05 and $packet->pid() !== 0x06){
			echo "[Receive] 0x".bin2hex(chr($packet->pid()))."\n"; //Debug
		}
		
		switch($packet->pid()){
			case 0x00: //KeepAlivePacket
				$pk->id = mt_rand();
				$player->putRawPacket($pk);
				return null;

			case 0x01: //ChatPacket
				$pk = new TextPacket();
				$pk->type = 1;//Chat Type
				$pk->source = "";
				$pk->message = $packet->message;
				return $pk;

			case 0x02: //UseEntityPacket
				$pk = new InteractPacket();
				$pk->target = $packet->target;
				$pk->action = $packet->type;
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
							echo "PlayerDiggingPacket: ".$packet->status."\n";
						}
					break;
					default:
						echo "PlayerDiggingPacket: ".$packet->status."\n";
					break;
				}

				return null;

			case 0x08; //PlayerBlockPlacementPacket
				echo "PlayerBlockPlacementPacket: ".$packet->direction."\n";

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
					/*case 6:

					break;*/
					default:
						echo "[AnimatePacket] ".$packet->actionID."\n";//Debug Code
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
				echo "Slot: ".$packet->slot."\n";
				echo "ItemId: ".$packet->item->getId()." : ".$packet->item->getDamage()."\n";

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
						echo "PluginChannel: ".$packet->channel."\n";
					break;
				}
				return null;

			case 0x19: //ResourcePackStatusPacket
				$player->setSetting(["ResourceStatus" => $packet->status, "ResourceHash" => $packet->hash]);
				return null;

			default:
				echo "[Receive] 0x".bin2hex(chr($packet->pid()))."\n";
				return null;
		}
	}

	public function serverToInterface(DesktopPlayer $player, DataPacket $packet){
		switch($packet->pid()){
			case Info::DISCONNECT_PACKET:
				if($player->bigBrother_getStatus() === 0){
					$pk = new LoginDisconnectPacket();
					$pk->reason = TextFormat::toJSON($packet->message === "" ? "You have been disconnected." : $packet->message);
				}else{
					$pk = new PlayDisconnectPacket();
					$pk->reason = TextFormat::toJSON($packet->message === "" ? "You have been disconnected." : $packet->message);
				}
				return $pk;

			case Info::TEXT_PACKET:

				echo $player->getSetting("Lang")."\n";

				/*if($packet->message === "chat.type.achievement"){
					/*$pk = new ScoreboardObjectivePacket();
					$pk->ObjectiveName = $packet->parameters[0];
					$pk->Mode = 0;
					$pk->ObjectiveValue = 3;
					return $pk;*/
					/*echo "TextPacket: achievement\n";
					return null;
				}else{
					$pk = new STCChatPacket();
					$pk->message = BigBrother::toJSON($packet->message, $packet->type, $packet->parameters);
				}*/

				//return $pk;
				return null;

			case Info::SET_TIME_PACKET:
				$pk = new TimeUpdatePacket();
				$pk->age = $packet->time;
				$pk->time = $packet->time; //TODO: calculate offset from MCPE
				return $pk;

			case Info::START_GAME_PACKET:
				$packets = [];

				$pk = new JoinGamePacket();
				$pk->eid = $packet->eid;
				$pk->gamemode = $packet->gamemode;
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

				$pk = new PlayerAbilitiesPacket();
				$pk->flyingSpeed = 0.05;
				$pk->walkingSpeed = 0.1;
				$pk->canFly = ($player->getGamemode() & 0x01) > 0;
				$pk->damageDisabled = ($player->getGamemode() & 0x01) > 0;
				$pk->isFlying = false;
				$pk->isCreative = ($player->getGamemode() & 0x01) > 0;
				$packets[] = $pk;

				$pk = new PositionAndLookPacket();
				$pk->x = $packet->x;
				$pk->y = $packet->y;
				$pk->z = $packet->z;
				$pk->yaw = $player->yaw;
				$pk->pitch = $player->pitch;
				$packets[] = $pk;

				return $packets;

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

				if($packetplayer instanceof DesktopPlayer){
					$pk->players[0][2] = $packetplayer->bigBrother_getPeroperties();
				}
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

			/*case Info::ADD_ENTITY_PACKET:
				return null;*/

			case Info::REMOVE_PLAYER_PACKET:
				$pk = new PlayerListPacket();
				$pk->actionID = PlayerListPacket::TYPE_REMOVE;

				$pk->players[] = [
					$packet->clientId->toBinary()
				];
				$packets[] = $pk;

				$pk = new DestroyEntitiesPacket();
				$pk->ids[] = $packet->eid;
				$packets[] = $pk;

				return $packets;

			case Info::REMOVE_ENTITY_PACKET:
				$pk = new DestroyEntitiesPacket();
				$pk->ids[] = $packet->eid;
				return $pk;

			case Info::ADD_ITEM_ENTITY_PACKET:
				$packets = [];
				$pk = new SpawnObjectPacket();
				$pk->eid = $packet->eid;
				$pk->type = 2;
				$pk->x = $packet->x;
				$pk->y = $packet->y;
				$pk->z = $packet->z;
				$pk->yaw = $player->yaw;
				$pk->pitch = $player->pitch;
				$packets[] = $pk;

				$pk = new EntityMetadataPacket();
				$pk->eid = $packet->eid;
				$pk->metadata = [
					0 => [0 => 0, 1 => 0],
					10 => [0 => 5, 1 => $packet->item],
				];
				$packets[] = $pk;

				return $packets;

			/*case Info::REMOVE_ITEM_ENTITY_PACKET:
				return null;*/

			case Info::MOVE_ENTITY_PACKET:
				$packets = [];
				foreach($packet->entities as $d){
					$pk = new EntityTeleportPacket();
					$pk->eid = $d[0];
					$pk->x = $d[1];
					$pk->y = $d[2] - $player->getEyeHeight();
					$pk->z = $d[3];
					$pk->yaw = $d[4];
					$pk->pitch = $d[6];
					$packets[] = $pk;

					$pk = new EntityHeadLookPacket();
					$pk->eid = $d[0];
					$pk->yaw = $d[5];
					$packets[] = $pk;
				}
				return $packets;

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

			case Info::UPDATE_BLOCK_PACKET://TODO
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
				/*if(isset($packet->metadata[16])){
					if($packet->metadata[16][1] === 2){
						$pk = new UseBedPacket(); //Bug
						$pk->eid = $packet->eid;
						$bedXYZ = $player->getSetting("BedXYZ");
						$pk->bedX = $bedXYZ[0];
						$pk->bedY = $bedXYZ[1];
						$pk->bedZ = $bedXYZ[2];
						$player->removeSetting("BedXYZ");
					}else{
						$pk = new CAnimatePacket();
						$pk->eid = $packet->eid;
						$pk->actionID = 2;
					}
					return $pk;
				}elseif(isset($packet->metadata[17])){
					$player->setSetting(["BedXYZ" => $packet->metadata[17][1]]);
				}*/

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

			case Info::SET_HEALTH_PACKET:
				$pk = new UpdateHealthPacket();
				$pk->health = $packet->health;
				$pk->food = 20;
				$pk->saturation = 5;
				return $pk;

			case Info::SET_SPAWN_POSITION_PACKET:
				$pk = new SpawnPositionPacket();
				$pk->spawnX = $packet->x;
				$pk->spawnY = $packet->y;
				$pk->spawnZ = $packet->z;
				return $pk;

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
						echo "AnimatePacket: ".$packet->action."\n";
					break;
				}	
				return null;

			case Info::RESPAWN_PACKET:
				$pk = new CRespawnPacket();
				$pk->dimension = 0;
				$pk->difficulty = $player->getServer()->getDifficulty();
				$pk->gamemode = $player->getGamemode();
				$pk->levelType = "default";
				return $pk;

			/*case Info::CONTAINER_OPEN_PACKET:
				$pk = new OpenWindowPacket();
				$pk->windowID = $packet->windowid;
				$pk->inventoryType = $packet->type;
				$pk->windowTitle = "";
				$pk->slots = $packet->slots;
				return $pk;

			case Info::CONTAINER_CLOSE_PACKET:
				$pk = new STCCloseWindowPacket();
				$pk->windowID = $packet->windowid;
				return $pk;

			case Info::CONTAINER_SET_SLOT_PACKET:
				echo "ContainerSetSlotPacket: 0x".bin2hex(chr($packet->windowid))."\n";
				$pk = new SetSlotPacket();
				$pk->windowID = $packet->windowid;
				if($pk->windowID === 0x00){
					$pk->slot = $packet->slot + 36;
				}elseif($pk->windowID === 0x78){
					$pk->windowID = 0;
					$pk->slot = $packet->slot + 5;
				}else{
					$pk->slot = $packet->slot;
				}
				$pk->item = $packet->item;
				return $pk;

			case Info::CONTAINER_SET_CONTENT_PACKET://Bug
				echo "ContainerSetContentPacket: 0x".bin2hex(chr($packet->windowid))."\n";
				if($packet->windowid !== 0x79 and $packet->windowid !== 0x78){
					$pk = new WindowItemsPacket();
					$pk->windowID = 0;
					for($i = 0; $i < 5; ++$i){
						$pk->items[] = Item::get(Item::AIR, 0, 0);
					}
					$pk->items[] = $player->getInventory()->getHelmet();
					$pk->items[] = $player->getInventory()->getChestplate();
					$pk->items[] = $player->getInventory()->getLeggings();
					$pk->items[] = $player->getInventory()->getBoots();

					if($player->getGamemode() === 0){
						for($i = 9; $i < 36; ++$i){
							$pk->items[] = $player->getInventory()->getItem($i);
						}
					}else{
						for($i = 0; $i < 27; ++$i){
							$pk->items[] = Item::get(Item::AIR, 0, 0);
						}
					}
					for($i = 0; $i < 9; ++$i){
						$pk->items[] = $player->getInventory()->getItem($i);
					}
					return $pk;
				}
				return null;*/

			case Info::CRAFTING_DATA_PACKET:
				$player->setSetting(["Recipes" => $packet->entries, "cleanRecipes" => $packet->cleanRecipes]);
				return null;

			case Info::BLOCK_ENTITY_DATA_PACKET:
				$nbt = new NBT(NBT::LITTLE_ENDIAN);
				$nbt->read($packet->namedtag);
				$nbt = $nbt->getData();
				if($nbt["id"] !== Tile::SIGN){
					return null;
				}else{
					$index = Level::chunkHash($packet->x >> 4, $packet->z >> 4);
					if(isset($player->usedChunks[$index]) and $player->usedChunks[$index]){
						$pk = new UpdateSignPacket();
						$pk->x = $packet->x;
						$pk->y = $packet->y;
						$pk->z = $packet->z;
						$pk->line1 = BigBrother::toJSON($nbt["Text1"]);
						$pk->line2 = BigBrother::toJSON($nbt["Text2"]);
						$pk->line3 = BigBrother::toJSON($nbt["Text3"]);
						$pk->line4 = BigBrother::toJSON($nbt["Text4"]);
						return $pk;
					}
				}
				
				return null;

			case Info::SET_DIFFICULTY_PACKET:
				$pk = new ServerDifficultyPacket();
				$pk->difficulty = $packet->difficulty;
				return $pk;

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

			case Info::PLAY_STATUS_PACKET:
			case Info::PLAYER_LIST_PACKET:
			case Info::ADVENTURE_SETTINGS_PACKET:
			case Info::FULL_CHUNK_DATA_PACKET:
			case Info::BATCH_PACKET:
				return null;

			default:
				echo "[Send] 0x".bin2hex(chr($packet->pid()))."\n";
				return null;
		}
	}
}