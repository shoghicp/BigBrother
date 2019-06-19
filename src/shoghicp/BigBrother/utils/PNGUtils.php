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

use pocketmine\utils\BinaryStream;

class PNGUtils{
	const PNGFileSignature = "\x89\x50\x4e\x47\x0d\x0a\x1a\x0a";

	private $stream;
	private $width = 0, $height = 0;
	private $isPalette = false, $palette = [];
	private $bitDepth = 8, $colorType = 6, $isAlpha = true;
	private $compressionMethod = 0, $filterMethod = 0, $interlaceMethod = 0;
	private $pixelData = [[[0,0,0,255]]];
	private $rawImageData = "";
	private $usedBit = 0, $usedBitNum = 0;

	public function __construct($binary = ""){
		$this->stream = new BinaryStream($binary);
		if($binary !== ""){
			$this->read();
		}
	}

	public function getWidth() : int{
		return $this->width;
	}

	public function getHeight() : int{
		return $this->height;
	}

	public function getRGBA($x, $z) : array{
		if(isset($this->pixelData[$x][$z])){
			return $this->pixelData[$x][$z];
		}

		return [0, 0, 0, 0];//Don't change it.
	}

	public function getBinary() : string{
		return $this->stream->getBuffer();
	}

	private function read(){
		if($this->stream->get(8) !== self::PNGFileSignature){
			$this->stream->reset();
			echo "Error\n";
			return;
		}

		while(!$this->stream->feof()){
			$length = $this->stream->getInt();
			$chunkType = $this->stream->get(4);

			switch($chunkType){
				case "IHDR":
					$this->readIHDR();
				break;
				case "PLTE":
					$this->readPLTE($length);
				break;
				case "IDAT":
					$this->readIDAT($length);
				break;
				case "IEND":
					$this->readIEND($length);
				break;
				case "tRNS":
					$this->readtRNS($length);
				break;
				default:
					$this->stream->offset += $length;
				break;
			}

			$this->stream->getInt();//crc32
		}

		$this->readAllIDAT();
	}

	private function readIHDR(){
		$this->setWidth($this->stream->getInt());
		$this->setHeight($this->stream->getInt());
		$this->bitDepth = $this->stream->getByte();
		$this->colorType = $this->stream->getByte();
		$this->compressionMethod = $this->stream->getByte();
		$this->filterMethod = $this->stream->getByte();
		$this->interlaceMethod = $this->stream->getByte();

		if($this->colorType === 3){
			$this->isPalette = true;
		}

		if($this->colorType === 4 or $this->colorType === 6){
			$this->isAlpha = true;
		}else{
			$this->isAlpha = false;
		}
	}

	private function readPLTE(int $length){
		$this->isPalette = true;//unused?

		$paletteCount = $length / 3;
		for($i = 0; $i < $paletteCount; $i++){
			$r = $this->stream->getByte();
			$g = $this->stream->getByte();
			$b = $this->stream->getByte();
			$a = 255;
			$this->palette[] = [$r, $g, $b, $a];
		}
	}

	private function readtRNS(int $length){
		switch($this->colorType){
			/*case 0:
				
			break;
			case 2:

			break;*/
			case 3:
				for($i = 0; $i < $length; $i++){
					$this->palette[$i][3] = $this->stream->getByte();
				}
			break;
			default:
				echo "Sorry, i can't parse png file. readtRNS: ".$this->colorType."\n";
				echo "Report to BigBrotherTeam!\n";
			break;
		}
	}

	private function readIDAT(int $length){
		$chunkdata = zlib_decode($this->stream->get($length));

		$this->rawImageData .= $chunkdata;
	}

