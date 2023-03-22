<?php
session_start();

require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');

$start = $_POST['start'];
$limit = $_POST['limit'];

$start_date = $_POST['start_date'];
$end_date = $_POST['end_date'];
$content_type = $_POST['content_type'];
$status = json_decode($_POST['status']);

$search_title = $db->escape($_POST['title']);

/* 2012-02-29 by 이성용	
콘텐츠의 상태정보로 조건을 주는 쿼리 후 페이징처리가 필요함.
따라서 리미트 후 필터링하는것이 아니라 쿼리문 자체에서 조건이 들어가야함.
노체크박스시 전체 콘텐츠 정보를 보여주고 , 체크박스 조건 추가시 마다 쿼리 조건 추가.
*/


$where_array = array(); //콘텐츠 조건 배열
$media_where_array = array();//미디어 조건 배열

$where = ''; //콘텐츠 조건
$media_where = ''; //미디어 조건


$status_info = array(
		'is_content_delete_info' => 'N',
		'is_archive_info' => 'N',
		'is_online_storage_info' => 'N',
		'is_proxy_info' => 'N',
		'is_thumb_info' => 'N'
	);

if(array_count_values($status)>0)
{
	foreach($status  as $s)
	{
		switch($s)
		{
			case 'is_content_delete_info':				
				$status_info['is_content_delete_info'] ='Y';
			break;

			case 'is_archive_info':
				$status_info['is_archive_info'] = 'Y';
			break;

			case 'is_online_storage_info':
				$status_info['is_online_storage_info'] ='Y';
			break;

			case 'is_proxy_info':
				$status_info['is_proxy_info'] ='Y';
			break;

			case 'is_thumbnail_info':
				$status_info['is_thumb_info'] ='Y';
			break;
		}		
	}
}


if($status_info['is_archive_info'] == 'Y' ) //아카이브가 안된 콘텐츠
{
	array_push($media_where_array, " archive_m.archive_id is null " );
}
else
{
	array_push($media_where_array, " archive_m.archive_id is not null " );
}

if($status_info['is_online_storage_info'] == 'Y' ) //온라인 스토리지가 없는 콘텐츠
{
	array_push($media_where_array, " ( ori_m.status = '1'  or ori_m.media_id is null ) " );
}
else
{
	array_push($media_where_array, "  ( ori_m.media_id is not null and ( ori_m.status  is null or ori_m.status ='0' ) )  " );
}

if($status_info['is_proxy_info'] == 'Y' ) //프록시가 없는 콘텐츠
{
	array_push($media_where_array, " ( proxy_m.status = '1' or proxy_m.media_id is null or  UPPER(proxy_m.path) = 'TEMP'  ) " );
}
else
{
	array_push($media_where_array, " ( proxy_m.media_id is not null and ( proxy_m.status  is null or proxy_m.status ='0' ) and UPPER(proxy_m.path) != 'TEMP' ) " );
}

if($status_info['is_thumb_info'] == 'Y' ) //섬네일이 없는 콘텐츠
{
	array_push($media_where_array, " ( thumb_m.status = '1' or thumb_m.media_id is null or UPPER(thumb_m.path) = 'INCOMING.JPG' ) " );
}
else
{
	array_push($media_where_array, " ( thumb_m.media_id is not null and ( thumb_m.status  is null or thumb_m.status ='0' ) and UPPER(thumb_m.path) != 'INCOMING.JPG' )  " );
}

if($content_type != '-1' && $content_type !='전체')
{
	$content_type_state = " C.UD_CONTENT_ID = '$content_type' ";

	array_push($where_array , $content_type_state);
}

if( !is_null($start_date) && !is_null($end_date) )
{
	$date_state  = " C.CREATED_DATE between ".$start_date ." and ".$end_date ."";
	array_push($where_array , $date_state);
}

if( !is_null($_POST['title']) )
{
	$title_where  = " C.TITLE like '%$search_title%'";
	array_push($where_array , $title_where);
}

if( $status_info['is_content_delete_info'] =='Y' )
{
	$content_status_state = " C.IS_DELETED ='Y'";
}
else 
{
	$content_status_state = " C.IS_DELETED ='N'";
}

array_push($where_array , $content_status_state);

if( !empty($where_array) )
{
	$where = ' AND '.join(' AND ', $where_array);
}

if( !empty($media_where_array) )
{
	$media_where = ' AND '.join(' AND ', $media_where_array);
}




