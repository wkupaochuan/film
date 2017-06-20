#!/bin/bash
slave_is=($(ssh filmfilm@166.62.86.13 "mysql -uroot -proot -e 'use skin;show slave status\G'" | grep "Slave_.*_Running" | awk '{print $2}'))

if [ "${slave_is[0]}" == "Yes" -a "${slave_is[1]}" == "Yes" ]
then
        echo `date "+%Y-%m-%d %H:%M:%S"` "ok"
else
        echo `date "+%Y-%m-%d %H:%M:%S"` "no"
fi