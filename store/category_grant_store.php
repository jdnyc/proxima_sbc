<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');


$data = array();

try
{
	/*{name: 'ud_content_id' },
	   {name: 'member_group_id'},
	   {name: 'category_id' },
	   {name: 'group_grant'}*/


		$query = "
		select
			bcg.ud_content_id,
			bcg.member_group_id,
			bcg.category_id,
			bcg.group_grant,
			bcg.category_full_path,
			buc.ud_content_title,
			bc.category_title,
			bmg.member_group_name
		from
			bc_category_grant bcg,
			bc_ud_content buc,
			bc_category bc,
			bc_member_group bmg
		where
			bcg.ud_content_id=buc.ud_content_id
		and bcg.category_id=bc.category_id
		and bcg.member_group_id=bmg.member_group_id
		";

		$rows = $db->queryAll($query);

		foreach($rows as $row)
		{
			array_push($data, array(
				$row['ud_content_title'],
				$row['member_group_name'],
				$row['category_title'],
				$row['group_grant'],
				$row['ud_content_id'],
				$row['member_group_id'],
				$row['category_id'],
				$row['category_full_path']
			));
		}



echo json_encode($data);

}
catch (Exception $e)
{
	echo '오류: '.$e->getMessage();
}
?>