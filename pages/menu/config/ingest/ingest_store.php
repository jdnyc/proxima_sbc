<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');

try
{
	$limit = 20;//$_POST['limit'];
	$start = $_POST['start'];
	$meta_table_id = $_POST['meta_table_id'];

	$order_dir	= $_POST['dir'];
	$order_field = $_POST['sort'];

	if(empty($_POST['meta_table_id']))
	{
		$meta_table = '';
	}
	else if( $meta_table_id == 'all' )
	{
		$meta_table = '';
	}
	else
	{
		$meta_table = " and t.meta_table_id='$meta_table_id' ";
	}


	$status = "(c.status = 0 or c.status = -6 )";


	if ($order_field != 'created_time' && $order_field != 'user_id' && $order_field != 'meta_table_id' )
	{
		$query = "select ".
						"c.category_id, c.id, c.title, c.created_time, c.content_type_id, c.status, c.user_id, ".
						"t.meta_table_id, t.content_type_name as content_type, t.meta_table_name as table_name ".
					"from ".
							"ingest c, ".
							"(select ".
									"ct.name as content_type_name, t.name as meta_table_name, t.meta_table_id, v.ingest_id, v.meta_value ".
							"from meta_table t, content_type ct, meta_field f, ingest_metadata v ".
							"where ".
							"f.name='".$order_field."' ".
							$meta_table.
							"and ct.content_type_id=t.content_type_id ".
							"and t.meta_table_id=f.meta_table_id ".
							"and f.meta_field_id=v.meta_field_id) t ".
					"where ".
					"t.ingest_id=c.id ";
	}
	else
	{
		$query = "select ".
						"c.category_id, c.id, c.title, c.created_time, c.content_type_id, c.status, ".
						"t.meta_table_id, ct.name as content_type, t.name as table_name, c.user_id ".
					"from ".
						"ingest c, content_type ct, meta_table t ".
					"where ".
					"c.meta_table_id=t.meta_table_id ".
					$meta_table.
					"and c.content_type_id=ct.content_type_id ";
	}

	if (!empty($_POST['start_date']))
	{
		$start_date = $_POST['start_date'];
		$end_date = $_POST['end_date'];
		$query .= " and c.created_time between $start_date and $end_date ";
	}
	if (!empty($_POST['search_combo']))
	{
		if(empty($_POST['meta_table_id']))
		{
			$meta_table_s = '';
		}
		else if( $meta_table_id == 'all' )
		{
			$meta_table_s = '';
		}
		else
		{
			$meta_table_s = " and mv.meta_table_id='$meta_table_id' ";
		}

		$search_combo =	$_POST['search_combo'];
		$search_text =	$_POST['search_text'];

		if( $search_combo == 'Tape NO' )//테잎넘버 대문자로
		{
			$search_text = strtoupper($search_text);
		}

		if( $search_combo == 'meta_table_id' )
		{
			$query .= " and t.meta_table_id = '".$search_text."' ";
		}
		else if( $search_combo == 'user_id' )
		{
			if( $search_text == 'NPS' )
			{
				$query .= " and c.nps_content_id is not null ";
			}
			else
			{
				$query .= " and c.user_id like '".$search_text."' ";
			}
		}
		else if( $search_combo == '방송일자' )
		{
			$search_text = strtotime($search_text);
			$search_text =	date('Ymd', $search_text);

			$query .=	" and c.id IN (select distinct mv.ingest_id from meta_field mf, ingest_metadata mv where mf.meta_field_id=mv.meta_field_id and mf.name ='".$search_combo."' ".$meta_table_s." and mv.meta_value like '%".$search_text."%' )";
		}
		else
		{
			$query .=	" and c.id IN (select distinct mv.ingest_id from meta_field mf, ingest_metadata mv where mf.meta_field_id=mv.meta_field_id and mf.name ='".$search_combo."' ".$meta_table_s." and mv.meta_value like '%".$search_text."%' )";
		}

	}
//	echo $query;exit;



	$total = $db->queryOne("select count(*) from (".$query.") cnt");
	//echo $db->last_query;

	if ( ($order_field == 'created_time') || ($order_field == 'user_id') || ($order_field == 'meta_table_id') )
	{
		$query .=  " order by c.$order_field $order_dir ";
		//print_r($query);
		//exit;
	}
	else
	{
		$query .=  " order by t.meta_value $order_dir ";
	}

	$db->setLimit($limit, $start);
	$content_list = $db->queryAll($query);

	$contents = fetchMetadata($content_list);
	echo json_encode(array(
		'success' => true,
		'total' => $total,
		'data' => $contents
	));
}
catch(Exception $e)
{
	echo json_encode(array(
		'success' => false,
		'msg' => $e->getMessage() . '(' . $db->last_query . ')'
	));
	exit;
}

function fetchMetadata($content_list)
{
	global $db;

	$result = array();
	foreach ($content_list as $content)
	{
		$id = $content['id'];
		$meta_table_id = $content['meta_table_id'];

		$meta_value = $db->queryAll("select ".
										"t1.name, ".
										"t1.type, ".
										"t2.meta_value ".
									"from ".
										"meta_field t1, ".
										"(select * from ingest_metadata where ingest_id=".$id.") t2 ".
									"where ".
										"t1.meta_field_id=t2.meta_field_id");
		foreach ($meta_value as $v)
		{
		/*	if($v['name'] == '메타데이터 작업자')
			{
				if( !empty( $v['meta_value'] ) )
				{
					$kr_nm = $db->queryOne("select name from member where user_id='{$v['meta_value']}'");
					if ( !empty( $kr_nm ) )
					{
						$v['meta_value'] = $kr_nm;
					}
				}
			}
			*/

			$content[$v['name']] = $v['meta_value'];
		}
		$tc_list = $db->queryRow("select * from ingest_tc_list where ingest_list_id='$id' order by id desc ");

		if( !empty($tc_list) )
		{
			$content['tc_in'] = $tc_list['tc_in'];
			$content['tc_out'] = $tc_list['tc_out'];
		}
		array_push($result, $content);
	}

	return $result;
}



?>