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

namespace shoghicp\BigBrother\network;

use pocketmine\Achievement;
use pocketmine\Player;
use pocketmine\entity\Entity;
use pocketmine\item\Item;
use pocketmine\block\Block;
use pocketmine\math\Vector3;
use pocketmine\level\particle\Particle;
use pocketmine\network\mcpe\protocol\PacketPool;
use pocketmine\network\mcpe\protocol\AnimatePacket;
use pocketmine\network\mcpe\protocol\AdventureSettingsPacket;
use pocketmine\network\mcpe\protocol\BlockEntityDataPacket;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\EntityEventPacket;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\network\mcpe\protocol\LevelEventPacket;
use pocketmine\network\mcpe\protocol\ProtocolInfo as Info;
use pocketmine\network\mcpe\protocol\InteractPacket;
use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;
use pocketmine\network\mcpe\protocol\TextPacket;
use pocketmine\network\mcpe\protocol\MovePlayerPacket;
use pocketmine\network\mcpe\protocol\PlayerActionPacket;
use pocketmine\network\mcpe\protocol\PlayStatusPacket;
use pocketmine\network\mcpe\protocol\MobEquipmentPacket;
use pocketmine\network\mcpe\protocol\MobEffectPacket;
use pocketmine\utils\TextFormat;
use pocketmine\utils\UUID;
use pocketmine\nbt\NBT;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\tile\Tile;
use shoghicp\BigBrother\BigBrother;
use shoghicp\BigBrother\DesktopPlayer;
use shoghicp\BigBrother\DesktopChunk;
use shoghicp\BigBrother\network\protocol\Login\LoginDisconnectPacket;
use shoghicp\BigBrother\network\protocol\Play\Server\PlayerAbilitiesPacket;
use shoghicp\BigBrother\network\protocol\Play\Server\AnimatePacket as STCAnimatePacket;
use shoghicp\BigBrother\network\protocol\Play\Server\ChatPacket;
use shoghicp\BigBrother\network\protocol\Play\Server\KeepAlivePacket;
use shoghicp\BigBrother\network\protocol\Play\Server\PlayerPositionAndLookPacket;
use shoghicp\BigBrother\network\protocol\Play\Server\ParticlePacket;
use shoghicp\BigBrother\network\protocol\Play\Server\HeldItemChangePacket;
use shoghicp\BigBrother\network\protocol\Play\Server\BlockActionPacket;
use shoghicp\BigBrother\network\protocol\Play\Server\BlockBreakAnimationPacket;
use shoghicp\BigBrother\network\protocol\Play\Server\BlockChangePacket;
use shoghicp\BigBrother\network\protocol\Play\Server\BossBarPacket;
use shoghicp\BigBrother\network\protocol\Play\Server\ChangeGameStatePacket;
use shoghicp\BigBrother\network\protocol\Play\Server\DestroyEntitiesPacket;
use shoghicp\BigBrother\network\protocol\Play\Server\EffectPacket;
use shoghicp\BigBrother\network\protocol\Play\Server\EntityEquipmentPacket;
use shoghicp\BigBrother\network\protocol\Play\Server\EntityEffectPacket;
use shoghicp\BigBrother\network\protocol\Play\Server\EntityStatusPacket;
use shoghicp\BigBrother\network\protocol\Play\Server\EntityHeadLookPacket;
use shoghicp\BigBrother\network\protocol\Play\Server\EntityMetadataPacket;
use shoghicp\BigBrother\network\protocol\Play\Server\EntityTeleportPacket;
use shoghicp\BigBrother\network\protocol\Play\Server\EntityVelocityPacket;
use shoghicp\BigBrother\network\protocol\Play\Server\EntityPropertiesPacket;
use shoghicp\BigBrother\network\protocol\Play\Server\ExplosionPacket;
use shoghicp\BigBrother\network\protocol\Play\Server\JoinGamePacket;
use shoghicp\BigBrother\network\protocol\Play\Server\PlayDisconnectPacket;
use shoghicp\BigBrother\network\protocol\Play\Server\PlayerListPacket;
use shoghicp\BigBrother\network\protocol\Play\Server\ChunkDataPacket;
use shoghicp\BigBrother\network\protocol\Play\Server\SelectAdvancementTabPacket;
use shoghicp\BigBrother\network\protocol\Play\Server\ServerDifficultyPacket;
use shoghicp\BigBrother\network\protocol\Play\Server\SpawnMobPacket;
use shoghicp\BigBrother\network\protocol\Play\Server\SpawnObjectPacket;
use shoghicp\BigBrother\network\protocol\Play\Server\SpawnPlayerPacket;
use shoghicp\BigBrother\network\protocol\Play\Server\SpawnPositionPacket;
use shoghicp\BigBrother\network\protocol\Play\Server\StatisticsPacket;
use shoghicp\BigBrother\network\protocol\Play\Server\SetExperiencePacket;
use shoghicp\BigBrother\network\protocol\Play\Server\RemoveEntityEffectPacket;
use shoghicp\BigBrother\network\protocol\Play\Server\TimeUpdatePacket;
use shoghicp\BigBrother\network\protocol\Play\Server\UpdateHealthPacket;
use shoghicp\BigBrother\network\protocol\Play\Server\UpdateBlockEntityPacket;
use shoghicp\BigBrother\network\protocol\Play\Server\UseBedPacket;
use shoghicp\BigBrother\network\protocol\Play\Server\NamedSoundEffectPacket;
use shoghicp\BigBrother\utils\ConvertUtils;

class Translator{

