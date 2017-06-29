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

namespace shoghicp\BigBrother\utils;

use pocketmine\network\mcpe\protocol\DropItemPacket;
use pocketmine\network\mcpe\protocol\ContainerClosePacket;
use pocketmine\network\mcpe\protocol\ContainerSetSlotPacket;
use pocketmine\network\mcpe\protocol\ContainerSetContentPacket;

use pocketmine\entity\Item as ItemEntity;
use pocketmine\math\Vector3;
use pocketmine\tile\Tile;
use pocketmine\tile\EnderChest as TileEnderChest;
use pocketmine\item\Item;

use shoghicp\BigBrother\BigBrother;
use shoghicp\BigBrother\network\protocol\Play\OpenWindowPacket;
use shoghicp\BigBrother\network\protocol\Play\SetSlotPacket;
use shoghicp\BigBrother\network\protocol\Play\WindowItemsPacket;
use shoghicp\BigBrother\network\protocol\Play\CollectItemPacket;
use shoghicp\BigBrother\network\protocol\Play\Server\CloseWindowPacket;

class InventoryUtils{
	private $player;
	private $windowInfo = [];
	private $craftInfoData = [];
	private $windowData = [
		//0 => 

	];
	private $playerHeldItem = null;
	private $playerCraftSlot = [];
	private $playerArmorSlot = [];
	private $playerInventorySlot = [];
	private $playerHotbarSlot = [];

	public function __construct($player){
		$this->player = $player;

		$this->playerCraftSlot = array_fill(0, 5, Item::get(Item::AIR));
		$this->playerArmorSlot = array_fill(0, 5, Item::get(Item::AIR));
		$this->playerInventorySlot = array_fill(0, 36, Item::get(Item::AIR));
		$this->playerHotbarSlot = array_fill(0, 9, Item::get(Item::AIR));
		$this->playerHeldItem = Item::get(Item::AIR);
	}

	/* easy call function */

	public function getInventory(array $items){
		foreach($this->playerInventorySlot as $item){
			$items[] = $item;
		}

		return $items;
	}

	public function getHotbar(array $items){
		foreach($this->playerHotbarSlot as $item){
			$items[] = $item;
		}

		return $items;
	}



	public function onWindowOpen($packet){
		$type = "";
		switch($packet->type){
			case 0:
				$type = "minecraft:chest";
				$title = "Chest";
			break;
			case 1:
				$type = "minecraft:crafting_table";
				$title = "Crafting Table";
			break;
			case 2:
				$type = "minecraft:furnace";
				$title = "Furnace";
			break;
			default://TODO: http://wiki.vg/Inventory#Windows
				echo "[InventoryUtils] ContainerOpenPacket: ".$packet->type."\n";

				$pk = new ContainerClosePacket();
				$pk->windowid = $packet->windowid;
				$player->handleDataPacket($pk);

				return null;
			break;
		}

		$slots = 9;
		if(($tile = $this->player->getLevel()->getTile(new Vector3($packet->x, $packet->y, $packet->z))) instanceof Tile){
			if($tile instanceof TileEnderChest){
				$slots = $this->player->getEnderChestInventory()->getSize();
				$title = "Ender Chest";
			}else{
				$slots = $tile->getInventory()->getSize();
			}
		}

		$pk = new OpenWindowPacket();
		$pk->windowID = $packet->windowid;
		$pk->inventoryType = $type;
		$pk->windowTitle = BigBrother::toJSON($title);
		$pk->slots = $slots;

		$this->windowInfo[$packet->windowid] = ["type" => $packet->type, "slots" => $slots];

		return $pk;
	}

	public function onWindowClose($isserver, $packet){
		foreach($this->playerCraftSlot as $item){
			$this->player->getLevel()->dropItem($this->player->add(0, 1.3, 0), $item);//TODO: Drop Packet
		}
		$this->player->getLevel()->dropItem($this->player->add(0, 1.3, 0), $this->playerHeldItem); //TODO: Drop Packet

		if($isserver){
			$pk = new CloseWindowPacket();
			$pk->windowID = $packet->windowid;

			unset($this->windowInfo[$packet->windowid]);
		}else{
			if($packet->windowID !== ContainerSetContentPacket::SPECIAL_INVENTORY){//Player Inventory
				$pk = new ContainerClosePacket();
				$pk->windowid = $packet->windowID;
			}else{
				return null;
			}
		}

		return $pk;
	}

	public function onWindowSetSlot($packet){
		$pk = new SetSlotPacket();
		$pk->windowID = $packet->windowid;

		switch($packet->windowid){
			case ContainerSetContentPacket::SPECIAL_INVENTORY:
				$pk->item = $packet->item;

				if($packet->slot >= 0 and $packet->slot < $this->player->getInventory()->getHotbarSize()){
					$pk->slot = $packet->slot + 36;
					
					$pk2 = new ContainerSetSlotPacket();//link hotbar in item
					$pk2->windowid = ContainerSetContentPacket::SPECIAL_HOTBAR;
					$pk2->slot = $packet->slot + 9;
					$pk2->hotbarSlot = $packet->slot;
					$pk2->item = $packet->item;
					$this->player->handleDataPacket($pk2);
				}else{
					$pk->slot = $packet->slot;
				}
				return $pk;
			break;
			case ContainerSetContentPacket::SPECIAL_ARMOR:
				$pk->windowID = ContainerSetContentPacket::SPECIAL_INVENTORY;
				$pk->item = $packet->item;
				$pk->slot = $packet->slot + 5;

				return $pk;
			break;
			case ContainerSetContentPacket::SPECIAL_CREATIVE:
			case ContainerSetContentPacket::SPECIAL_HOTBAR:
			break;
			default:
				echo "[InventoryUtils] ContainerSetSlotPacket: 0x".bin2hex(chr($packet->windowid))."\n";
			break;
		}
		return null;
	}

