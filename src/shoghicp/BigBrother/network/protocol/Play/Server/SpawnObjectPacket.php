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

class SpawnObjectPacket extends OutboundPacket{

	const BOAT              =  1;
	const ITEM_STACK        =  2;
	const AREA_EFFECT_CLOUD =  3;
	const MINECART          = 10;
	const ACTIVATED_TNT     = 50;
	const ENDERCRYSTAL      = 51;
	const TIPPED_ARROW      = 60;
	const SNOWBALL          = 61;
	const EGG               = 62;
	const FIREBALL          = 63;
	const FIRECHARGE        = 64;
	const THROWN_ENDERPEARL = 65;
	const WITHER_SKULL      = 66;
	const SHULKER_BULLET    = 67;
	const LLAMA_SPIT        = 68;
	const FALLING_OBJECTS   = 70;
	const ITEM_FRAMES       = 71;
	const EYE_OF_ENDER      = 72;
	const THROWN_POTION     = 73;
	const THROWN_EXP_BOTTLE = 75;
	const FIREWORK_ROCKET   = 76;
	const LEASH_KNOT        = 77;
	const ARMORSTAND        = 78;
	const EVOCATION_FANGS   = 79;
	const FISHING_HOOK      = 90;
	const SPECTRAL_ARROW    = 91;
	const DRAGON_FIREBALL   = 93;

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
	public $pitch;
	/** @var float */
	public $yaw;
	/** @var int */
	public $data = 0;
	/** @var bool */
	public $sendVelocity = false;
	/** @var float */
	public $velocityX;
	/** @var float */
	public $velocityY;
	/** @var float */
	public $velocityZ;

	public function pid() : int{
		return self::SPAWN_OBJECT_PACKET;
	}

	protected function encode() : void{
		$this->putVarInt($this->eid);
		$this->put($this->uuid);
		$this->putByte($this->type);
		$this->putDouble($this->x);
		$this->putDouble($this->y);
		$this->putDouble($this->z);
		$this->putAngle($this->pitch);
		$this->putAngle($this->yaw);
		$this->putInt($this->data);
		if($this->sendVelocity){
			$this->putShort((int) round($this->velocityX * 8000));
			$this->putShort((int) round($this->velocityY * 8000));
			$this->putShort((int) round($this->velocityZ * 8000));
		}
	}
}
