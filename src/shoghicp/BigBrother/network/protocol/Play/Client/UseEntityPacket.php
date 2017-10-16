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

class UseEntityPacket extends InboundPacket{

	const INTERACT    = 0;
	const ATTACK      = 1;
	const INTERACT_AT = 2;

	/** @var int */
	public $target;
	/** @var int */
	public $type;

	/** @var float */
	public $targetX;
	/** @var float */
	public $targetY;
	/** @var float */
	public $targetZ;

	/** @var int */
	public $hand;

	public function pid() : int{
		return self::USE_ENTITY_PACKET;
	}

	protected function decode() : void{
		$this->target = $this->getVarInt();
		$this->type = $this->getVarInt();
		if($this->type === self::INTERACT_AT){
			$this->targetX = $this->getFloat();
			$this->targetY = $this->getFloat();
			$this->targetZ = $this->getFloat();
		}
		if($this->type !== self::ATTACK){
			$this->hand = $this->getVarInt();
		}
	}
}
