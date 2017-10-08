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

use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\ContainerOpenPacket;
use pocketmine\network\mcpe\protocol\ContainerClosePacket;
use pocketmine\network\mcpe\protocol\ContainerSetDataPacket;
use pocketmine\network\mcpe\protocol\InventoryContentPacket;
use pocketmine\network\mcpe\protocol\InventorySlotPacket;
use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;
use pocketmine\network\mcpe\protocol\TakeItemEntityPacket;
use pocketmine\network\mcpe\protocol\types\ContainerIds;
use pocketmine\network\mcpe\protocol\types\NetworkInventoryAction;
use pocketmine\network\mcpe\protocol\types\WindowTypes;

use pocketmine\entity\Item as ItemEntity;
use pocketmine\math\Vector3;
use pocketmine\tile\Tile;
use pocketmine\tile\EnderChest as TileEnderChest;
use pocketmine\item\Item;
use pocketmine\inventory\InventoryHolder;

use shoghicp\BigBrother\BigBrother;
use shoghicp\BigBrother\DesktopPlayer;
use shoghicp\BigBrother\network\OutboundPacket;
use shoghicp\BigBrother\network\protocol\Play\Server\ConfirmTransactionPacket;
use shoghicp\BigBrother\network\protocol\Play\Server\OpenWindowPacket;
use shoghicp\BigBrother\network\protocol\Play\Server\SetSlotPacket;
use shoghicp\BigBrother\network\protocol\Play\Server\WindowItemsPacket;
use shoghicp\BigBrother\network\protocol\Play\Server\WindowPropertyPacket;
use shoghicp\BigBrother\network\protocol\Play\Server\CollectItemPacket;
use shoghicp\BigBrother\network\protocol\Play\Server\CloseWindowPacket as ServerCloseWindowPacket;
use shoghicp\BigBrother\network\protocol\Play\Client\ClickWindowPacket;
use shoghicp\BigBrother\network\protocol\Play\Client\CloseWindowPacket as ClientCloseWindowPacket;
use shoghicp\BigBrother\network\protocol\Play\Client\CreativeInventoryActionPacket;

class InventoryUtils{

	/** @var DesktopPlayer */
	private $player;
	/** @var array */
	private $windowInfo = [];
	/** @var array */
	private $craftInfoData = [];
	/** @var Item */
	private $playerHeldItem = null;
	/** @var int */
	private $playerHeldItemSlot = -1;
	/** @var Item[] */
	private $playerCraftSlot = [];
	/** @var Item[] */
	private $playerArmorSlot = [];
	/** @var Item[] */
	private $playerInventorySlot = [];
	/** @var Item[] */
	private $playerHotbarSlot = [];

	/**
	 * @param DesktopPlayer $player
	 */
	public function __construct(DesktopPlayer $player){
		$this->player = $player;

		$this->playerCraftSlot = array_fill(0, 5, Item::get(Item::AIR));
		$this->playerArmorSlot = array_fill(0, 5, Item::get(Item::AIR));
		$this->playerInventorySlot = array_fill(0, 36, Item::get(Item::AIR));
		$this->playerHotbarSlot = array_fill(0, 9, Item::get(Item::AIR));
		$this->playerHeldItem = Item::get(Item::AIR);
	}

	/**
	 * @param Item[] $items
	 * @return Item[]
	 */
	public function getInventory(array $items) : array{
		foreach($this->playerInventorySlot as $item){
			$items[] = $item;
		}

		return $items;
	}

	/**
	 * @param Item[] $items
	 * @return Item[]
	 */
	public function getHotbar(array $items) : array{
		foreach($this->playerHotbarSlot as $item){
			$items[] = $item;
		}

		return $items;
	}

