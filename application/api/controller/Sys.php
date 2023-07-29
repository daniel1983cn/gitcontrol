<?php

namespace app\api\controller;

use app\common\controller\Api;
use Exception;
use mysqli;
use think\db;


/**
 * 示例接口
 */
class Sys
{

    //如果$noNeedLogin为空表示所有接口都需要登录才能请求
    //如果$noNeedRight为空表示所有接口都需要验证权限才能请求
    //如果接口已经设置无需登录,那也就无需鉴权了
    //
    // 无需登录的接口,*表示全部
    protected $noNeedLogin = ['*'];
    // 无需鉴权的接口,*表示全部
    protected $noNeedRight = ['*'];

    //获取上次代码覆盖率的更新时间
    public function get_lastcodeupdate(){
        $dbConfigFile = '/www/admin/www.gitcontrol.com_80/wwwroot/gitcontrol/application/database.php';
        $dbConfigText = @file_get_contents($dbConfigFile);
//echo $dbConfigText;
        $mysqlinfo['Hostname']=explode("'),",explode("database.hostname', '",$dbConfigText)[1])[0];
        $mysqlinfo['Database']=explode("'),",explode("database.database', '",$dbConfigText)[1])[0];
        $mysqlinfo['Username']=explode("'),",explode("database.username', '",$dbConfigText)[1])[0];
        $mysqlinfo['Password']=explode("'),",explode("database.password', '",$dbConfigText)[1])[0];
        $mysqlinfo['Hostport']=explode("'),",explode("database.hostport', '",$dbConfigText)[1])[0];

        $coon=new mysqli($mysqlinfo['Hostname'],$mysqlinfo['Username'],$mysqlinfo['Password'],$mysqlinfo['Database'],$mysqlinfo['Hostport']);

        $last_sql="SELECT `value` FROM `fa_config` WHERE `group`='sys_info' and `name`='codetime';";
        $last_date=$coon->query($last_sql);

//        $local_path=dirname(__DIR__)."/../../../";
//        $logfile=$local_path."/gitcontrol/log/app_".date("Ymd").".log";

//        error_log("【".date('Y-m-d H:i:s')."】获取上次code同步时间的执行sql：".$last_sql.",最终获得的结果：".mysqli_fetch_row($last_date)[0]."\n",3,$logfile);
        echo mysqli_fetch_row($last_date)[0];
//        return mysqli_fetch_row($last_date)[0];
    }
}
switch ($argv[1]){
    case "getlastcodeupdate":
        echo (new Sys)->get_lastcodeupdate();
        break;
}


