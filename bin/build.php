<?php

define('WEBPATH', dirname(__DIR__) . '/src'); 

class Build {
  public function __construct($opts) {
    $cmd = '_'.$opts[1];
    if ($cmd && method_exists($this, $cmd)) {
      $this->$cmd();
    } 
  }

  private function _agent() {

    $pharFile = __DIR__ . '/mrs-agent.phar';
    if (is_file($pharFile)) {
      unlink($pharFile);
    }
    $phar = new Phar($pharFile);
    $phar->buildFromDirectory(WEBPATH, '/\.php$/');
    $phar->addFile(WEBPATH . '/encrypt.key', 'encrypt.key');
    $phar->compressFiles(\Phar::GZ);
    $phar->stopBuffering();
    $phar->setStub($phar->createDefaultStub('agent.php'));
    $this->_log('agent.phar打包成功');
  }

  private function _log($msg) {

    echo '[' . date('Y-m-d H:i:s.u') . ']' . $msg . PHP_EOL; 
  }
}

$b = new Build($argv);

