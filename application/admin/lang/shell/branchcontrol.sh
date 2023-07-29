#!/bin/sh
#获取git仓库地址
#base_url=$(awk '{if(($0~ "external_url")&&($0~"http://"))print $0}' /etc/gitlab/gitlab.rb |grep -v "#"|awk -F "external_url " '{print $2}'|awk -F "'" '{print $2}')

#echo -e "===============以下为调试信息=============\n"
existbranch=""
while read oldValue newValue refName;do
  branchname=${refName#refs/heads/}
  #定义git仓库路径
  gitres="/var/opt/gitlab/git-data/repositories/@hashed"
  branchpath="refs/heads/"
  cd $gitres
  #读取所有项目的配置
  objectlist=$(find ./ -name "config"|grep -v ".wiki.git/config")
  for i in $objectlist;do
    #解析项目配置中的项目名称
    object=$(awk -F "fullpath = " '{if ( $0 ~ "fullpath = " )print $2}' ${i})
    #拼接git项目的完整路径
    obpath=${i%config*}
#	  echo -e "\n项目的地址："$obpath
    #解析git项目的分支列表
    brancharr=$(ls ${obpath}${branchpath})
    for j in $brancharr;do
      #解析出遍历的当前远程仓库分支的最新提交hash值
      remote_oldValue=$(cat $obpath$branchpath${j})
      #根据当前提交的上个push hash值与远程仓库分支的最新hash值的一致性判断获取当前push对应的项目和分支
      if [ "$remote_oldValue" == "$oldValue"  ];then
#        echo -e "\n当前提交的分支"$branchname";该分支远程仓库的最新提交hash值："$remote_oldValue"本地上次hash值："$oldValue
        #如果当前push的分支名在项目中存在，则表示当前push不是创建分支
        if [ "$branchname" == "${j}" ];then
#          echo -e "\n远程仓库已存在分支"${j}
          existbranch=$branchname
          break
        fi
      fi
    done
  done
done
#echo -e "===============调试结束=============\n"
#若push操作为新创建分支，则拒绝push操作
if [ ! -n "$existbranch"  ];then
  echo "不允许自主创建分支，请联系git管理员"
  exit 1
fi