<?php


function insertContent($content_info)
{
	global $db;
	global $flag;
	$content_id = getSequence('SEQ_CONTENT_ID');

	$map_meta_table_id = array(
		4000282 => 4000282,
		4000283 => 4000282,
		4000284 => 4000284
	);

	$category_id = $content_info['category_id'];
	$category_full_path = $content_info['category_full_path'];;
	$bs_content_id = $content_info['content_type_id'];
	$ud_content_id = $map_meta_table_id[$content_info['meta_table_id']];
	$title = $db->escape($content_info['title']);
	$is_deleted = 'N';
	$status = '2';
	$reg_user_id =  $content_info['user_id'];
	$created_date =  $content_info['created_time'];
	$das_content_id =  $content_info['das_content_id'];

	$expired_date = '9999-12-31';

	$query = "insert into bc_content 
				(content_id, category_id, category_full_path, bs_content_id, ud_content_id, title, reg_user_id, created_date, EXPIRED_DATE, status, das_content_id) 
			values
				($content_id, $category_id, '$category_full_path', $bs_content_id, $ud_content_id, '$title', '$reg_user_id', '$created_date','$expired_date', '$status' ,'$das_content_id' )";

	$r = ExecQuery($flag , $query);

	return $content_id;
}

function insertCodeInfo($content_id )
{
	global $db;
	global $flag;

	$query = "insert into CONTENT_CODE_INFO (CONTENT_ID) values('$content_id') ";
	
	$r = ExecQuery($flag , $query);
	return true;	
}

function insertSystemMeta($content_type_id ,$content_id, $systemMetaList)
{
	global $db;
	global $flag;

	foreach($systemMetaList as $list)
	{		
		$sys_meta_field_id = $list['content_field_id'];
		$sys_meta_value = $db->escape($list['value']);
		$insert_meta_value = "insert into bc_sys_meta_value (content_id, sys_meta_field_id, sys_meta_value) values ($content_id, $sys_meta_field_id, '$sys_meta_value')";
		$r = ExecQuery($flag , $insert_meta_value);		
	}
}


function insertUDMeta($content_id, $UDMetaOldData , $field_mapping  )
{
	global $db;
	global $flag;	

	$map_meta_table_id = array(
		4000282 => 4000282,
		4000283 => 4000282,
		4000284 => 4000284
	);

	foreach($UDMetaOldData as $olddata)
	{
		$usr_meta_field_id = $field_mapping[$olddata['meta_table_id']][$olddata['meta_field_id']] ;

		if(!empty($usr_meta_field_id))//매핑되는 필드 아이디가 없다면 패스
		{			
			$ud_content_id = $map_meta_table_id[$olddata['meta_table_id']];

			$value = trim($olddata['value']);

			//밸류에서 변환필요	날짜형					
			$value = TypeCheck($olddata['type'] , $value );//값 변환이나 타입체크					
			

			$insert_meta_value = "insert into bc_usr_meta_value
								(content_id, ud_content_id, usr_meta_field_id, usr_meta_value )
							values 
								($content_id, $ud_content_id, $usr_meta_field_id, '$value')";
			$r = ExecQuery($flag , $insert_meta_value);
				
		}
	}

	
}

function insertMedia($content_id , $media_list )
{
	global $db;
	global $flag;

	foreach( $media_list as $list)
	{

		$media_type = $list['type'];
		$path = $db->escape($list['path']);
		$created_date = $list['created_time'];
		$reg_type = $list['register'];
		$filesize = $list['filesize'];
		$status = $list['status'];
		$delete_date = $list['delete_date'];
		$flag = $list['flag'];
		$delete_status = $list['delete_status'];

		$query = "insert into bc_media (content_id, media_type, storage_id, path, filesize, reg_type, created_date ,STATUS, DELETE_DATE,FLAG,DELETE_STATUS) 
							  values ({$content_id}, '{$media_type}', 0, '{$path}','$filesize', '{$reg_type}', '{$created_date}' ,'$status','$delete_date','$flag','$delete_status')";
		$r = ExecQuery($flag , $query);
	}
}

function insertScene($content_id,$media_id ,$scene_lists)
{
	global $db;
	global $flag;

	if( empty($scene_lists)  ) return;

	foreach($scene_lists as $scene_list)
	{
		$scene_id = getNextSequence();
		
		$show_order = $scene_list['sortnumber'];
		$path = $scene_list['thumbnail'];
		$start_frame = $scene_list['framestart'];
		$end_frame = $scene_list['frameend'];
		$start_tc = $scene_list['positiontime'];
		$title = $scene_list['title'];
		$comments = $db->escape($scene_list['comments']);

		 $query = "insert into BC_SCENE (
		SCENE_ID,
		MEDIA_ID,
		SHOW_ORDER,
		PATH,
		START_FRAME,
		END_FRAME,
		START_TC,
		TITLE,
		COMMENTS ) values ('$scene_id','$media_id','$show_order' ,'$path','$start_frame','$end_frame','$start_tc','$title','$comments')";
		$r = ExecQuery($flag , $query);
	}
}

