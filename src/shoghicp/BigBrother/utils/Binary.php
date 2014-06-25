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

class Binary extends \pocketmine\utils\Binary{

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