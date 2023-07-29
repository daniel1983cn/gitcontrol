<?php

namespace app\api\controller;

use app\common\controller\Api;
use think\Db;
use think\Model;

/**
 * 示例接口
 */
class Branch extends Api
{

    //如果$noNeedLogin为空表示所有接口都需要登录才能请求
    //如果$noNeedRight为空表示所有接口都需要验证权限才能请求
    //如果接口已经设置无需登录,那也就无需鉴权了
    //
    // 无需登录的接口,*表示全部
    protected $noNeedLogin = ['*'];
    // 无需鉴权的接口,*表示全部
    protected $noNeedRight = ['*'];


    /*
     * 根据第三方传递的参与自动创建对应项目的git分支
     * 参数1：String：项目名称
     * 参数2：Array：分支名变量数组（需要与分支控制后台配置的变量名一致）
     * @ApiReturn   ({
         'code':'1',
         'msg':'返回成功'
        })
     */
    public function create()
    {

        $projectname = $this->request->post("project_name");
        $branch_params = $this->request->post("branch_params");
        if(!isset($projectname)||$projectname==''){
            $this->error('项目名称不能为空',['projectname'=>$projectname]);
        } elseif (!isset($branch_params)||!is_array($branch_params)&&count($branch_params)==0){
            $this->error('分支名不能为空',['branch_params'=>$branch_params]);
        }
        //获取git配置信息
        $gitinfoarr = db::query("select `name`,`value` from fa_config where `group`='gitinfo';");
        foreach ($gitinfoarr as $grk=>$grv){
            $git_conf_info[$grv['name']]=$grv['value'];
        }
        $gitres_path=$git_conf_info["gitinfo_gitres"];
        //获取git配置信息
        $branch_conf_arr = db::query("select `name`,`value` from fa_config where `group`='branchcontrol';");
        foreach ($branch_conf_arr as $bck=>$bcv){
            $branch_conf_info[$bcv['name']]=$bcv['value'];
        }
        //全局控制分支
        if(isset($branch_conf_info['branchcontrol_true'])) {
            $local_path=dirname(__DIR__)."/../../../";
            $logfile=$local_path."/gitcontrol/log/app_".date("Ymd").".log";
//            echo dirname(__DIR__)."\n";
//            echo $local_path;
            $shell_c="/bin/sh ".$local_path."gitcontrol/application/admin/lang/shell/create_branch.sh";
            if(in_array($branch_conf_info['branchcontrol_true'],['1'])){
                //定义分支名变量
                $branch_name="";
                $admin_branch_conf = (array)json_decode($branch_conf_info['branchcontrol_variable']);
                foreach ($admin_branch_conf as $admin_k=>$admin_v){
                    foreach ($branch_params[0] as $req_k=>$req_v){
                        //如果传递的变量名不在管理后台配置的列表中，则变量名非法
                        if(!in_array($req_k,array_keys($admin_branch_conf))){
                            $this->error('参数名非法');
                        }
                        else if($admin_k == $req_k){
                            if(substr($admin_v,0,2)=='${'){
                            }
                            if(substr($admin_v,0,2)=='${' && substr($admin_v,-1,1)=="}"){
                                $branch_name.=$req_v."-";
                            }
                            else {
                                $branch_name.=$admin_v."-";
                            }
                        }
                    }
                }
                if(substr($branch_name,-1,1)=="-"){
                    $branch_name=substr($branch_name,0,strlen($branch_name)-1);
                }
//                echo "项目名称：".$projectname."\n";
//                echo "git仓库地址：".$gitres_path."\n";
//                echo "分支名：".$branch_name;
//                exit;
                $shell_paramt=$gitres_path." ".$projectname." ".$branch_name." ".$local_path;
                $shell_c=$shell_c." ".$shell_paramt;
                error_log("【".date('Y-m-d H:i:s')."】执行命令为：".$shell_c."\n",3,$logfile);
                system($shell_c);
            }else{
                $this->error('当前的分支管理模式为：沿用gitlab分支管理');
            }
        }else{
            $this->error('未配置分支管理模式');
        }
    }

