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
use pocketmine\entity\Arrow;
use pocketmine\entity\Item as ItemEntity;
use pocketmine\item\Item;
use pocketmine\block\Block;
use pocketmine\level\Level;
use pocketmine\math\Vector3;
use pocketmine\network\protocol\AnimatePacket;
use pocketmine\network\protocol\BlockEntityDataPacket;
use pocketmine\network\protocol\ContainerClosePacket;
use pocketmine\network\protocol\ContainerOpenPacket;
use pocketmine\network\protocol\ContainerSetContentPacket;
use pocketmine\network\protocol\ContainerSetSlotPacket;
use pocketmine\network\protocol\CraftingEventPacket;
use pocketmine\network\protocol\DataPacket;
use pocketmine\network\protocol\DropItemPacket;
use pocketmine\network\protocol\EntityEventPacket;
use pocketmine\network\protocol\LevelEventPacket;
use pocketmine\network\protocol\Info;
use pocketmine\network\protocol\InteractPacket;
use pocketmine\network\protocol\TextPacket;
use pocketmine\network\protocol\MovePlayerPacket;
use pocketmine\network\protocol\PlayerActionPacket;
use pocketmine\network\protocol\MobArmorEquipmentPacket;
use pocketmine\network\protocol\MobEquipmentPacket;
use pocketmine\network\protocol\MobEffectPacket;
use pocketmine\network\protocol\RemoveBlockPacket;
use pocketmine\network\protocol\UseItemPacket;
use pocketmine\utils\BinaryStream;
use pocketmine\utils\TextFormat;
use pocketmine\utils\UUID;
use pocketmine\nbt\NBT;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\tile\Tile;
use shoghicp\BigBrother\BigBrother;
use shoghicp\BigBrother\DesktopPlayer;
use shoghicp\BigBrother\network\Info as CInfo; //Computer Edition
use shoghicp\BigBrother\network\Packet;
use shoghicp\BigBrother\network\protocol\Login\LoginDisconnectPacket;
use shoghicp\BigBrother\network\protocol\Play\Server\AnimatePacket as STCAnimatePacket;
use shoghicp\BigBrother\network\protocol\Play\Server\KeepAlivePacket;
use shoghicp\BigBrother\network\protocol\Play\BlockActionPacket;
use shoghicp\BigBrother\network\protocol\Play\BlockChangePacket;
use shoghicp\BigBrother\network\protocol\Play\BossBarPacket;
use shoghicp\BigBrother\network\protocol\Play\ChangeGameStatePacket;
use shoghicp\BigBrother\network\protocol\Play\CollectItemPacket;
use shoghicp\BigBrother\network\protocol\Play\DestroyEntitiesPacket;
use shoghicp\BigBrother\network\protocol\Play\EffectPacket;
use shoghicp\BigBrother\network\protocol\Play\EntityEquipmentPacket;
use shoghicp\BigBrother\network\protocol\Play\EntityEffectPacket;
use shoghicp\BigBrother\network\protocol\Play\EntityStatusPacket;
use shoghicp\BigBrother\network\protocol\Play\EntityHeadLookPacket;
use shoghicp\BigBrother\network\protocol\Play\EntityMetadataPacket;
use shoghicp\BigBrother\network\protocol\Play\EntityTeleportPacket;
use shoghicp\BigBrother\network\protocol\Play\EntityVelocityPacket;
use shoghicp\BigBrother\network\protocol\Play\EntityPropertiesPacket;
use shoghicp\BigBrother\network\protocol\Play\JoinGamePacket;
use shoghicp\BigBrother\network\protocol\Play\OpenWindowPacket;
use shoghicp\BigBrother\network\protocol\Play\PlayDisconnectPacket;
use shoghicp\BigBrother\network\protocol\Play\Server\PlayerAbilitiesPacket;
use shoghicp\BigBrother\network\protocol\Play\PlayerListPacket;
use shoghicp\BigBrother\network\protocol\Play\Server\PlayerPositionAndLookPacket;
use shoghicp\BigBrother\network\protocol\Play\Server\TabComletePacket;
use shoghicp\BigBrother\network\protocol\Play\ScoreboardObjectivePacket;
use shoghicp\BigBrother\network\protocol\Play\ServerDifficultyPacket;
use shoghicp\BigBrother\network\protocol\Play\SetSlotPacket;
use shoghicp\BigBrother\network\protocol\Play\SoundEffectPacket;
use shoghicp\BigBrother\network\protocol\Play\SpawnMobPacket;
use shoghicp\BigBrother\network\protocol\Play\SpawnObjectPacket;
use shoghicp\BigBrother\network\protocol\Play\SpawnPlayerPacket;
use shoghicp\BigBrother\network\protocol\Play\SpawnPaintingPacket;
use shoghicp\BigBrother\network\protocol\Play\SpawnPositionPacket;
use shoghicp\BigBrother\network\protocol\Play\StatisticsPacket;
use shoghicp\BigBrother\network\protocol\Play\SetPassengersPacket;
use shoghicp\BigBrother\network\protocol\Play\SetExperiencePacket;
use shoghicp\BigBrother\network\protocol\Play\RemoveEntityEffectPacket;
use shoghicp\BigBrother\network\protocol\Play\Server\ChatPacket;
use shoghicp\BigBrother\network\protocol\Play\Server\CloseWindowPacket;
use shoghicp\BigBrother\network\protocol\Play\TimeUpdatePacket;
use shoghicp\BigBrother\network\protocol\Play\UpdateHealthPacket;
use shoghicp\BigBrother\network\protocol\Play\UpdateSignPacket;
use shoghicp\BigBrother\network\protocol\Play\UpdateBlockEntityPacket;
use shoghicp\BigBrother\network\protocol\Play\UseBedPacket;
use shoghicp\BigBrother\network\protocol\Play\WindowItemsPacket;
use shoghicp\BigBrother\network\protocol\Play\NamedSoundEffectPacket;
use shoghicp\BigBrother\utils\Binary;
use shoghicp\BigBrother\utils\ConvertUtils;

class Translator_107 implements Translator{

