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

namespace shoghicp\BigBrother\network;

use shoghicp\BigBrother\utils\Binary;

abstract class Packet extends \stdClass{

	protected $buffer;
	protected $offset = 0;

	protected function get($len){
		if($len < 0){
			$this->offset = strlen($this->buffer) - 1;

			return "";
		}elseif($len === true){
			return substr($this->buffer, $this->offset);
		}

		$buffer = "";
		for(; $len > 0; --$len, ++$this->offset){
			$buffer .= @$this->buffer{$this->offset};
		}

		return $buffer;
	}

	protected function getLong($unsigned = false){
		return Binary::readLong($this->get(8), $unsigned);
	}

	protected function getInt(){
		return Binary::readInt($this->get(4));
	}

	protected function getFloat(){
		return Binary::readFloat($this->get(4));
	}

	protected function getDouble(){
		return Binary::readDouble($this->get(8));
	}

	protected function getShort($unsigned = false){
		return Binary::readShort($this->get(2), $unsigned);
	}

	protected function getTriad(){
		return Binary::readTriad($this->get(3));
	}

	protected function getLTriad(){
		return Binary::readTriad(strrev($this->get(3)));
	}

	protected function getByte(){
		return ord($this->buffer{$this->offset++});
	}

	protected function getString(){
		return $this->get($this->getVarInt());
	}

	protected function getVarInt(){
		return Binary::readVarInt($this->buffer, $this->offset);
	}

	protected function feof(){
		return !isset($this->buffer{$this->offset});
	}

	protected function put($str){
		$this->buffer .= $str;
	}

	protected function putLong($v){
		$this->buffer .= Binary::writeLong($v);
	}

	protected function putInt($v){
		$this->buffer .= Binary::writeInt($v);
	}

	protected function putFloat($v){
		$this->buffer .= Binary::writeFloat($v);
	}

	protected function putDouble($v){
		$this->buffer .= Binary::writeDouble($v);
	}

	protected function putShort($v){
		$this->buffer .= Binary::writeShort($v);
	}

	protected function putTriad($v){
		$this->buffer .= Binary::writeTriad($v);
	}

	protected function putLTriad($v){
		$this->buffer .= strrev(Binary::writeTriad($v));
	}

	protected function putByte($v){
		$this->buffer .= chr($v);
	}

	protected function putString($v){
		$this->putVarInt(strlen($v));
		$this->put($v);
	}

	protected function putVarInt($v){
		$this->buffer .= Binary::writeVarInt($v);
	}

	public abstract function pid();

	protected abstract function encode();

	protected abstract function decode();

	public function write(){
		$this->buffer = "";
		$this->offset = 0;
		$this->encode();
		return Binary::writeVarInt($this->pid()) . $this->buffer;
	}

	public function read($buffer, $offset = 0){
		$this->buffer = $buffer;
		$this->offset = $offset;
		$this->decode();
	}

}