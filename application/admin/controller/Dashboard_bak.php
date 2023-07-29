<?php

namespace app\admin\controller;

use app\admin\model\Admin;
use app\admin\model\User;
use app\common\controller\Backend;
use app\common\model\Attachment;
use fast\Date;
use think\Db;

/**
 * 控制台
 *
 * @icon   fa fa-dashboard
 * @remark 用于展示当前系统中的统计数据、统计报表及重要实时数据
 */
class Dashboard extends Backend
{

    /**
     * 查看
     */
    public function index()
    {
        try {
            \think\Db::execute("SET @@sql_mode='';");
            //判断是否存在log目录和文件
            $shell_c="sudo ".$_SERVER['DOCUMENT_ROOT']."gitcontrol/application/admin/lang/shell/log.sh";
            if(is_dir("/var/log/gitcontrol/")){
                $file_names=scandir("/var/log/gitcontrol");
                foreach ($file_names as $f_k=>$f_v){
                    if($f_v!="."&&$f_v!=".."){
                        if(explode(".",$f_v)[1]!="log"){
                            system($shell_c,$status);
                            if(!$status){
                                $this->error(__('log.sh脚本执行失败', ''));
                            }
                        }
                    }
                }
            } else {
                system($shell_c,$status);
                if(!$status){
                    $this->error(__('log.sh脚本执行失败', ''));
                }
            }
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
//        $column = [];
//        $starttime = Date::unixtime('day', -6);
//        $endtime = Date::unixtime('day', 0, 'end');
//        $joinlist = Db("user")->where('jointime', 'between time', [$starttime, $endtime])
//            ->field('jointime, status, COUNT(*) AS nums, DATE_FORMAT(FROM_UNIXTIME(jointime), "%Y-%m-%d") AS join_date')
//            ->group('join_date')
//            ->select();
//        for ($time = $starttime; $time <= $endtime;) {
//            $column[] = date("Y-m-d", $time);
//            $time += 86400;
//        }
//        $userlist = array_fill_keys($column, 0);
//        foreach ($joinlist as $k => $v) {
//            $userlist[$v['join_date']] = $v['nums'];
//        }
//
//        $dbTableList = Db::query("SHOW TABLE STATUS");
//        $addonList = get_addon_list();
//        $totalworkingaddon = 0;
//        $totaladdon = count($addonList);
//        foreach ($addonList as $index => $item) {
//            if ($item['state']) {
//                $totalworkingaddon += 1;
//            }
//        }


//        $this->view->assign([
//            'totaluser'         => User::count(),
//            'totaladdon'        => $totaladdon,
//            'totaladmin'        => Admin::count(),
//            'totalcategory'     => \app\common\model\Category::count(),
//            'todayusersignup'   => User::whereTime('jointime', 'today')->count(),
//            'todayuserlogin'    => User::whereTime('logintime', 'today')->count(),
//            'sevendau'          => User::whereTime('jointime|logintime|prevtime', '-7 days')->count(),
//            'thirtydau'         => User::whereTime('jointime|logintime|prevtime', '-30 days')->count(),
//            'threednu'          => User::whereTime('jointime', '-3 days')->count(),
//            'sevendnu'          => User::whereTime('jointime', '-7 days')->count(),
//            'dbtablenums'       => count($dbTableList),
//            'dbsize'            => array_sum(array_map(function ($item) {
//                return $item['Data_length'] + $item['Index_length'];
//            }, $dbTableList)),
//            'totalworkingaddon' => $totalworkingaddon,
//            'attachmentnums'    => Attachment::count(),
//            'attachmentsize'    => Attachment::sum('filesize'),
//            'picturenums'       => Attachment::where('mimetype', 'like', 'image/%')->count(),
//            'picturesize'       => Attachment::where('mimetype', 'like', 'image/%')->sum('filesize'),
//        ]);


        $all_sql="select count(DISTINCT user) as all_user,count(DISTINCT projectname) as all_projects, count(DISTINCT IF(op_type='push',ref,null)) as all_branchs, count(IF(op_type='push',id,null)) as all_commits, sum(IF(op_type='push',commits_count,null)) as all_files, count(IF(op_type='merge_request',id,null)) as all_merges, count(IF(op_type='note',id,null)) as all_notes from fa_gitlog;";
        $loginfo["allinfo"]=db::query($all_sql);

        $today_sql="select count(DISTINCT user) as today_user,count(DISTINCT projectname) as today_projects, count(DISTINCT IF(op_type='push',ref,null)) as today_branchs, count(IF(op_type='push',id,null)) as today_commits, sum(IF(op_type='push',commits_count,null)) as today_files, count(IF(op_type='merge_request',id,null)) as today_merges, count(IF(op_type='note',id,null)) as today_notes from fa_gitlog where TO_DAYS(commit_time)=TO_DAYS(NOW());";
        $loginfo["todayinfo"]=db::query($today_sql);

        $per_commits="select DATE_FORMAT(commit_time,'%Y-%m-%d') as days, count(id) as nums from fa_gitlog where op_type='push' group by DATE_FORMAT(commit_time,'%Y-%m-%d');";
        $commits_perday=db::query($per_commits);


        $this->view->assign([
            'totaluser'         => $loginfo["allinfo"]["all_user"],
            'totalprojects'     => $loginfo["allinfo"]["all_projects"],
            'totalbranchs'      => $loginfo["allinfo"]["all_branchs"],
            'totalcommits'      => $loginfo["allinfo"]["all_commits"],
            'totalfiles'        => $loginfo["allinfo"]["all_files"],
            'totalmerges'       => $loginfo["allinfo"]["all_merges"],
            'totalnotes'        => $loginfo["allinfo"]["all_notes"],

            'todayuser'         => $loginfo["allinfo"]["today_user"],
            'todayprojects'     => $loginfo["allinfo"]["today_projects"],
            'todaybranchs'      => $loginfo["allinfo"]["today_branchs"],
            'todaycommits'      => $loginfo["allinfo"]["today_commits"],
            'todayfiles'        => $loginfo["allinfo"]["today_files"],
            'todaymerges'       => $loginfo["allinfo"]["today_merges"],
            'todaynotes'        => $loginfo["allinfo"]["today_notes"]
        ]);

        $this->assignconfig('column', array_keys($commits_perday));
        $this->assignconfig('userdata', array_values($commits_perday));

        return $this->view->fetch();
    }

}
