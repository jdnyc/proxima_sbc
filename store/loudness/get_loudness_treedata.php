<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');

$content_id = $_POST['content_id'];

try {
	$no = 1;
	$node_list = array();
	
	$loudness_lists = $db->queryAll("
						SELECT	L.*, (SELECT USER_NM FROM BC_MEMBER WHERE USER_ID = L.REQ_USER_ID) AS REQ_USER_NM
						FROM	TB_LOUDNESS L
						WHERE	L.CONTENT_ID = $content_id
						ORDER BY L.LOUDNESS_ID DESC
					");
	
	foreach ($loudness_lists as $item) {
		$node = array();
		$child_list = array();
		$child_no = 1;
		$leaf = true; //기본 리프노드
		
		$loudness_id = $item['loudness_id'];
		
		$node['no'] = (string)$no;
		$node['icon'] = '/led-icons/folder.gif';
		$node['expanded'] = false;
		
		$node['id'] = $loudness_id;
		
		foreach ($item as $key => $value)
		{
			$node[$key] = $value;
		}
		
		$db->setLoadNEWCLOB(true);
		$logs = $db->queryAll("
					SELECT	L.*
					FROM	TB_LOUDNESS_LOG L
					WHERE	L.LOUDNESS_ID = $loudness_id
					ORDER BY L.LOUDNESS_LOG_ID DESC
				");
		
		foreach($logs as $log) {
			$leaf = false;
			$child = array();
			
			$child['id'] = $loudness_id.'_'.$log['loudness_log_id'];
			$child['no'] = (string)$child_no;
			$child['icon'] = '/led-icons/folder.gif';
			
			foreach($log as $logKey => $logValue) {
				$child[$logKey] = $logValue;
			}
			
			$child['leaf'] = true;
			$child_no ++;
			$child_list[] = $child;
		}
		
		$node['children'] = $child_list;
		
		$node['leaf'] = false;
		$node['log'] = '';
		$node['creation_date'] = $item['req_datetime'];
		$node_list [] = $node;
		
		$no ++ ;
	}
	
	echo json_encode($node_list);
} catch (Exception $e) {
	echo _text('MN01039').' : '.$e->getMessage();
}