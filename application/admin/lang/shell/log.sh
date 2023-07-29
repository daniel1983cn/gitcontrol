#!/bin/sh
#定义日志文件，若不存在则创建
logpath=$1
logfile=$logpath"/app_"$(date +%Y%m%d)".log"
mkdir -p $logpath
if [ ! -f "$logfile" ];then
  touch $logfile
  chown -R www:www $logpath
  chmod 777 -R $logfile
fi