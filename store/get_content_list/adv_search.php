<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/store/get_content_list/libs/functions.php');

$limit = $_POST['limit'];
$start = $_POST['start'];

$p = json_decode($_POST['params']);

$table_join =  array();
//$q = array();
$i = 1;
foreach ($p->fields as $field) {
	if (is_numeric($field->meta_field_id)) {
		if ( $field->type == 'datefield' )	{
			if ( empty($field->s_dt) ) {
				$tables[] = '(select distinct content_id from bc_usr_meta_value where usr_meta_field_id ='.$field->meta_field_id.' and usr_meta_value <= \''.$f->e_dt.'\') t'.$i++;
			}
			else if ( empty($f->e_dt) ) {
				$tables[] = '(select distinct content_id from bc_usr_meta_value where usr_meta_field_id ='.$field->meta_field_id.' and usr_meta_value >= \''.$field->s_dt.'\') t'.$i++;
			}
			else {
				$tables[] = '(select distinct content_id from bc_usr_meta_value where usr_meta_field_id ='.$field->meta_field_id.' and usr_meta_value >= \''.$field->s_dt.'\' and usr_meta_value <= \''.$field->e_dt.'\') t'.$i++;
			}
		}
		else if ($field->type == 'listview') {
			switch ($f->meta_field_id)
			{
				case 4037607:
					$tables[] = '(select distinct content_id from meta_multi_xml where contains(val, \'%'.trim($db->escape($field->value)).'% INPATH (/columns/columnF)\') > 0) t'.$i++;
				break;

				case 11879136:
					$tables[] = '(select distinct content_id from meta_multi_xml where contains(val, \'%'.trim($db->escape($field->value)).'% INPATH (/columns/columnG)\') > 0) t'.$i++;
				break;
			}
		}
		else
		{
			$tables[] = '(select distinct content_id from bc_usr_meta_value where usr_meta_field_id ='.$field->meta_field_id.' and lower(usr_meta_value) like \'%'.trim($db->escape($field->value)).'%\') t'.$i++;
		}
	}
	else {
		if ($field->field == 'created_date') {
			if ( empty($field->s_dt) ) {
				$tables[] = '(select content_id from bc_content where created_date <= \''.$f->e_dt.'\') t'.$i++;
			}
			else if ( empty($f->e_dt) ) {
				$tables[] = '(select content_id from bc_content where created_date >= \''.$field->s_dt.'\') t'.$i++;
			}
			else {
				$tables[] = '(select content_id from bc_content where created_date >= \''.$field->s_dt.'\' and created_date <= \''.$field->e_dt.'\') t'.$i++;
			}
		}
		else {	
			$tables[] = '(select content_id from bc_content where lower(title) like \'%'.trim($db->escape($field->value)).'%\') t'.$i++;
		}
	}
}

for ($i=0; $i<count($tables); $i++)
{
	if ( $i+1 == count($tables) )
	{
		$join_tables[] = 't'.($i+1).'.content_id = c.content_id';
	}
	else
	{
		$join_tables[] = 't'.($i+1).'.content_id = t'.($i+2).'.content_id';
	}
}

$tables = join(', ', $tables);
$join_tables = join(' and ', $join_tables);

$q = "select
			c.content_id
		from
			bc_content c,
			$tables
		where
			$join_tables
		and
			c.bs_content_id =506
		and
			c.is_deleted = 'N'";
//		and
//			c.status > 0";
//echo $q;exit;
$total = $db->queryOne("select count(*) from ($q) t");

if ( $total > 0 )
{
	$db->setLimit($limit, $start);
	$data = $db->queryAll($q);

	//echo $db->last_query;

	foreach ( $data as $i )
	{
		$contents[] = $i['content_id'];
	}

	$contents = implode(',', $contents);
	$content_list_query = "select
							c.category_id, c.content_id, c.title, c.created_date, c.ud_content_id, c.bs_content_id, ct.bs_content_title as content_type, mt.ud_content_title as table_name
						from
							bc_content c, bc_bs_content ct, bc_ud_content mt
						where
							c.content_id in ($contents)
						and
							c.ud_content_id=mt.ud_content_id
						and
							c.bs_content_id=ct.bs_content_id
						order by c.content_id desc";

	$content_list = $db->queryAll($content_list_query);

	$contents = fetchMetadata($content_list);
}
else
{
	$total=0;
	$contents = array();
}

die(json_encode(array(
	'success' => true,
	'total' => $total,
	'results' => $contents
)));
//print_r($data);
?>