	private function readAllIDAT(){
		$stream = new BinaryStream($this->rawImageData);

		for($height = 0; $height < $this->height; $height++){
			$filterMethod = $stream->getByte();

			for($width = 0; $width < $this->width; $width++){
				if($this->isPalette){
					$paletteIndex = $this->getData($stream);
					$rgb = $this->palette[$paletteIndex];

					$this->setRGBA($height, $width, [$rgb[0], $rgb[1], $rgb[2], $rgb[3]]);
				}else{
					$r = $this->getData($stream);
					$g = $this->getData($stream);
					$b = $this->getData($stream);
					if($this->isAlpha){
						$a = $this->getData($stream);
					}else{
						$a = 255;
					}

					switch($filterMethod){
						case 0://none
						break;
						case 1:
							$left = $this->getRGBA($height, $width - 1);
							$r = ($r + $left[0]) % 256;
							$g = ($g + $left[1]) % 256;
							$b = ($b + $left[2]) % 256;
							$a = ($a + $left[3]) % 256;
						break;
						case 2:
							$above = $this->getRGBA($height - 1, $width);
							$r = ($r + $above[0]) % 256;
							$g = ($g + $above[1]) % 256;
							$b = ($b + $above[2]) % 256;
							$a = ($a + $above[3]) % 256;
						break;
						case 3:
							$left = $this->getRGBA($height, $width - 1);
							$above = $this->getRGBA($height - 1, $width);
							$avrgR = floor(($left[0] + $above[0]) / 2);
							$avrgG = floor(($left[1] + $above[1]) / 2);
							$avrgB = floor(($left[2] + $above[2]) / 2);
							$avrgA = floor(($left[3] + $above[3]) / 2);

							$r = ($r + $avrgR) % 256;
							$g = ($g + $avrgG) % 256;
							$b = ($b + $avrgB) % 256;
							$a = ($a + $avrgA) % 256;
						break;
						case 4:
							$left = $this->getRGBA($height, $width - 1);
							$above = $this->getRGBA($height - 1, $width);
							$upperLeft = $this->getRGBA($height - 1, $width - 1);

							$paethR = $this->paethPredictor($left[0], $above[0], $upperLeft[0]);
							$paethG = $this->paethPredictor($left[1], $above[1], $upperLeft[1]);
							$paethB = $this->paethPredictor($left[2], $above[2], $upperLeft[2]);
							$paethA = $this->paethPredictor($left[3], $above[3], $upperLeft[3]);

							$r = ($r + $paethR) % 256;
							$g = ($g + $paethG) % 256;
							$b = ($b + $paethB) % 256;
							$a = ($a + $paethA) % 256;
						break;
					}
					
					$this->setRGBA($height, $width, [$r, $g, $b, $a]);
				}
			}
		}
	}

	private function getData(BinaryStream &$stream){
		switch($this->bitDepth){
			/*case 1:

			break;
			case 2:

			break;*/
			case 4:
				if($this->usedBitNum === 0){
					$this->usedBit = $stream->getByte();
					$this->usedBitNum = 4;

					return $this->usedBit >> 4;
				}else{
					$this->usedBitNum = 0;

					return $this->usedBit & 0x0f;
				}
			break;
			case 8:
				return $stream->getByte();
			break;
			case 16:
				return $stream->getShort();
			break;
			default:
				echo "Sorry, i can't parse png file. getData: ".$this->bitDepth."\n";
				echo "Report to BigBrotherTeam!\n";
			break;
		}
		return 0;
	}

	private function paethPredictor($a, $b, $c){
		$p = $a + $b - $c;
		$pa = abs($p - $a);
		$pb = abs($p - $b);
		$pc = abs($p - $c);
		if($pa <= $pb && $pa <= $pc){
			return $a;
		}elseif($pb <= $pc){
			return $b;
 		}else{
			return $c;
		}
	}

	private function readIEND($length){
		//No chunk data
	}

	public function setWidth(int $width){
		$this->width = $width;
		$this->generatePixelData();
	}

	public function setHeight(int $height){
		$this->height = $height;
		$this->generatePixelData();
	}

	public function setRGBA($x, $z, $pixeldata) : bool{
		if(isset($this->pixelData[$x][$z])){
			$this->pixelData[$x][$z] = $pixeldata;
			return true;
		}

		return false;
	}

	private function generatePixelData(){
		$old_pixeldata = $this->pixelData;
		$this->pixelData = [];

		for($height = 0; $height < $this->height; $height++){
			$this->pixelData[$height] = [];

			for($width = 0; $width < $this->width; $width++){
				$pixel = [0,0,0,255];
				if(isset($old_pixeldata[$height][$width])){
					$pixel = $old_pixeldata[$height][$width];
				}

				$this->pixelData[$height][$width] = $pixel;
			}
		}
	}

	//TODO: write image data

}
