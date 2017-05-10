#!/bin/bash

type=$1
if [ $type -eq "1" ]
then
    rsync -tpcrv --delay-updates --timeout=60 ./* filmfilm@166.62.86.13:/home/www/film/
else
    rsync -tpcrv --delay-updates --timeout=60 ./* root@120.76.76.195:/home/wangchuan/film/
fi