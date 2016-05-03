<?php
namespace MrsAgent;

class Client extends Base {

    protected $sock;
    protected $errCode;
    protected $errMsg;

    function connect($host, $port, $timeout = 30) {

        $this->sock = new \swoole_client(SWOOLE_SOCK_TCP, SWOOLE_SOCK_SYNC);
        if ($this->sock->connect($host, $port, $timeout) === false) {
            $this->errCode = $this->sock->errCode;
            return false;
        }

        return true;
    }

    function upload($localFile, $remoteFile) {

        $filesize = filesize($localFile);

        if (!$filesize) {
            throw new \Exception('Local file size cannot be zero.');
        }

        $req = array(
            'cmd' => 'upload',
            'size' => $filesize,
            'file' => $remoteFile,
        );

        $result = $this->_request($req);

        if ($result['code'] == 0) {
            $this->sock->sendfile($localFile);
            $ret = $this->unpack($this->sock->recv());
        }

        $this->_response($ret);
    }

    function exec($shellScript, $args  = array()) {

        $req = array(
            'cmd' => 'execute',
            'script' => $shellScript,
            'args' => $args
        );

        $result = $this->_request($req);

        $this->_response($result);
    }

    private function _request($data) {

        $pkg = $this->pack($data, true);
        $ret = $this->sock->send($pkg);
        if ($ret === false) {
            $this->errCode = $this->sock->errCode;
            return false;
        }
        $ret = $this->sock->recv();
        if (!$ret) {
            $this->errCode = $this->sock->errCode;
            return false;
        }
        $json = $this->unpack($ret, true);

        //服务器端返回的内容不正确
        if (!isset($json['code'])) {
            $this->errCode = 9000;
            return false;
        }

        return $json;
    }

    private function _response($ret) {

        if ($ret['code'] != 0) {
            print_r($ret);
            $this->errCode = $ret['code'];
            $this->errMsg = $ret['msg'];
            throw new \Exception($this->errMsg, $this->errCode);
        } else {
            $this->log($ret['msg']);
        }
    }
}