function TypeCheck($type , $value)
{
	global $db;		
	if( $type == 'datefield' )//날짜형
	{
		if( !empty($value) ) // 0 이나 빈값이 아닐때
		{
			if( strtotime($value) )//날짜형변환이 가능할때
			{
				$value = date("YmdHis" , strtotime($value) );
			}
			else
			{
				$value = '';
			}
		}
		else
		{
			$value = '';
		}
	}

	$value = $db->escape($value);
	
	return $value;
}

function ExecQuery($flag , $query)
{
	global $db;
		
	$r = true;
	file_put_contents(TEMP_ROOT.'/log/'.basename(__FILE__).'_exec_'.CREATED_TIME.'.log', $query."\n", FILE_APPEND);	

	//if($flag)
	//{
		$r = $db->exec($query);	
	//}

	return $r;
}



function timeValue( $value ) //  타임코드 변환
{
	if(strlen($value) == 5)
	{
		$sub_value = $value;
		$h = substr($sub_value,0,1);
		$i = substr($sub_value,1,2);
		$s = substr($sub_value,3,2);
		$h = str_pad($h,2,'0', STR_PAD_LEFT);
		$m = str_pad($m,2,'0', STR_PAD_LEFT);
		$s = str_pad($s,2,'0', STR_PAD_LEFT);

		$val = $h.':'.$i.':'.$s;
	}
	else if(strlen($value) == 6)
	{
		$sub_value = $value;
		$h = substr($sub_value,0,2);
		$i = substr($sub_value,2,2);
		$s = substr($sub_value,4,2);
		$h = str_pad($h,2,'0', STR_PAD_LEFT);
		$m = str_pad($m,2,'0', STR_PAD_LEFT);
		$s = str_pad($s,2,'0', STR_PAD_LEFT);

		$val = $h.':'.$i.':'.$s;
	}
	else if(strlen($value)==8)
	{
		$sub_value = $value;
		$h = substr($sub_value,0,2);
		$i = substr($sub_value,2,2);
		$s = substr($sub_value,4,2);
		$fr = substr($sub_value,6,2);
		$h = str_pad($h,2,'0', STR_PAD_LEFT);
		$m = str_pad($m,2,'0', STR_PAD_LEFT);
		$s = str_pad($s,2,'0', STR_PAD_LEFT);
		$fr = str_pad($fr,2,'0', STR_PAD_LEFT);

		$val = $h.':'.$i.':'.$s;
	}
	else
	{
		return $value;//값 그대로 돌려줌
	}

	return $val;
}

function convertTimesec( $value )  /// 00000000 형식을 sec 로 변환
{
	if( strlen($value) == 8 )
	{
		$sub_value = $value;
		$h = substr($sub_value,0,2);
		$i = substr($sub_value,2,2);
		$s = substr($sub_value,4,2);
		$fr = substr($sub_value,6,2);

		$time = ($h*3600) + ($i*60) + $s;
	}
	else if( strlen($value) == 6 )
	{
		$sub_value = $value;
		$h = substr($sub_value,0,2);
		$i = substr($sub_value,2,2);
		$s = substr($sub_value,4,2);

		$time = ($h*3600) + ($i*60) + $s;
	}
	else
	{
		return false;
	}
	return $time;
}

function calculationLength($start, $end) // 길이 계산, 리턴값 00:00:00
{

	$length_time = $end - $start;

	$h = (int)($length_time / 3600);
	$i = (int)(($length_time % 3600) / 60) ;
	$s = (int)(($length_time % 3600) % 60) ;

	$h = str_pad($h,2,'0', STR_PAD_LEFT);
	$i = str_pad($i,2,'0', STR_PAD_LEFT);
	$s = str_pad($s,2,'0', STR_PAD_LEFT);

	return $h.':'.$i.':'.$s;

}

function convertstorterm( $reqymd ,$storterm  ) //보관기간 계산 <보관기간-신청일자>
{
	if($storterm == '99999999')
	{
		$value = '영구';
		return $value;
	}

	$time = $storterm - $reqymd;

	$year = substr($time, 0, 2);

	if( $year == '1' )
	{
		$value = '1년';
	}
	else if( $year == '3' )
	{
		$value = '3년';
	}
	else if( $year == '5' )
	{
		$value = '5년';
	}
	else if( $year == '10' )
	{
		$value = '10년';
	}
	else
	{
		$value = '기타';
	}

	return $value;
}

function string_cut($string,$cut_size)
{
	$StringArray=explode(" ",$string);

	for($i=0;$i<$cut_size;$i++)
	{
		$string_cut.=" ".$StringArray[$i];
	}
	return $string_cut;
}

?>