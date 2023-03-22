<?php
set_time_limit(0);
define('TROOT', '/oradata/web/nps');
require_once(TROOT.'/lib/config.php');
require_once(TROOT.'/lib/config.php');
require_once(TROOT.'/mssql_connection.php');
$created =date('Ymd');
$filename =basename(__FILE__);

define('CREATED_TIME', date('YmdHis'));

$limit = 2000;
try
{
	$query = " select c.*,v.usr_meta_value subprognm from view_content c ,(
select content_id , usr_meta_value from bc_usr_meta_value where usr_meta_field_id=4000293 and usr_meta_value is not null 
) v,
( select * from nps_work_list where work_type='review' )
tm
where c.content_id=v.content_id   and c.progcd is null and tm.content_id=c.content_id ";

	$order = '  order by c.content_id asc  ';
	$total = $db->queryOne("select count(*) from ( $query ) cnt ");
	$j =0 ;
	$total_list = array();

	echo $log_msg = '['.date("Y-m-d H:i:s").'] '.$total.' data DB Loading...'."\n";//확인
	@file_put_contents(TROOT.'/log/migration/'.$filename.$created.'.log', $log_msg."\n", FILE_APPEND);

	for($start = 0 ; $start< $total ; $start+=$limit)
	{
		$db->setLimit($limit, 0);
		$lists = $db->queryAll($query.$order);

		foreach( $lists as $key => $row )
		{
			//echo $total."/".$j++."\r";
			$content_id = $row['content_id'];
			$subprognm = trim($row['subprognm']);
			$sub_array = explode(' ', $subprognm );
			array_shift($sub_array);
			$subsubprognm = join(' ', $sub_array);
			
			echo $subsubprognm.'<br />';
			
			$medcd;
			$formbaseymd;
			$progcd;
			$forquery = " 
			select
				tm2.*,
				tb1.korname
			from
				tbbf002 tf2,
				tbbma02 tm2,
				tbpae01 tb1
			where
				tm2.pdempno=tb1.empno
			and tm2.medcd=tf2.medcd
			and tf2.progcd=tm2.progcd
			and tf2.formbaseymd=tm2.formbaseymd
			and tf2.brodgu='001'
			and tf2.medcd='$medcd'
			and tf2.formbaseymd='$formbaseymd'
			and tf2.progcd='$progcd' ";
			$res = $db_ms->queryAll($forquery);
			foreach($res as $re)
			{
				
			}
			
		}
	}

	echo $log_msg = '['.date("Y-m-d H:i:s").'] '.$total.' data DB Loaded...'."\n";//확인
	@file_put_contents(TROOT.'/log/migration/'.$filename.$created.'.log', $log_msg."\n", FILE_APPEND);

}
catch(Exception $e)
{
	echo $e->getMessage().' '.$db->last_query;
	@file_put_contents(TROOT.'/log/migration/'.basename(__FILE__).CREATED_TIME.'_error_.log', date("Y-m-d H:i:s").' '.$e->getMessage().' '.$db->last_query."\n", FILE_APPEND);
}

?>