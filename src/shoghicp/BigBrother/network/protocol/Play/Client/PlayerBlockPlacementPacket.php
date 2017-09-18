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

namespace shoghicp\BigBrother\network\protocol\Play\Client;

use shoghicp\BigBrother\network\InboundPacket;

class PlayerBlockPlacementPacket extends InboundPacket{

	/** @var int */
	public $x;
	/** @var int */
	public $y;
	/** @var int */
	public $z;
	/** @var int */
	public $direction;
	/** @var int */
	public $hand;
	/** @var float */
	public $cursorX;
	/** @var float */
	public $cursorY;
	/** @var float */
	public $cursorZ;

	public function pid() : int{
		return self::PLAYER_BLOCK_PLACEMENT_PACKET;
	}

	protected function decode() : void{
		$this->getPosition($this->x, $this->y, $this->z);
		$this->direction = $this->getVarInt();
		$this->hand = $this->getVarInt();
		$this->cursorX = $this->getFloat();
		$this->cursorY = $this->getFloat();
		$this->cursorZ = $this->getFloat();
	}
}
