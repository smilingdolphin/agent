<?php
namespace MrsAgent;

class Server extends Base {

    /**
     * files 
     * 客户端连接句柄
     * 
     * @var mixed
     * @access protected
     */
    protected $files;

    /**
     * serv 
     * 监听服务句柄
     * 
     * @var mixed
     * @access protected
     */
    protected $serv;

    /**
     * cCMD 
     * 当前请求命令
     * 
     * @var mixed
     * @access protected
     */
    protected $cCMD;

    /**
     * locks 
     * 
     * @var mixed
     * @access protected
     */
    protected $locks;

    function onConnect($serv, $fd, $from_id) {

        clearstatcache();
        $this->log('New client connected.');
    }

    function onClose($serv, $fd, $from_id) {

        unset($this->files[$fd]);
        $this->log('client closed.');
    }

    function onReceive($serv, $fd, $from_id, $data) {
        
        // 传输未开始
        if (!isset($this->files[$fd])) {
            $req = $this->unpack($data);
            if ($req === false || !$req['cmd']) {
                return $this->response($fd, 400, 'Error Request');
            }
            $func = '_cmd' . ucfirst($req['cmd']);
            $this->cCMD = $req['cmd'];

            if (is_callable([$this, $func])) {
                call_user_func([$this, $func], $fd, $req);
            } else {
                return $this->response($fd, 404, 'Command not support.');
            }

        // 建立传输
        } else {
            $this->transportStart($fd, $data);
        }

    }

    private function _cmdUpload($fd, $req) {

        if (!$req['file'] || !$req['size']) {
            return $this->response($fd, 500, 'Require dst file and size');
        }
        // size 检查 todo
        $file = $req['file'];
        if ($this->locks[$file]) {
            return $this->response($fd, 501, 'File locked by other task.');
        }
        // 文件能否被访问 todo
        // 是否重写文件 todo
        // 创建目录 todo
        $fp = fopen($file, 'w');
        if (!$fp) {
            return $this->response($fd, 504, 'Cannot open file['.$file.'].');
        }

        $task = array(
            'fp' => $fp,
            'file' => $file,
            'size' => $req['size'],
            'recv' => 0,
        );
        // 锁文件
        if (!flock($fp, LOCK_EX)) {
            return $this->response($fd, 505, 'Cannot lock file['.$file.'].');
        }
        $this->locks[$file] = true;

        $this->response($fd, 0, 'Transmission start.');
        $this->files[$fd] = $task;
    }

    private function _cmdExecute($fd, $req) {

        if (!isset($req['script'])) {
            return $this->response($fd, 500, 'Require shell script file');
        }

        // 清除stat缓存
        clearstatcache();
        if (!is_file($req['script'])) {
            return $this->response($fd, 404, 'shell script ' . $req['script'] . ' not found.');
        }

        // 只允许执行指定目录的script todo
        if (!isset($req['args'])) {
            $req['args'] = array();
        }

        // 规定shell脚本里返回json数据
        $result = shell_exec($req['script'] . ' ' . implode(' ', $req['args']));
        if ($result)  {
            $result = json_decode($result, true);
        }

        if ($result['code'] == 0) {
            $this->response($fd, 0, 'Execute shell success.');
        } else {
            $this->response($fd, $result['code'], $result['msg']);
        }
    }

    /**
     * response 
     * 发送回应数据
     * 
     * @access public
     * @return void
     */
    function response($fd, $code, $msg, $data = null) {

        $array = array('code' => $code, 'msg' => $msg);
        if ($data) {
            $array['data'] = $data;
        }
        $this->serv->send($fd, $this->pack($array));

        //打印日志
        if (is_string($msg) and strlen($msg) < 256)
        {
            $this->log("[-->$fd]\t{$this->cCMD}\t$code\t$msg\n");
        }
        return true;
    }

    function transportStart($fd, $data) {

        $info = &$this->files[$fd];
        $fp = $info['fp'];
        $file = $info['file'];

        // 如果写入失败，则终止传输
        if (!fwrite($fp, $data)) {
            $this->response($fd, 600, 'Fwrite failed, transmission stop.');
            flock($fp, LOCK_UN);
            fclose($fp);
            unlink($file);
        }

        $info['recv'] += strlen($data);
        // 如果接收大等于文件size，则传输完成
        if ($info['recv'] >= $info['size']) {
            $this->transportEnd($fd, $info);
        }
    }

    function transportEnd($fd, $info) {

        $fp = $info['fp'];
        $file = $info['file'];

        flock($fp, LOCK_UN);
        fclose($fp);
        unset($this->locks[$file]);
        unset($this->files[$fd]);
        return $this->response($fd, 0, 'Success. transmission complete.');
    }

    public function run($host = '0.0.0.0', $port = 9527) {

        $this->serv = new \swoole_server($host, $port, SWOOLE_BASE);
        global $argv;
        $options = array();
        if (isset($argv[1]) && $argv[1] == 'deamon') {
            $options['daemonize'] = true;
        }

        $this->serv->set($options);
        $this->serv->on('connect', [$this, 'onConnect']);
        $this->serv->on('receive', [$this, 'onReceive']);
        $this->serv->on('close', [$this, 'onClose']);
        $this->serv->start();
    }
}
