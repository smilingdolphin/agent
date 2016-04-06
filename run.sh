#!/bin/bash

cmd=$1
PHP=/usr/local/bin/php7
NAME=mrs-server
PHPFILE=$(cd $(dirname $0);pwd)/${NAME}.php

case $cmd in
'start')
    $PHP $PHPFILE
;;
'restart')
    kill `ps aux|grep php|grep $NAME|awk '{print $2}'`
    $PHP $PHPFILE
;;
'stop')
    kill `ps aux|grep php|grep $NAME|awk '{print $2}'`
;;
esac
