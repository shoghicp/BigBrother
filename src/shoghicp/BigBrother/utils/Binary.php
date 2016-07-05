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
		$m = "";
		foreach($data as $bottom => $d){
			$m .= chr(($d["type"] << 5) | ($bottom & 0x1F));
			switch($d["type"]){
				case 0:
					$m .= self::writeByte($d["value"]);
					break;
				case 1:
					$m .= self::writeShort($d["value"]);
					break;
				case 2:
					$m .= self::writeInt($d["value"]);
					break;
				case 3:
					$m .= self::writeFloat($d["value"]);
					break;
				case 4:
					$m .= self::writeVarInt(strlen($d["value"])) . $d["value"];
					break;
				case 5:
					/** @var \pocketmine\item\Item $item */
					$item = $d["value"];
					if($item->getID() === 0){
						$m .= self::writeShort(-1);
					}else{
						$m .= self::writeShort($item->getID());
						$m .= self::writeByte($item->getCount());
						$m .= self::writeShort($item->getDamage());
						$m .= self::writeShort(-1);
					}
					break;
				case 6:
					$m .= self::writeInt($d["value"][0]);
					$m .= self::writeInt($d["value"][1]);
					$m .= self::writeInt($d["value"][2]);
					break;
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