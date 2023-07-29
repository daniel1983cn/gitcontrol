#!/bin/sh
#接收传递给shell脚本的变量
#第一个为gitlab安装目录
hookpath=$1"/gitaly/custom_hooks/pre-receive.d"
#第二个为gitcontrol的站点目录
#gitcontrol_path=$2

#定义hook脚本目录
cd ../
cp_shell="application/admin/lang/shell/branchcontrol.sh "$hookpath"/"

sh_file=$hookpath"/branchcontrol.sh"
if [ ! -f "$sh_file" ];then
  # shellcheck disable=SC2225
  cp $cp_shell
fi
###脚本文件中可能会存在换行符^M， 所以要执行以下命令去除换行符
sed -i -e 's/\r//g' $hookpath"/branchcontrol.sh"
chown -R www:www $sh_file
chmod 777 -R $sh_file
