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

use phpseclib\Crypt\Rijndael;

class AES extends Rijndael{

	const MODE_CFB8 = 38;

	/**
	 * @param int $mode
	 * @override
	 */
	public function __construct(int $mode = self::MODE_CFB8){
		parent::__construct($mode);
		if($mode === self::MODE_CFB8){
			$this->mode = $mode;
			$this->paddable = false;
		}

		if($this->use_inline_crypt !== false){
			$this->use_inline_crypt = version_compare(PHP_VERSION, '5.3.0', '>=') or function_exists('create_function');
		}
	}

	/**
	 * TODO this method overrides private Base::_setupMcrypt() method
	 * @override
	 */
	function _setupMcrypt(){
		switch($this->mode){
			case self::MODE_CFB8:
				$this->_clearBuffers();
				$this->enchanged = $this->dechanged = true;

				if(!isset($this->enmcrypt)){
					$mode = constant("MCRYPT_MODE_CFB");
					$this->demcrypt = @mcrypt_module_open($this->cipher_name_mcrypt, '', $mode, '');
					$this->enmcrypt = @mcrypt_module_open($this->cipher_name_mcrypt, '', $mode, '');
				}
			break;
			default:
				parent::_setupMcrypt();
			break;
		}
	}

	/**
	 * TODO this method override private Base::_openssl_translate_mode()
	 * @return string
	 * @override
	 */
	function _openssl_translate_mode(){
		switch($this->mode){
			case self::MODE_CFB8:
				return "cfb8";
			default:
				return parent::_openssl_translate_mode();
		}
	}

	/**
	 * TODO this method override private Base::_createInlineCryptFunction() method
	 * @param array $cipher_code
	 * @return string
	 * @override
	 */
	public function _createInlineCryptFunction($cipher_code){
		$inline = null;

		if($this->mode === self::MODE_CFB8){
			$block_size = $this->block_size;

			$init_crypt    = isset($cipher_code['init_crypt'])    ? $cipher_code['init_crypt']    : '';
			$init_encrypt  = isset($cipher_code['init_encrypt'])  ? $cipher_code['init_encrypt']  : '';
			$encrypt_block = $cipher_code['encrypt_block'];

			$encrypt = $init_encrypt . '
				$_ciphertext = "";
				$_len = strlen($_text);
				$_iv = $self->encryptIV;

				for ($_i = 0; $_i < $_len; ++$_i) {
					$in = $_iv;
					'.$encrypt_block.'
					$_ciphertext .= ($_c = $_text[$_i] ^ $in);
					$_iv = substr($_iv, 1, '.$block_size.' - 1) . $_c;
				}

				if ($self->continuousBuffer) {
					if ($_len >= '.$block_size.') {
						$self->encryptIV = substr($_ciphertext, -'.$block_size.');
					} else {
						$self->encryptIV = substr($self->encryptIV, $_len - '.$block_size.') . substr($_ciphertext, -$_len);
					}
				}

				return $_ciphertext;
			';

			$decrypt = $init_encrypt . '
				$_plaintext = "";
				$_len = strlen($_text);
				$_iv = $self->decryptIV;

				for ($_i = 0; $_i < $_len; ++$_i) {
					$in = $_iv;
					'.$encrypt_block.'
					$_plaintext .= $_text[$_i] ^ $in;
					$_iv = substr($_iv, 1, '.$block_size.' - 1) . $_text[$_i];
				}

				if ($self->continuousBuffer) {
					if ($_len >= '.$block_size.') {
						$self->decryptIV = substr($_text, -'.$block_size.');
					} else {
						$self->decryptIV = substr($self->decryptIV, $_len - '.$block_size.') . substr($_text, -$_len);
					}
				}

				return $_plaintext;
			';

			if(version_compare(PHP_VERSION, '5.3.0', '>=')){
				$inline = eval('return function($_action, &$self, $_text){' . $init_crypt . 'if ($_action) {' . $encrypt . '} else {' . $decrypt . '}};');
			}else{
				$inline = @create_function('$_action, &$self, $_text', $init_crypt . 'if ($_action) {' . $encrypt . '} else {' . $decrypt . '};');
			}
		} else {
			$this->use_inline_crypt = false;
		}

		return $inline;
	}

