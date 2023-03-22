<?php
set_time_limit(0);
define('TROOT', '/oradata/web/nps');
require_once(TROOT.'/lib/config.php');
require_once(TROOT.'/lib/functions.php');
$created =date('Ymd');
$filename =basename(__FILE__);

define('CREATED_TIME', date('YmdHis'));

$limit = 2000;
try
{


	/*view_content 
	
	SELECT 
BC.BS_CONTENT_TITLE BS_CONTENT_TITLE,
UC.UD_CONTENT_TITLE UD_CONTENT_TITLE,
CA.CATEGORY_TITLE,
c."CATEGORY_ID",c."CATEGORY_FULL_PATH",c."BS_CONTENT_ID",c."UD_CONTENT_ID",c."CONTENT_ID",c."TITLE",c."IS_DELETED",c."IS_HIDDEN",c."REG_USER_ID",c."EXPIRED_DATE",c."LAST_MODIFIED_DATE",c."CREATED_DATE",c."STATUS",c."READED",c."SEND_TO_DAS",c."DAS_CONTENT_ID",c."LAST_ACCESSED_DATE",c."PARENT_CONTENT_ID",
BM.USER_NM USER_NM, BM.MEMBER_ID MEMBER_ID,cc.file_created_date file_created_date,cc.formbaseymd, cc.progcd, cc.subprogcd,brodymd,cc.medcd, n.status archive_status, ca.code category_code , ca.path category_path,  ca.member_group_id category_member_group_id, ca.PARENT_ID PARENT_ID , ca.sub_category_id sub_category_id,
m.status media_status,
ca.using_review,ca.storage_group
FROM
BC_CONTENT C,
BC_BS_CONTENT BC,
BC_UD_CONTENT UC,
BC_MEMBER BM,
( select c.* ,s.category_id sub_category_id ,p.path,p.using_review,p.storage_group, p.member_group_id from  BC_CATEGORY c, path_mapping p, SUBPROG_MAPPING s where c.category_id=p.category_id(+) and c.category_id=s.category_id(+)  ) CA,
content_code_info cc,
( select * from bc_media where media_type='original' ) m,
( select * from nps_work_list where work_type='archive' ) n
WHERE
C.BS_CONTENT_ID=BC.BS_CONTENT_ID
AND C.UD_CONTENT_ID=UC.UD_CONTENT_ID
and cc.content_id(+)=c.content_id
and n.content_id(+)=c.content_id
and m.content_id(+)=c.content_id

AND C.CATEGORY_ID=CA.CATEGORY_ID
AND C.REG_USER_ID=BM.USER_ID(+)*/
	
	//필드 추가
	/*
	$r = $db->exec("ALTER TABLE PATH_MAPPING ADD(   STORAGE_GROUP VARCHAR2(20 BYTE)   )");
	$r = $db->exec("ALTER TABLE PATH_MAPPING ADD(   USING_REVIEW VARCHAR2(20 BYTE)   )");

	$r = $db->exec("ALTER TABLE BC_TASK_WORKFLOW_RULE ADD(   SOURCE_PATH_ID NUMBER   )");
	$r = $db->exec("ALTER TABLE BC_TASK_WORKFLOW_RULE ADD(   TARGET_PATH_ID NUMBER   )");
	$r = $db->exec("ALTER TABLE BC_TASK_WORKFLOW_RULE ADD(   STORAGE_GROUP NUMBER  )");
	$r = $db->exec("ALTER TABLE BC_TASK_WORKFLOW_RULE ADD(   WORKFLOW_RULE_PARENT_ID NUMBER   )");

	$r = $db->exec("update path_mapping set USING_REVIEW='1'");
	$r = $db->exec("update path_mapping set storage_group='1'");
	*/

	$query = " select 
				tr.TASK_WORKFLOW_ID,
					tr.TASK_RULE_ID,
					tr.JOB_PRIORITY,
					tr.WORKFLOW_RULE_ID,
					tr.CONDITION,
					tr.TASK_RULE_PARANT_ID,
					tr.CONTENT_STATUS,
					tr.SOURCE_PATH_ID,
					tr.TARGET_PATH_ID,
					tr.STORAGE_GROUP,
					tr.WORKFLOW_RULE_PARENT_ID,
				 t.source_path src_path,
			 t.target_path  tar_path
			from bc_task_workflow_rule tr, bc_task_rule t where t.task_rule_id=tr.task_rule_id ";

	$order= '  order by tr.workflow_rule_id asc  ';
	$total = $db->queryOne("select count(*) from ( $query ) cnt ");
	$j =0 ;
	$total_list = array();

	echo $log_msg = '['.date("Y-m-d H:i:s").'] '.$total.' data DB Loading...'."\n";//확인
	@file_put_contents(TROOT.'/log/migration/'.$filename.$created.'.log', $log_msg."\n", FILE_APPEND);

	for($start = 0 ; $start< $total ; $start+=$limit)
	{
		$db->setLimit($limit, 0);
		$lists = $db->queryAll($query.$order);

		foreach( $lists as $key => $row )
		{
			echo $total."/".$j++."\r";

			$workflow_rule_id = $row[workflow_rule_id];
			$task_rule_parant_id = $row[task_rule_parant_id];	
			
			$task_workflow_id =  $row[task_workflow_id];

			if( empty($row[workflow_rule_parent_id]) ){
			
			
				if($task_rule_parant_id == 0){
					$r = $db->exec("update BC_TASK_WORKFLOW_RULE  set WORKFLOW_RULE_PARENT_ID='0' where workflow_rule_id='$workflow_rule_id'  ");
				
				}else{
					$parent = $db->queryRow("select * from BC_TASK_WORKFLOW_RULE where TASK_WORKFLOW_ID='$task_workflow_id'  and task_rule_id= '$task_rule_parant_id' ");
					
					$r = $db->exec("update BC_TASK_WORKFLOW_RULE  set WORKFLOW_RULE_PARENT_ID='$parent[workflow_rule_id]' where workflow_rule_id='$workflow_rule_id'  ");
				}
			}

			if( empty($row[source_path_id]) ){
				$r = $db->exec("update BC_TASK_WORKFLOW_RULE  set source_path_id='$row[src_path]' where workflow_rule_id='$workflow_rule_id'  ");

			}

			if( empty($row[target_path_id]) ){
				$r = $db->exec("update BC_TASK_WORKFLOW_RULE  set target_path_id='$row[tar_path]' where workflow_rule_id='$workflow_rule_id'  ");
			}
			

			@file_put_contents(TROOT.'/log/migration/'.$filename.$created.'.log', $content_id.' , '.$archive_id."\n", FILE_APPEND);
		}
	}

	echo $log_msg = '['.date("Y-m-d H:i:s").'] '.$total.' data DB Loaded...'."\n";//확인
	@file_put_contents(TROOT.'/log/migration/'.$filename.$created.'.log', $log_msg."\n", FILE_APPEND);

}
catch(Exception $e)
{
	echo $e->getMessage().' '.$db->last_query;
	@file_put_contents(TROOT.'/log/migration/'.basename(__FILE__).CREATED_TIME.'_error_.log', date("Y-m-d H:i:s").' '.$e->getMessage().' '.$db->last_query."\n", FILE_APPEND);
}

?>