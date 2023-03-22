<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/lib.php');
fn_checkAuthPermission($_SESSION);

try
{
	$k=0;
	$types = $mdb->queryAll("select bs_content_id as type, bs_content_title as name, bs_content_code from bc_bs_content");

	foreach( $types as $type )
	{
        $sys_table = 'bc_sysmeta_'.$type['bs_content_code'];
        $regist = $mdb->queryOne("
            select  count(*)
            from    bc_content a
                    left outer join ".$sys_table." b
                    on a.content_id=b.sys_content_id
            where   a.bs_content_id='{$type['type']}'
            and     a.is_deleted='N'
            and     a.is_group != 'C'
            and     a.status in ('-3', '2', '0')
            and     b.sys_content_id is not null");
		$del_count = $mdb->queryOne("select count(*) from bc_content where bs_content_id='{$type['type']}' and is_deleted='N'");

		$name[] = $type['name'];
		$count[] = $regist;
//		$count[] = $regist-$del_count;
//		if($count[$k] < 0)
//		{
//			$count[$k]=0;
//		}
		$k++;
	}
	$data = array(
		'success' => true,
		'data' => array()
	);
					
	for($i=0; $i<$k; $i++)
	{
		array_push($data['data'], array('name' => $name[$i], 'count' => $count[$i]));
	}

	echo json_encode($data);
}
catch (Exception $e)
{
	echo '오류: '.$e->getMessage();
}

?>