	/**
	 * @param ContainerOpenPacket $packet
	 * @return OutboundPacket|null
	 */
	public function onWindowOpen(ContainerOpenPacket $packet) : ?OutboundPacket{
		$type = "";
		switch($packet->type){
			case WindowTypes::CONTAINER:
				$type = "minecraft:chest";
				$title = "chest";
			break;
			case WindowTypes::WORKBENCH:
				$type = "minecraft:crafting_table";
				$title = "crafting";
			break;
			case WindowTypes::FURNACE:
				$type = "minecraft:furnace";
				$title = "furnace";
			break;
			case WindowTypes::ENCHANTMENT:
				$type = "minecraft:enchanting_table";
				$title = "enchant";
			break;
			case WindowTypes::ANVIL:
				$type = "minecraft:anvil";
				$title = "repair";
			break;
			default://TODO: http://wiki.vg/Inventory#Windows
				echo "[InventoryUtils] ContainerOpenPacket: ".$packet->type."\n";

				$pk = new ContainerClosePacket();
				$pk->windowId = $packet->windowId;
				$this->player->handleDataPacket($pk);

				return null;
			break;
		}

		$slots = 0;
		if(($tile = $this->player->getLevel()->getTile(new Vector3((int)$packet->x, (int)$packet->y, (int)$packet->z))) instanceof Tile){
			if($tile instanceof TileEnderChest){
				$slots = $this->player->getEnderChestInventory()->getSize();
				$title = "enderchest";
			}elseif($tile instanceof InventoryHolder){
				$slots = $tile->getInventory()->getSize();
				if($title === "chest" and $slots === 54){
					$title = "chestDouble";
				}
			}
		}

		$pk = new OpenWindowPacket();
		$pk->windowID = $packet->windowId;
		$pk->inventoryType = $type;
		$pk->windowTitle = json_encode(["translate" => "container.".$title]);
		$pk->slots = $slots;

		$this->windowInfo[$packet->windowId] = ["type" => $packet->type, "slots" => $slots, "items" => []];

		return $pk;
	}

	/**
	 * @param bool $isserver
	 * @param ContainerClosePacket $packet
	 * @return OutboundPacket|null
	 */
	public function onWindowCloseFromPCtoPE(ClientCloseWindowPacket $packet) : ?ContainerClosePacket{
		foreach($this->playerCraftSlot as $num => $item){
			$this->player->dropItemNaturally($item);
			$this->playerCraftSlot[$num] = Item::get(Item::AIR);
		}

		$this->player->dropItemNaturally($this->playerHeldItem);
		$this->playerHeldItem = Item::get(Item::AIR);

		if($packet->windowID !== ContainerIds::INVENTORY){//Player Inventory
			$pk = new ContainerClosePacket();
			$pk->windowId = $packet->windowID;

			return $pk;
		}

		return null;
	}

	/**
	 * @param bool $isserver
	 * @param ContainerClosePacket $packet
	 * @return OutboundPacket|null
	 */
	public function onWindowCloseFromPEtoPC(ContainerClosePacket $packet) : ServerCloseWindowPacket{
		foreach($this->playerCraftSlot as $num => $item){
			$this->player->dropItemNaturally($item);
			$this->playerCraftSlot[$num] = Item::get(Item::AIR);
		}

		$this->player->dropItemNaturally($this->playerHeldItem);
		$this->playerHeldItem = Item::get(Item::AIR);

		$pk = new ServerCloseWindowPacket();
		$pk->windowID = $packet->windowId;

		unset($this->windowInfo[$packet->windowId]);

		return $pk;
	}

	/**
	 * @param InventorySlotPacket $packet
	 * @return OutboundPacket|null
	 */
	public function onWindowSetSlot(InventorySlotPacket $packet) : ?OutboundPacket{
		$pk = new SetSlotPacket();
		$pk->windowID = $packet->windowId;

		switch($packet->windowId){
			case ContainerIds::INVENTORY:
				$pk->item = $packet->item;

				if($packet->inventorySlot >= 0 and $packet->inventorySlot < $this->player->getInventory()->getHotbarSize()){
					$pk->slot = $packet->inventorySlot + 36;
				}elseif($packet->inventorySlot >= $this->player->getInventory()->getHotbarSize() and $packet->inventorySlot < $this->player->getInventory()->getSize()){
					$pk->slot = $packet->inventorySlot;
				}elseif($packet->inventorySlot >= $this->player->getInventory()->getSize() and $packet->inventorySlot < $this->player->getInventory()->getSize() + 4){
					// ignore this packet (this packet is not needed because this is duplicated packet)
					$pk = null;
				}

				return $pk;
			break;
			case ContainerIds::ARMOR:
				$pk->windowID = ContainerIds::INVENTORY;
				$pk->item = $packet->item;
				$pk->slot = $packet->inventorySlot + 5;

				return $pk;
			break;
			case ContainerIds::CREATIVE:
			case ContainerIds::HOTBAR:
			break;
			default:
				if(isset($this->windowInfo[$packet->windowId])){//TODO
					$pk->item = $packet->item;
					$pk->slot = $packet->inventorySlot;

					var_dump($packet);

					return $pk;
				}
				echo "[InventoryUtils] InventorySlotPacket: 0x".bin2hex(chr($packet->windowId))."\n";
			break;
		}
		return null;
	}

