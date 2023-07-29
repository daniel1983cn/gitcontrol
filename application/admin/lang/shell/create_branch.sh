#!/bin/sh
gitres=$1
project_name=$2
new_branchName=$3
local_path=$4
logfile=$local_path"gitcontrol/log/app_"$(date +%Y%m%d)".log"
echo -e "\ncreate_branch.sh脚本开始执行,gitres的值："$gitres";project_name的值："$project_name";新分支名称："$new_branchName";网站地址："$local_path >> $logfile
#cd $gitres
#echo -e "当前目录"$(pwd)"\n" >> $logfile
objectlist=$(find ${gitres} -name "config"|grep -v ".wiki.git/config")
echo -e "获取项目列表命令：find "$gitres"/ -name \"config\"|grep -v \".wiki.git/config\"\n" >> $logfile
branchpath="refs/heads"
echo -e "获取到的项目列表数组,长度为"${#objectlist[*]}"；第0个数组"${objectlist[0]}"；第1个数组"${objectlist[1]}"其他数组"${objectlist[*]}"\n" >> $logfile

for i in $objectlist;do
  #解析项目配置中的项目名称
  object=$(awk -F "fullpath = " '{if ( $0 ~ "fullpath = " )print $2}' ${i})
  echo "awk -F "fullpath = " '{if ( $0 ~ "fullpath = " )print $2}' ${i}" >> $logfile
  echo -e "\n项目名称"$object >> $logfile
  if [ "$object" == "$project_name" ];then
    obpath=${i%config*}
#    echo -e ";地址："$obpath >> $logfile
    brancharr=$(ls ${obpath}${branchpath})
#    echo -e "\n分支数组"${brancharr} >> $logfile
    for j in $brancharr;do
#      echo -e "\nbrancharr数组遍历，j的值为"${j} >> $logfile
      if [ "${j}" == "$new_branchName" ];then
        echo -e "\n"$object"项目下已经存在名称为："$new_branchName"的分支"  >> $logfile
        echo "{\"code\":1,\"msg\":"$object"\"项目下已经存在名称为："$new_branchName"的分支\"}"
        exit 1
      fi
    done
	  cd $obpath
	  git branch $new_branchName
	  echo -e "\n分支创建成功，新分支名称"$new_branchName >> $logfile
	  echo "{\"code\":200,\"message\":\"新分支: "$new_branchName"创建成功!\"}"
	  exit 0
  fi
done
echo "{\"code\":404,\"msg\":\"项目: "$project_name"  不存在!\"}"