<?php

class MrsServer {
    private $_host = '127.0.0.1';
    private $_port = 9501;
    private $_file = '';
    private $_serv;
    private $_transfile = false;

    const CRLF = "\n";

    public function __construct() {
        
        $this->_serv = new swoole_server($this->_host, $this->_port, SWOOLE_BASE, SWOOLE_SOCK_TCP);
        $this->eventHandler();
    }

    public function eventHandler() {

        $this->_serv->on('connect', function($serv, $fd){

            $this->_log("Client: Connect.");
        });

        $this->_serv->on('receive', function($serv, $fd, $from_id, $data){
            if (strpos($data, 'END') === 0) {
                $this->_log("End.");
                $this->_transfile = false;
                $this->_serv->send($fd, 'MRS: uploaded filename: ' . $this->_file, $from_id);
                $this->_serv->close($fd);
            } else if (strpos($data, 'BEGIN') === 0) {
                $this->_log("Begin:");
                $this->_file = trim(substr($data, 5));
                $this->_transfile = true;
                if (is_file('/tmp/' . $this->_file)) {
                    unlink('/tmp/' . $this->_file);
                }
                $this->_serv->send($fd, 'MRS: uploading filename: ' . $this->_file, $from_id);
            } else {
                if ($this->_transfile) {
                    file_put_contents('/tmp/' . $this->_file, $data, FILE_APPEND);
                    $this->_log("Data Length:" . strlen($data));
                    $this->_serv->send($fd, 'MRS: Data Length: ' . strlen($data), $from_id);
                } else {
                    $this->_log("Upload invalid.");
                    $this->_serv->send($fd, 'invalid data.', $from_id);
                }
            }
        });

        $this->_serv->on('close', function ($serv, $fd) {
            $this->_log("Client: Close.");
        });
    }

    public function start() {

        $this->_serv->set(array(
            'daemonize' => true,
        ));
        $this->_serv->start();
    }

    private function _log($msg) {

        $out = '[' . date('Y-m-d H:i:s') . '] ' . $msg. self::CRLF;

        $logFile = './mrs-server.log';

        // 增加STDOUT的输出
        if ($stdout = fopen('php://stdout', 'w')) {
            fwrite($stdout, $out);
            fclose($stdout);
        }

        // 文件日志输出
        if($fp = fopen($logFile, 'a')) {
            fwrite($fp, $out);
            fclose($fp);
            return true;
        } else {
            error_log("MRS Log: Cannot open file ($logFile)");
            return true;
        }
    }
}

try {
    $mrs = new MrsServer;
    $mrs->start();
} catch (Exception $e) {
    file_put_contents('./error.log', $e->getMessage().PHP_EOL, FILE_APPEND);
}