	public function interfaceToServer(DesktopPlayer $player, Packet $packet){
		switch($packet->pid()){
			case 0x00: //TeleportConfirmPacket
				//Confirm
				return null;

			case 0x01: //TabCompletePacket
				//TODO
				return null;

			case 0x02: //ChatPacket
				$pk = new TextPacket();
				$pk->type = 1;//Chat Type
				$pk->source = "";
				$pk->message = $packet->message;
				return $pk;

			case 0x03: //ClientStatusPacket
				switch($packet->actionID){
					case 0:
						$pk = new PlayerActionPacket();
						$pk->eid = 0;

						$reflect = new \ReflectionClass($pk);
						$found = false;
						foreach($reflect->getConstants() as $constantname => $value){
							if($constantname === "ACTION_RESPAWN"){
								$pk->action = PlayerActionPacket::ACTION_RESPAWN;//for PocketMine-MP
								$found = true;
								break;
							}
						}

						if(!$found){
							$pk->action = PlayerActionPacket::ACTION_SPAWN_SAME_DIMENSION;
						}

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

						//TODO: stat https://gist.github.com/thinkofdeath/a1842c21a0cf2e1fb5e0

						$pk = new StatisticsPacket();
						$pk->count = count($statistic);//TODO stat
						$pk->statistic = $statistic;
						$player->putRawPacket($pk);
					break;
					case 2:
						//$player->awardAchievement("openInventory"); this for DesktopPlayer
						//Achievement::broadcast($player, "openInventory");//Debug
					break;
					default:
						echo "ClientStatusPacket: ".$packet->actionID."\n";
					break;
				}
				return null;

			case 0x04: //ClientSettingsPacket
				$player->setSetting([
					"Lang" => $packet->lang,
					"View" => $packet->view,
					"ChatMode" => $packet->chatmode,
					"ChatColor" => $packet->chatcolor,
					"SkinSettings" => $packet->skinsetting,
				]);

				return null;

			case 0x07: //ClickWindowPacket
				$pk = new ContainerSetSlotPacket();
				$pk->windowid = $packet->windowID;
				$pk->hotbarSlot = 0;//unused
				$pk->item = $packet->clickedItem;

				switch($packet->mode){
					case 0:
						switch($packet->button){
							case 0:
								//var_dump($packet->slot);
								//var_dump($packet);
								//$pk->slot = $packet->slot - 5;
							break;
							/*case 1:

							break;*/
						}
					break;
					default:
						echo "ClickWindowPacket: ".$packet->mode."\n";
					break;
				}


				/*

					
				}else{//Inventory
					$pk->windowid = 0;

					if($packet->slot > 35 and $packet->slot < 45){//hotbar
						$pk->slot = $packet->slot - 36;
					}else{
						$pk->slot = $packet->slot + 9;
						//TODO: hotbar slot in inventory slot
					}
				}

				*/
				return null;

			case 0x08: //CloseWindowPacket
				if($packet->windowID !== 0x00){
					$pk = new ContainerClosePacket();
					$pk->windowid = $packet->windowID;
					return $pk;
				}
				return null;

			case 0x09: //PluginMessagePacket
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

			case 0x0a: //UseEntityPacket
				$pk = new InteractPacket();
				$pk->target = $packet->target;

				switch($packet->type){
					case 0://interact
						$pk->action = InteractPacket::ACTION_RIGHT_CLICK;
					break;
					case 1://attack
						$pk->action = InteractPacket::ACTION_LEFT_CLICK;
					break;
					case 2://interact at
						$pk->action = InteractPacket::ACTION_MOUSEOVER;
					break;
				}

				return $pk;

			case 0x0b: //KeepAlivePacket
				$pk = new KeepAlivePacket();
				$pk->id = mt_rand();
				$player->putRawPacket($pk);

				return null;

			case 0x0c: //PlayerPositonPacket
				$packets = [];
				$pk = new MovePlayerPacket();
				$pk->x = $packet->x;
				$pk->y = $packet->y + $player->getEyeHeight();
				$pk->z = $packet->z;
				$pk->yaw = $player->yaw;
				$pk->bodyYaw = $player->yaw;
				$pk->pitch = $player->pitch;
				$packets[] = $pk;

				if(strpos($player->y, ".") === false){
					if(strpos($packet->y, ".") !== false){
						if(floor($player->y) === floor($packet->y)){
							$pk = new PlayerActionPacket();
							$pk->eid = 0;
							$pk->action = PlayerActionPacket::ACTION_JUMP;
							$pk->x = $packet->x;
							$pk->y = $packet->y;
							$pk->z = $packet->z;
							$pk->face = 0;
							$packets[] = $pk;
						}
					}
				}

				return $packets;

			case 0x0d: //PlayerPositionAndLookPacket
				$packets = [];
				$pk = new MovePlayerPacket();
				$pk->x = $packet->x;
				$pk->y = $packet->y + $player->getEyeHeight();
				$pk->z = $packet->z;
				$pk->yaw = $packet->yaw;
				$pk->bodyYaw = $packet->yaw;
				$pk->pitch = $packet->pitch;
				$packets[] = $pk;

				if(strpos($player->y, ".") === false){
					if(strpos($packet->y, ".") !== false){
						if(floor($player->y) === floor($packet->y)){
							$pk = new PlayerActionPacket();
							$pk->eid = 0;
							$pk->action = PlayerActionPacket::ACTION_JUMP;
							$pk->x = $packet->x;
							$pk->y = $packet->y;
							$pk->z = $packet->z;
							$pk->face = 0;
							$packets[] = $pk;
						}
					}
				}

				return $packets;

			case 0x0e: //PlayerLookPacket
				$pk = new MovePlayerPacket();
				$pk->x = $player->x;
				$pk->y = $player->y + $player->getEyeHeight();
				$pk->z = $player->z;
				$pk->yaw = $packet->yaw;
				$pk->bodyYaw = $packet->yaw;
				$pk->pitch = $packet->pitch;
				return $pk;

			case 0x0f: //PlayerPacket
				$player->setSetting(["onGround" => $packet->onGround]);
				return null;
			
			case 0x12: //PlayerAbilitiesPacket
				$player->setSetting(["isFlying" => $packet->isFlying]);
				return null;

			case 0x13: //PlayerDiggingPacket
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
					case 5:
						$item = $player->getInventory()->getItemInHand();
						if($item->getId() === Item::BOW){//Shoot Arrow
							$pk = new PlayerActionPacket();
							$pk->eid = 0;
							$pk->action = PlayerActionPacket::ACTION_RELEASE_ITEM;
							$pk->x = $packet->x;
							$pk->y = $packet->y;
							$pk->z = $packet->z;
							$pk->face = $packet->face;
						}else{//Eating
							$pk = new EntityEventPacket();
							$pk->eid = 0;
							$pk->event = EntityEventPacket::USE_ITEM;
						}

						return $pk;
					break;
					default:
						echo "PlayerDiggingPacket: ".$packet->status."\n";
					break;
				}

				return null;

			case 0x14: //EntityActionPacket
				switch($packet->actionID){
					case 0://Start sneaking
						$pk = new PlayerActionPacket();
						$pk->eid = 0;
						$pk->action = PlayerActionPacket::ACTION_START_SNEAK;
						$pk->x = 0;
						$pk->y = 0;
						$pk->z = 0;
						$pk->face = 0;
						return $pk;
					break;
					case 1://Stop sneaking
						$pk = new PlayerActionPacket();
						$pk->eid = 0;
						$pk->action = PlayerActionPacket::ACTION_STOP_SNEAK;
						$pk->x = 0;
						$pk->y = 0;
						$pk->z = 0;
						$pk->face = 0;
						return $pk;
					break;
					case 2://leave bed
						$pk = new PlayerActionPacket();
						$pk->eid = 0;
						$pk->action = PlayerActionPacket::ACTION_STOP_SLEEPING;
						$pk->x = 0;
						$pk->y = 0;
						$pk->z = 0;
						$pk->face = 0;
						return $pk;
					break;
					case 3://Start sprinting
						$pk = new PlayerActionPacket();
						$pk->eid = 0;
						$pk->action = PlayerActionPacket::ACTION_START_SPRINT;
						$pk->x = 0;
						$pk->y = 0;
						$pk->z = 0;
						$pk->face = 0;
						return $pk;
					break;
					case 4://Stop sprinting
						$pk = new PlayerActionPacket();
						$pk->eid = 0;
						$pk->action = PlayerActionPacket::ACTION_STOP_SPRINT;
						$pk->x = 0;
						$pk->y = 0;
						$pk->z = 0;
						$pk->face = 0;
						return $pk;
					break;
					default:
						echo "EntityActionPacket: ".$packet->actionID."\n";
					break;
				}

				return null;

			case 0x17: //HeldItemChangePacket
				$slot = $player->getInventory()->getHotbarSlotIndex($packet->selectedSlot);

				$pk = new MobEquipmentPacket();
				$pk->eid = 0;
				$pk->item = $player->getInventory()->getItem($slot);
				$pk->slot = $slot + 9;
				$pk->inventorySlot = $pk->slot;//for PocketMine-MP
				$pk->selectedSlot = $packet->selectedSlot;
				$pk->hotbarSlot = $pk->selectedSlot;//for PocketMine-MP

				return $pk;

			case 0x18: //CreativeInventoryActionPacket
				if($packet->slot === 65535){
					$pk = new DropItemPacket();
					$pk->type = 0;
					$pk->item = $packet->item;
					return $pk;
				}else{
					$pk = new ContainerSetSlotPacket();

					if($packet->slot > 4 and $packet->slot < 9){//Armor
						$pk->windowid = ContainerSetContentPacket::SPECIAL_ARMOR;

						$pk->slot = $packet->slot - 5;
					}else{//Inventory
						$pk->windowid = 0;

						if($packet->slot > 35 and $packet->slot < 45){//hotbar
							$pk->slot = $packet->slot - 36;
						}else{
							$pk->slot = $packet->slot + 9;
							//TODO: hotbar slot in inventory slot
						}
					}

					$pk->hotbarSlot = 0;//unused
					$pk->item = $packet->item;
					return $pk;
				}

			case 0x19: //UpdateSignPacket
				$tags = new CompoundTag("", [
					new StringTag("id", Tile::SIGN),
					new StringTag("Text1", $packet->line1),
					new StringTag("Text2", $packet->line2),
					new StringTag("Text3", $packet->line3),
					new StringTag("Text4", $packet->line4),
					new IntTag("x", (int) $packet->x),
					new IntTag("y", (int) $packet->y),
					new IntTag("z", (int) $packet->z)
				]);

				$nbt = new NBT(NBT::LITTLE_ENDIAN);
				$nbt->setData($tags);

				$pk = new BlockEntityDataPacket();
				$pk->x = $packet->x;
				$pk->y = $packet->y;
				$pk->z = $packet->z;
				$pk->namedtag = $nbt->write(true);

				return $pk;

			case 0x1a: //AnimatePacket
				$pk = new AnimatePacket();
				$pk->action = 1;
				$pk->eid = $player->getId();
				return $pk;

			case 0x1c; //PlayerBlockPlacementPacket
				if($packet->direction !== 255){
					$pk = new UseItemPacket();
					$pk->x = $packet->x;
					$pk->y = $packet->y;
					$pk->z = $packet->z;
					$pk->blockId = $player->getInventory()->getItemInHand()->getId();
					$pk->face = $packet->direction;
					$pk->item = $player->getInventory()->getItemInHand();
					$pk->fx = $packet->cursorX;
					$pk->fy = $packet->cursorY;
					$pk->fz = $packet->cursorZ;
					$pk->posX = $player->getX();
					$pk->posY = $player->getY();
					$pk->posZ = $player->getZ();
					$pk->slot = $player->getInventory()->getHeldItemSlot();

					return $pk;
				}else{
					echo "PlayerBlockPlacementPacket: ".$packet->direction."\n";
				}

				return null;

			case 0x1d://UseItemPacket
				$pk = new UseItemPacket();
				$pk->x = 0;
				$pk->y = 0;
				$pk->z = 0;
				$pk->blockId = $player->getInventory()->getItemInHand()->getId();
				$pk->face = -1;
				$pk->item = $player->getInventory()->getItemInHand();
				$pk->fx = 0;
				$pk->fy = 0;
				$pk->fz = 0;
				$pk->posX = $player->getX();
				$pk->posY = $player->getY();
				$pk->posZ = $player->getZ();
				$pk->slot = $player->getInventory()->getHeldItemSlot();
				return $pk;

			default:
				echo "[Receive][Translator] 0x".bin2hex(chr($packet->pid()))." Not implemented\n";
				return null;
		}
	}

	public function serverToInterface(DesktopPlayer $player, DataPacket $packet){
		switch($packet->pid()){
			case Info::DISCONNECT_PACKET:
				if($player->bigBrother_getStatus() === 0){
					$pk = new LoginDisconnectPacket();
					$pk->reason = BigBrother::toJSON($packet->message === "" ? "You have been disconnected." : $packet->message);
				}else{
					$pk = new PlayDisconnectPacket();
					$pk->reason = BigBrother::toJSON($packet->message === "" ? "You have been disconnected." : $packet->message);
				}
				return $pk;

			case Info::TEXT_PACKET:
				if($packet->message === "chat.type.achievement"){
					return null;//TODO
				}else{
					$pk = new ChatPacket();
					$pk->message = BigBrother::toJSON($packet->message, $packet->source, $packet->type, $packet->parameters);
					switch($packet->type){
						case TextPacket::TYPE_CHAT:
						case TextPacket::TYPE_TRANSLATION:
						case TextPacket::TYPE_WHISPER:
						case TextPacket::TYPE_RAW:
							$pk->position = 0;
							break;
						case TextPacket::TYPE_SYSTEM:
							$pk->position = 1;
							break;
						case TextPacket::TYPE_POPUP:
						case TextPacket::TYPE_TIP:
							$pk->position = 2;
							break;
					}
				}

				return $pk;

			case Info::SET_TIME_PACKET:
				$pk = new TimeUpdatePacket();
				$pk->age = $packet->time;
				$pk->time = $packet->time;
				return $pk;

			case Info::START_GAME_PACKET:
				$packets = [];

				$pk = new JoinGamePacket();
				$pk->eid = $packet->entityUniqueId;
				$pk->gamemode = $packet->gamemode;
				$pk->dimension = $player->bigBrother_getDimensionPEToPC($pk->dimension);
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

				$pk = new PlayerPositionAndLookPacket();
				$pk->x = $packet->x;
				$pk->y = $packet->y;
				$pk->z = $packet->z;
				$pk->yaw = $player->yaw;
				$pk->pitch = $player->pitch;
				$pk->teleportId = 0;
				$packets[] = $pk;

				return $packets;

			case Info::ADD_PLAYER_PACKET:
				$packets = [];

				$pk = new SpawnPlayerPacket();
				$pk->eid = $packet->eid;
				$pk->uuid = $packet->uuid->toBinary();
				$pk->x = $packet->x;
				$pk->z = $packet->z;
				$pk->y = $packet->y;
				$pk->yaw = $packet->yaw;
				$pk->pitch = $packet->pitch;
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

			case Info::ADD_ENTITY_PACKET:
				$packets = [];

				$isobject = false;

				switch($packet->type){
					case 10://Chicken
						$packet->type = 93;
					break;
					case 11://Cow
						$packet->type = 92;
					break;
					case 12://Pig
						$packet->type = 90;
					break;
					case 13://Sheep
						$packet->type = 91;
					break;
					case 14://Wolf
						$packet->type = 95;
					break;
					case 15://Villager
						$packet->type = 120;
					break;
					case 16://Mooshroom
						$packet->type = 96;
					break;
					case 17://Squid
						$packet->type = 94;
					break;
					case 18://Rabbit
						$packet->type = 101;
					break;
					case 19://Bat
						$packet->type = 65;
					break;
					case 20://IronGolem
						$packet->type = 99;
					break;
					case 21://SnowGolem (Snowman)
						$packet->type = 97;
					break;
					case 22://Ocelot
						$packet->type = 98;
					break;
					case 23://Horse
						$packet->type = 100;
					break;
					case 28://PolarBear
						$packet->type = 102;
					break;
					case 32://Zombie
						$packet->type = 54;
					break;
					case 33://Creeper
						$packet->type = 50;
					break;
					case 34://Skeleton
						$packet->type = 51;
					break;
					case 35://Spider
						$packet->type = 52;
					break;
					case 36://PigZombie
						$packet->type = 57;
					break;
					case 37://Slime
						$packet->type = 55;
					break;
					case 38://Enderman
						$packet->type = 58;
					break;
					case 39://Silverfish
						$packet->type = 60;
					break;
					case 40://CaveSpider
						$packet->type = 59;
					break;
					case 41://Ghast
						$packet->type = 56;
					break;
					case 42://LavaSlime
						$packet->type = 62;
					break;
					case 43://Blaze
						$packet->type = 61;
					break;
					case 44://ZombieVillager
						$packet->type = 27;
					break;
					case 45://Witch
						$packet->type = 66;
					break;
					case 46://Stray
						$packet->type = 6;
					break;
					case 47://Husk
						$packet->type = 23;
					break;
					case 48://WitherSkeleton
						$packet->type = 5;
					break;
					case 49://Guardian
						$packet->type = 68;
					break;
					case 50://ElderGuardian
						$packet->type = 4;
					break;
					/*case 52://Wither (Skull)
						//Spawn Object
					break;*/
					case 53://EnderDragon
						$packet->type = 63;
					break;
					case 54://Shulker
						$packet->type = 69;
					break;
					/*case 64://Item
						//Spawn Object
					break;*/
					case 65://PrimedTNT
						//Spawn Object
						$isobject = true;
						$packet->type = 50;
					break;
					case 66://FallingSand
						//Spawn Object
						$isobject = true;
						$packet->type = 70;
					break;
					/*case 68://ThrownExpBottle
						//Spawn Object
					break;
					case 69://XPOrb
						//Spawn Experience Orb
					break;
					case 71://EnderCrystal
						//Spawn Object
					break;
					case 76://ShulkerBullet
						//Spawn Object
					break;*/
					case 77://FishingHook
						//Spawn Object
						$isobject = true;
						$packet->type = 90;
					break;
					/*case 79://DragonFireBall
						//Spawn Object
					break;*/
					case 80://Arrow
						//Spawn Object
						$isobject = true;
						$packet->type = 60;
					break;
					case 81://Snowball
						//Spawn Object
						$isobject = true;
						$packet->type = 61;
					break;
					case 82://Egg
						//Spawn Object
						$isobject = true;
						$packet->type = 62;
					break;
					/*case 83://Painting
						//Spawn Painting
					break;
					case 84://Minecart
						//Spawn Object
					break;
					case 85://GhastFireball
						//Spawn Object
					break;
					case 86://ThrownPotion
						//Spawn Object
					break;
					case 87://EnderPearl
						//Spawn Object
					break;
					case 88://LeashKnot
						//Spawn Object
					break;
					case 89://BlueWitherSkull
						//Spawn Object
					break;*/
					case 90;//Boat
						$packet->type = 1;
					break;
					/*case 93://Lightning
						//Spawn Global Entity
					break;
					case 94://BlazeFireball
						//Spawn Object
					break;
					case 96://MinecartHopper
						//Spawn Object
					break;
					case 97:MinecartTNT
						//Spawn Object
					break;
					case 98://MinecartChest
						//Spawn Object
					break;*/
					default:
						$packet->type = 57;
						echo "AddEntityPacket: ".$packet->eid."\n";
					break;
				}

				if($isobject){
					$pk = new SpawnObjectPacket();
					$pk->eid = $packet->eid;
					$pk->type = $packet->type;
					$pk->uuid = UUID::fromRandom()->toBinary();
					$pk->x = $packet->x;
					$pk->y = $packet->y;
					$pk->z = $packet->z;
					$pk->yaw = 0;
					$pk->pitch = 0;
					$pk->data = 1;
					$pk->velocityX = 0;
					$pk->velocityY = 0;
					$pk->velocityZ = 0;
				}else{
					$pk = new SpawnMobPacket();
					$pk->eid = $packet->eid;
					$pk->type = $packet->type;
					$pk->uuid = UUID::fromRandom()->toBinary();
					$pk->x = $packet->x;
					$pk->z = $packet->z;
					$pk->y = $packet->y;
					$pk->yaw = $packet->yaw;
					$pk->pitch = $packet->pitch;
					$pk->metadata = $packet->metadata;
				}
				
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

			case Info::REMOVE_ENTITY_PACKET:
				$pk = new DestroyEntitiesPacket();
				$pk->ids[] = $packet->eid;
				return $pk;

			case Info::ADD_ITEM_ENTITY_PACKET://Bug
				//echo "AddItemEntityPacket: ".$packet->eid."\n";

				$item = clone $packet->item;
				ConvertUtils::convertItemData(true, $item);

				$packets = [];

				$pk = new SpawnObjectPacket();
				$pk->eid = $packet->eid;
				$pk->uuid = UUID::fromRandom()->toBinary();
				$pk->type = 2;
				$pk->x = $packet->x;
				$pk->y = $packet->y;
				$pk->z = $packet->z;
				$pk->yaw = 0;
				$pk->pitch = 0;
				$pk->data = 1;
				$pk->velocityX = $packet->speedX;
				$pk->velocityY = $packet->speedY;
				$pk->velocityZ = $packet->speedZ;
				$packets[] = $pk;

				$pk = new EntityMetadataPacket();
				$pk->eid = $packet->eid;
				$pk->metadata = [
					0 => [0, 0],
					6 => [5, $item],
					"convert" => true,
				];
				$packets[] = $pk;

				return $packets;

			case Info::TAKE_ITEM_ENTITY_PACKET:
				$itemCount = 1;

				if(($entity = $player->getLevel()->getEntity($packet->target)) instanceof ItemEntity){
					$itemCount = $entity->getItem()->getCount();
				}

				$pk = new CollectItemPacket();
				$pk->eid = $packet->eid;
				$pk->target = $packet->target;
				$pk->itemCount = $itemCount;

				return $pk;

			case Info::MOVE_ENTITY_PACKET:
				if($packet->eid === $player->getId()){//TODO
					return null;
				}else{
					//echo "MoveEntityPacket: ".$packet->eid."\n";

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

			case Info::MOVE_PLAYER_PACKET:
				if($packet->eid === $player->getId()){//TODO
					$pk = new PlayerPositionAndLookPacket();
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

			case Info::UPDATE_BLOCK_PACKET:
				ConvertUtils::convertBlockData(true, $packet->blockId, $packet->blockData);

				$pk = new BlockChangePacket();
				$pk->x = $packet->x;
				$pk->y = $packet->y;
				$pk->z = $packet->z;
				$pk->blockId = $packet->blockId;
				$pk->blockMeta = $packet->blockData;
				return $pk;

			/*case Info::ADD_PAINTING_PACKET://Bug
				$pk = new SpawnPaintingPacket();
				$pk->eid = $packet->eid;
				$pk->uuid = UUID::fromRandom()->toBinary();
				$pk->title = $packet->title;
				$pk->x = $packet->x;
				$pk->y = $packet->y;
				$pk->z = $packet->z;
				$pk->direction = $packet->direction;

				//var_dump($pk);

				return $pk;*/

			case Info::LEVEL_EVENT_PACKET://TODO
				$issoundeffect = false;

				if($packet->evid & LevelEventPacket::EVENT_ADD_PARTICLE_MASK){
					$packet->evid &= ~LevelEventPacket::EVENT_ADD_PARTICLE_MASK;
				}

				switch($packet->evid){
					case LevelEventPacket::EVENT_SOUND_SHOOT:
						$issoundeffect = true;
						$category = 0;

						switch(($id = $player->getInventory()->getItemInHand()->getId())){
							case Item::SNOWBALL:
								$name = "entity.snowball.throw";
							break;
							case Item::EGG:
								$name = "entity.egg.throw";
							break;
							case Item::ENCHANTING_BOTTLE:
								$name = "entity.experience_bottle.throw";
							break;
							case Item::SPLASH_POTION:
								$name = "entity.splash_potion.throw";
							break;
							case Item::ENDER_PEARL:
								$name = "entity.enderpearl.throw";
							break;
							default:
								echo "LevelEventPacket: ".$id."\n";
							break;
						}
					break;
					default:
						echo "LevelEventPacket: ".$packet->evid."\n";
					break;
				}


				if($issoundeffect){
					$pk = new NamedSoundEffectPacket();
					$pk->category = $category;
					$pk->x = $packet->x;
					$pk->y = $packet->y;
					$pk->z = $packet->z;
					$pk->volume = 0.5;
					$pk->pitch = 1.0;
					$pk->name = $name;
				}else{
					$pk = new EffectPacket();
					$pk->effectId = $packet->evid;
					$pk->x = $packet->x;
					$pk->y = $packet->y;
					$pk->z = $packet->z;
					$pk->data = $packet->data;
					$pk->disableRelativeVolume = false;
				}

				return $pk;

			case Info::BLOCK_EVENT_PACKET:
				$packets = [];

				$pk = new BlockActionPacket();
				$pk->x = $packet->x;
				$pk->y = $packet->y;
				$pk->z = $packet->z;
				$pk->actionID = $packet->case1;
				$pk->actionParam = $packet->case2;
				$pk->blockType = $block = $player->getLevel()->getBlock(new Vector3($packet->x, $packet->y, $packet->z))->getId();
				$packets[] = $pk;

				if($packet->case1 === 1){//TODO: EnderChest
					$pk = new NamedSoundEffectPacket();
					$pk->category = 1;
					$pk->x = $packet->x;
					$pk->y = $packet->y;
					$pk->z = $packet->z;
					$pk->volume = 0.5;
					$pk->pitch = 1.0;

					if($packet->case2 >= 1){
						if($block === Block::ENDER_CHEST){
							$pk->name = "block.enderchest.open";
						}else{
							$pk->name = "block.chest.open";
						}
					}else{
						if($block === Block::ENDER_CHEST){
							$pk->name = "block.enderchest.close";
						}else{
							$pk->name = "block.chest.close";
						}
					}

					$packets[] = $pk;
				}

				return $packets;

			case Info::ENTITY_EVENT_PACKET:
				switch($packet->event){
					case EntityEventPacket::HURT_ANIMATION:
						$pk = new EntityStatusPacket();
						$pk->status = 2;
						$pk->eid = $packet->eid;

						//TODO: sound

						return $pk;
					break;
					case EntityEventPacket::DEATH_ANIMATION:
						$pk = new EntityStatusPacket();
						$pk->status = 3;
						$pk->eid = $packet->eid;

						//TODO: sound

						return $pk;
					break;
					case EntityEventPacket::RESPAWN:
						//unused
					break;
					default:
						echo "EntityEventPacket: ".$packet->event."\n";
					break;
				}

				return null;

			case Info::MOB_EFFECT_PACKET:
				switch($packet->eventId){
					case MobEffectPacket::EVENT_ADD:
					case MobEffectPacket::EVENT_MODIFY:
						$flags = 0;
						if($packet->particles){
							$flags |= 0x02;
						}

						$pk = new EntityEffectPacket();
						$pk->eid = $packet->eid;
						$pk->effectId = $packet->effectId;
						$pk->amplifier = $packet->amplifier;
						$pk->duration = $packet->duration;
						$pk->flags = $flags;

						return $pk;
					break;
					case MobEffectPacket::EVENT_REMOVE:
						$pk = new RemoveEntityEffectPacket();
						$pk->eid = $packet->eid;
						$pk->effectId = $packet->effectId;

						return $pk;
					break;
					default:
						echo "MobEffectPacket: ".$packet->eventId."\n";
					break;
				}
				
				return null;

			case Info::UPDATE_ATTRIBUTES_PACKET:
				$packets = [];
				$entries = [];

				foreach($packet->entries as $entry){
					//echo "UpdateAtteributesPacket: ".$entry->getName()."\n";
					switch($entry->getName()){
						case "minecraft:player.saturation":
						case "minecraft:player.hunger":
							//move to minecraft:health
						break;
						case "minecraft:health":
							if($packet->entityId === $player->getId()){
								$pk = new UpdateHealthPacket();
								$pk->health = $entry->getValue();//TODO: Defalut Value
								$pk->food = $player->getFood();//TODO: Default Value
								$pk->saturation = $player->getSaturation();//TODO: Default Value

							}elseif($player->getSetting("BossBar") !== false){
								if($packet->entityId === $player->getSetting("BossBar")[0]){
									$pk = new BossBarPacket();
									$pk->uuid = $player->getSetting("BossBar")[1];//Temporary
									$pk->actionID = BossBarPacket::TYPE_UPDATE_HEALTH;
									//$pk->health = $entry->getValue();//
									$pk->health = 1;
								}
							}else{
								$pk = new EntityMetadataPacket();
								$pk->eid = $packet->entityId;
								$pk->metadata = [
									7 => [2, $entry->getValue()],
									"convert" => true,
								];
							}

							$packets[] = $pk;
						break;
						case "minecraft:movement":
							$entries[] = [
								"generic.movementSpeed",
								$entry->getValue()//TODO: Default Value
							];
						break;
						case "minecraft:player.experience":
							if($packet->entityId === $player->getId()){
								$pk = new SetExperiencePacket();
								$pk->experience = $entry->getValue();//TODO: Default Value
								$pk->level = $player->getXpLevel();//TODO: Default Value
								$pk->totalexperience = $player->getTotalXp();//TODO: Default Value

								$packets[] = $pk;
							}
						break;
						case "minecraft:player.level":
							//move to minecraft:player.experience
						break;
						default:
							echo "UpdateAtteributesPacket: ".$entry->getName()."\n";
						break;
					}
				}

				if(count($entries) > 0){
					$pk = new EntityPropertiesPacket();
					$pk->eid = $packet->entityId;
					$pk->entries = $entries;
					$packets[] = $pk;
				}

				return $packets;

			case Info::MOB_EQUIPMENT_PACKET:
				$pk = new EntityEquipmentPacket();
				$pk->eid = $packet->eid;
				$pk->slot = 0;//main hand
				$pk->item = $packet->item;

				return $pk;

			case Info::MOB_ARMOR_EQUIPMENT_PACKET:
				$packets = [];

				foreach($packet->slots as $num => $item){
					$pk = new EntityEquipmentPacket();
					$pk->eid = $packet->eid;
					$pk->slot = $num + 2;
					$pk->item = $item;
					$packets[] = $pk;
				}

				return $packets;

			case Info::SET_ENTITY_DATA_PACKET:
				$packets = [];

				if($player->getSetting("BossBar") !== false){
					if($packet->eid === $player->getSetting("BossBar")[0]){
						if(isset($packet->metadata[Entity::DATA_NAMETAG])){
							$title = str_replace("\n", "", $packet->metadata[Entity::DATA_NAMETAG][1]);
						}else{
							$title = "Test";
						}

						$pk = new BossBarPacket();
						$pk->uuid = $player->getSetting("BossBar")[1];
						$pk->actionID = BossBarPacket::TYPE_UPDATE_TITLE;
						$pk->title = BigBrother::toJSON($title);

						$packets[] = $pk;
					}
				}

				if(isset($packet->metadata[Player::DATA_PLAYER_BED_POSITION])){
					$bedXYZ = $packet->metadata[Player::DATA_PLAYER_BED_POSITION][1];
					if($bedXYZ[0] !== 0 or $bedXYZ[1] !== 0 or $bedXYZ[2] !== 0){
						$pk = new UseBedPacket();
						$pk->eid = $packet->eid;
						$pk->bedX = $bedXYZ[0];
						$pk->bedY = $bedXYZ[1];
						$pk->bedZ = $bedXYZ[2];
						
						$packets[] = $pk;
					}
				}

				$pk = new EntityMetadataPacket();
				$pk->eid = $packet->eid;
				$pk->metadata = $packet->metadata;
				$packets[] = $pk;

				return $packets;

			case Info::SET_ENTITY_MOTION_PACKET:
				$pk = new EntityVelocityPacket();
				$pk->eid = $packet->eid;
				$pk->velocityX = $packet->motionX;
				$pk->velocityY = $packet->motionY;
				$pk->velocityZ = $packet->motionZ;
				return $pk;

			/*case Info::SET_ENTITY_LINK_PACKET:
				$pk = new SetPassengersPacket();
				$pk->eid = $packet->from;
				$pk->passengers = [$packet->to];
				return $pk;*/

			case Info::SET_HEALTH_PACKET:
				$pk = new UpdateHealthPacket();
				$pk->health = $packet->health;//TODO: Default Value
				$pk->food = $player->getFood();//TODO: Default Value
				$pk->saturation = $player->getSaturation();//TODO: Default Value
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
						$pk = new STCAnimatePacket();
						$pk->actionID = 0;
						$pk->eid = $packet->eid;
						return $pk;
					break;
					case 3: //Leave Bed
						$pk = new STCAnimatePacket();
						$pk->actionID = 2;
						$pk->eid = $packet->eid;
						return $pk;
					break;
					default:
						echo "AnimatePacket: ".$packet->actionID."\n";
					break;
				}	
				return null;

			case Info::CONTAINER_OPEN_PACKET:
				$type = "";
				switch($packet->type){
					case 0:
						$type = "minecraft:chest";
						$title = "Chest Inventory";
					break;
					case 1:
						$type = "minecraft:crafting_table";
						$title = "Crafting Table Inventory";
					break;
					case 2:
						$type = "minecraft:furnace";
						$title = "Furnace Inventory";
					break;
					default:
						echo "ContainerOpenPacket: ".$packet->type."\n";
						//TODO: http://wiki.vg/Inventory#Windows
						$title = "Unknown Inventory";
					break;
				}

				$pk = new OpenWindowPacket();
				$pk->windowID = $packet->windowid;
				$pk->inventoryType = $type;
				$pk->windowTitle = BigBrother::toJSON($title);
				$pk->slots = $packet->slots;

				$player->setSetting(["windowid:".$packet->windowid => [$packet->type, $packet->slots]]);

				return $pk;

			case Info::CONTAINER_CLOSE_PACKET:
				$pk = new CloseWindowPacket();
				$pk->windowID = $packet->windowid;

				$player->removeSetting("windowid:".$packet->windowid);

				return $pk;

			case Info::CONTAINER_SET_SLOT_PACKET:
				$pk = new SetSlotPacket();
				$pk->windowID = $packet->windowid;

				switch($packet->windowid){
					case ContainerSetContentPacket::SPECIAL_INVENTORY:




						$pk->slot = $packet->slot + 18;
						$pk->item = $packet->item;

						//

						var_dump($pk);

						return $pk;
					case ContainerSetContentPacket::SPECIAL_ARMOR:
						//TODO
					break;
					case ContainerSetContentPacket::SPECIAL_CREATIVE:
					case ContainerSetContentPacket::SPECIAL_HOTBAR:
					break;
					default:
						echo "ContainerSetSlotPacket: 0x".bin2hex(chr($packet->windowid))."\n";
					break;
				}

				return null;

			case Info::CONTAINER_SET_CONTENT_PACKET:
				$pk = new WindowItemsPacket();
				$pk->windowID = $packet->windowid;

				switch($packet->windowid){
					case ContainerSetContentPacket::SPECIAL_INVENTORY:
						for($i = 0; $i < 5; ++$i){
							$pk->items[] = Item::get(Item::AIR, 0, 0);//Craft Inventory
						}

						$pk->items[] = $player->getInventory()->getHelmet();
						$pk->items[] = $player->getInventory()->getChestplate();
						$pk->items[] = $player->getInventory()->getLeggings();
						$pk->items[] = $player->getInventory()->getBoots();

						$hotbar = [];
						$hotbardata = [];
						for($i = 0; $i < 9; $i++){ 
							$hotbardata[] = $player->getInventory()->getHotbarSlotIndex($i);
						}

						foreach($hotbardata as $hotbarslot){
							$hotbar[$hotbarslot] = $player->getInventory()->getItem($hotbarslot);
						}

						for($i = 0; $i < 27; ++$i){
							if(!isset($hotbar[$i])){
								$pk->items[] = $player->getInventory()->getItem($i);
							}else{
								$pk->items[] = $hotbar[$i];//dummy item 
							}
						}

						foreach($hotbar as $slot){
							$pk->items[] = $slot;//hotbar
						}

						$pk->items[] = Item::get(Item::AIR, 0, 0);//off hand

						return $pk;
					break;
					/*case ContainerSetContentPacket::SPECIAL_ARMOR:
						//TODO
					break;*/
					case ContainerSetContentPacket::SPECIAL_CREATIVE:
					case ContainerSetContentPacket::SPECIAL_HOTBAR:
					break;
					default:
						$windowdata = $player->getSetting("windowid:".$packet->windowid);

						if($windowdata !== false){
							switch($windowdata[0]){
								case 0:
									$pk->items = $packet->slots;

									$hotbar = [];
									$hotbardata = [];
									for($i = 0; $i < 9; $i++){ 
										$hotbardata[] = $player->getInventory()->getHotbarSlotIndex($i);
									}

									foreach($hotbardata as $hotbarslot){
										$hotbar[$hotbarslot] = $player->getInventory()->getItem($hotbarslot);
									}

									for($i = 0; $i < 27; ++$i){
										if(!isset($hotbar[$i])){
											$pk->items[] = $player->getInventory()->getItem($i);
										}else{
											$pk->items[] = $hotbar[$i];
										}
									}

									foreach($hotbar as $slot){
										$pk->items[] = $slot;
									}

									return $pk;
								break;
								default:
									echo "UnknownWindowType: ".$windowdata[0]."\n";
								break;
							}
						}else{
							echo "ContainerSetContentPacket: 0x".bin2hex(chr($packet->windowid))."\n";
						}
					break;
				}

				return null;

			case Info::CRAFTING_DATA_PACKET:
				$player->setSetting(["Recipes" => $packet->entries, "cleanRecipes" => $packet->cleanRecipes]);
				return null;

			case Info::BLOCK_ENTITY_DATA_PACKET:
				$pk = new UpdateBlockEntityPacket();
				$pk->x = $packet->x;
				$pk->y = $packet->y;
				$pk->z = $packet->z;

				$nbt = new NBT(NBT::LITTLE_ENDIAN);
				$nbt->read($packet->namedtag, false, true);
				$nbt = $nbt->getData();

				switch($nbt["id"]){
					case Tile::CHEST:
						$pk->actionID = 7;
						$pk->namedtag = $nbt;
					break;
					case Tile::SIGN:
						$pk->actionID = 9;
						$pk->namedtag = $nbt;
					break;
					default:
						echo "BlockEntityDataPacket: ".$nbt["id"]."\n";
						return null;
					break;
				}
				
				return $pk;

			case Info::SET_DIFFICULTY_PACKET:
				$pk = new ServerDifficultyPacket();
				$pk->difficulty = $packet->difficulty;
				return $pk;

			case Info::SET_PLAYER_GAME_TYPE_PACKET:
				$packets = [];

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

			case Info::PLAYER_LIST_PACKET:
				$packets = [];
				$pk = new PlayerListPacket();

				switch($packet->type){
					case 0://Add
						$pk->actionID = PlayerListPacket::TYPE_ADD;

						if(!($playerlist = $player->getSetting("PlayerList"))){
							$playerlist = [];
						}

						foreach($packet->entries as $entry){
							if(isset($playerlist[$entry[0]->toString()])){
								if(!isset($pk2)){
									$pk2 = new PlayerListPacket();
									$pk2->actionID = PlayerListPacket::TYPE_UPDATE_NAME;
								}
								$pk2->players[] = [
									$entry[0]->toBinary(),
									true,
									BigBrother::toJSON($entry[2])
								];
								continue;
							}

							$packetplayer = $player->getServer()->getPlayerExact(TextFormat::clean($entry[2]));
							if($packetplayer instanceof DesktopPlayer){
								$peroperties = $packetplayer->bigBrother_getPeroperties();
							}else{
								//TODO: Skin Problem
								$value = [//Dummy Data
									"timestamp" => 0,
									"profileId" => str_replace("-", "", $entry[0]->toString()),
									"profileName" => TextFormat::clean($entry[2]),
									"textures" => [
										"SKIN" => [
											//TODO
										] 
									]
								];

								$peroperties = [
									[
										"name" => "textures",
										"value" => base64_encode(json_encode($value)),
									]
								];
							}

							$pk->players[] = [
								$entry[0]->toBinary(),
								TextFormat::clean($entry[2]),
								$peroperties,
								0,
								0,
								true,
								BigBrother::toJSON($entry[2])
							];

							$playerlist[$entry[0]->toString()] = true;
						}

						$player->setSetting(["PlayerList" => $playerlist]);
					break;
					case 1://Remove
						$pk->actionID = PlayerListPacket::TYPE_REMOVE;

						if(!($playerlist = $player->getSetting("PlayerList"))){
							$playerlist = [];
						}

						foreach($packet->entries as $entry){
							$pk->players[] = [
								$entry[0]->toBinary(),
							];

							if(isset($playerlist[$entry[0]->toString()])){
								unset($playerlist[$entry[0]->toString()]);
							}
						}

						$player->setSetting(["PlayerList" => $playerlist]);
					break;
				}

				if(isset($pk2) and count($pk->players) > 0){//php bug
					$packets[] = $pk2;
					$packets[] = $pk;
					return $packets;
				}elseif(isset($pk2)){
					return $pk2;
				}elseif(count($pk->players) > 0){
					return $pk;
				}

				return null;

			case Info::BATCH_PACKET:
				$packets = [];

				$str = zlib_decode($packet->payload, 1024 * 1024 * 64); //Max 64MB
				$len = strlen($str);

				if($len === 0){
					throw new \InvalidStateException("Decoded BatchPacket payload is empty");
				}

				$stream = new BinaryStream($str);

				while($stream->offset < $len){
					$buf = $stream->getString();
					if(($pk = $player->getServer()->getNetwork()->getPacket(ord($buf{0}))) !== null){
						if($pk::NETWORK_ID === Info::BATCH_PACKET){
							throw new \InvalidStateException("Invalid BatchPacket inside BatchPacket");
						}

						$pk->setBuffer($buf, 1);

						$pk->decode();

						switch($pk::NETWORK_ID){
							case Info::PLAYER_LIST_PACKET:
								$pk->type = $pk->getByte();
								$entries = $pk->getUnsignedVarInt();
								for($i = 0; $i < $entries; $i++){
									if($pk->type === 0){
										$pk->entries[] = [
											$pk->getUUID(),
											$pk->getEntityId(),
											$pk->getString(),
											$pk->getString(),
											$pk->getString(),
										];
									}else{
										$pk->entry[] = [
											$pk->getUUID(),
										];
									}
								}
							break;
							case Info::ADD_ENTITY_PACKET:
								$pk->eid = $pk->getEntityId();
								$pk->eid = $pk->getEntityId();
								$pk->type = $pk->getUnsignedVarInt();
								$pk->getVector3f($pk->x, $pk->y, $pk->z);
								$pk->getVector3f($pk->speedX, $pk->speedY, $pk->speedZ);
								$pk->pitch = $pk->getLFloat();
								$pk->yaw = $pk->getLFloat();
								$count = $pk->getUnsignedVarInt();
								for($i = 0; $i < $count; $i++){
									$pk->attributes[] = [
										$pk->getString(),
										$pk->getLFloat(),
										$pk->getLFloat(),
										$pk->getLFloat(),
									];
								}
								$pk->metadata = $pk->getEntityMetadata();
								$count = $pk->getUnsignedVarInt();
								for($i = 0; $i < $count; $i++){
									$pk->links[] = [
										$pk->getEntityId(),
										$pk->getEntityId(),
										$pk->getByte(),
									];
								}
							break;
							default:
								echo "BatchPacket: ".$pk::NETWORK_ID."\n";
							break;
						}
						if(($desktop = $this->serverToInterface($player, $pk)) !== null){
							if(is_array($desktop)){
								foreach($desktop as $desktoppk){
									$desktop[] = $desktoppk;
								}
							}else{
								$packets[] = $desktop;
							}
						}
					}
				}
				
				return $packets;

			case Info::BOSS_EVENT_PACKET:
				$pk = new BossBarPacket();

				switch($packet->type){
					case 0:
						if($player->getSetting("BossBar") !== false){//PE is Update
							return null;
						}

						if(($entity = $player->getLevel()->getEntity($packet->eid)) instanceof Entity){
							$title = str_replace("\n", "", $entity->getNameTag());
							$health = 1;//TODO
						}else{
							$title = "Test";
							$health = 1;
						}

						$flags = 0;
						$flags |= 0x01;
						$flags |= 0x02;

						$pk->actionID = BossBarPacket::TYPE_ADD;
						$pk->uuid = UUID::fromRandom()->toBinary();
						$pk->title = BigBrother::toJSON($title);
						$pk->health = $health;
						$pk->color = 0;
						$pk->division = 0;
						$pk->flags = $flags;

						$player->setSetting(["BossBar" => [$packet->eid, $pk->uuid]]);

						return $pk;
					break;
					case 1:
						if($player->getSetting("BossBar") === false){
							return null;
						}

						$pk->actionID = BossBarPacket::TYPE_REMOVE;
						$pk->uuid = $player->getSetting("BossBar")[1];

						$player->removeSetting("BossBar");

						return $pk;
					break;
					default:
						echo "BossEventPacket: ".$packet->type."\n";
					break;
				}
				return null;

			case Info::PLAY_STATUS_PACKET:
			case Info::RESOURCE_PACKS_INFO_PACKET:
			case Info::RESPAWN_PACKET:
			case Info::ADVENTURE_SETTINGS_PACKET:
			case Info::FULL_CHUNK_DATA_PACKET:
			case Info::CHUNK_RADIUS_UPDATED_PACKET:
			case Info::AVAILABLE_COMMANDS_PACKET:
				return null;

			default:
				echo "[Send][Translator] 0x".bin2hex(chr($packet->pid()))." Not implemented\n";
				return null;
		}
	}
}
