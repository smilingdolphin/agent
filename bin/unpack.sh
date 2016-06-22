#!/bin/bash

file=$1

cd "`dirname $file`"
tar zxf $file > /dev/null

if [ $? -eq 0 ] ; then
    echo '{"code": 0, "msg": "unpack success."}'
else
    echo '{"code": 1, "msg": "unpack failed."}'
fi
