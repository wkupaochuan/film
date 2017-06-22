#!/bin/bash

type=$1
excludeCmd=`cat << EXCLUDECMD
--exclude=application/config/database.php
--exclude=application/logs/*.log
--exclude=application/logs/archive/*
--exclude=application/data/douban/*
--exclude=templates_c/
--exclude=templates_m/
--exclude=sitemap_index.xml
--exclude=sitemap_updated_index.xml"
EXCLUDECMD
`

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
elif [ $type -eq "3" ]
then
   user="wangchuanchuan"
   ip="10.108.214.103"
   path="/home/wangchuanchuan/film/film/"
else
    echo "false"
    exit
fi

rsync -tpcrvzog --delay-updates --progress --timeout=60 $excludeCmd ./*  $user@$ip:$path