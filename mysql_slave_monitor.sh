#!/bin/bash
slave_is=($(ssh filmfilm@166.62.86.13 "mysql -uroot -proot -e 'use skin;show slave status\G'" | grep "Slave_.*_Running" | awk '{print $2}'))

if [ "${slave_is[0]}" == "Yes" -a "${slave_is[1]}" == "Yes" ]
then
        echo film `date "+%Y-%m-%d %H:%M:%S"` "ok"
else
        echo film `date "+%Y-%m-%d %H:%M:%S"` "no"
fi

ali_slave_is=($(/usr/local/mysql/bin/mysql -S /data/mysql/3307/mysql.sock -uroot -proot -e "use skin;show slave status\G;" | grep  Slave_.*_Running | awk '{print $2}'))

if [ "${ali_slave_is[0]}" == "Yes" -a  "${ali_slave_is[1]}" == "Yes" ]
then
        echo ali `date "+%Y-%m-%d %H:%M:%S"` "ok"
else
        echo ali `date "+%Y-%m-%d %H:%M:%S"` "no"
fi

echo -e "\n"

exit