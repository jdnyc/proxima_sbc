<?PHP

$_SERVER['DOCUMENT_ROOT'] = empty($_SERVER['DOCUMENT_ROOT']) ? '/oradata/web/nps' : $_SERVER['DOCUMENT_ROOT'];
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/workflow/lib/functions.php');

try
{
	@file_put_contents( $_SERVER['DOCUMENT_ROOT'].'/log/'.basename(__FILE__).date('Ym').'.log', date("Y-m-d H:i:s\t").'작업시작'."\n", FILE_APPEND);
	//자동 재시작 프로세스 함수 2012-12-26 이성용
	$types = $db->queryAll("select * from bc_task_type order by show_order ");
	foreach($types as $type)
	{
		taskAutoRetry( $type['type'] );
	}
}
catch (Exception $e)
{
	echo $e->getMessage();
	@file_put_contents( $_SERVER['DOCUMENT_ROOT'].'/log/'.basename(__FILE__).date('Ym').'_err.log', date("Y-m-d H:i:s\t").$e->getMessage()."\n", FILE_APPEND);
}
