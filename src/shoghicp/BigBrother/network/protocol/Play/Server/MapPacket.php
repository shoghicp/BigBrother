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

class MapPacket extends OutboundPacket{

	public static $code = 0;

	/** @var int */
	public $itemDamage;
	/** @var int */
	public $scale;
	/** @var bool */
	public $trackingPosition = false;
	/** @var resource *///TODO: We must change object type
	public $icons = [];
	/** @var int */
	public $columns = 0;
	/** @var int */
	public $rows = 0;
	/** @var int */
	public $x = 0;
	/** @var int */
	public $z = 0;
	/** @var string */
	public $data;

	public function pid() : int{
		return self::MAP_PACKET;
	}

	protected function encode() : void{
		$this->putVarInt($this->itemDamage);
		$this->putByte($this->scale);
		$this->putBool($this->trackingPosition);

		$this->putVarInt(0);
		/*$this->putVarInt(count($this->icons));
		foreach($this->icons as $icon){
			$this->putByte($icon->directionAndType);
			$this->putByte($icon->X);
			$this->putByte($icon->Z);
		}*/

		$this->putByte($this->columns);
		if($this->columns > 0){
			$this->putByte($this->rows);
			$this->putByte($this->x);
			$this->putByte($this->z);

			assert(strlen($this->data) == $this->rows * $this->columns);
			$this->putVarInt(strlen($this->data));
			$this->put($this->data);
		}
	}
}
