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

namespace shoghicp\BigBrother\network\protocol\Play\Server;

use shoghicp\BigBrother\network\OutboundPacket;

class BossBarPacket extends OutboundPacket{

	const TYPE_ADD = 0;
	const TYPE_REMOVE = 1;
	const TYPE_UPDATE_HEALTH = 2;
	const TYPE_UPDATE_TITLE = 3;

	/** @var string */
	public $uuid;
	/** @var int */
	public $actionID;

	/** @var string */
	public $title;
	/** @var float */
	public $health;
	/** @var int */
	public $color;
	/** @var int */
	public $division;
	/** @var int */
	public $flags;

	public function pid() : int{
		return self::BOSS_BAR_PACKET;
	}

	protected function encode() : void{
		$this->put($this->uuid);
		$this->putVarInt($this->actionID);
		switch($this->actionID){
			case self::TYPE_ADD:
				$this->putString($this->title);//Chat format
				$this->putFloat($this->health);
				$this->putVarInt($this->color);
				$this->putVarInt($this->division);
				$this->putByte($this->flags);
			break;
			case self::TYPE_REMOVE:
			break;
			case self::TYPE_UPDATE_HEALTH:
				$this->putFloat($this->health);
			break;
			case self::TYPE_UPDATE_TITLE:
				$this->putString($this->title);//Chat format
			break;
			//TODO: addtype
		}
	}
}
