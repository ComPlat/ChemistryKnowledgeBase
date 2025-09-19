#!/bin/bash
if [ -z "$1" ]
  then
    echo "No DB pw supplied"
    exit
fi
if [ -z "$2" ]
  then
    echo "No DB name supplied"
    exit
fi
NOW=`date '+%F_%H:%M:%S'`;
mysqldump -u wiki -p$1 $2 > /tmp/db_$NOW.sql