	public function onWindowSetContent($packet){
		switch($packet->windowid){
			case ContainerSetContentPacket::SPECIAL_INVENTORY:
				$pk = new WindowItemsPacket();
				$pk->windowID = $packet->windowid;

				for($i = 0; $i < 5; ++$i){
					$pk->items[] = Item::get(Item::AIR, 0, 0);//Craft
				}

				for($i = 0; $i < 4; ++$i){
					$pk->items[] = Item::get(Item::AIR, 0, 0);//Armor
				}

				$hotbar = [];
				foreach($packet->hotbar as $num => $hotbarslot){
					if($hotbarslot === -1){
						$packet->hotbar[$num] = $hotbarslot = $num + $this->player->getInventory()->getHotbarSize();
					}

					$hotbarslot -= $this->player->getInventory()->getHotbarSize();
					$hotbar[] = $packet->slots[$hotbarslot];
				}

				$inventory = [];
				for($i = 0; $i < $this->player->getInventory()->getSize(); $i++){
					$hotbarslot = $i + $this->player->getInventory()->getHotbarSize();
					if(!in_array($hotbarslot, $packet->hotbar)){
						$pk->items[] = $packet->slots[$i];
						$inventory[] = $packet->slots[$i];
					}
				}

				foreach($hotbar as $item){
					$pk->items[] = $item;//hotbar
				}

				$pk->items[] = Item::get(Item::AIR, 0, 0);//offhand

				$this->playerInventorySlot = $inventory;
				$this->playerHotbarSlot = $hotbar;

				return $pk;
			break;
			case ContainerSetContentPacket::SPECIAL_ARMOR:
				$packets = [];

				foreach($packet->slots as $slot => $item){
					$pk = new SetSlotPacket();
					$pk->windowID = ContainerSetContentPacket::SPECIAL_INVENTORY;
					$pk->item = $item;
					$pk->slot = $slot + 5;

					$packets[] = $pk;
				}

				$this->playerArmorSlot = $packet->slots;
				
				return $packets;
			break;
			case ContainerSetContentPacket::SPECIAL_CREATIVE:
			case ContainerSetContentPacket::SPECIAL_HOTBAR:
			break;
			default:
				if(isset($this->windowInfo[$packet->windowid])){
					$pk = new WindowItemsPacket();
					$pk->windowID = $packet->windowid;

					$pk->items = $packet->slots;

					$pk->items = $this->getInventory($pk->items);
					$pk->items = $this->getHotbar($pk->items);

					return $pk;
				}

				echo "[InventoryUtils] ContainerSetContentPacket: 0x".bin2hex(chr($packet->windowid))."\n";
			break;
		}

		return null;
	}

	public function onWindowClick($packet){
		switch($packet->mode){
			case 0:
				switch($packet->button){
					case 0://Left mouse click
						/*if($packet->item->getCount() % 2 === 0){
							$item = clone $packet->item;
							$item->setCount($item->getCount() / 2);
							$item->setCount($item->getCount() / 2);
						}else{
							$item->setCount((($item->getCount() - 1) / 2) + 1);
							//$item->getCount() / 2);
						}*/
					break;
					case 1://Right mouse click

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

		var_dump($packet);

		return null;
	}

	public function onCreativeInventoryAction($packet){
		if($packet->slot === 65535){
			$pk = new DropItemPacket();
			$pk->type = 0;
			$pk->item = $packet->item;

			return $pk;
		}else{
			$pk = new ContainerSetSlotPacket();
			$pk->item = $packet->item;

			if($packet->slot > 4 and $packet->slot < 9){//Armor
				$pk->windowid = ContainerSetContentPacket::SPECIAL_ARMOR;
				$pk->slot = $packet->slot - 5;
			}else{//Inventory
				$pk->windowid = ContainerSetContentPacket::SPECIAL_INVENTORY;

				if($packet->slot > 35 and $packet->slot < 45){//hotbar
					$pk->slot = $packet->slot - 36;
				}else{
					$pk->slot = $packet->slot;
				}
			}
			return $pk;
		}
		return null;
	}

	public function onTakeItemEntity($packet){
		$itemCount = 1;
		$item = Item::get(0);
		if(($entity = $this->player->getLevel()->getEntity($packet->target)) instanceof ItemEntity){
			$item = $entity->getItem();
			$itemCount = $item->getCount();
		}

		if($this->player->getInventory()->canAddItem($item)){
			$emptyslot = $this->player->getInventory()->firstEmpty();

			$slot = -1;
			for($index = 0; $index < $this->player->getInventory()->getSize(); ++$index){
				$i = $this->player->getInventory()->getItem($index);
				if($i->equals($item) and $item->getCount() < $item->getMaxStackSize()){
					$slot = $index;
					$i->setCount($i->getCount() + 1);
					break;
				}
			}

			if($slot === -1){
				$slot = $emptyslot;
				$i = clone $item;
			}

			$pk = new ContainerSetSlotPacket();
			$pk->windowid = ContainerSetContentPacket::SPECIAL_INVENTORY;
			$pk->slot = $slot;
			$pk->item = $i;
			$this->player->handleDataPacket($pk);

			$pk = new CollectItemPacket();
			$pk->eid = $packet->eid;
			$pk->target = $packet->target;
			$pk->itemCount = $itemCount;

			return $pk;
		}

		return null;
	}

	public function onCraft(){

	}

	public function setCraftInfoData($craftInfoData){
		$this->craftInfoData = $craftInfoData;
	}

}