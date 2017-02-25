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
use pocketmine\entity\Human;

class Binary extends \pocketmine\utils\Binary{

	public static function sha1($input){
		$number = new BigInteger(sha1($input, true), -256);
		$zero = new BigInteger(0);
		return ($zero->compare($number) <= 0 ? "":"-") . ltrim($number->toHex(), "0");
	}

	public static function UUIDtoString($uuid){
		return substr($uuid, 0, 8) ."-". substr($uuid, 8, 4) ."-". substr($uuid, 12, 4) ."-". substr($uuid, 16, 4) ."-". substr($uuid, 20);
	}

	public static function convertPEToPCMetadata(array $olddata){
		$newdata = [];

		foreach($olddata as $bottom => $d){
			switch($bottom){
				case Human::DATA_FLAGS://Flags
					$flags = 0;

					if(((int) $d[1] & (1 << Human::DATA_FLAG_ONFIRE)) > 0){
						$flags |= 0x01;
					}

					if(((int) $d[1] & (1 << Human::DATA_FLAG_SNEAKING)) > 0){
						$flags |= 0x02;
					}

					if(((int) $d[1] & (1 << Human::DATA_FLAG_SPRINTING)) > 0){
						$flags |= 0x08;
					}

					if(((int) $d[1] & (1 <<  Human::DATA_FLAG_INVISIBLE)) > 0){
						//$flags |= 0x20;
					}

					if(((int) $d[1] & (1 <<  Human::DATA_FLAG_SILENT)) > 0){
						$newdata[4] = [6, true];
					}

					if(((int) $d[1] & (1 <<  Human::DATA_FLAG_IMMOBILE)) > 0){
						//$newdata[11] = [0, true];
					}

					$newdata[0] = [0, $flags];
				break;
				case Human::DATA_AIR://Air
					$newdata[1] = [1, $d[1]];
				break;
				case Human::DATA_NAMETAG://Custom name
					//var_dump(bin2hex($d[1]));
					$newdata[2] = [3, str_replace("\n", "\r\n", $d[1])];
					$newdata[3] = [6, true];
				break;
				case Human::DATA_PLAYER_FLAGS:
				case Human::DATA_PLAYER_BED_POSITION:
				case Human::DATA_LEAD_HOLDER_EID:
				case Human::DATA_SCALE:
				case Human::DATA_MAX_AIR:
					//Unused
				break;
				default:
					echo "key: ".$bottom." Not implemented\n";
				break;
				//TODO: add data type
			}
		}

		$newdata["convert"] = true;

		return $newdata;
	}

	public static function writeMetadata(array $data){
		if(!isset($data["convert"])){
			$data = self::convertPEToPCMetadata($data);
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
						$nbt = $item->getCompoundTag();
						$m .= self::writeByte(strlen($nbt)).$nbt;
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
					$int2 = ($d[1][2] & 0x3FFFFFF); //26 bits
					$int2 |= ($d[1][1] & 0x3F) << 26; //6 bits
					$int1 = ($d[1][1] & 0xFC0) >> 6; //6 bits
					$int1 |= ($d[1][0] & 0x3FFFFFF) << 6; //26 bits
					$this->buffer .= self::writeInt($int1) . self::writeInt($int2);
				break;
				//TODO: add data type
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