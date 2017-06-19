#!/bin/bash

type=$1
excludeCmd="--exclude=application/config/database.php --exclude=templates_c/ --exclude=templates_m/ --exclude=sitemap_index.xml --exclude=sitemap_updated_index.xml"

if [ $type -eq "1" ]
then
    user="filmfilm"
    ip="166.62.86.13"
    path="/home/www/film/"
elif [ $type -eq "2" ]
then
   user="root"
   ip="120.76.76.195"
   path="/home/wangchuan/film/"
#rsync -tpcrv --delay-updates --timeout=60 $excludeCmd ./* root@120.76.76.195:/home/wangchuan/film/
elif [ $type -eq "3" ]
then
   user="wangchuanchuan"
   ip="10.108.214.103"
   path="/home/wangchuanchuan/film/film/"
else
    echo "false"
    exit
fi

rsync -tpcrv --delay-updates -vzrtopg --delete --progress --timeout=60 $excludeCmd ./*  $user@$ip:$path