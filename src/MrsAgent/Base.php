<?php
namespace MrsAgent;

class Base {

    protected $encrypt = false;

    protected $des;

    const CRLF = PHP_EOL;

    function __construct($key = '') {

        if ($key) {
            $this->encrypt = true;
            $this->des = new Des($key);
        }
    }

    function pack($data, $ejson = true) {

        $_data = ($ejson) ? json_encode($data, JSON_UNESCAPED_UNICODE) : $data;
        $_sdata = ($this->encrypt) ? $this->des->encode($_data) : $_data;
        return pack('N', strlen($_sdata)) . $_sdata;
    }

    function unpack($data, $djson = true) {

        $_data = substr($data, 4);
        $_rdata = ($this->encrypt) ? $this->des->decode($_data) : $_data;
        return ($djson) ? json_decode(trim($_rdata), true) : trim($_rdata);
    }

    function log($msg) {

        $out = '[' . date('Y-m-d H:i:s') . '] ' . $msg . self::CRLF;

        // 增加STDOUT的输出
        if ($stdout = fopen('php://stdout', 'w')) {
            fwrite($stdout, $out);
            fclose($stdout);
        }
    }
}
