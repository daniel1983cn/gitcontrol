#!/bin/sh
#接收传递给shell脚本的变量
server_path=$1
hookpath=$server_path"/gitaly/custom_hooks/pre-receive.d"
del_file=$hookpath"/branchcontrol.sh"
rm -rf $del_file