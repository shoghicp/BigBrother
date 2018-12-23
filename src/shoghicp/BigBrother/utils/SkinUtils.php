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

class SkinUtils{
	private $utils, $existSkin = false;

	public function __construct($binary){
		$this->utils = new PNGUtils($binary);
		if($binary !== ""){
			$this->existSkin = true;
		}
	}

	public function getSKinData() : string{
		$data = "";
		if($this->existSkin){
			for($height = 0; $height < $this->utils->getHeight(); $height++){
				for($width = 0; $width < $this->utils->getWidth(); $width++){
					$rgbadata = $this->utils->getRGBA($height, $width);
					$data .= chr($rgbadata[0]).chr($rgbadata[1]).chr($rgbadata[2]).chr($rgbadata[3]);
				}
			}
		}else{
			//TODO implement default skin
			$data = str_repeat(" ", 64 * 32 * 4);//dummy data
		}

		return base64_encode($data);
	}

}
