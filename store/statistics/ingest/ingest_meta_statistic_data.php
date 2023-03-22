<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');
$mapping_status = array(
	0	=> "<font color=red>등록대기</font>",
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

$start			= $_POST['start'];
$limit			= $_POST['limit'];

$search			= $_POST['search'];



try
{
	$content_query = "
		select
			*
		from
			content
		where
			meta_table_id='$meta_table_id'
		and is_deleted != '1'
		and LAST_MODIFIED_TIME is not null
		and LAST_MODIFIED_TIME between $start_date and $end_date
			order by created_time desc
		";


	$db->setLimit($limit,$start);
	$content = $db->queryAll($content_query);

	$no = $start+1;
	$node_list = array();

	foreach($content as $data)
	{
		$status_count=0;
		$status_total=0;
		$content_id			= $data['content_id'];
		$content_type_id	= $data['content_type_id'];
		$meta_table_id		= $data['meta_table_id'];

		$fields = $db->queryAll("
								select
									meta_field_id, value
								from
									meta_value
								where
									content_id='$content_id'
							");
		$runningtime = $db->queryOne("select value from content_value where content_id ='$content_id' and content_field_id='507'");

		$node_item = array();
		array_push($node_item, "no : '".$no."'");
		array_push($node_item, "id : '".$content_id."'");
		array_push($node_item, "title : '".escape($data['title'])."'");
		array_push($node_item,"status : '".$mapping_status[$data['status']]."'");//인제스트 상태
		array_push($node_item,"507 : '".$runningtime."'");//인제스트 상태
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

		//메타 데이터에서 타임코드가 있는 tc정보 리스트를 자식노드로 추가

		$tc_sub_qeury = "(select meta_field_id from meta_field where name like '%TC정보%' and depth =1 and meta_table_id='$meta_table_id')";

		$tc_info = "select value from meta_multi where content_id='$content_id' and meta_field_id=$tc_sub_qeury order by sort";
		$tc_list = $mdb->queryAll($tc_info);

		if(count($tc_list) <= 0)
		{
			$node = '{'.join(',', $node_item);
			$node .= ", icon: '/led-icons/page_2.png'";
			$node .= ",iconCls: 'list'";
			$node .= ',leaf: true';
		}
		else
		{
			$node_other = "";
			$node_other .= ", icon: '/led-icons/page_2.png'";
			$node_other .= ",iconCls: 'list'";
			$node_other .= ',leaf: false ';
			$node_other .= ',expanded: false';
			$node_other .=', children: ';

			$node_tc_list = array();
			$tc_no =1;

			for( $i=0; $i<count($tc_list) ; $i++ )
			{
				$list = json_decode($tc_list[$i]['value'], true);
				if(!empty($list))
				{
					$tc_node = '{';

					$node_tc_item = array();

					//print_r($list);	exit;
					foreach($list as $key=>$value)
					{
						if($key=='columnA')
						{
							array_push($node_tc_item, "no: '".escape($value)."'");
						}
						array_push($node_tc_item, "".$key.": '".escape($value)."'");

					}
					$tc_no++;
					$tc_node .=join(',',$node_tc_item);
					$tc_node .= ", iconCls: 'tc'";
					$tc_node .= ", icon: '/led-icons/arrow_right.png'";
					$tc_node .= ', leaf: true';
					$tc_node .= '}';
					$node_tc_list[] = $tc_node;
				}
				//print_r($node_tc_list);
			}

			$node = '{'.join(',', $node_item);
			$node .= $node_other;
			$node .= '['.join(',', $node_tc_list).']';
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