	/**
	 * @param ContainerSetDataPacket $packet
	 * @return OutboundPacket[]
	 */
	public function onWindowSetData(ContainerSetDataPacket $packet) : array{
		if(!isset($this->windowInfo[$packet->windowId])){
			echo "[InventoryUtils] ContainerSetDataPacket: 0x".bin2hex(chr($packet->windowId))."\n";
		}

		$packets = [];
		switch($this->windowInfo[$packet->windowId]["type"]){
			case WindowTypes::FURNACE:
				switch($packet->property){
					case 0://Smelting
						$pk = new WindowPropertyPacket();
						$pk->windowID = $packet->windowId;
						$pk->property = 3;
						$pk->value = 200;//changed?
						$packets[] = $pk;

						$pk = new WindowPropertyPacket();
						$pk->windowID = $packet->windowId;
						$pk->property = 2;
						$pk->value = $packet->value;
						$packets[] = $pk;
					break;
					case 1://Fire icon
						$pk = new WindowPropertyPacket();
						$pk->windowID = $packet->windowId;
						$pk->property = 1;
						$pk->value = 200;//changed?
						$packets[] = $pk;

						$pk = new WindowPropertyPacket();
						$pk->windowID = $packet->windowId;
						$pk->property = 0;
						$pk->value = $packet->value;
						$packets[] = $pk;
					break;
					default:
						echo "[InventoryUtils] ContainerSetDataPacket: 0x".bin2hex(chr($packet->windowId))."\n";
					break;
				}
			break;
			default:
				echo "[InventoryUtils] ContainerSetDataPacket: 0x".bin2hex(chr($packet->windowId))."\n";
			break;
		}

		return $packets;
	}

	/**
	 * @param InventoryContentPacket $packet
	 * @return OutboundPacket[]
	 */
	public function onWindowSetContent(InventoryContentPacket $packet) : array{
		$packets = [];

		switch($packet->windowId){
			case ContainerIds::INVENTORY:
				$pk = new WindowItemsPacket();
				$pk->windowID = $packet->windowId;

				for($i = 0; $i < 5; ++$i){
					$pk->items[] = Item::get(Item::AIR, 0, 0);//Craft
				}

				for($i = 0; $i < 4; ++$i){
					$pk->items[] = Item::get(Item::AIR, 0, 0);//Armor
				}

				$hotbar = [];
				$inventory = [];
				for($i = 0; $i < $this->player->getInventory()->getSize(); $i++){
					if($i >= 0 and $i < $this->player->getInventory()->getHotbarSize()){
						$hotbar[] = $packet->items[$i];
					}else{
						$inventory[] = $packet->items[$i];
						$pk->items[] = $packet->items[$i];
					}
				}

				foreach($hotbar as $item){
					$pk->items[] = $item;
				}

				$pk->items[] = Item::get(Item::AIR, 0, 0);//offhand

				$this->playerInventorySlot = $inventory;
				$this->playerHotbarSlot = $hotbar;

				$packets[] = $pk;
			break;
			case ContainerIds::ARMOR:
				foreach($packet->items as $slot => $item){
					$pk = new SetSlotPacket();
					$pk->windowID = ContainerIds::INVENTORY;
					$pk->item = $item;
					$pk->slot = $slot + 5;

					$packets[] = $pk;
				}

				$this->playerArmorSlot = $packet->items;
			break;
			case ContainerIds::CREATIVE:
			case ContainerIds::HOTBAR:
			break;
			default:
				if(isset($this->windowInfo[$packet->windowId])){
					$pk = new WindowItemsPacket();
					$pk->windowID = $packet->windowId;

					$pk->items = $packet->items;

					//var_dump($packet->slots);

					$pk->items = $this->getInventory($pk->items);
					$pk->items = $this->getHotbar($pk->items);

					$packets[] = $pk;
				}

				echo "[InventoryUtils] InventoryContentPacket: 0x".bin2hex(chr($packet->windowId))."\n";
			break;
		}

		return $packets;
	}

