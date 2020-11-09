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

namespace shoghicp\BigBrother\utils;

use phpseclib\Math\BigInteger;
use pocketmine\item\Item;
use shoghicp\BigBrother\network\Session;

class Binary extends \pocketmine\utils\Binary{

	/**
	 * @param string $input
	 * @return string
	 */
	public static function sha1(string $input) : string{
		$number = new BigInteger(sha1($input, true), -256);
		$zero = new BigInteger(0);
		return ($zero->compare($number) <= 0 ? "":"-") . ltrim($number->toHex(), "0");
	}

	/**
	 * @param string $uuid
	 * @return string
	 */
	public static function UUIDtoString(string $uuid) : string{
		return substr($uuid, 0, 8) ."-". substr($uuid, 8, 4) ."-". substr($uuid, 12, 4) ."-". substr($uuid, 16, 4) ."-". substr($uuid, 20);
	}

	/**
	 * @param array $data
	 * @return string
	 */
	public static function writeMetadata(array $data) : string{
		if(!isset($data["convert"])){
			$data = ConvertUtils::convertPEToPCMetadata($data);
		}

		$m = "";

		foreach($data as $bottom => $d){
			if($bottom === "convert"){
				continue;
			}

			assert(is_int($bottom));
			$m .= self::writeByte($bottom);
			$m .= self::writeByte($d[0]);

			switch($d[0]){
				case 0://Byte
					$m .= self::writeByte($d[1]);
				break;
				case 1://VarInt
					$m .= self::writeComputerVarInt($d[1]);
				break;
				case 2://Float
					$m .= self::writeFloat($d[1]);
				break;
				case 3://String
				case 4://Chat
					$m .= self::writeComputerVarInt(strlen($d[1])) . $d[1];
				break;
				case 5://Slot
					/** @var Item $item */
					$item = $d[1];
					if($item->getId() === 0){
						$m .= self::writeShort(-1);
					}else{
						$m .= self::writeShort($item->getId());
						$m .= self::writeByte($item->getCount());
						$m .= self::writeShort($item->getDamage());

						if($item->hasCompoundTag()){
							$itemNBT = clone $item->getNamedTag();
							$m .= ConvertUtils::convertNBTDataFromPEtoPC($itemNBT);
						}else{
							$m .= "\x00";//TAG_End
						}
					}
				break;
				case 6://Boolean
					$m .= self::writeByte($d[1] ? 1 : 0);
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

	/**
	 * @param string $buffer
	 * @param int    &$offset
	 * @return int
	 */
	public static function readComputerVarInt(string $buffer, int &$offset = 0) : int{
		$number = 0;
		$shift = 0;

		while(true){
			$c = ord($buffer[$offset++]);
			$number |= ($c & 0x7f) << $shift;
			$shift += 7;
			if(($c & 0x80) === 0x00){
				break;
			}
		}
		return $number;
	}

	/**
	 * @param Session $session
	 * @param int     &$offset
	 * @return int|bool
	 */
	public static function readVarIntSession(Session $session, int &$offset = 0){
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


	/**
	 * @param resource $fp
	 * @param int      &$offset
	 * @return int|bool
	 */
	public static function readVarIntStream($fp, int &$offset = 0){
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

	/**
	 * @param int $number
	 * @return string
	 */
	public static function writeComputerVarInt(int $number) : string{
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