$query = "
		select
			c.* ,
			ori_m.status ostatus,
			ori_m.media_id ori_media_id,
			ori_m.path ori_path,

			archive_m.archive_id astatus,

			proxy_m.status pstatus,
			proxy_m.media_id proxy_media_id,
			proxy_m.path proxy_path,

			thumb_m.status tstatus,
			thumb_m.media_id thumb_media_id,
			thumb_m.path thumb_path

		from
			(SELECT
				C.CONTENT_ID,
				C.TITLE,
				C.IS_DELETED content_deleted,
				C.CREATED_DATE,
				UC.UD_CONTENT_TITLE
			FROM 
				BC_CONTENT C,
				BC_UD_CONTENT UC
			WHERE 
				C.UD_CONTENT_ID=UC.UD_CONTENT_ID 
			".$where.") c,
			(SELECT
				m.content_id,
				m.MEDIA_ID ,
				m.MEDIA_TYPE ,
				m.PATH,
				m.STATUS 
			FROM
				BC_MEDIA m
			where
				m.media_type='original') ori_m,
			(SELECT
				m.content_id,
				m.MEDIA_ID ,
				m.MEDIA_TYPE ,
				m.PATH,
				m.STATUS ,
				a.archive_id
			FROM
				BC_MEDIA m,
				alto_archive a
			where m.media_id=a.media_id) archive_m,
			(SELECT
				m.content_id,
				m.MEDIA_ID ,
				m.MEDIA_TYPE ,
				m.PATH,
				m.STATUS
			FROM 
				BC_MEDIA m 
			where m.media_type='proxy') proxy_m,
			(SELECT
				m.content_id,
				m.MEDIA_ID ,
				m.MEDIA_TYPE ,
				m.PATH,
				m.STATUS
			FROM
				BC_MEDIA m
			where m.media_type='thumb') thumb_m
		where
			c.content_id=ori_m.content_id(+)  
		and c.content_id=archive_m.content_id(+)  
		and c.content_id=proxy_m.content_id(+)   
		and c.content_id=thumb_m.content_id(+)   
		".$media_where;

//$query = "SELECT C.CONTENT_ID AS ID, C.TITLE, C.IS_DELETED, C.CREATED_DATE, UC.UD_CONTENT_TITLE
//							FROM BC_CONTENT C, BC_UD_CONTENT UC
//							WHERE C.UD_CONTENT_ID=UC.UD_CONTENT_ID ".$where;

$order = " ORDER BY c.CONTENT_ID DESC";


$total = $db->queryOne("select count(*) from (".$query.")");


$db->setLimit($limit, $start);

$contents = $db->queryAll($query.$order);

$result = array();

foreach($contents as $content)
{	
	$media_info = array(
		'archive' => 'N',
		'original' => 'N',
		'proxy' => 'N',
		'thumb' => 'N'
	);

	if( !empty($content['astatus']) )
	{
		$media_info['archive'] = 'Y';
	}
	
	if( !empty($content['ori_media_id']) && ( is_null($content['ostatus']) || $content['ostatus'] == '0' ) )
	{ 
		$media_info['original'] = 'Y';
	}
	
	if(  !empty($content['proxy_media_id']) && ( is_null($content['pstatus']) || $content['pstatus'] == '0' ) && ( strtoupper($content['proxy_path']) != 'TEMP' )  )
	{
		$media_info['proxy'] = 'Y';
	}
	
	if(  !empty($content['thumb_media_id']) && ( is_null($content['tstatus']) || $content['tstatus'] == '0' ) && ( strtoupper($content['thumb_path']) != 'INCOMING.JPG' ) )
	{
		$media_info['thumb'] = 'Y';
	}

	array_push($result , array(
		'content_id' => $content['content_id'],
		'title' => $content['title'],
		'ud_content_title' => $content['ud_content_title'],
		'content_deleted' => $content['content_deleted'],
		'created_date' => $content['created_date'],
		'media_info' => $media_info
	));
}


//$result = array();
//$count =0;
//foreach ($contents as $content) 
//{
//	$medias = $db->queryAll("SELECT MEDIA_ID AS ID, MEDIA_TYPE AS TYPE, PATH, STATUS 
//								FROM BC_MEDIA 
//								WHERE CONTENT_ID=".$content['id']);
//
//	$media_info = array(
//		'archive' => 'N',
//		'original' => 'N',
//		'proxy' => 'N',
//		'thumb' => 'N'
//	);
//
//	foreach ($medias as $media) 
//	{
//		if ($media['type'] == 'original') 
//		{
//			if ($media['status'] != 1) 
//			{
//				$media_info['original'] = 'Y';
//			}
//
//			$archive = $db->queryOne("select uuid from alto_archive where media_id=".$media['id']);
//			//echo $media['id']."\t'".$archive."'".chr(10);
//			if (!empty($archive)) 
//			{
//				$media_info['archive'] = 'Y';
//			}
//		}
//		else if ( $media['type'] == 'proxy' && ( ( strtolower($media['path']) != 'temp' ) && ( $media['status'] != '1') ) ) 
//		{
//			$media_info['proxy'] = 'Y';
//		}
//		else if ($media['type'] == 'thumb' && ( ( strtolower($media['path']) != 'incoming.jpg' ) && ( $media['status'] != '1') ) ) 
//		{
//			$media_info['thumb'] = 'Y';
//		}
//	}
//	
//	if( ( $status_info['is_archive_info'] == $media_info['archive'] )
//		&& ($status_info['is_online_storage_info'] == $media_info['original'])
//		&& ($status_info['is_proxy_info'] == $media_info['proxy'])
//		&& ($status_info['is_thumb_info'] == $media_info['thumb'])
//		)
//	{
//			$result[] = array(
//			'content_id' => $content['id'],
//			'title' => $content['title'],
//			'ud_content_title' => $content['ud_content_title'],
//			'content_deleted' => $content['is_deleted'],
//			'created_date' => $content['created_date'],
//			'media_info' => $media_info			
//		);
//			$count++;
//	}	
//	
//}
echo json_encode(array(
	"success" => true,
	"total" => $total,
	"data" => $result
));