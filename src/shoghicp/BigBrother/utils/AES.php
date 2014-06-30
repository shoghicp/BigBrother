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

class AES{
	private $key, $keyLenght, $IV, $IVLenght, $enc, $dec, $mode, $algorithm;

	function __construct($bits, $mode, $blockSize){
		$this->algorithm = "rijndael-".intval($bits);
		$this->mode = strtolower($mode);
		$mcrypt = mcrypt_module_open($this->algorithm, "", $this->mode, "");
		$this->IVLenght = mcrypt_enc_get_iv_size($mcrypt);
		mcrypt_module_close($mcrypt);
		$this->keyLenght = $bits >> 3;
		$this->setKey();
		$this->setIV();
		$this->init();
	}

	public function init(){
		if(is_resource($this->enc)){
			mcrypt_generic_deinit($this->enc);
			mcrypt_module_close($this->enc);
		}
		$this->enc = mcrypt_module_open($this->algorithm, "", $this->mode, "");
		mcrypt_generic_init($this->enc, $this->key, $this->IV);

		if(is_resource($this->dec)){
			mcrypt_generic_deinit($this->dec);
			mcrypt_module_close($this->dec);
		}
		$this->dec = mcrypt_module_open($this->algorithm, "", $this->mode, "");
		mcrypt_generic_init($this->dec, $this->key, $this->IV);
	}

	public function setKey($key = ""){
		$this->key = str_pad($key, $this->keyLenght, "\x00", STR_PAD_RIGHT);
	}

	public function setIV($IV = ""){
		$this->IV = str_pad($IV, $this->IVLenght, "\x00", STR_PAD_RIGHT);
	}

	public function encrypt($plaintext){
		return mcrypt_generic($this->enc, $plaintext);
	}

	public function decrypt($ciphertext){
		return mdecrypt_generic($this->dec, $ciphertext);
	}

}