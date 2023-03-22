<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/searchengine/solr/searcher.class.php');
try
{
	$items = json_decode(urldecode($_POST['values']));
	//print_r($items);exit;

	$contents_id		= $items->k_contents->contents_id;
 	$ud_content_id		= $items->k_contents->ud_content_id;
	$ud_content_id		= substr($ud_content_id, 0, strpos($ud_content_id, ','));
	$fields				= $items->values;

	$update_content_fields = array();
	foreach ( $fields as $field )
	{
		$field_id		= $field[0];
		$field_value	= $field[1];

		// content 테이블에 정의된 필드
		if ( preg_match('/^c\_/', $field_id) )
		{
			if ($field_id == 'c_category_id')
			{
				if ( is_numeric($field_value) )
				{
					array_push($update_content_fields, "category_id=".$field_value);
					array_push($update_content_fields, "category_full_path='/0".getCategoryFullPath($field_value)."'");
				}
				else if( preg_match('/->/', $field_value) )
				{
					$field_value = explode(' -> ', $field_value);
					$field_value = $field_value[count($field_value)-1];

					array_push($update_content_fields, "category_id=".$field_value);
					array_push($update_content_fields, "category_full_path='/0".getCategoryFullPath($field_value)."'");
				}
				else
				{
					array_push($update_content_fields, "category_id=0");
					array_push($update_content_fields, "category_full_path='/0'");
				}

			}
			else
			{
				array_push($update_content_fields, substr($field_id, 2)."='".$field_value."'");
			}

			continue;
		}
		else if (preg_match('/^x\_/', $field_id))
		{
			if ($field_id == 'x_items_list')
			{
				$items = json_decode($field_value);
				$rtn = $db->exec("delete from items where content_id in (".$contents_id.")");

				$_contents_id = explode(',', $contents_id);
				foreach ($_contents_id as $content_id)
				{
					foreach ($items as $item)
					{
						$rtn = $db->exec("insert into items (content_id, item_cd, item_nm) values ($content_id, {$item->item_cd}, '{$item->item_nm}')");
					}
				}
			}
		}
		else
		{
			$_contents_id = explode(',', $contents_id);
			foreach ($_contents_id as $entry)
			{
				$is_exists_field = $db->queryOne("select count(*) from bc_usr_meta_value where content_id=$entry and usr_meta_field_id=$field_id");
				if ($is_exists_field > 0)
				{
					$db->exec("update bc_usr_meta_value set usr_meta_value='$field_value' where content_id in ($contents_id) and usr_meta_field_id=$field_id");
				}
				else
				{
					$db->exec("insert into bc_usr_meta_value
									(content_id, ud_content_id, usr_meta_field_id, usr_meta_value)
								values
									($entry, $field_id, $ud_content_id, '$field_value')");
				}

			}
		}
	}


	//// 수정시 메타데이터 작업자 등록과 로그 남기기 2011-1-28 by 이성용

	$contents = explode(',', $contents_id);
	$total = count($contents);

	$user_id			= $_SESSION['user']['user_id'];
	$created_time		= date('YmdHis');

	if ( !empty($update_content_fields) )
	{
		executeQuery(sprintf("update bc_content set %s where content_id in (%s)", join(', ', $update_content_fields),	$contents_id));
	}

	for($i=0;$i<$total;$i++)
	{
		$content_id=$contents[$i];


		$log_id = getNextSequence();

		$content_data = $db->queryRow("select bs_content_id, ud_content_id from bc_content where content_id='$content_id'");

		$ud_content_id = $content_data['ud_content_id'];
		$bs_content_id = $content_data['bs_content_id'];

		// 2011-1-20 메타데이터 수정시에 작업정보 - 메타데이터 작업자 입력 by 이성용

		$meta_modifiy_id = $db->queryOne("select usr_meta_field_id from bc_usr_meta_field where ud_content_id='$ud_content_id' and usr_meta_field_title like '%메타데이터 작업자%'");

		if( !empty( $meta_modifiy_id ) ) //메타필드아이디가 있을때만
		{
			executeQuery("update bc_usr_meta_value set usr_meta_value='$user_id' where content_id='$content_id' and usr_meta_field_id='$meta_modifiy_id'");
		}

		$meta_modifiy_date = $db->queryOne("select usr_meta_field_id from bc_usr_meta_field where ud_content_id='$ud_content_id' and usr_meta_field_title like '%메타데이터 작업일자%'");
		if(!empty($meta_modifiy_date)) //메타필드아이디가 있을때만
		{
			executeQuery("update bc_usr_meta_value set usr_meta_value='$created_time' where content_id='$content_id' and bc_usr_meta_field='$meta_modifiy_date'");
		}
		////////////////////////////////////////////

		// 2010-1-28  로그 남기기

		$description = _text('MN00227');
		executeQuery("insert into bc_log (log_id, action, user_id, bs_content_id, content_id, created_date, ud_content_id, description)
						values
							($log_id, 'edit', '$user_id', '$bs_content_id', '$content_id', '$created_time', '$ud_content_id', '$description')");

		// 검색엔진에 등록/
		//$s = new Searcher($db);
		//$s->update($content_id, 'DAS');
	}

	die(json_encode(array(
		'success' => true
	)));
}
catch (Exception $e)
{
	die(json_encode(array(
		'success' => false,
		'msg' => $e->getMessage(),
		'query' => $db->last_query
	)));
}
?>