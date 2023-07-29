#!/bin/sh
#获取网站的根目录
code_path=$1
#定义最终返回的数据
result_str=""

#定义日志文件
logfile=$code_path"/gitcontrol/log/crontab_"$(date +%Y%m%d)".log"
mkdir -p $code_path"/gitcontrol/log/"
touch $logfile
chown -R www:www $code_path"/gitcontrol/log"
chmod 777 -R $logfile

#读取上次同步code的开始日期
php_path=$code_path"/gitcontrol/application/api/controller"
recieve_start_t=$(/bin/php $php_path"/Sys.php" 'getlastcodeupdate')

echo -e "=============开始执行代码量统计的定时任务=============\n" >> $logfile
echo -e "上次同步的时间为"$recieve_start_t"\n" >> $logfile
start_t=$(date -d "$recieve_start_t last day" +"%Y-%m-%d")
echo -e "实际开始同步的时间"$start_t"\n" >> $logfile
if [ "$start_t" != "" ];then
  code_start=" --since =='"$start_t"'"
else
  code_start=$start_t
fi
#if [ "$end_t" != "" ];then
#  code_end=" --until=='"$end_t"'"
#else
#  code_end=$end_t
#fi
#echo "start_t的值::"$code_start
#echo -e "end_t的值::"$code_end"\n"
#定义git仓库路径
gitres="/var/opt/gitlab/git-data/repositories/@hashed"
branchpath="refs/heads/"
cd $gitres
#读取所有项目的配置
objectlist=$(find ./ -name "config"|grep -v ".wiki.git/config")
#定义代码统计维度
total_codes=0
add_codes=0
deleted_codes=0
for i in $objectlist;do
    cd $gitres
#    echo -e "i的值："${i}"\n"
    #解析项目配置中的项目名称
    object=$(awk -F "fullpath = " '{if ( $0 ~ "fullpath = " )print $2}' ${i})
    echo -e "::::项目名称："$object"," >> $logfile
    #拼接git项目的完整路径
    pro_path=${i%config*}
#    echo -e "项目路径："$pro_path"\n" >> $logfile
#    echo -e "获取分支列表的路径："${pro_path}${branchpath}"\n"
    #解析git项目的分支列表
    if [ ! -d "${pro_path}${branchpath}" ];then
      continue
    fi
    brancharr=$(ls ${pro_path}${branchpath})
        cd $pro_path
        for j in $brancharr;do
          echo -e "::::::分支:"${j}"\n" >> $logfile
          #获取当前分支的提交用户
          userlist=$(git log ${j} --pretty='%an' | uniq)
#          echo -e "当前分支有提交记录的用户："$userlist"\n"
          for k in $userlist; do
#              echo -e "当前用户："${k}"\n" >> $logfile
              echo -e "命令：：：：git log "${j}" $code_start $code_end --author="${k}" --date=iso --pretty=format:\"%an,%ad\" --shortstat\n" >> $logfile
#              exit 1
              exec_gitlog=$(git log "${j}" $code_start $code_end --author="${k}" --date=iso --pretty=format:"%an,%ad" --shortstat)
              if [ "$exec_gitlog" != "" ];then
                user_codelog=""
                for l in $exec_gitlog;do
#                  echo -e ${l}"===\n"
                  if [[ "${l:0:1}" == "+" ]] || [[ "${l:0:1}" == "-" ]] ; then
                    l=$l","
                  fi
                  if [[ "${l:0:${#k}}" == "${k}" ]] && [[ "$user_codelog" != "" ]] ;then
#                    echo -e "user_codelog:0-3:3的值："${user_codelog:0-3:3}"--\n"
                    if [[ "${user_codelog:0-3:3}" == "(+)" ]] || [[ "${user_codelog:0-3:3}" == "(-)" ]] ;then
#                      echo -e "代码量统计日志："$object","${j}","$user_codelog"\n" >> $logfile
                      if [ "${result_str}" == "" ];then
                        result_str=$object","${j}","$user_codelog
                      else
                        result_str=$result_str"  "$object","${j}","$user_codelog
                      fi
                      user_codelog=$l
                    fi
                  else
                    user_codelog=$user_codelog$l
                  fi
                done
                if [[ "${user_codelog:0-3:3}" == "(+)" ]] || [[ "${user_codelog:0-3:3}" == "(-)" ]] ;then
#                  echo -e $object","${j}","$user_codelog
                  if [ "${result_str}" == "" ];then
                    result_str=$object","${j}","$user_codelog
                  else
                    result_str=$result_str"  "$object","${j}","$user_codelog
                  fi
                fi
              fi
          done
        done
done
echo -e "=============执行代码量统计结束=============\n" >> $logfile
php $php_path"/Code.php "$result_str
