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

class TitlePacket extends OutboundPacket{

	const TYPE_SET_TITLE = 0;
	const TYPE_SET_SUB_TITLE = 1;
	const TYPE_SET_SETTINGS = 2;
	const TYPE_HIDE = 3;
	const TYPE_RESET = 4;

	/** @var int */
	public $actionID;
	/** @var string|int[] */
	public $data = null;

	public function pid() : int{
		return self::TITLE_PACKET;
	}

	protected function encode() : void{
		$this->putVarInt($this->actionID);
		switch($this->actionID){
			case self::TYPE_SET_TITLE:
			case self::TYPE_SET_SUB_TITLE:
				$this->putString($this->data);
			break;
			case self::TYPE_SET_SETTINGS:
				$this->putInt($this->data[0]);
				$this->putInt($this->data[1]);
				$this->putInt($this->data[2]);
			break;
		}
	}
}
