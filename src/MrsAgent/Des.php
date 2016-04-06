<?php
namespace MrsAgent;

/**
 * DES 
 * 我的简易对称性加密类
 * 
 * @package 
 * @copyright 2003-2016 The PHP Developer
 * @author 温达明 <wendaming@qq.com> 
 * @license PHP Version 5.5/7.0 {@link http://www.php.net/}
 */
class Des {

    private $_key;
    private $_iv;
    const KEY_LENGTH = 32;

    public function __construct($key = '') {

        if (!function_exists('mcrypt_create_iv')) {
            throw new \Exception(__CLASS__ . " require mcrypt extension.");
        }

        if (strlen($key) !== self::KEY_LENGTH) {
            throw new \Exception(__CLASS__ . " key length must be 32 chars.");
        }

        $this->_key = $key;

        $ivsize = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB);
        $this->_iv = mcrypt_create_iv($ivsize, MCRYPT_RAND);
    }

    public function encode($str) {

        return base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $this->_key, $str, MCRYPT_MODE_ECB, $this->_iv));
    }

    public function decode($cryptStr) {

        return mcrypt_decrypt(MCRYPT_RIJNDAEL_256, $this->_key, base64_decode($cryptStr), MCRYPT_MODE_ECB, $this->_iv);
    }
}
