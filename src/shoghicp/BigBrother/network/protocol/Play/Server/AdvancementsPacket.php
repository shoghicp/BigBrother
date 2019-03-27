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

class AdvancementsPacket extends OutboundPacket{

	/** @var bool */
	public $doClear = false;
	/** @var array */
	public $advancements = [];
	/** @var array */
	public $identifiers = [];
	/** @var array */
	public $progress = [];

	public function pid() : int{
		return self::ADVANCEMENTS_PACKET;
	}

	protected function encode() : void{
		$this->putBool($this->doClear);
		$this->putVarInt(count($this->advancements));
		foreach($this->advancements as $advancement){
			$this->putString($advancement[0]);//id
			$this->putBool($advancement[1][0]);//has parent
			if($advancement[1][0]){
				$this->putString($advancement[1][1]);//parent id
			}
			$this->putBool($advancement[2][0]);//has display
			if($advancement[2][0]){
				$this->putString($advancement[2][1]);//title
				$this->putString($advancement[2][2]);//description
				$this->putSlot($advancement[2][3]);//icon (item)
				$this->putVarInt($advancement[2][4]);// frame type
				$this->putInt($advancement[2][5][0]);// flag
				if(($advancement[2][5][0] & 0x01) > 0){
					$this->putString($advancement[2][5][1]);
				}
				$this->putFloat($advancement[2][6]);// x coordinate
				$this->putFloat($advancement[2][7]);// z coordinate
			}
			$this->putVarInt(count($advancement[3]));//criteria
			foreach($advancement[3] as $criteria){
				$this->putString($criteria[0]);//key
				//value but void
			}
			$this->putVarInt(count($advancement[4]));
			foreach($advancement[4] as $requirements){//Requirements
				$this->putVarInt(count($requirements));
				foreach($requirements as $requirement){
					$this->putString($requirement);
				}
			}
		}
		$this->putVarInt(count($this->identifiers));
		foreach($this->identifiers as $identifier){
			$this->putString($identifier);
		}
		$this->putVarInt(count($this->progress));
		foreach($this->progress as $progressData){
			$this->putString($progressData[0]);//id
			$this->putVarInt(count($progressData[1]));//Criteria size
			foreach($progressData[1] as $criterion){
				$this->putString($criterion[0]);//criteria id
				$this->putBool($criterion[1][0]);//
				if($criterion[1][0]){
					$this->putLong($criterion[1][1]);//time
				}
			}
		}
	}
}
