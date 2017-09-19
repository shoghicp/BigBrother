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

class ParticlePacket extends OutboundPacket{

	/** @var int */
	public $id;
	/** @var boolean */
	public $longDistance = false;
	/** @var float */
	public $x;
	/** @var float */
	public $y;
	/** @var float */
	public $z;
	/** @var float */
	public $offsetX;
	/** @var float */
	public $offsetY;
	/** @var float */
	public $offsetZ;
	/** @var float */
	public $data;
	/** @var int */
	public $count;
	/** @var array */
	public $addData = [];

	public function pid() : int{
		return self::PARTICLE_PACKET;
	}

	protected function encode() : void{
		$this->putInt($this->id);
		$this->putByte($this->longDistance > 0 ? 1 : 0);
		$this->putFloat($this->x);
		$this->putFloat($this->y);
		$this->putFloat($this->z);
		$this->putFloat($this->offsetX);
		$this->putFloat($this->offsetY);
		$this->putFloat($this->offsetZ);
		$this->putFloat($this->data);
		$this->putInt($this->count);
		foreach($this->addData as $addData){
			$this->putVarInt($addData);
		}
	}
}
