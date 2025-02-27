#!/bin/bash
if [ -z "$1" ]
  then
    echo "No DB pw supplied"
    exit
fi
NOW=`date '+%F_%H:%M:%S'`;
mysqldump -u wiki -p$1 chemmain_139_new > /tmp/db_$NOW.sql
