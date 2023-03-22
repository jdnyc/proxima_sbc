<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');
$mapping_status = array(
	0	=> "<font color=red>대기</font>",
	1	=> "<font color=green>완료</font>",
	-1	=> '오류',
	2	=>	"<font color=blue>진행중</font>",
	-3  => "<font color=red>대기</font>",
);

$user_id		= $_SESSION['user']['user_id'];
$member_id		= $db->queryOne("select member_id from member where user_id ='$user_id'");
$start_date		= trim($_POST['start_date']);
$end_date		= trim($_POST['end_date']);
$meta_table_id	= $_POST['meta_table_id'];
$container_id	= $_POST['container_id'];
$status			= INGEST_READY;
$start			= $_POST['start'];
$limit			= $_POST['limit'];

$search			= $_POST['search'];



try
{
	$content_query = "
		select
			*
		from
			ingest
		where
			meta_table_id='$meta_table_id' and
			created_time between $start_date and $end_date
			order by id desc
		";

	if(!empty($search))
	{
		$search = trim(strtoupper($search));
		$tape_list = $db->queryAll("select ingest_id from ingest_metadata where  meta_table_id='$meta_table_id' and meta_value like '%$search%'");

		if(empty($tape_list))
		{
			throw new Exception('검색된 Tape No가 없습니다.',-1);
		}

		$tape_list_item = array();

		$search_query ="select
			*
		from
			ingest
		where
		";

		foreach($tape_list as $tape_list_id)
		{
			array_push($tape_list_item,'id='.$tape_list_id['ingest_id']);
		}

		$search_query .= join(' or ', $tape_list_item);
		$search_query .= " order by id desc";
		$content_query = $search_query;

		//print_r($content_query);

	}

	$db->setLimit($limit,$start);
	$content = $db->queryAll($content_query);

	$no = $start+1;
	$node_list = array();

	foreach($content as $data)
	{
		$status_count=0;
		$status_total=0;
		$ingest_id			= $data['id'];
		$content_type_id	= $data['content_type_id'];
		$meta_table_id		= $data['meta_table_id'];

		$fields = $db->queryAll("
								select
									meta_field_id, meta_value
								from
									ingest_metadata
								where
									ingest_id='$ingest_id'
							");

		$node_item = array();
		array_push($node_item, "no : '".$no."'");
		array_push($node_item, "id : '".$ingest_id."'");
		array_push($node_item, "title : '".escape($data['title'])."'");
		foreach($fields as $i)
		{
			// 필드값 추가
			$k=1;
			foreach($i as $key=>$val)
			{
				if ( $k%2 == 1 )
				{
					$fil = $val; //field
				}
				else
				{
					array_push($node_item, "'".$fil."' : '".escape($val)."'");//value
				}
				$k++;
			}
		}
		$no++;

		$tc_query = "select * from ingest_tc_list where ingest_list_id = $ingest_id order by id desc ";
		$ingest_tc_list = $mdb->queryAll($tc_query);

		if(count($ingest_tc_list)>0)
		{
			$node_other = "";
			$node_other .= ", icon: '/led-icons/page_2.png'";
			$node_other .= ",iconCls: 'list'";
			$node_other .= ',leaf: false ';
			$node_other .= ',expanded: false';
			$node_other .=', children: ';

			$node_tc_list = array();
			$tc_no =1;
			foreach($ingest_tc_list as $tc_item)
			{
				$tc_node = '{';

				$node_tc_item = array();
				foreach($tc_item as $key=>$value)
				{
					if($key=='status')
					{
						$status_total++;
						if($value=='1')
						{
							$status_count++;
						}
						array_push($node_tc_item, "".$key.": '".$mapping_status[$value]."'");
					}
					else
					{
						array_push($node_tc_item, "".$key.": '".$value."'");
					}
				}
				array_push($node_tc_item," no : '".$tc_no."'");
				$tc_no++;
				$tc_node .=join(',',$node_tc_item);
				$tc_node .= ", iconCls: 'tc'";
				$tc_node .= ", icon: '/led-icons/arrow_right.png'";
				$tc_node .= ', leaf: true';
				$tc_node .= '}';
				$node_tc_list[] = $tc_node;
			}

			array_push($node_item,"status : '".$mapping_status[$data['status']]."'");//인제스트 상태

			$node = '{'.join(',', $node_item);
			$node .= $node_other;
			$node .= '['.join(',', $node_tc_list).']';
		}
		else
		{
			array_push($node_item,"status : '".$mapping_status[$data['status']]."'");//인제스트 상태
			$node = '{'.join(',', $node_item);
			$node .= ", icon: '/led-icons/page_2.png'";
			$node .= ",iconCls: 'list'";
			$node .= ',leaf: true';
		}
		$node .= '}';

		$node_list[] = $node;


	}

	if (empty($node_list))
	{
		echo '[]';
	}
	else
	{
		echo '['.join(',', $node_list).']';
	}

}
catch (Exception $e)
{
	echo $e->getMessage();
}

function escape($v)
{
	$v = str_replace("'", "\'", $v);
	$v = str_replace("\r", '', $v);
	$v = str_replace("\n", '\\n', $v);

	return $v;
}
?>