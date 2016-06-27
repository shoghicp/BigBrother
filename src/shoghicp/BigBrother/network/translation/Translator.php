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
use shoghicp\BigBrother\DesktopPlayer;
use shoghicp\BigBrother\network\Packet;

interface Translator{

	/**
	 * @param DesktopPlayer     $player
	 * @param DataPacket $packet
	 *
	 * @return null|Packet|Packet[]
	 */
	public function serverToInterface(DesktopPlayer $player, DataPacket $packet);

	/**
	 * @param DesktopPlayer $player
	 * @param Packet $packet
	 *
	 * @return null|DataPacket|DataPacket[]
	 */
	public function interfaceToServer(DesktopPlayer $player, Packet $packet);
}