	/**
	 * TODO this method override internal Base::encrypt() method
	 * @param string $plain text to encrypt
	 * @return string encrypted text
	 * @override
	 */
	public function encrypt($plain){
		switch(true){
			case $this->engine === self::ENGINE_OPENSSL and $this->mode === self::MODE_CFB8:
				if($this->paddable){
					$plain = $this->_pad($plain);
				}

				if($this->changed){
					$this->_clearBuffers();
					$this->changed = false;
				}

				$cipher = openssl_encrypt($plain, $this->cipher_name_openssl, $this->key, $this->openssl_options, $this->encryptIV);
				$length = strlen($cipher);

				if($this->continuousBuffer){
					if($length >= $this->block_size){
						$this->encryptIV = substr($cipher, -$this->block_size);
					}else{
						$this->encryptIV = substr($this->encryptIV, $length -$this->block_size) . substr($cipher, -$length);
					}
				}
			break;
			case $this->engine === self::ENGINE_INTERNAL and $this->mode === self::MODE_CFB8:
				if($this->paddable){
					$plain = $this->_pad($plain);
				}

				if ($this->changed) {
					$this->_setup();
					$this->changed = false;
				}

				if($this->use_inline_crypt){
					$inline = $this->inline_crypt;
					return $inline(true, $this, $plain);
				}

				$cipher = '';
				$length = strlen($plain);
				$vector = $this->encryptIV;

				for($i=0; $i<$length; ++$i){
					$cipher .= ($c = $plain[$i] ^ $this->_encryptBlock($vector));
					$vector  = substr($vector, 1, $this->block_size - 1) . $c;
				}

				if($this->continuousBuffer){
					if($length >= $this->block_size){
						$this->encryptIV = substr($cipher, -$this->block_size);
					}else{
						$this->encryptIV = substr($this->encryptIV, $length -$this->block_size) . substr($cipher, -$length);
					}
				}
			break;
			default:
				$cipher = parent::encrypt($plain);
			break;
		}

		return $cipher;
	}

	/**
	 * TODO this method override internal Base::decrypt() method
	 * @param string $cipher text to decrypt
	 * @return string decrypted text
	 * @override
	 */
	public function decrypt($cipher){
		switch(true){
			case $this->engine === self::ENGINE_OPENSSL and $this->mode === self::MODE_CFB8:
				if($this->paddable){
					$cipher = str_pad($cipher, strlen($cipher) + ($this->block_size - strlen($cipher)) % $this->block_size, chr(0));
				}

				if($this->changed){
					$this->_clearBuffers();
					$this->changed = false;
				}

				$plain = openssl_decrypt($cipher, $this->cipher_name_openssl, $this->key, $this->openssl_options, $this->decryptIV);

				if($this->continuousBuffer){
					if(($length = strlen($cipher)) >= $this->block_size){
						$this->decryptIV = substr($cipher, -$this->block_size);
					}else{
						$this->decryptIV = substr($this->decryptIV, $length -$this->block_size) . substr($cipher, -$length);
					}
				}
			break;
			case $this->engine === self::ENGINE_INTERNAL and $this->mode === self::MODE_CFB8:
				if($this->paddable){
					$cipher = str_pad($cipher, strlen($cipher) + ($this->block_size - strlen($cipher)) % $this->block_size, chr(0));
				}

				if($this->changed) {
					$this->_setup();
					$this->changed = false;
				}

				if($this->use_inline_crypt){
					$inline = $this->inline_crypt;
					return $inline(false, $this, $cipher);
				}

				$plain = '';
				$length = strlen($cipher);
				$vector = $this->decryptIV;

				for($i=0; $i<$length; ++$i){
					$plain .= $cipher[$i] ^ $this->_encryptBlock($vector);
					$vector = substr($vector, 1, $this->block_size - 1) . $cipher[$i];
				}

				if($this->continuousBuffer){
					if($length >= $this->block_size){
						$this->decryptIV = substr($cipher, -$this->block_size);
					}else{
						$this->decryptIV = substr($this->decryptIV, $length -$this->block_size) . substr($cipher, -$length);
					}
				}
			break;
			default:
				$plain = parent::decrypt($cipher);
			break;
		}

		return $plain;
	}
}
