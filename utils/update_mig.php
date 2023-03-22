<?php
require_once('../lib/config.php');
require_once('../lib/functions.php');

$query = "select * from ebsdas.content where is_deleted !='1' and nps_content_id is not null ";

$total = $db->queryOne("select count(*) from ( $query ) cnt ");

$limit = '1000';

for ( $start=0 ; $start <= $total ; $start += $limit )
{
	$db->setLimit($limit, $start);
	$content_list = $db->queryAll($query.' order by content_id desc ');

	foreach ($content_list as $content)
	{
		echo "\r".$total.'/'.$start."\t\t";
		$db->exec("insert into archive_list ( CONTENT_ID,DAS_CONTENT_ID,STATUS,USER_ID,REG_DATE, TASK_ID ) values ( '{$content['nps_content_id']}', '{$content['content_id']}', '{$content['status']}', '{$content['user_id']}', '{$content['created_time']}', '') ");		
	}
}
?>