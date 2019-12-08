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

class PNGParser{
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
		$chunkData = zlib_decode($this->stream->get($length));

		$this->rawImageData .= $chunkData;
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
						case 0://None
						break;
						case 1://Sub
							$left = $this->getRGBA($height, $width - 1);
							$r = $this->calculateColor($r, $left[0]);
							$g = $this->calculateColor($g, $left[1]);
							$b = $this->calculateColor($b, $left[2]);
							$a = $this->calculateColor($a, $left[3]);
						break;
						case 2://Up
							$above = $this->getRGBA($height - 1, $width);
							$r = $this->calculateColor($r, $above[0]);
							$g = $this->calculateColor($g, $above[1]);
							$b = $this->calculateColor($b, $above[2]);
							$a = $this->calculateColor($a, $above[3]);
						break;
						case 3://Average
							$left = $this->getRGBA($height, $width - 1);
							$above = $this->getRGBA($height - 1, $width);
							$avrgR = $this->average($left[0], $above[0]);
							$avrgG = $this->average($left[1], $above[1]);
							$avrgB = $this->average($left[2], $above[2]);
							$avrgA = $this->average($left[3], $above[3]);

							$r = $this->calculateColor($r, $avrgR);
							$g = $this->calculateColor($g, $avrgG);
							$b = $this->calculateColor($b, $avrgB);
							$a = $this->calculateColor($a, $avrgA);
						break;
						case 4://Paeth
							$left = $this->getRGBA($height, $width - 1);
							$above = $this->getRGBA($height - 1, $width);
							$upperLeft = $this->getRGBA($height - 1, $width - 1);

							$paethR = $this->paethPredictor($left[0], $above[0], $upperLeft[0]);
							$paethG = $this->paethPredictor($left[1], $above[1], $upperLeft[1]);
							$paethB = $this->paethPredictor($left[2], $above[2], $upperLeft[2]);
							$paethA = $this->paethPredictor($left[3], $above[3], $upperLeft[3]);

							$r = $this->calculateColor($r, $paethR);
							$g = $this->calculateColor($g, $paethG);
							$b = $this->calculateColor($b, $paethB);
							$a = $this->calculateColor($a, $paethA);
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

	private function calculateColor($color1, $color2){
		return ($color1 + $color2) % 256;
	}

	private function average($color1, $color2){
		return floor(($color1[0] + $color2[0]) / 2);
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

	public function setRGBA(int $x, int $z, array $pixelData) : bool{
		if(isset($this->pixelData[$x][$z])){
			$this->pixelData[$x][$z] = $pixelData;
			return true;
		}

		return false;
	}

	private function generatePixelData(){
		$old_pixelData = $this->pixelData;
		$this->pixelData = [];

		for($height = 0; $height < $this->height; $height++){
			$this->pixelData[$height] = [];

			for($width = 0; $width < $this->width; $width++){
				$pixel = [0,0,0,255];
				if(isset($old_pixelData[$height][$width])){
					$pixel = $old_pixelData[$height][$width];
				}

				$this->pixelData[$height][$width] = $pixel;
			}
		}
	}

	//TODO: write image data

}
