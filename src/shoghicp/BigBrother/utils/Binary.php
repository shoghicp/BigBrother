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
use pocketmine\nbt\NBT;

class Binary extends \pocketmine\utils\Binary{

	public static function sha1($input){
		$number = new BigInteger(sha1($input, true), -256);
		$zero = new BigInteger(0);
		return ($zero->compare($number) <= 0 ? "":"-") . ltrim($number->toHex(), "0");
	}

	public static function UUIDtoString($uuid){
		return substr($uuid, 0, 8) ."-". substr($uuid, 8, 4) ."-". substr($uuid, 12, 4) ."-". substr($uuid, 16, 4) ."-". substr($uuid, 20);
	}

	public static function writeMetadata(array $data){
		if(!isset($data["convert"])){
			$data = ConvertUtils::convertPEToPCMetadata($data);
		}

		$m = "";

		foreach($data as $bottom => $d){
			if($bottom === "convert"){
				continue;
			}

			$m .= self::writeByte($bottom);
			$m .= self::writeByte($d[0]);

			switch($d[0]){
				case 0://Byte
					$m .= self::writeByte($d[1]);
				break;
				case 1://VarInt
					$m .= self::writeVarInt($d[1]);
				break;
				case 2://Float
					$m .= self::writeFloat($d[1]);
				break;
				case 3://String
				case 4://Chat
					$m .= self::writeVarInt(strlen($d[1])) . $d[1];
				break;
				case 5://Slot
					$item = $d[1];
					if($item->getID() === 0){
						$m .= self::writeShort(-1);
					}else{
						$m .= self::writeShort($item->getID());
						$m .= self::writeByte($item->getCount());
						$m .= self::writeShort($item->getDamage());

						$nbt = new NBT(NBT::LITTLE_ENDIAN);
						$nbt->read($item->getCompoundTag());
						$nbt = $nbt->getData();

						if($nbt->getType() !== NBT::TAG_End){
							ConvertUtils::convertNBTData(true, $nbt);

							$m .= self::writeByte(strlen($nbt)).$nbt;
						}else{
							$m .= self::writeByte(0);
						}
					}
				break;
				case 6://Boolean
					$m .= self::writeByte($d[1]);
				break;
				case 7://Rotation
					$m .= self::writeFloat($d[1][0]);
					$m .= self::writeFloat($d[1][1]);
					$m .= self::writeFloat($d[1][2]);
				break;
				case 8://Position
					$long = (($d[1][0] & 0x3FFFFFF) << 38) | (($d[1][1] & 0xFFF) << 26) | ($d[1][2] & 0x3FFFFFF);
					$m .= self::writeLong($long);
				break;
			}
		}

		$m .= "\xff";

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