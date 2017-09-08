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

namespace shoghicp\BigBrother\network\protocol\Play\Server;

use shoghicp\BigBrother\network\OutboundPacket;

class AdvancementsPacket extends OutboundPacket{

	/** @var boolean */
	public $doClear = false;
	/** @var array */
	public $advancements = [];
	/** @var array */
	public $progress = [];

	public function pid(){
		return self::ADVANCEMENTS_PACKET;
	}

	public function encode(){
		$this->putByte($this->doClear > 0);

		$this->putVarInt(count($this->advancements));
		foreach($this->advancements as $advancement){
			$this->putByte($advancement[0][0] > 0);
			if($advancement[0]){
				//put id
			}
			$this->putByte($advancement[1][0] > 0);
			$this->putVarInt(count($advancement[2]));
			//foreach
			$this->putVarInt(count($advancement[3]));
			//foreach
			//TODO
		}
	}
}
