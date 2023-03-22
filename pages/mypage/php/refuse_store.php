<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib.php');
$user_id = $_SESSION['user']['user_id'];
$user_name = $_SESSION['user']['KOR_NM'];
$full_id = $user_name.'('.$user_id.')';

$limit = $_POST['limit'];
$start = $_POST['start'];
if(empty($limit)){
    $limit = 20;
}

try
{
	if( $_SESSION['user']['is_admin'] == 'Y' )
	{
		$refuse_list_q ="select c.content_id, c.status, c.title, r.user_id, r.action, r.target_user_id, r.description, r.created_time from refuse_list r,content c where c.content_id=r.content_id and c.is_deleted=0 and c.status < 0";
	}
	else
	{
		$refuse_list_q ="select c.content_id, c.status, c.title, r.user_id, r.action, r.target_user_id, r.description, r.created_time from refuse_list r,content c where c.content_id=r.content_id and r.target_user_id='$user_id' and c.is_deleted=0 and c.status < 0";
	}

	$db->setLimit($limit, $start);
	$refuse_list= $db->queryAll($refuse_list_q.' order by r.created_time');
	if(PEAR::isError($refuse_list)) throw new Exception($refuse_list->getMessage());


	for($i=0;$i<count($refuse_list);$i++)
	{
		$val = $refuse_list[$i]['user_id'];
		$name = $db->queryOne("select name from member where user_id='$val'");
		if( !PEAR::isError($name) && !empty($name) )
		{
			$refuse_list[$i]['user_id'] =$name;
		}

		$val2 = $refuse_list[$i]['target_user_id'];
		$name2 = $db->queryOne("select name from member where user_id='$val2'");
		if( !PEAR::isError($name2) && !empty($name2) )
		{
			$refuse_list[$i]['target_user_id'] =$name2;
		}

	}

	$total = $db->queryOne("select count(*) from ( $refuse_list_q ) cnt ");

	if(PEAR::isError($total)) throw new Exception($total->getMessage());
	echo json_encode(array(
		'success'	=> true,
		'data'		=> $refuse_list,
		'total'		=> $total
	));
}
catch (Exception $e)
{
	echo '오류 : '.$e->getMessage();
}



/*////////////////로그에 인코딩 반려는 인코딩 작업자에서 검색해서 가져옴
	$refuse_q ="(
		select mv.content_id
			from
				log l,
				meta_field mf,
				meta_value mv
			where
				mf.meta_field_id = mv.meta_field_id
			and mv.content_id = l.link_table_id
			and
				( mv.value like '%$user_name%' and mf.name like '%인코딩 작업자%' and l.action='refuse_encoding')
	)";
	$contents_q = "select
			c.*, l.description
		from
			content c,
			log l
		where
			c.status != '-3'
		and
			c.IS_DELETED = 0
		and
			l.link_table_id=c.content_id
		and
			l.action='refuse_encoding'
		and
		c.content_id in {$refuse_q}
		order by c.created_time desc";

	$db->setLimit($limit,$start);
//	print_r($contents_q);
	$contents = $db->queryAll($contents_q);
	if(PEAR::isError($contents)) throw new Exception($contents->getMessage());
//	print_r($contents);
	$total = $db->queryOne("
		select
			count(c.content_id)
		from
			content c
		where
			c.status != '-3'
		and
			c.IS_DELETED = 0
		and
		c.content_id in {$refuse_q}");
	if(PEAR::isError($total)) throw new Exception($total->getMessage());

	if( !empty($contents) )
	{
	//	array_push($list, $contents);
		foreach($contents as $content)
		{
			array_push($list, $content);
		}
	}
	////////////////로그에 인코딩 반려는 인코딩 작업자에서 검색해서 가져옴
	$refuse_meta = $db->queryAll("select link_table_id from log where action='refuse_meta'");
	if(PEAR::isError($refuse_meta)) throw new Exception($refuse_meta->getMessage());
	foreach( $refuse_meta as $meta)
	{
		$refuse_meta_id = $meta['link_table_id'];
		$refuse_meta_first_q = "select * from log where action='edit' and link_table_id='$refuse_meta_id'  order by id asc";
		$refuse_meta_first =  $db->queryRow($refuse_meta_first_q);//최초 수정자 한줄만
		if(PEAR::isError($refuse_meta_first)) throw new Exception($refuse_meta_first->getMessage());

		if($refuse_meta_first['user_id'] == $user_id)
		{
			$refuse_d = $db->queryRow("select
				c.*,l.description
			from
				content c,
				log l
			where
				c.status != '-3'
			and
				c.IS_DELETED = 0
			and
				l.link_table_id=c.content_id
			and
				l.action='refuse_meta'
			and
				c.content_id='$refuse_meta_id'
			");
			//print_r($refuse_d);

			if(!empty($refuse_d))
			{
				array_push($list, $refuse_d);
				$total += 1;
			}
			//array_push($list, $refuse_meta_first);

		}
	}*/
?>