	/**
	 * @param ClickWindowPacket $packet
	 * @return DataPacket[]
	 */
	public function onWindowClick(ClickWindowPacket $packet) : array{
		$item = $packet->clickedItem;

		$accepted = false;

		switch($packet->mode){
			case 0:
				switch($packet->button){
					case 0://Left mouse click
						$accepted = true;

						list($this->playerHeldItem, $item) = [$item, $this->playerHeldItem];//reverse
					break;
					case 1://Right mouse click
						$accepted = true;

						if($this->playerHeldItem->getId() === Item::AIR){
							if($item->getCount() % 2 === 0){
								$this->playerHeldItem = clone $item;
								$this->playerHeldItem->setCount($item->getCount() / 2);

								$item->setCount($item->getCount() / 2);
							}else{
								$item->setCount($item->getCount() / 2);
								$this->playerHeldItem->setCount((($item->getCount() - 1) / 2) + 1);
							}
						}else{
							if($item->getId() === Item::AIR){
								$item = clone $this->playerHeldItem;
								$item->setCount(1);

								$this->playerHeldItem->setCount($this->playerHeldItem->getCount() - 1);
							}else{
								$item = clone $this->playerHeldItem;
								$item->setCount($item->getCount() + 1);

								$this->playerHeldItem->setCount($this->playerHeldItem->getCount() - 1);
							}
						}
					break;
					default:
						echo "[InventoryUtils] UnknownButtonType: ".$packet->mode." : ".$packet->button."\n";
					break;
				}
			break;
			case 1:
				switch($packet->button){
					case 0://Shift + left mouse click
					case 1://Shift + right mouse click

					break;
					default:
						echo "[InventoryUtils] UnknownButtonType: ".$packet->mode." : ".$packet->button."\n";
					break;
				}
			break;
			case 2:
				switch($packet->button){
					case 0://Number key 1

					break;
					case 1://Number key 2

					break;
					case 2://Number key 3

					break;
					case 3://Number key 4

					break;
					case 4://Number key 5

					break;
					case 5://Number key 6

					break;
					case 6://Number key 7

					break;
					case 7://Number key 8

					break;
					case 8://Number key 9

					break;
					default:
						echo "[InventoryUtils] UnknownButtonType: ".$packet->mode." : ".$packet->button."\n";
					break;
				}
			break;
			case 3:
				switch($packet->button){
					case 2://Middle click

					break;
					default:
						echo "[InventoryUtils] UnknownButtonType: ".$packet->mode." : ".$packet->button."\n";
					break;
				}
			break;
			case 4:
				switch($packet->button){
					case 0:
						if($packet->slot !== -999){//Drop key

						}else{//Left click outside inventory holding nothing

						}
					break;
					case 1:
						if($packet->slot !== -999){//Ctrl + Drop key

						}else{//Right click outside inventory holding nothing

						}
					break;
					default:
						echo "[InventoryUtils] UnknownButtonType: ".$packet->mode." : ".$packet->button."\n";
					break;
				}
			break;
			case 5:
				switch($packet->button){
					case 0://Starting left mouse drag

					break;
					case 1://Add slot for left-mouse drag

					break;
					case 2://Ending left mouse drag

					break;
					case 4://Starting right mouse drag

					break;
					case 5://Add slot for right-mouse drag

					break;
					case 6://Ending right mouse drag

					break;
					case 8://Starting middle mouse drag

					break;
					case 9://Add slot for middle-mouse drag

					break;
					case 10://Ending middle mouse drag

					break;
					default:
						echo "[InventoryUtils] UnknownButtonType: ".$packet->mode." : ".$packet->button."\n";
					break;
				}
			break;
			case 6:
				switch($packet->button){
					case 0://Double click

					break;
					default:
						echo "[InventoryUtils] UnknownButtonType: ".$packet->mode." : ".$packet->button."\n";
					break;
				}
			break;
			default:
				echo "[InventoryUtils] ClickWindowPacket: ".$packet->mode."\n";
			break;
		}

		if($packet->windowID === 0){
			if($packet->slot >= 1 and $packet->slot){

			}
			$this->onCraft();
		}

		var_dump($packet);

		$packets = [];
		if($accepted){
			$action = new NetworkInventoryAction();
			$action->sourceType = NetworkInventoryAction::SOURCE_CONTAINER;
			$action->windowId = $packet->windowID;
			$action->inventorySlot = $packet->slot;
			$action->newItem = $item;

			if($packet->windowID !== ContainerIds::INVENTORY){
				if($pk->slot >= $this->windowInfo[$packet->windowID]["slots"]){
					$action->windowId = ContainerIds::INVENTORY;

					if($action->inventorySlot >= 36 and $action->inventorySlot < 45){
						$slots = 0;
					}else{
						$slots = 9;
					}

					$action->inventorySlot = ($action->inventorySlot - $this->windowInfo[$packet->windowID]["slots"]) + $slots;
					$action->oldItem = $this->player->getInventory()->getItem($action->inventorySlot);
				}else{
					$action->oldItem = $this->windowInfo[$packet->windowId]["items"][$action->inventorySlot];
				}
			}else{
				if($action->inventorySlot >= 36 and $action->inventorySlot < 45){
					$action->inventorySlot -= 36;
				}
				$action->oldItem = $this->player->getInventory()->getItem($action->inventorySlot);
			}

			$pk = new InventoryTransactionPacket();
			$pk->transactionType = InventoryTransactionPacket::TYPE_NORMAL;
			$pk->actions[] = $action;

			$packets[] = $pk;
		}

		$pk = new ConfirmTransactionPacket();
		$pk->windowID = $packet->windowID;
		$pk->actionNumber = $packet->actionNumber;
		$pk->accepted = $accepted;
		$this->player->putRawPacket($pk);

		return $packets;
	}

