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
use shoghicp\BigBrother\utils\Binary;

class SpawnMobPacket extends OutboundPacket{

	/** @var int */
	public $eid;
	/** @var string */
	public $uuid;
	/** @var int */
	public $type;
	/** @var float */
	public $x;
	/** @var float */
	public $y;
	/** @var float */
	public $z;
	/** @var float */
	public $yaw;
	/** @var float */
	public $pitch;
	/** @var float */
	public $headPitch;
	/** @var float */
	public $velocityX;
	/** @var float */
	public $velocityY;
	/** @var float */
	public $velocityZ;
	/** @var array */
	public $metadata;

	public function pid() : int{
		return self::SPAWN_MOB_PACKET;
	}

	protected function encode() : void{
		$this->putVarInt($this->eid);
		$this->put($this->uuid);
		$this->putVarInt($this->type);
		$this->putDouble($this->x);
		$this->putDouble($this->y);
		$this->putDouble($this->z);
		$this->putAngle($this->yaw);
		$this->putAngle($this->pitch);
		$this->putAngle($this->headPitch);
		$this->putShort((int) round($this->velocityX * 8000));
		$this->putShort((int) round($this->velocityY * 8000));
		$this->putShort((int) round($this->velocityZ * 8000));
		$this->put(Binary::writeMetadata($this->metadata));
	}
}
