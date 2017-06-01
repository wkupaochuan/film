#!/bin/bash

type=$1
excludeCmd="--exclude=application/config/database.php"
if [ $type -eq "1" ]
then
    rsync -tpcrv --delay-updates --timeout=60 $excludeCmd ./* filmfilm@166.62.86.13:/home/www/film/
else
    rsync -tpcrv --delay-updates --timeout=60 $excludeCmd ./* root@120.76.76.195:/home/wangchuan/film/
fi