	/**
	 * @param CreativeInventoryActionPacket $packet
	 * @return DataPacket|null
	 */
	public function onCreativeInventoryAction(CreativeInventoryActionPacket $packet) : ?DataPacket{
		if($packet->slot === 65535){
			foreach($this->player->getInventory()->getContents() as $slot => $item){
				if($item->equals($packet->item, true, true)){
					$this->player->getInventory()->setItem($slot, Item::get(Item::AIR));
					break;
				}
			}

			// TODO check if item in the packet is not illegal
			$this->player->dropItemNaturally($packet->item);

			return null;
		}else{
			$action = new NetworkInventoryAction();
			$action->sourceType = NetworkInventoryAction::SOURCE_CONTAINER;

			if($packet->slot > 4 and $packet->slot < 9){//Armor
				$action->windowId = ContainerIds::ARMOR;
				$action->inventorySlot = $packet->slot - 5;
				$action->oldItem = $oldItem = $this->player->getInventory()->getItem(36 + $packet->slot);
				$action->newItem = $packet->item;
			}else{
				$action->windowId = ContainerIds::INVENTORY;

				if($packet->slot > 35 and $packet->slot < 45){//hotbar
					$action->inventorySlot = $packet->slot - 36;
				}else{
					$action->inventorySlot = $packet->slot;
				}

				$action->oldItem = $oldItem = $this->player->getInventory()->getItem($action->inventorySlot);
				$action->newItem = $packet->item;
			}

			$pk = new InventoryTransactionPacket();
			$pk->transactionType = InventoryTransactionPacket::TYPE_NORMAL;
			$pk->actions[] = $action;

			if($oldItem->getId() !== Item::AIR and !$oldItem->equals($packet->item, true, true)){
				$action = new NetworkInventoryAction();
				$action->sourceType = NetworkInventoryAction::SOURCE_CREATIVE;
				$action->windowId = -1;
				$action->inventorySlot = NetworkInventoryAction::ACTION_MAGIC_SLOT_CREATIVE_DELETE_ITEM;
				$action->oldItem = Item::get(Item::AIR);
				$action->newItem = $oldItem;

				$pk->actions[] = $action;
			}

			if(!$oldItem->equals($packet->item, true, true)){
				$action = new NetworkInventoryAction();
				$action->sourceType = NetworkInventoryAction::SOURCE_CREATIVE;
				$action->windowId = -1;
				$action->inventorySlot = NetworkInventoryAction::ACTION_MAGIC_SLOT_CREATIVE_CREATE_ITEM;
				$action->oldItem = $packet->item;
				$action->newItem = Item::get(Item::AIR);

				$pk->actions[] = $action;
			}

			return $pk;
		}
		return null;
	}

	/**
	 * @param TakeItemEntityPacket $packet
	 * @return OutboundPacket|null
	 */
	public function onTakeItemEntity(TakeItemEntityPacket $packet) : ?OutboundPacket{
		$itemCount = 1;
		$item = Item::get(0);

		$entity = $this->player->getLevel()->getEntity($packet->target);
		if($entity instanceof ItemEntity){
			$item = $entity->getItem();
			$itemCount = $item->getCount();
		}

		if($this->player->getInventory()->canAddItem($item)){
			$pk = new CollectItemPacket();
			$pk->eid = $packet->eid;
			$pk->target = $packet->target;
			$pk->itemCount = $itemCount;

			return $pk;
		}

		return null;
	}

	public function onCraft() : void{
		//TODO implement me!!
	}

	/**
	 * @param array $craftInfoData
	 */
	public function setCraftInfoData(array $craftInfoData) : void{
		$this->craftInfoData = $craftInfoData;
	}
}
