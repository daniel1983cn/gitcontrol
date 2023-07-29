<?php

namespace app\api\controller;

use app\common\controller\Api;
use Exception;
use mysqli;
use think\db;


/**
 * 示例接口
 */
class Code
{

    //如果$noNeedLogin为空表示所有接口都需要登录才能请求
    //如果$noNeedRight为空表示所有接口都需要验证权限才能请求
    //如果接口已经设置无需登录,那也就无需鉴权了
    //
    // 无需登录的接口,*表示全部
    protected $noNeedLogin = ['*'];
    // 无需鉴权的接口,*表示全部
    protected $noNeedRight = ['*'];


    public function in_codeinfo(){
        global $c_k,$c_v;
//        var_dump($c_v);exit;
        $codes=array();
        if($c_k>1){
//        print_r($argv);
            $c_str="";
//            echo "c_v的值";var_dump($c_v);echo "c_v结束";
            foreach ($c_v as $k=>$v){
//                $code_info['id']="";
                if($k>0){
//                    echo $v;
                    $td_v=explode(",",$v);
//                    echo "td_v的值";var_dump($td_v);echo "\n";
                    $code_info['del_lines']=0;
                    $code_info['project']="'".$td_v[0]."'";
                    $code_info['branch']="'".$td_v[1]."'";
                    $code_info['user']="'".$td_v[2]."'";
                    $code_info['code_time']="'".substr($td_v[3],0,10)." ".substr($td_v[3],10,8)."'";

                    if(strpos($td_v[4],"filechanged")){
                        $code_info['changes']=substr($td_v[4],0,-11);
                    } elseif (strpos($td_v[4],"fileschanged")){
                        $code_info['changes']=substr($td_v[4],0,-12);
                    }

                    if(strpos($td_v[5],"insertion(+)")){
                        $code_info['add_lines']=substr($td_v[5],0,-12);
                    }elseif(strpos($td_v[5],"insertions(+)")){
                        $code_info['add_lines']=substr($td_v[5],0,-13);
                    }elseif(strpos($td_v[5],"deletions(-)")){
                        $code_info['add_lines']=0;
                         $code_info['del_lines']=substr($td_v[5],0,-12);
                    }elseif (strpos($td_v[5],"deletion(-)")){
                        $code_info['add_lines']=0;
                        $code_info['del_lines']=substr($td_v[5],0,-11);
                    }
                    if(isset($td_v[6])){
                        if(strpos($td_v[6],"deletions(-)")){
                            $code_info['del_lines']=substr($td_v[6],0,-12);
                        }elseif (strpos($td_v[6],"deletion(-)")){
                            $code_info['del_lines']=substr($td_v[6],0,-11);
                        }
                    }
                    $c_str=$code_info['project'].",".$code_info['branch'].",".$code_info['user'].",".$code_info['code_time'].",".$code_info['changes'].",".$code_info['add_lines'].",".$code_info['del_lines'];
                    array_push($codes,$c_str);
//                    echo "-----------程中codes数组:";var_dump($codes);echo "\n";
                }
            }
//            echo "最终codes数组:";var_dump($codes);
            return array_filter($codes);
        }
    }
}

$c_k=$argc;
$c_v=$argv;
//var_dump($argv);
global $c_k,$c_v;
$a=new Code();//执行数据写入
$b=$a->in_codeinfo();
$local_path=dirname(__DIR__)."/../../../";
$dbConfigFile = $local_path."/gitcontrol/application/database.php";
$dbConfigText = @file_get_contents($dbConfigFile);
//echo $dbConfigText;
$logfile=$local_path."/gitcontrol/log/app_".date("Ymd").".log";

$mysqlinfo['Hostname']=explode("'),",explode("database.hostname', '",$dbConfigText)[1])[0];
$mysqlinfo['Database']=explode("'),",explode("database.database', '",$dbConfigText)[1])[0];
$mysqlinfo['Username']=explode("'),",explode("database.username', '",$dbConfigText)[1])[0];
$mysqlinfo['Password']=explode("'),",explode("database.password', '",$dbConfigText)[1])[0];
$mysqlinfo['Hostport']=explode("'),",explode("database.hostport', '",$dbConfigText)[1])[0];
//echo "mysqlinfo:::::";print_r($mysqlinfo);echo "\n";

$coon=new mysqli($mysqlinfo['Hostname'],$mysqlinfo['Username'],$mysqlinfo['Password'],$mysqlinfo['Database'],$mysqlinfo['Hostport']);
if($coon->connect_error){
    die("mysql链接失败".$coon->connect_error);
} else {
    try{
//        var_dump($b);exit;
        foreach ($b as $prek=>$pre_v){
            $sql_str="insert into fa_codelog (`project`,`branch`,`user`,`code_time`,`changes`,`add_lines`,`del_lines`) values (".$pre_v.");";
            echo $sql_str."----\n";
            $in_res=$coon->query($sql_str);
            if(!$in_res){
//                echo "{'code':1,'message':'codeinfo写失败','data':$sql_str}";
                error_log("【".date('Y-m-d H:i:s')."】{'code':1,'message':'codeinfo写失败','data':$sql_str}\n",3,$logfile);
            }
        }
        $up_str="update fa_config set value='".date('Y-m-d')."' where `group`='sys_info' and `name`='codetime';";
        $up_res=$coon->query($up_str);
        if(!$up_res){
//            echo "{'code':1,'message':'更新【获取代码覆盖率统计】时间失败','data':''}";
            error_log("【".date('Y-m-d H:i:s')."】{'code':1,'message':'更新【获取代码覆盖率统计】时间失败','data':$up_str}\n",3,$logfile);
        }
    }catch (Exception $e) {
        $this->error($e->getMessage());
    }
}
