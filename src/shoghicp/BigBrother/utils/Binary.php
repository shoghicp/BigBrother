<?php

/*
 * BigBrother plugin for PocketMine-MP
 * Copyright (C) 2014 shoghicp <https://github.com/shoghicp/BigBrother>
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
*/

namespace shoghicp\BigBrother\utils;

use phpseclib\Math\BigInteger;
use shoghicp\BigBrother\network\Session;
use pocketmine\entity\Entity;

class Binary extends \pocketmine\utils\Binary{

	public static function sha1($input){
		$number = new BigInteger(sha1($input, true), -256);
		$zero = new BigInteger(0);
		return ($zero->compare($number) <= 0 ? "":"-") . ltrim($number->toHex(), "0");
	}

	public static function writeMetadata(array $data){
		$m = "";
		foreach($data as $bottom => $d){
			if($d[0] !== 6){//6 not use
				$m .= chr(($d[0] << 5) | ($bottom & 0x1F));
				switch($d[0]){
					case Entity::DATA_TYPE_BYTE://0
						$m .= self::writeByte($d[1]);
						break;
					case Entity::DATA_TYPE_SHORT://1
						$m .= self::writeShort($d[1]);
						break;
					case Entity::DATA_TYPE_INT://2
						$m .= self::writeInt($d[1]);
						break;
					case Entity::DATA_TYPE_FLOAT://3
						$m .= self::writeFloat($d[1]);
						break;
					case Entity::DATA_TYPE_STRING://4
						$m .= self::writeVarInt(strlen($d[1])) . $d[1];
						break;
					case Entity::DATA_TYPE_SLOT://5
						$item = $d[1];
						if($item->getID() === 0){
							$m .= self::writeShort(-1);
						}else{
							$m .= self::writeShort($item->getID());
							$m .= self::writeByte($item->getCount());
							$m .= self::writeShort($item->getDamage());
							$nbt = $item->getCompoundTag();
							$m .= self::writeByte(strlen($nbt)).$nbt;
						}
						break;
					case Entity::DATA_TYPE_ROTATION://7
						$m .= self::writeFloat($d[1][0]);
						$m .= self::writeFloat($d[1][1]);
						$m .= self::writeFloat($d[1][2]);
					break;
					case Entity::DATA_TYPE_LONG://8
						$m .= self::writeLong($d[1]);
						break;
				}
			}
		}
		$m .= "\x7f";

		return $m;
	}

	public static function readVarInt($buffer, &$offset = 0){
		$number = 0;
		$shift = 0;

		while(true){
			$c = ord($buffer{$offset++});
			$number |= ($c & 0x7f) << $shift;
			$shift += 7;
			if(($c & 0x80) === 0x00){
				break;
			}
		}
		return $number;
	}

	public static function readVarIntSession(Session $session, &$offset = 0){
		$number = 0;
		$shift = 0;


		while(true){
			$b = $session->read(1);
			$c = ord($b);
			$number |= ($c & 0x7f) << $shift;
			$shift += 7;
			++$offset;
			if($b === false){
				return false;
			}elseif(($c & 0x80) === 0x00){
				break;
			}
		}
		return $number;
	}


	public static function readVarIntStream($fp, &$offset = 0){
		$number = 0;
		$shift = 0;


		while(true){
			$b = fgetc($fp);
			$c = ord($b);
			$number |= ($c & 0x7f) << $shift;
			$shift += 7;
			++$offset;
			if($b === false){
				return false;
			}elseif(($c & 0x80) === 0x00){
				break;
			}
		}
		return $number;
	}

	public static function writeVarInt($number){
		$encoded = "";
		do{
			$next_byte = $number & 0x7f;
			$number >>= 7;

			if($number > 0){
				$next_byte |= 0x80;
			}

			$encoded .= chr($next_byte);
		}while($number > 0);

		return $encoded;
	}
}