    /*
     * webhook,对git的远程仓库触发的事件完成后git主动调用该接口，用于分析具体时间信息
     * @ApiReturn   ({
         'code':'1',
         'msg':'返回成功'
        })
     */
    public function git_log(){
        $json = file_get_contents("php://input");
        $data = json_decode($json,true);

        $loginfo['id']="";
        $loginfo['op_type']=$data['object_kind'];
        $loginfo['projectname']=$data['project']['path_with_namespace'];
        if ($data['object_kind']=="push"){
            $loginfo['user']=$data['user_name'];
            $loginfo['ref']=$data['ref'];
            $data['before']=="0000000000000000000000000000000000000000"?$loginfo['isnewbranch']=1:$loginfo['isnewbranch']=0;

            if(count(explode("UTC",$data['commits'][0]['timestamp']))>1){
                $loginfo['commit_time']=date("Y-m-d H:i:s",strtotime(substr($data['commits'][0]['timestamp'],0,-4))+28800);
            } elseif ((count(explode("T",$data['commits'][0]['timestamp']))>1)||(count(explode("Z",$data['commits'][0]['timestamp']))>1)){
                $loginfo['commit_time']=str_ireplace(array("T","Z")," ",substr($data['commits'][0]['timestamp'],0,-6));
            } else{
                $loginfo['commit_time']=$data['commits'][0]['timestamp'];
            }
            $loginfo['commits_count']=$data['total_commits_count'];
            $loginfo['repository']=$data['repository']['git_http_url'];
            $loginfo['commit_message']=$data['commits'][0]['message'];
        } elseif ($data['object_kind']=="note"){
            $loginfo['user']=$data['user']['name'];
            $loginfo['ref']=$data['commit']['id'];
            $loginfo['isnewbranch']=0;

            if(count(explode("UTC",$data['object_attributes']['created_at']))>1){
                $loginfo['commit_time']=date("Y-m-d H:i:s",strtotime(substr($data['object_attributes']['created_at'],0,-4))+28800);
            }elseif(count(explode("T",$data['object_attributes']['created_at'])>1)||count(explode("Z",$data['object_attributes']['created_at']))>1) {
                $loginfo['commit_time']=str_ireplace(array("T","Z")," ",substr($data['object_attributes']['created_at'],0,-6));
            } else {
                $loginfo['commit_time']=$data['object_attributes']['created_at'];
            }
            $loginfo['commits_count']=0;
            $loginfo['repository']=$data['repository']['homepage'];
            $loginfo['commit_message']=$data['object_attributes']['note'];
        } elseif ($data['object_kind']=="merge_request"){
            $loginfo['user']=$data['user']['name'];
            $loginfo['ref']=$data['object_attributes']['target_branch'];
            $loginfo['isnewbranch']=0;

            if(count(explode("UTC",$data['object_attributes']['created_at']))>1){
                $loginfo['commit_time']=date("Y-m-d H:i:s",strtotime(substr($data['object_attributes']['created_at'],0,-4))+28800);
            }elseif(count(explode("T",$data['object_attributes']['created_at']))>1||count(explode("Z",$data['object_attributes']['created_at']))>1) {
                $loginfo['commit_time']=str_ireplace(array("T","Z")," ",substr($data['object_attributes']['created_at'],0,-6));
            } else {
                $loginfo['commit_time']=$data['object_attributes']['created_at'];
            }
            $loginfo['commits_count']=0;
            $loginfo['repository']=$data['repository']['homepage'];
            $loginfo['commit_message']=$data['object_attributes']['description'];
        }
        $loginfo['logjson']=$json;
        try{
            $in_result=db::table("fa_gitlog")->strict(false)->insert($loginfo);
            if($in_result){
                $this->success("git操作同步成功");
            }else{
                $this->error("git操作同步失败");
            }
        }catch (\Exception $e) {
                $this->error($e->getMessage());
        }
    }

}
