<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/store/get_content_list/libs/functions.php');

$limit = $_POST['limit'];
$start = $_POST['start'];

$p = json_decode($_POST['params']);

$category_full_path = $_POST['category_full_path'];

$ud_content_id = $p->meta_table_id;

//$q = array();
$i = 1;

//메타데이터 정보
$tablename = MetaDataClass::getTableName('usr',$ud_content_id);
$fieldIdtoNameMap = MetaDataClass::getFieldIdtoNameMap('usr',$ud_content_id);
$prefix = MetaDataClass::getVar('usr','field');

foreach ($p->fields as $field) {
	$field_name='';
	if ( is_numeric($field->meta_field_id)) {

		$field_name = $fieldIdtoNameMap[$field->meta_field_id];

		if( empty($field_name) ) continue;

		if ( $field->type == 'datefield' )	{
			if ( empty($field->s_dt) ) {
				$tables[] = '(select distinct usr_content_id as content_id from '.$tablename.' where '.$field_name.' <= \''.$f->e_dt.'\') t'.$i++;
			}
			else if ( empty($field->e_dt) ) {
				$tables[] = '(select distinct usr_content_id as  content_id from '.$tablename.' where '.$field_name.' >= \''.$field->s_dt.'\') t'.$i++;
			}
			else {
				$tables[] = '(select distinct usr_content_id as  content_id from '.$tablename.' where '.$field_name.' >= \''.$field->s_dt.'\' and $field_name <= \''.$field->e_dt.'\') t'.$i++;
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
			$tables[] = '(select distinct usr_content_id as  content_id from '.$tablename.' where lower('.$field_name.') like \'%'.strtolower(trim($db->escape($field->value))).'%\') t'.$i++;
		}

	}
	else {
		if ($field->field == 'created_date') {
			if ( empty($field->s_dt) ) {
				$tables[] = '(select content_id from bc_content where ud_content_id='.$ud_content_id.' and created_date <= \''.$f->e_dt.'\') t'.$i++;
			}
			else if ( empty($field->e_dt) ) {
				$tables[] = '(select content_id from bc_content where ud_content_id='.$ud_content_id.' and created_date >= \''.$field->s_dt.'\') t'.$i++;
			}
			else {
				$tables[] = '(select content_id from bc_content where ud_content_id='.$ud_content_id.' and created_date >= \''.$field->s_dt.'\' and created_date <= \''.$field->e_dt.'\') t'.$i++;
			}
		}
		else {


				$tables[] = '(select content_id from bc_content where ud_content_id='.$ud_content_id.' and lower(title) like \'%'.strtolower(trim($db->escape($field->value))).'%\') t'.$i++;

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
			c.*
		from
			view_bc_content c,
			$tables
		where
			c.ud_content_id='$ud_content_id'
		and
			c.is_deleted = 'N' and c.status in( 2, 0)
		and
			$join_tables";


if(!empty($category_full_path))
{
	$q .= " and c.category_full_path like '$category_full_path%' ";
}


//		and
//			c.status > 0";
//echo $q;exit;
$total = $db->queryOne("select count(*) from ($q) t ");

if ( $total > 0 )
{
	$db->setLimit($limit, $start);
	$content_list = $db->queryAll($q);

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
	'results' => $contents,
        'ud_content_id' => $ud_content_id,
	'query' => $q
)));
//print_r($data);
?>