	/**
	 * @param DesktopPlayer $player
	 * @param Packet        $packet
	 * @return DataPacket|array<DataPacket>|null
	 */
	public function interfaceToServer(DesktopPlayer $player, Packet $packet){
		switch($packet->pid()){
			case InboundPacket::TELEPORT_CONFIRM_PACKET://Confirm
				return null;

			case InboundPacket::TAB_COMPLETE_PACKET:
				//TODO: Tab Button
				return null;

			case InboundPacket::CHAT_PACKET:
				$pk = new TextPacket();
				$pk->type = 1;//Chat Type
				$pk->source = "";
				$pk->message = $packet->message;
				return $pk;

			case InboundPacket::CLIENT_STATUS_PACKET:
				switch($packet->actionID){
					case 0:
						$pk = new PlayerActionPacket();
						$pk->entityRuntimeId = $player->getId();
						$pk->action = PlayerActionPacket::ACTION_RESPAWN;
						$pk->x = 0;
						$pk->y = 0;
						$pk->z = 0;
						$pk->face = 0;

						return $pk;
					break;
					case 1:
						//TODO: stat https://gist.github.com/Alvin-LB/8d0d13db00b3c00fd0e822a562025eff
						$statistic = [];

						$pk = new StatisticsPacket();
						$pk->count = count($statistic);
						$pk->statistic = $statistic;
						$player->putRawPacket($pk);
					break;
					default:
						echo "ClientStatusPacket: ".$packet->actionID."\n";
					break;
				}
				return null;

			case InboundPacket::CLIENT_SETTINGS_PACKET:
				$player->bigBrother_setClientSetting([
					"Lang" => $packet->lang,
					"View" => $packet->view,
					"ChatMode" => $packet->chatMode,
					"ChatColor" => $packet->chatColor,
					"SkinSettings" => $packet->skinSetting,
				]);

				return null;

			case InboundPacket::CONFIRM_TRANSACTION_PACKET://Confirm
				return null;

			case InboundPacket::CLICK_WINDOW_PACKET:
				$pk = $player->getInventoryUtils()->onWindowClick($packet);

				return $pk;

			case InboundPacket::CLOSE_WINDOW_PACKET:
				$pk = $player->getInventoryUtils()->onWindowCloseFromPCtoPE($packet);

				return $pk;

			case InboundPacket::PLUGIN_MESSAGE_PACKET:
				switch($packet->channel){
					case "REGISTER"://Mods Register
						$player->bigBrother_setPluginMessageList("Channels", $packet->data);
					break;
					case "MC|Brand": //ServerType
						$player->bigBrother_setPluginMessageList("ServerType", $packet->data);
					break;
					default:
						echo "PluginChannel: ".$packet->channel."\n";
					break;
				}
				return null;

			case InboundPacket::USE_ENTITY_PACKET:
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

			case InboundPacket::KEEP_ALIVE_PACKET:
				$pk = new KeepAlivePacket();
				$pk->id = mt_rand();
				$player->putRawPacket($pk);

				return null;

			case InboundPacket::PLAYER_PACKET:
				$player->onGround = $packet->onGround;
				return null;

			case InboundPacket::PLAYER_POSITION_PACKET:
				if($player->isImmobile()){
					$pk = new PlayerPositionAndLookPacket();
					$pk->x = $player->x;
					$pk->y = $player->y;
					$pk->z = $player->z;
					$pk->yaw = $player->yaw;
					$pk->pitch = $player->pitch;
					$pk->onGround = $player->isOnGround();

					$player->putRawPacket($pk);

					return null;
				}

				$packets = [];

				$pk = new MovePlayerPacket();
				$pk->position = new Vector3($packet->x, $packet->y + $player->getEyeHeight(), $packet->z);
				$pk->yaw = $player->yaw;
				$pk->bodyYaw = $player->yaw;
				$pk->pitch = $player->pitch;
				$packets[] = $pk;

				if($player->isOnGround() and !$packet->onGround){
					$pk = new PlayerActionPacket();
					$pk->entityRuntimeId = $player->getId();
					$pk->action = PlayerActionPacket::ACTION_JUMP;
					$pk->x = $packet->x;
					$pk->y = $packet->y;
					$pk->z = $packet->z;
					$pk->face = 0;
					$packets[] = $pk;
				}

				return $packets;

			case InboundPacket::PLAYER_POSITION_AND_LOOK_PACKET:
				if($player->isImmobile()){
					$pk = new PlayerPositionAndLookPacket();
					$pk->x = $player->x;
					$pk->y = $player->y;
					$pk->z = $player->z;
					$pk->yaw = $player->yaw;
					$pk->pitch = $player->pitch;
					$pk->onGround = $player->isOnGround();

					$player->putRawPacket($pk);

					return null;
				}

				$packets = [];

				$pk = new MovePlayerPacket();
				$pk->position = new Vector3($packet->x, $packet->y + $player->getEyeHeight(), $packet->z);
				$pk->yaw = $packet->yaw;
				$pk->bodyYaw = $packet->yaw;
				$pk->pitch = $packet->pitch;
				$packets[] = $pk;

				if($player->isOnGround() and !$packet->onGround){
					$pk = new PlayerActionPacket();
					$pk->entityRuntimeId = $player->getId();
					$pk->action = PlayerActionPacket::ACTION_JUMP;
					$pk->x = $packet->x;
					$pk->y = $packet->y;
					$pk->z = $packet->z;
					$pk->face = 0;
					$packets[] = $pk;
				}

				return $packets;

			case InboundPacket::PLAYER_LOOK_PACKET:
				if($player->isImmobile()){
					$pk = new PlayerPositionAndLookPacket();
					$pk->x = $player->x;
					$pk->y = $player->y;
					$pk->z = $player->z;
					$pk->yaw = $player->yaw;
					$pk->pitch = $player->pitch;
					$pk->onGround = $player->isOnGround();

					$player->putRawPacket($pk);

					return null;
				}

				$pk = new MovePlayerPacket();
				$pk->position = new Vector3($player->x, $player->y + $player->getEyeHeight(), $player->z);
				$pk->yaw = $packet->yaw;
				$pk->bodyYaw = $packet->yaw;
				$pk->pitch = $packet->pitch;

				return $pk;

			case InboundPacket::PLAYER_ABILITIES_PACKET:
				$pk = new AdventureSettingsPacket();
				$pk->entityUniqueId = $player->getId();
				$pk->setFlag(AdventureSettingsPacket::FLYING, $packet->isFlying);

				return $pk;

			case InboundPacket::PLAYER_DIGGING_PACKET:
				switch($packet->status){
					case 0:
						if($player->getGamemode() === 1){
							$pk = new InventoryTransactionPacket();
							$pk->transactionType = InventoryTransactionPacket::TYPE_USE_ITEM;
							$pk->trData = new \stdClass();
							$pk->trData->actionType = InventoryTransactionPacket::USE_ITEM_ACTION_BREAK_BLOCK;
							$pk->trData->x = $packet->x;
							$pk->trData->y = $packet->y;
							$pk->trData->z = $packet->z;
							$pk->trData->face = $packet->face;
							$pk->trData->hotbarSlot = $player->getInventory()->getHeldItemIndex();
							$pk->trData->itemInHand = $player->getInventory()->getItemInHand();
							$pk->trData->playerPos = new Vector3($player->getX(), $player->getY(), $player->getZ());
							$pk->trData->clickPos = new Vector3($packet->x, $packet->y, $packet->z);

							return $pk;
						}else{
							$player->bigBrother_setBreakPosition([new Vector3($packet->x, $packet->y, $packet->z), $packet->face]);

							$packets = [];

							$pk = new PlayerActionPacket();
							$pk->entityRuntimeId = $player->getId();
							$pk->action = PlayerActionPacket::ACTION_START_BREAK;
							$pk->x = $packet->x;
							$pk->y = $packet->y;
							$pk->z = $packet->z;
							$pk->face = $packet->face;
							$packets[] = $pk;

							$block = $player->getLevel()->getBlock(new Vector3($packet->x, $packet->y, $packet->z));
							if($block->getHardness() === 0){
								$pk = new PlayerActionPacket();
								$pk->entityRuntimeId = $player->getId();
								$pk->action = PlayerActionPacket::ACTION_STOP_BREAK;
								$pk->x = $packet->x;
								$pk->y = $packet->y;
								$pk->z = $packet->z;
								$pk->face = $packet->face;
								$packets[] = $pk;

								$pk = new InventoryTransactionPacket();
								$pk->transactionType = InventoryTransactionPacket::TYPE_USE_ITEM;
								$pk->trData = new \stdClass();
								$pk->trData->actionType = InventoryTransactionPacket::USE_ITEM_ACTION_BREAK_BLOCK;
								$pk->trData->x = $packet->x;
								$pk->trData->y = $packet->y;
								$pk->trData->z = $packet->z;
								$pk->trData->face = $packet->face;
								$pk->trData->hotbarSlot = $player->getInventory()->getHeldItemIndex();
								$pk->trData->itemInHand = $player->getInventory()->getItemInHand();
								$pk->trData->playerPos = new Vector3($player->getX(), $player->getY(), $player->getZ());
								$pk->trData->clickPos = new Vector3($packet->x, $packet->y, $packet->z);

								$packets[] = $pk;
							}

							return $packets;
						}
					break;
					case 1:
						$player->bigBrother_setBreakPosition([new Vector3(0, 0, 0), 0]);

						$pk = new PlayerActionPacket();
						$pk->entityRuntimeId = $player->getId();
						$pk->action = PlayerActionPacket::ACTION_ABORT_BREAK;
						$pk->x = $packet->x;
						$pk->y = $packet->y;
						$pk->z = $packet->z;
						$pk->face = $packet->face;

						return $pk;
					break;
					case 2:
						if($player->getGamemode() !== 1){
							$player->bigBrother_setBreakPosition([new Vector3(0, 0, 0), 0]);

							$packets = [];

							$pk = new PlayerActionPacket();
							$pk->entityRuntimeId = $player->getId();
							$pk->action = PlayerActionPacket::ACTION_STOP_BREAK;
							$pk->x = $packet->x;
							$pk->y = $packet->y;
							$pk->z = $packet->z;
							$pk->face = $packet->face;
							$packets[] = $pk;

							$pk = new InventoryTransactionPacket();
							$pk->transactionType = InventoryTransactionPacket::TYPE_USE_ITEM;
							$pk->trData = new \stdClass();
							$pk->trData->actionType = InventoryTransactionPacket::USE_ITEM_ACTION_BREAK_BLOCK;
							$pk->trData->x = $packet->x;
							$pk->trData->y = $packet->y;
							$pk->trData->z = $packet->z;
							$pk->trData->face = $packet->face;
							$pk->trData->hotbarSlot = $player->getInventory()->getHeldItemIndex();
							$pk->trData->itemInHand = $player->getInventory()->getItemInHand();
							$pk->trData->playerPos = new Vector3($player->getX(), $player->getY(), $player->getZ());
							$pk->trData->clickPos = new Vector3($packet->x, $packet->y, $packet->z);
							$packets[] = $pk;

							return $packets;
						}else{
							echo "PlayerDiggingPacket: ".$packet->status."\n";
						}
					break;
					case 3:
					case 4:
						if($packet->status === 4){
							$item = $player->getInventory()->getItemInHand();
							$item->setCount($item->getCount() - 1);

							$dropItem = $player->getInventory()->getItemInHand();
							$dropItem->setCount(1);
						}else{
							$item = Item::get(Item::AIR);
							$dropItem = $player->getInventory()->getItemInHand();
						}

						$player->getInventory()->setItemInHand($item);
						$player->dropItemNaturally($dropItem);

						return null;
					break;
					case 5:
						$pk = new InventoryTransactionPacket();
						$pk->transactionType = InventoryTransactionPacket::TYPE_RELEASE_ITEM;
						$pk->trData = new \stdClass();
						$pk->trData->hotbarSlot = $player->getInventory()->getHeldItemIndex();
						$pk->trData->itemInHand = $item = $player->getInventory()->getItemInHand();
						$pk->trData->headPos = new Vector3($packet->x, $packet->y, $packet->z);

						if($item->getId() === Item::BOW){//Shoot Arrow
							$pk->trData->actionType = InventoryTransactionPacket::RELEASE_ITEM_ACTION_RELEASE;
						}else{//Eating
							$pk->trData->actionType = InventoryTransactionPacket::RELEASE_ITEM_ACTION_CONSUME;
						}

						return $pk;
					break;
					default:
						echo "PlayerDiggingPacket: ".$packet->status."\n";
					break;
				}

				return null;

			case InboundPacket::ENTITY_ACTION_PACKET:
				switch($packet->actionID){
					case 0://Start sneaking
						$pk = new PlayerActionPacket();
						$pk->entityRuntimeId = $player->getId();
						$pk->action = PlayerActionPacket::ACTION_START_SNEAK;
						$pk->x = 0;
						$pk->y = 0;
						$pk->z = 0;
						$pk->face = 0;
						return $pk;
					break;
					case 1://Stop sneaking
						$pk = new PlayerActionPacket();
						$pk->entityRuntimeId = $player->getId();
						$pk->action = PlayerActionPacket::ACTION_STOP_SNEAK;
						$pk->x = 0;
						$pk->y = 0;
						$pk->z = 0;
						$pk->face = 0;
						return $pk;
					break;
					case 2://leave bed
						$pk = new PlayerActionPacket();
						$pk->entityRuntimeId = $player->getId();
						$pk->action = PlayerActionPacket::ACTION_STOP_SLEEPING;
						$pk->x = 0;
						$pk->y = 0;
						$pk->z = 0;
						$pk->face = 0;
						return $pk;
					break;
					case 3://Start sprinting
						$pk = new PlayerActionPacket();
						$pk->entityRuntimeId = $player->getId();
						$pk->action = PlayerActionPacket::ACTION_START_SPRINT;
						$pk->x = 0;
						$pk->y = 0;
						$pk->z = 0;
						$pk->face = 0;
						return $pk;
					break;
					case 4://Stop sprinting
						$pk = new PlayerActionPacket();
						$pk->entityRuntimeId = $player->getId();
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

			case InboundPacket::ADVANCEMENT_TAB_PACKET:
				if($packet->status === 0){
					$pk = new SelectAdvancementTabPacket();
					$pk->hasTab = true;
					$pk->tabId = $packet->tabId;
					$player->putRawPacket($pk);
				}

				return null;

			case InboundPacket::HELD_ITEM_CHANGE_PACKET:
				$pk = new MobEquipmentPacket();
				$pk->entityRuntimeId = $player->getId();
				$pk->item = $player->getInventory()->getHotbarSlotItem($packet->selectedSlot);
				$pk->inventorySlot = $packet->selectedSlot;
				$pk->hotbarSlot = $packet->selectedSlot;

				return $pk;

			case InboundPacket::CREATIVE_INVENTORY_ACTION_PACKET:
				$pk = $player->getInventoryUtils()->onCreativeInventoryAction($packet);

				return $pk;

			case InboundPacket::UPDATE_SIGN_PACKET:
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

			case InboundPacket::ANIMATE_PACKET:
				$pk = new AnimatePacket();
				$pk->action = 1;
				$pk->entityRuntimeId = $player->getId();

				if($player->lastBreak !== PHP_INT_MAX){
					$packets = [$pk];

					$pos = $player->bigBrother_getBreakPosition();
					if(!$pos[0]->equals(new Vector3(0, 0, 0))){
						$pk = new PlayerActionPacket();
						$pk->entityRuntimeId = $player->getId();
						$pk->action = PlayerActionPacket::ACTION_CONTINUE_BREAK;
						$pk->x = $pos[0]->x;
						$pk->y = $pos[0]->y;
						$pk->z = $pos[0]->z;
						$pk->face = $pos[1];
						$packets[] = $pk;
					}

					return $packets;
				}

				return $pk;

			case InboundPacket::PLAYER_BLOCK_PLACEMENT_PACKET:
				$pk = new InventoryTransactionPacket();
				$pk->transactionType = InventoryTransactionPacket::TYPE_USE_ITEM;
				$pk->trData = new \stdClass();
				$pk->trData->actionType = InventoryTransactionPacket::USE_ITEM_ACTION_CLICK_BLOCK;
				$pk->trData->x = $packet->x;
				$pk->trData->y = $packet->y;
				$pk->trData->z = $packet->z;
				$pk->trData->face = $packet->direction;
				$pk->trData->hotbarSlot = $player->getInventory()->getHeldItemIndex();
				$pk->trData->itemInHand = $player->getInventory()->getItemInHand();
				$pk->trData->playerPos = new Vector3($player->getX(), $player->getY(), $player->getZ());
				$pk->trData->clickPos = new Vector3($packet->x, $packet->y, $packet->z);

				return $pk;

			case InboundPacket::USE_ITEM_PACKET:
				$pk = new InventoryTransactionPacket();
				$pk->transactionType = InventoryTransactionPacket::TYPE_USE_ITEM;
				$pk->trData = new \stdClass();
				$pk->trData->actionType = InventoryTransactionPacket::USE_ITEM_ACTION_CLICK_AIR;
				$pk->trData->x = 0;
				$pk->trData->y = 0;
				$pk->trData->z = 0;
				$pk->trData->face = -1;
				$pk->trData->hotbarSlot = $player->getInventory()->getHeldItemIndex();
				$pk->trData->itemInHand = $player->getInventory()->getItemInHand();
				$pk->trData->playerPos = new Vector3($player->getX(), $player->getY(), $player->getZ());
				$pk->trData->clickPos = new Vector3(0, 0, 0);

				return $pk;

			default:
				if(\pocketmine\DEBUG > 3){
					echo "[Receive][Translator] 0x".bin2hex(chr($packet->pid()))." Not implemented\n";
				}
				return null;
		}
	}

	/**
	 * @param DesktopPlayer $player
	 * @param DataPacket    $packet
	 * @return Packet|array<Packet>|null
	 */
	public function serverToInterface(DesktopPlayer $player, DataPacket $packet){
		switch($packet->pid()){
			case Info::PLAY_STATUS_PACKET:
				if($packet->status === PlayStatusPacket::PLAYER_SPAWN){
					$pk = new PlayerPositionAndLookPacket();//for loading screen
					$pk->x = $player->getX();
					$pk->y = $player->getY();
					$pk->z = $player->getZ();
					$pk->yaw = 0;
					$pk->pitch = 0;
					$pk->flags = 0;

					return $pk;
				}

				return null;

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
					$packet->message = "chat.type.advancement.task";
				}

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
				$pk->gamemode = $packet->playerGamemode;
				$pk->dimension = $player->bigBrother_getDimensionPEToPC($packet->dimension);
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

				return $packets;

			case Info::ADD_PLAYER_PACKET:
				$packets = [];

				$pk = new SpawnPlayerPacket();
				$pk->eid = $packet->entityRuntimeId;
				$pk->uuid = $packet->uuid->toBinary();
				$pk->x = $packet->position->x;
				$pk->y = $packet->position->y;
				$pk->z = $packet->position->z;
				$pk->yaw = $packet->yaw;
				$pk->pitch = $packet->pitch;
				$pk->metadata = $packet->metadata;
				$packets[] = $pk;

				$pk = new EntityTeleportPacket();
				$pk->eid = $packet->entityRuntimeId;
				$pk->x = $packet->position->x;
				$pk->y = $packet->position->y;
				$pk->z = $packet->position->z;
				$pk->yaw = $packet->yaw;
				$pk->pitch = $packet->pitch;
				$packets[] = $pk;

				$player->bigBrother_addEntityList($packet->entityRuntimeId, "player");

				return $packets;

			case Info::ADD_ENTITY_PACKET:
				$packets = [];

				$isobject = false;
				$type = "generic";
				$data = 1;

				switch($packet->type){
					case 10://Chicken
						$type = "chicken";
						$packet->type = 93;
					break;
					case 11://Cow
						$type = "cow";
						$packet->type = 92;
					break;
					case 12://Pig
						$type = "pig";
						$packet->type = 90;
					break;
					case 13://Sheep
						$type = "sheep";
						$packet->type = 91;
					break;
					case 14://Wolf
						$type = "wolf";
						$packet->type = 95;
					break;
					case 15://Villager
						$type = "villager";
						$packet->type = 120;
					break;
					case 16://Mooshroom
						$type = "cow";
						$packet->type = 96;
					break;
					case 17://Squid
						$type = "squid";
						$packet->type = 94;
					break;
					case 18://Rabbit
						$type = "rabbit";
						$packet->type = 101;
					break;
					case 19://Bat
						$type = "bat";
						$packet->type = 65;
					break;
					case 20://IronGolem
						$type = "iron_golem";
						$packet->type = 99;
					break;
					case 21://SnowGolem (Snowman)
						$type = "snowman";
						$packet->type = 97;
					break;
					case 22://Ocelot
						$type = "cat";
						$packet->type = 98;
					break;
					case 23://Horse
						$type = "horse";
						$packet->type = 100;
					break;
					case 28://PolarBear
						$type = "polar_bear";
						$packet->type = 102;
					break;
					case 32://Zombie
						$type = "zombie";
						$packet->type = 54;
					break;
					case 33://Creeper
						$type = "creeper";
						$packet->type = 50;
					break;
					case 34://Skeleton
						$type = "skeleton";
						$packet->type = 51;
					break;
					case 35://Spider
						$type = "spider";
						$packet->type = 52;
					break;
					case 36://PigZombie
						$type = "zombie_pigman";
						$packet->type = 57;
					break;
					case 37://Slime
						$type = "slime";
						$packet->type = 55;
					break;
					case 38://Enderman
						$type = "enderman";
						$packet->type = 58;
					break;
					case 39://Silverfish
						$type = "silverfish";
						$packet->type = 60;
					break;
					case 40://CaveSpider
						$type = "spider";
						$packet->type = 59;
					break;
					case 41://Ghast
						$type = "ghast";
						$packet->type = 56;
					break;
					case 42://LavaSlime
						$type = "magmacube";
						$packet->type = 62;
					break;
					case 43://Blaze
						$type = "blaze";
						$packet->type = 61;
					break;
					case 44://ZombieVillager
						$type = "zombie_village";
						$packet->type = 27;
					break;
					case 45://Witch
						$type = "witch";
						$packet->type = 66;
					break;
					case 46://Stray
						$type = "stray";
						$packet->type = 6;
					break;
					case 47://Husk
						$type = "husk";
						$packet->type = 23;
					break;
					case 48://WitherSkeleton
						$type = "wither_skeleton";
						$packet->type = 5;
					break;
					case 49://Guardian
						$type = "guardian";
						$packet->type = 68;
					break;
					case 50://ElderGuardian
						$type = "elder_guardian";
						$packet->type = 4;
					break;
					/*case 52://Wither (Skull)
						//Spawn Object
					break;*/
					case 53://EnderDragon
						$type = "enderdragon";
						$packet->type = 63;
					break;
					case 54://Shulker
						$type = "shulker";
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

						$block = $packet->metadata[2][1];//block data
						$blockId = $block & 0xff;
						$blockDamage = $block >> 8;

						ConvertUtils::convertBlockData(true, $blockId, $blockDamage);

						$data = $blockId | ($blockDamage << 12);
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
						echo "AddEntityPacket: ".$packet->entityRuntimeId."\n";
					break;
				}

				if($isobject){
					$pk = new SpawnObjectPacket();
					$pk->eid = $packet->entityRuntimeId;
					$pk->type = $packet->type;
					$pk->uuid = UUID::fromRandom()->toBinary();
					$pk->x = $packet->position->x;
					$pk->y = $packet->position->y;
					$pk->z = $packet->position->z;
					$pk->yaw = 0;
					$pk->pitch = 0;
					$pk->data = $data;
					$pk->velocityX = 0;
					$pk->velocityY = 0;
					$pk->velocityZ = 0;
				}else{
					$pk = new SpawnMobPacket();
					$pk->eid = $packet->entityRuntimeId;
					$pk->type = $packet->type;
					$pk->uuid = UUID::fromRandom()->toBinary();
					$pk->x = $packet->position->x;
					$pk->y = $packet->position->y;
					$pk->z = $packet->position->z;
					$pk->yaw = $packet->yaw;
					$pk->pitch = $packet->pitch;
					$pk->metadata = $packet->metadata;
				}

				$packets[] = $pk;

				$pk = new EntityTeleportPacket();
				$pk->eid = $packet->entityRuntimeId;
				$pk->x = $packet->position->x;
				$pk->y = $packet->position->y;
				$pk->z = $packet->position->z;
				$pk->yaw = $packet->yaw;
				$pk->pitch = $packet->pitch;
				$packets[] = $pk;

				$player->bigBrother_addEntityList($packet->entityRuntimeId, $type);

				return $packets;

			case Info::REMOVE_ENTITY_PACKET:
				$pk = new DestroyEntitiesPacket();
				$pk->ids[] = $packet->entityUniqueId;

				$player->bigBrother_removeEntityList($packet->entityUniqueId);
				return $pk;

			case Info::ADD_ITEM_ENTITY_PACKET:
				$item = clone $packet->item;
				ConvertUtils::convertItemData(true, $item);

				$packets = [];

				$pk = new SpawnObjectPacket();
				$pk->eid = $packet->entityRuntimeId;
				$pk->uuid = UUID::fromRandom()->toBinary();
				$pk->type = 2;
				$pk->x = $packet->position->x;
				$pk->y = $packet->position->y;
				$pk->z = $packet->position->z;
				$pk->yaw = 0;
				$pk->pitch = 0;
				$pk->data = 1;
				$pk->velocityX = $packet->motion->x;
				$pk->velocityY = $packet->motion->y;
				$pk->velocityZ = $packet->motion->z;
				$packets[] = $pk;

				$pk = new EntityMetadataPacket();
				$pk->eid = $packet->entityRuntimeId;
				$pk->metadata = [
					0 => [0, 0],
					6 => [5, $item],
					"convert" => true,
				];
				$packets[] = $pk;

				return $packets;

			case Info::TAKE_ITEM_ENTITY_PACKET:
				$packet->target = $packet->getEntityRuntimeId(); //blame pmmp :(
				$packet->eid = $packet->getEntityRuntimeId(); //blame pmmp :(

				$pk = $player->getInventoryUtils()->onTakeItemEntity($packet);

				return $pk;

			case Info::MOVE_ENTITY_PACKET:
				if($packet->entityRuntimeId === $player->getId()){//TODO
					return null;
				}else{
					$baseOffset = 0;
					$entity = $player->getLevel()->getEntity($packet->entityRuntimeId);
					if($entity instanceof Entity){
						switch($entity::NETWORK_ID){
							case -1://Player
								$baseOffset = 1.62;
							break;
							case 64://Item
								$baseOffset = 0.125;
							break;
							case 65://PrimedTNT
							case 66://FallingSand
								$baseOffset = 0.49;
							break;
						}
					}

					$packets = [];

					$pk = new EntityTeleportPacket();
					$pk->eid = $packet->entityRuntimeId;
					$pk->x = $packet->position->x;
					$pk->y = $packet->position->y - $baseOffset;
					$pk->z = $packet->position->z;
					$pk->yaw = $packet->yaw;
					$pk->pitch = $packet->pitch;
					$packets[] = $pk;

					$pk = new EntityHeadLookPacket();
					$pk->eid = $packet->entityRuntimeId;
					$pk->yaw = $packet->yaw;
					$packets[] = $pk;

					return $packets;
				}

			case Info::MOVE_PLAYER_PACKET:
				if($packet->entityRuntimeId === $player->getId()){
					if($player->spawned){//for Loading Chunks
						$pk = new PlayerPositionAndLookPacket();
						$pk->x = $packet->position->x;
						$pk->y = $packet->position->y - $player->getEyeHeight();
						$pk->z = $packet->position->z;
						$pk->yaw = $packet->yaw;
						$pk->pitch = $packet->pitch;
						$pk->onGround = $player->isOnGround();

						return $pk;
					}
				}else{
					$packets = [];

					$pk = new EntityTeleportPacket();
					$pk->eid = $packet->entityRuntimeId;
					$pk->x = $packet->position->x;
					$pk->y = $packet->position->y - $player->getEyeHeight();
					$pk->z = $packet->position->z;
					$pk->yaw = $packet->yaw;
					$pk->pitch = $packet->pitch;
					$packets[] = $pk;

					$pk = new EntityHeadLookPacket();
					$pk->eid = $packet->entityRuntimeId;
					$pk->yaw = $packet->yaw;
					$packets[] = $pk;

					return $packets;
				}

				return null;

			case Info::UPDATE_BLOCK_PACKET:
				ConvertUtils::convertBlockData(true, $packet->blockId, $packet->blockData);

				$pk = new BlockChangePacket();
				$pk->x = $packet->x;
				$pk->y = $packet->y;
				$pk->z = $packet->z;
				$pk->blockId = $packet->blockId;
				$pk->blockMeta = $packet->blockData;

				return $pk;

			case Info::EXPLODE_PACKET:
				$pk = new ExplosionPacket();
				$pk->x = $packet->position->x;
				$pk->y = $packet->position->y;
				$pk->z = $packet->position->z;
				$pk->radius = $packet->radius;
				$pk->records = $packet->records;
				$pk->motionX = 0;
				$pk->motionY = 0;
				$pk->motionZ = 0;

				return $pk;

			case Info::LEVEL_SOUND_EVENT_PACKET:
				$issoundeffect = false;

				switch($packet->sound){
					case LevelSoundEventPacket::SOUND_EXPLODE:
						$issoundeffect = true;
						$category = 0;

						$name = "entity.generic.explode";
					break;
					case LevelSoundEventPacket::SOUND_CHEST_OPEN:
						$issoundeffect = true;
						$category = 1;

						$blockId = $player->getLevel()->getBlock($packet->position)->getId();
						if($blockId === Block::ENDER_CHEST){
							$name = "block.enderchest.open";
						}else{
							$name = "block.chest.open";
						}
					break;
					case LevelSoundEventPacket::SOUND_CHEST_CLOSED:
						$issoundeffect = true;
						$category = 1;

						$blockId = $player->getLevel()->getBlock($packet->position)->getId();
						if($blockId === Block::ENDER_CHEST){
							$name = "block.enderchest.close";
						}else{
							$name = "block.chest.close";
						}
					break;
					case LevelSoundEventPacket::SOUND_PLACE://unused
						return null;
					break;
					default:
						echo "LevelSoundEventPacket: ".$packet->sound."\n";
						return null;
					break;
				}

				if($issoundeffect){
					$pk = new NamedSoundEffectPacket();
					$pk->category = $category;
					$pk->x = (int) $packet->position->x;
					$pk->y = (int) $packet->position->y;
					$pk->z = (int) $packet->position->z;
					$pk->volume = 0.5;
					$pk->pitch = $packet->pitch;
					$pk->name = $name;
				}

				return $pk;

			case Info::LEVEL_EVENT_PACKET://TODO
				$issoundeffect = false;
				$isparticle = false;

				switch($packet->evid){
					case LevelEventPacket::EVENT_SOUND_IGNITE:
						$issoundeffect = true;
						$category = 0;
						$name = "entity.tnt.primed";
					break;
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
							case Item::BOTTLE_O_ENCHANTING:
								$name = "entity.experience_bottle.throw";
							break;
							case Item::SPLASH_POTION:
								$name = "entity.splash_potion.throw";
							break;
							case Item::BOW:
								$name = "entity.arrow.shoot";
							break;
							case 368:
								$name = "entity.enderpearl.throw";
							break;
							default:
								echo "LevelEventPacket: ".$id."\n";
								return null;
							break;
						}
					break;
					case LevelEventPacket::EVENT_SOUND_DOOR:
						$issoundeffect = true;
						$category = 0;

						$block = $player->getLevel()->getBlock($packet->position);

						switch($block->getId()){
							case Block::WOODEN_DOOR_BLOCK:
							case Block::SPRUCE_DOOR_BLOCK:
							case Block::BIRCH_DOOR_BLOCK:
							case Block::JUNGLE_DOOR_BLOCK:
							case Block::ACACIA_DOOR_BLOCK:
							case Block::DARK_OAK_DOOR_BLOCK:
								if(($block->getDamage() & 0x04) === 0x04){
									$name = "block.wooden_door.open";
								}else{
									$name = "block.wooden_door.close";
								}
							break;
							case Block::IRON_DOOR_BLOCK:
								if(($block->getDamage() & 0x04) === 0x04){
									$name = "block.iron_door.open";
								}else{
									$name = "block.iron_door.close";
								}
							break;
							case Block::TRAPDOOR:
								if(($block->getDamage() & 0x08) === 0x08){
									$name = "block.wooden_trapdoor.open";
								}else{
									$name = "block.wooden_trapdoor.close";
								}
							break;
							case Block::IRON_TRAPDOOR:
								if(($block->getDamage() & 0x08) === 0x08){
									$name = "block.iron_trapdoor.open";
								}else{
									$name = "block.iron_trapdoor.close";
								}
							break;
							case Block::OAK_FENCE_GATE:
							case Block::SPRUCE_FENCE_GATE:
							case Block::BIRCH_FENCE_GATE:
							case Block::JUNGLE_FENCE_GATE:
							case Block::DARK_OAK_FENCE_GATE:
							case Block::ACACIA_FENCE_GATE:
								if(($block->getDamage() & 0x04) === 0x04){
									$name = "block.fence_gate.open";
								}else{
									$name = "block.fence_gate.close";
								}
							break;
							default:
								echo "[LevelEventPacket] Unkwnon DoorSound\n";
								return null;
							break;
						}
					break;
					case LevelEventPacket::EVENT_ADD_PARTICLE_MASK | Particle::TYPE_HUGE_EXPLODE_SEED:
						$isparticle = true;

						$id = 2;
					break;
					case LevelEventPacket::EVENT_PARTICLE_DESTROY:
						$block = $packet->data;//block data
						$blockId = $block & 0xff;
						$blockDamage = $block >> 8;

						ConvertUtils::convertBlockData(true, $blockId, $blockDamage);

						$packet->data = $blockId | ($blockDamage << 12);
					break;
					case LevelEventPacket::EVENT_PARTICLE_PUNCH_BLOCK:
						//TODO: BreakAnimation
						return null;
					break;
					case LevelEventPacket::EVENT_BLOCK_START_BREAK:
						//TODO: set BreakTime
						return null;
					break;
					case LevelEventPacket::EVENT_BLOCK_STOP_BREAK:
						//TODO: remove BreakTime

						return null;
					break;
					default:
						echo "LevelEventPacket: ".$packet->evid."\n";
						return null;
					break;
				}


				if($issoundeffect){
					$pk = new NamedSoundEffectPacket();
					$pk->category = $category;
					$pk->x = (int) $packet->position->x;
					$pk->y = (int) $packet->position->y;
					$pk->z = (int) $packet->position->z;
					$pk->volume = 0.5;
					$pk->pitch = 1.0;
					$pk->name = $name;
				}elseif($isparticle){
					$pk = new ParticlePacket();
					$pk->id = $id;
					$pk->longDistance = false;
					$pk->x = (int) $packet->position->x;
					$pk->y = (int) $packet->position->y;
					$pk->z = (int) $packet->position->z;
					$pk->offsetX = 0;
					$pk->offsetY = 0;
					$pk->offsetZ = 0;
					$pk->data = $packet->data;
					$pk->count = 1;
				}else{
					$pk = new EffectPacket();
					$pk->effectId = $packet->evid;
					$pk->x = (int) $packet->position->x;
					$pk->y = (int) $packet->position->y;
					$pk->z = (int) $packet->position->z;
					$pk->data = $packet->data;
					$pk->disableRelativeVolume = false;
				}

				return $pk;

			case Info::BLOCK_EVENT_PACKET:
				$pk = new BlockActionPacket();
				$pk->x = $packet->x;
				$pk->y = $packet->y;
				$pk->z = $packet->z;
				$pk->actionID = $packet->case1;
				$pk->actionParam = $packet->case2;
				$pk->blockType = $blockId = $player->getLevel()->getBlock(new Vector3($packet->x, $packet->y, $packet->z))->getId();

				return $pk;

			case Info::ENTITY_EVENT_PACKET:
				switch($packet->event){
					case EntityEventPacket::HURT_ANIMATION:
						$type = $player->bigBrother_getEntityList($packet->entityRuntimeId);

						$packets = [];

						$pk = new EntityStatusPacket();
						$pk->status = 2;
						$pk->eid = $packet->entityRuntimeId;
						$packets[] = $pk;

						$pk = new NamedSoundEffectPacket();
						$pk->category = 0;
						$pk->x = (int) $player->getX();
						$pk->y = (int) $player->getY();
						$pk->z = (int) $player->getZ();
						$pk->volume = 0.5;
						$pk->pitch = 1.0;
						$pk->name = "entity.".$type.".hurt";
						$packets[] = $pk;

						return $packets;
					break;
					case EntityEventPacket::DEATH_ANIMATION:
						$type = $player->bigBrother_getEntityList($packet->entityRuntimeId);

						$packets = [];

						$pk = new EntityStatusPacket();
						$pk->status = 3;
						$pk->eid = $packet->entityRuntimeId;
						$packets[] = $pk;

						$pk = new NamedSoundEffectPacket();
						$pk->category = 0;
						$pk->x = (int) $player->getX();
						$pk->y = (int) $player->getY();
						$pk->z = (int) $player->getZ();
						$pk->volume = 0.5;
						$pk->pitch = 1.0;
						$pk->name = "entity.".$type.".death";
						$packets[] = $pk;

						return $packets;
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
						$pk->eid = $packet->entityRuntimeId;
						$pk->effectId = $packet->effectId;
						$pk->amplifier = $packet->amplifier;
						$pk->duration = $packet->duration;
						$pk->flags = $flags;

						return $pk;
					break;
					case MobEffectPacket::EVENT_REMOVE:
						$pk = new RemoveEntityEffectPacket();
						$pk->eid = $packet->entityRuntimeId;
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
					switch($entry->getName()){
						case "minecraft:player.saturation": //TODO
						case "minecraft:player.exhaustion": //TODO
						case "minecraft:absorption": //TODO
						break;
						case "minecraft:player.hunger": //move to minecraft:health
						break;
						case "minecraft:health":
							if($packet->entityRuntimeId === $player->getId()){
								$pk = new UpdateHealthPacket();
								$pk->health = $entry->getValue();//TODO: Defalut Value
								$pk->food = (int) $player->getFood();//TODO: Default Value
								$pk->saturation = $player->getSaturation();//TODO: Default Value

							/*}elseif($player->getSetting("BossBar") !== false){
								if($packet->entityRuntimeId === $player->getSetting("BossBar")[0]){
									$pk = new BossBarPacket();
									$pk->uuid = $player->getSetting("BossBar")[1];//Temporary
									$pk->actionID = BossBarPacket::TYPE_UPDATE_HEALTH;
									//$pk->health = $entry->getValue();//TODO
									$pk->health = 1;
								}*/
							}else{
								$pk = new EntityMetadataPacket();
								$pk->eid = $packet->entityRuntimeId;
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
						case "minecraft:player.level": //move to minecraft:player.experience
						break;
						case "minecraft:player.experience":
							if($packet->entityRuntimeId === $player->getId()){
								$pk = new SetExperiencePacket();
								$pk->experience = $entry->getValue();//TODO: Default Value
								$pk->level = $player->getXpLevel();//TODO: Default Value
								$pk->totalexperience = $player->getTotalXp();//TODO: Default Value

								$packets[] = $pk;
							}
						break;
						case "minecraft:attack_damage":
							$entries[] = [
								"generic.attackDamage",
								$entry->getValue()//TODO: Default Value
							];
						break;
						case "minecraft:knockback_resistance":
							$entries[] = [
								"generic.knockbackResistance",
								$entry->getValue()//TODO: Default Value
							];
						break;
						case "minecraft:follow_range":
							$entries[] = [
								"generic.followRange",
								$entry->getValue()//TODO: Default Value
							];
						break;
						default:
							echo "UpdateAtteributesPacket: ".$entry->getName()."\n";
						break;
					}
				}

				if(count($entries) > 0){
					$pk = new EntityPropertiesPacket();
					$pk->eid = $packet->entityRuntimeId;
					$pk->entries = $entries;
					$packets[] = $pk;
				}

				return $packets;

			case Info::MOB_EQUIPMENT_PACKET:
				$packets = [];

				if($packet->entityRuntimeId === $player->getId()){
					$pk = new HeldItemChangePacket();
					$pk->selectedSlot = $packet->hotbarSlot;
					$packets[] = $pk;
				}

				$pk = new EntityEquipmentPacket();
				$pk->eid = $packet->entityRuntimeId;
				$pk->slot = 0;//main hand
				$pk->item = $packet->item;

				if(count($packets) > 0){
					$packets[] = $pk;

					return $packets;
				}

				return $pk;

			case Info::MOB_ARMOR_EQUIPMENT_PACKET:
				$packets = [];

				foreach($packet->slots as $num => $item){
					$pk = new EntityEquipmentPacket();
					$pk->eid = $packet->entityRuntimeId;
					$pk->slot = 2 + 3 - $num;
					$pk->item = $item;
					$packets[] = $pk;
				}

				return $packets;

			case Info::SET_ENTITY_DATA_PACKET:
				$packets = [];

				/*if($player->getSetting("BossBar") !== false){
					if($packet->entityRuntimeId === $player->getSetting("BossBar")[0]){
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
				}*/

				if(isset($packet->metadata[Player::DATA_PLAYER_BED_POSITION])){
					$bedXYZ = $packet->metadata[Player::DATA_PLAYER_BED_POSITION][1];
					if($bedXYZ !== [0, 0, 0]){
						$pk = new UseBedPacket();
						$pk->eid = $packet->entityRuntimeId;
						$pk->bedX = $bedXYZ[0];
						$pk->bedY = $bedXYZ[1];
						$pk->bedZ = $bedXYZ[2];

						$packets[] = $pk;
					}
				}

				$pk = new EntityMetadataPacket();
				$pk->eid = $packet->entityRuntimeId;
				$pk->metadata = $packet->metadata;
				$packets[] = $pk;

				return $packets;

			case Info::SET_ENTITY_MOTION_PACKET:
				$pk = new EntityVelocityPacket();
				$pk->eid = $packet->entityRuntimeId;
				$pk->velocityX = $packet->motion->x;
				$pk->velocityY = $packet->motion->y;
				$pk->velocityZ = $packet->motion->z;
				return $pk;

			case Info::SET_HEALTH_PACKET:
				$pk = new UpdateHealthPacket();
				$pk->health = $packet->health;//TODO: Default Value
				$pk->food = (int) $player->getFood();//TODO: Default Value
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
						$pk->eid = $packet->entityRuntimeId;
						return $pk;
					break;
					case 3: //Leave Bed
						$pk = new STCAnimatePacket();
						$pk->actionID = 2;
						$pk->eid = $packet->entityRuntimeId;
						return $pk;
					break;
					default:
						echo "AnimatePacket: ".$packet->actionID."\n";
					break;
				}
				return null;

			case Info::CONTAINER_OPEN_PACKET:
				return $player->getInventoryUtils()->onWindowOpen($packet);

			case Info::CONTAINER_CLOSE_PACKET:
				return $player->getInventoryUtils()->onWindowCloseFromPEtoPC($packet);

			case Info::INVENTORY_SLOT_PACKET:
				return $player->getInventoryUtils()->onWindowSetSlot($packet);

			case Info::CONTAINER_SET_DATA_PACKET:
				return $player->getInventoryUtils()->onWindowSetData($packet);

			case Info::INVENTORY_CONTENT_PACKET:
				return $player->getInventoryUtils()->onWindowSetContent($packet);

			case Info::CRAFTING_DATA_PACKET:
				$player->getInventoryUtils()->setCraftInfoData($packet->entries);
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
					case Tile::ENCHANT_TABLE:
					case Tile::FURNACE:
						$pk->actionID = 7;
						$pk->namedtag = $nbt;
					break;
					case Tile::FLOWER_POT:
						$pk->actionID = 5;

						$nbt->Item = $nbt->item;
						$nbt->Item->setName("Item");
						unset($nbt["item"]);

						$nbt->Data = $nbt->mData;
						$nbt->Data->setName("Data");
						unset($nbt["mData"]);

						$pk->namedtag = $nbt;
					break;
					case Tile::ITEM_FRAME:
						//TODO: Convert Item Frame block to its entity.
						return null;
					break;
					case Tile::SIGN:
						$pk->actionID = 9;
						$pk->namedtag = $nbt;
					break;
					case Tile::SKULL:
						$pk->actionID = 4;
						$pk->namedtag = $nbt;
					break;
					case Tile::BED:
						$pk->actionID = 11;
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

			case Info::FULL_CHUNK_DATA_PACKET:
				$blockEntities = [];
				foreach($player->getLevel()->getChunkTiles($packet->chunkX, $packet->chunkZ) as $tile){
					$blockEntities[] = clone $tile->getSpawnCompound();
				}

				$chunk = new DesktopChunk($player, $packet->chunkX, $packet->chunkZ);

				$pk = new ChunkDataPacket();
				$pk->chunkX = $packet->chunkX;
				$pk->chunkZ = $packet->chunkZ;
				$pk->groundUp = true;
				$pk->primaryBitmap = $chunk->getBitMapData();
				$pk->payload = $chunk->getChunkData();
				$pk->biomes = $chunk->getBiomesData();
				$pk->blockEntities = $blockEntities;

				return $pk;

			case Info::PLAYER_LIST_PACKET:
				$pk = new PlayerListPacket();

				switch($packet->type){
					case 0://Add
						$pk->actionID = PlayerListPacket::TYPE_ADD;

						foreach($packet->entries as $entry){
							$playerdata = $player->getServer()->getLoggedInPlayers()[$entry->uuid->toBinary()];

							if($playerdata instanceof DesktopPlayer){
								$properties = $playerdata->bigBrother_getProperties();
							}else{
								//TODO: Skin Problem
								$value = [//Dummy Data
									"timestamp" => 0,
									"profileId" => str_replace("-", "", $entry->uuid->toString()),
									"profileName" => TextFormat::clean($entry->username),
									"textures" => [
										"SKIN" => [
											//TODO
										]
									]
								];

								$properties = [
									[
										"name" => "textures",
										"value" => base64_encode(json_encode($value)),
									]
								];
							}

							$pk->players[] = [
								$entry->uuid->toBinary(),
								TextFormat::clean($entry->username),
								$properties,
								$playerdata->getGamemode(),
								0,
								true,
								BigBrother::toJSON($entry->username)
							];
						}
					break;
					case 1://Remove
						$pk->actionID = PlayerListPacket::TYPE_REMOVE;

						foreach($packet->entries as $entry){
							$pk->players[] = [
								$entry->uuid->toBinary(),
							];
						}
					break;
				}

				return $pk;

			/*case Info::BOSS_EVENT_PACKET:
				$pk = new BossBarPacket();

				switch($packet->type){
					case 0:
						/*if($player->getSetting("BossBar") !== false){//PE is Update
							return null;
						}

						if(($entity = $player->getLevel()->getEntity($packet->entityRuntimeId)) instanceof Entity){
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

						$player->setSetting(["BossBar" => [$packet->entityRuntimeId, $pk->uuid]]);

						return $pk;
					break;
					case 1:
						/*if($player->getSetting("BossBar") === false){
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
				return null;*/

			case 0xfe: //Info::BATCH_PACKET
				$packets = [];

				$packet->decode();
				foreach($packet->getPackets() as $buf){
					if(($pk = PacketPool::getPacketById(ord($buf{0}))) !== null){
						if($pk::NETWORK_ID === 0xfe){
							throw new \InvalidStateException("Invalid BatchPacket inside BatchPacket");
						}
					}

					$pk->setBuffer($buf, 1);
					$pk->decode();

					if(($desktop = $this->serverToInterface($player, $pk)) !== null){
						if(is_array($desktop)){
							$packets = array_merge($packets, $desktop);
						}else{
							$packets[] = $desktop;
						}
					}
				}

				return $packets;

			case Info::RESOURCE_PACKS_INFO_PACKET:
			case Info::RESPAWN_PACKET:
			case Info::ADVENTURE_SETTINGS_PACKET:
			case Info::CHUNK_RADIUS_UPDATED_PACKET:
			case Info::AVAILABLE_COMMANDS_PACKET:
				return null;

			default:
				if(\pocketmine\DEBUG > 3){
					echo "[Send][Translator] 0x".bin2hex(chr($packet->pid()))." Not implemented\n";
				}
				return null;
		}
	}
}
