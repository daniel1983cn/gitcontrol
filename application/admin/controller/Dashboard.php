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
            $local_path=dirname(__DIR__)."/../../../";
            $log_path="'".$local_path."gitcontrol/log'";
            $shell_logstr=$local_path."gitcontrol/application/admin/lang/shell/log.sh ". $log_path;
            $shell_c="/bin/sh ".$shell_logstr;
            system($shell_c,$status);
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }

        $dbTableList = Db::query("SHOW TABLE STATUS");
        $addonList = get_addon_list();
        $totalworkingaddon = 0;
        $totaladdon = count($addonList);
        foreach ($addonList as $index => $item) {
            if ($item['state']) {
                $totalworkingaddon += 1;
            }
        }

        $all_sql="select * from ( select count(DISTINCT user) as all_user,count(DISTINCT projectname) as all_projects, count(DISTINCT IF(op_type='push',ref,null)) as all_branchs, count(IF(op_type='push',id,null)) as all_commits, sum(IF(op_type='push',commits_count,null)) as all_files, count(IF(op_type='merge_request',id,null)) as all_merges, count(IF(op_type='note',id,null)) as all_notes from fa_gitlog ) c1, (SELECT IFNULL(sum(`add_lines`),0) as adds,IFNULL(sum(`del_lines`),0) as dels FROM `fa_codelog`) c2;";
        $loginfo["allinfo"]=db::query($all_sql)[0];

        $today_sql="select * from (select count(DISTINCT user) as today_user,count(DISTINCT projectname) as today_projects, count(DISTINCT IF(op_type='push',ref,null)) as today_branchs, count(IF(op_type='push',id,null)) as today_commits, sum(IF(op_type='push',commits_count,null)) as today_files, count(IF(op_type='merge_request',id,null)) as today_merges, count(IF(op_type='note',id,null)) as today_notes from fa_gitlog where TO_DAYS(commit_time)=TO_DAYS(NOW())) c1,(SELECT IFNULL(sum(`add_lines`),0) as adds,IFNULL(sum(`del_lines`),0) as dels FROM `fa_codelog` where TO_DAYS(`code_time`)=TO_DAYS(NOW())) c2;";
        $loginfo["todayinfo"]=db::query($today_sql)[0];


        $per_codes="SELECT DATE_FORMAT(`code_time`,'%Y-%m-%d') as days,sum(`add_lines`) as adds,sum(`del_lines`) as dels FROM `fa_codelog` group by DATE_FORMAT(`code_time`,'%Y-%m-%d') order by DATE_FORMAT(`code_time`,'%Y-%m-%d') ASC; ";
        $codesinfo=db::query($per_codes);
        $codes_perday=[];
        $codes_days=[];
        $codes_adds=[];
        $codes_dels=[];
        if(count($codesinfo)>0){
            foreach ($codesinfo as $k=>$v){
//                $codes_perday[$v['days']]=$v['nums'];
//                $codes_perday[$v['days']]['adds']=$v['adds'];
//                $codes_perday[$v['days']]['dels']=$v['dels'];
                array_push($codes_days,$v['days']);
                array_push($codes_adds,$v['adds']);
                array_push($codes_dels,$v['dels']);
            }
        }


        $this->view->assign([
            'totaluser'         => $loginfo["allinfo"]["all_user"],
            'totaladdon'        => $totaladdon,
            'totaladmin'        => Admin::count(),
            'totalcategory'     => \app\common\model\Category::count(),
            'todayusersignup'   => User::whereTime('jointime', 'today')->count(),
            'todayuserlogin'    => User::whereTime('logintime', 'today')->count(),
            'sevendau'          => User::whereTime('jointime|logintime|prevtime', '-7 days')->count(),
            'thirtydau'         => User::whereTime('jointime|logintime|prevtime', '-30 days')->count(),
            'threednu'          => User::whereTime('jointime', '-3 days')->count(),
            'sevendnu'          => User::whereTime('jointime', '-7 days')->count(),
            'dbtablenums'       => count($dbTableList),
            'dbsize'            => array_sum(array_map(function ($item) {
                return $item['Data_length'] + $item['Index_length'];
            }, $dbTableList)),
            'totalworkingaddon' => $totalworkingaddon,
            'attachmentnums'    => Attachment::count(),
            'attachmentsize'    => Attachment::sum('filesize'),
            'picturenums'       => Attachment::where('mimetype', 'like', 'image/%')->count(),
            'picturesize'       => Attachment::where('mimetype', 'like', 'image/%')->sum('filesize'),

            'totalprojects'     => $loginfo["allinfo"]["all_projects"],
            'totalbranchs'      => $loginfo["allinfo"]["all_branchs"],
            'totalcommits'      => $loginfo["allinfo"]["all_commits"],
            'totalfiles'        => $loginfo["allinfo"]["all_files"],
            'totalmerges'       => $loginfo["allinfo"]["all_merges"],
            'totalnotes'        => $loginfo["allinfo"]["all_notes"],
            'totaladds'         => $loginfo["allinfo"]["adds"],
            'totaldels'         => $loginfo["allinfo"]["dels"],

            'todayuser'         => $loginfo["todayinfo"]["today_user"],
            'todayprojects'     => $loginfo["todayinfo"]["today_projects"],
            'todaybranchs'      => $loginfo["todayinfo"]["today_branchs"],
            'todaycommits'      => $loginfo["todayinfo"]["today_commits"],
            'todayfiles'        => $loginfo["todayinfo"]["today_files"],
            'todaymerges'       => $loginfo["todayinfo"]["today_merges"],
            'todaynotes'        => $loginfo["todayinfo"]["today_notes"],
            'todayadds'        => $loginfo["todayinfo"]["adds"],
            'todaydels'        => $loginfo["todayinfo"]["dels"]

        ]);

//        $this->assignconfig('column', array_keys($codes_perday));
//        $this->assignconfig('userdata', array_values($codes_perday));
        $this->assignconfig('column', $codes_days);
        $this->assignconfig('adds', $codes_adds);
        $this->assignconfig('dels', $codes_dels);

        return $this->view->fetch();
    }

}
