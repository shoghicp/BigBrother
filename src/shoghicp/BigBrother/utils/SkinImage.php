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

class SkinImage{
	private $utils, $existSkinImage = false;

	public function __construct($binary){
		$this->utils = new PNGParser($binary);
		if($binary !== ""){
			$this->existSkinImage = true;
		}
	}

	public function getRawSkinImageData(bool $enableDummyImage = false) : string{
		$data = "";
		if($this->existSkinImage){
			for($height = 0; $height < $this->utils->getHeight(); $height++){
				for($width = 0; $width < $this->utils->getWidth(); $width++){
					$rgbaData = $this->utils->getRGBA($height, $width);
					$data .= chr($rgbaData[0]).chr($rgbaData[1]).chr($rgbaData[2]).chr($rgbaData[3]);
				}
			}
		}elseif($enableDummyImage){
			$data = str_repeat(" ", 64 * 32 * 4);//dummy data
		}

		return $data;
	}

	public function getSkinImageData(bool $enableDummyImage = false) : string{
		return base64_encode($this->getRawSkinImageData($enableDummyImage));
	}

}
