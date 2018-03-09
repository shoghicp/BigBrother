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

namespace shoghicp\BigBrother\network\protocol\Play\Client;

use shoghicp\BigBrother\network\InboundPacket;

class PluginMessagePacket extends InboundPacket{

	/** @var string */
	public $channel;
	/** @var string[] */
	public $data = [];

	public function pid() : int{
		return self::PLUGIN_MESSAGE_PACKET;
	}

	protected function decode() : void{
		$this->channel = $this->getString();
		switch($this->channel){
			case "REGISTER":
				$channels = bin2hex($this->getString());
				$channels = str_split($channels, 2);
				$string = "";
				foreach($channels as $num => $str){
					if($str === "00"){
						$this->data[] = hex2bin($string);
						$string = "";
					}else{
						$string .= $str;
						if(count($channels) -1 === $num){
							$this->data[] = hex2bin($string);
						}
					}
				}
			break;
			case "MC|Brand":
				$this->data[] = $this->getString();
			break;
			case "MC|BEdit":
			case "MC|BSign":
				$this->data[] = $this->getSlot();
			break;
		}
	}
}
