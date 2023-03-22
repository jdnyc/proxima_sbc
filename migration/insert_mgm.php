
<?php
set_time_limit(0);
define('TEMP_ROOT', '/oradata/web/nps');
require_once(TEMP_ROOT.'/lib/config.php');
require_once(TEMP_ROOT.'/migration/mig_functions.php');//마이그레이션용 함수


$GLOBALS['flag'] = '1';

$content_type_id = '506';
$cur_date = date('YmdHis');
$created_time = date('YmdHis');

define('CREATED_TIME', $created_time);

$log_path = TEMP_ROOT.'/log/'.basename(__FILE__).'_'.$cur_date.'.log';
$log_path_error = TEMP_ROOT.'/log/'.basename(__FILE__).'_error_'.$cur_date.'.log';
try
{



	$query = " select m.member_id,p.path,p.member_group_id, u.* ,mm.member_id ch_m from bc_member m, user_mapping u,path_mapping p,bc_member_group_member mm where m.user_id=u.user_id and p.category_id=u.category_id and mm.member_id(+)=m.member_id and mm.member_id is null " ;

	$lists = $db->queryAll($query);

	foreach($lists as $list)
	{
		$db->exec("insert into bc_member_group_member (member_id, member_group_id) values ('{$list['member_id']}','{$list['member_group_id']}' ) ");
	}

}

catch ( Exception $e )
{
	file_put_contents($log_path_error, date("Y-m-d H:i:s").' '.$e->getMessage().' '.$db->last_query."\n", FILE_APPEND);
	echo $e->getMessage().' '.$db->last_query;	
}

?>