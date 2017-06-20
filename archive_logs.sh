#! /bin/bash

basepath=$(cd `dirname $0`; pwd)
f_time=`date "+%Y-%m-%d"`
for file_name in `ls $basepath/application/logs/ | grep .log`
do
    full_path=$basepath/application/logs/$file_name
    if test -f $full_path
    then
        des_path=$basepath/application/logs/archive/$file_name.$f_time
        cp $full_path $des_path
        > $full_path
    fi
done

exit