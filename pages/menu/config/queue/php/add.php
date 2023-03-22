<?php
session_start();
$user_id = $_SESSION['user']['user_id'];
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/solr/functions.php');

try {

	foreach($_POST as $k => $v){
		if(preg_match('/^k\_/', $k)) continue;
		
		## 검색어 파싱
		$solrContent .= ' ' . strip_tags($v);
	
		$custom = $db->queryRow("select f.meta_table_id as table_id, f.meta_field_id as field_id from meta_table t, meta_field f where t.meta_table_id=".$_POST['k_meta_table_id']." and t.meta_table_id=f.meta_table_id and f.name='". $k ."'");
				
		if($custom){
			$is_exists_id = $db->queryOne("select meta_value_id from meta_value where content_id={$_POST['k_content_id']} and meta_table_id={$custom['table_id']} and meta_field_id={$custom['field_id']}");
			
			if( $is_exists_id ) {
				$r = $db->exec("update meta_value set value='$v' where meta_value_id=$is_exists_id");
			}else{
				$meta_value_id = getNextSequence();
				$r = $db->exec("insert into meta_value (content_id, meta_table_id, meta_field_id, meta_value_id, value) values ({$_POST['k_content_id']}, {$custom['table_id']}, {$custom['field_id']}, $meta_value_id, '$v')");
	
				## content 테이블에 meta_table_id 값 추가
				if($_POST['k_is_hidden'] == 'Y'){
					$is_hidden = "1";
				}else{
					$is_hidden = "0";
				}
				$r = $db->exec("update content set meta_table_id = '{$custom['table_id']}', category_id = '{$_POST['k_category']}', is_hidden = '$is_hidden',  expire_date = '{$_POST['k_expire_date']}' where content_id = {$_POST['k_content_id']}");
			}
		}else{
			throw new Exception('메타데이터 정보를 얻을수 없습니다.', ERROR_QUERY);
		}
	}	
	
	## 검색엔진에 메타데이터 등록
	$content_id = $_POST['k_content_id'];
	
	## 솔라에 검색어 업데이트
	$result = solrUpdate($content_id, $solrContent);
	if(!strstr($result, '<int name="status">0</int>')) throw new Exception('failure solrUpdate');

	$result = solrCommit();
	if(!strstr($result, '<int name="status">0</int>')) throw new Exception('failure solrCommit');

	unset($solrContent);	
	
	### task 테이블에 작업 추가
	$cur_time = date('YmdHis');
	$status = 'queue';
	$priority = 300;
	
	$media_id = $mdb->queryOne("select media_id from media where content_id={$_POST['k_content_id']} and type='original'");
	if(empty($media_id)) throw new Exception('디비에서 원본 파일 정보를 얻어 올 수 없습니다.');

	$source = $mdb->queryOne("select file from queue where content_id={$_POST['k_content_id']} and status='queue'");
	if(empty($source)) throw new Exception('대기중인 파일 정보를 얻어 올 수 없습니다.');
	
	$save_path = buildSavePath();	
	$filename = substr($source, strrpos($source, '/'));
	
	## 트랜스퍼
	$target_storage = $mdb->queryRow("select * from storage where name='queue_transfer'");
	if(empty($target_storage['path'])) throw new Exception('타겟 스토리지를 찾을 수 없습니다.');

	$transper_target = $target_storage['path']."/".$save_path."/".$filename;
	$watch_source = $save_path."/".$filename;
	$transper_target = addslashes(str_replace('\\', '/', $transper_target));
	
	$r = $db->exec("insert into task (media_id, type, status, priority, source, target, target_id, target_pw, parameter, creation_datetime) values ('$media_id', '".ARIEL_TRANSFER_FS."', '$status', $priority, '$source', '$transper_target', '{$target_storage['login_id']}', '{$target_storage['login_pw']}', 'keep', '$cur_time')");

	## 트랜스코딩
	$target_storage = $mdb->queryRow("select * from storage where name='transcoder'");
	if(empty($target_storage['path'])) throw new Exception('타겟 스토리지를 찾을 수 없습니다.');

	$proxy_trans_target = $target_storage['path']."/".$save_path.'/Proxy';
	$proxy_target_path = addslashes(str_replace('\\', '/', $proxy_trans_target));

	$down_trans_target = $target_storage['path']."/".$save_path.'/Download';
	$down_target_path = addslashes(str_replace('\\', '/', $down_trans_target));
	

	## 스트리밍용 트랜스코딩 등록
	$proxy_mediaID = $mdb->queryOne("select media_id from media where content_id={$_POST['k_content_id']} and type='proxy'");
	if(empty($proxy_mediaID)) throw new Exception('디비에서 프록시 파일 정보 얻어오기를 실패했습니다.');

	$r = $db->exec("insert into task (media_id, type, status, priority, source, target, target_id, target_pw, parameter, creation_datetime) values ($proxy_mediaID, '".ARIEL_TRANSCODER."', 'watchFolder', $priority, '$watch_source', '$proxy_target_path', '{$target_storage['login_id']}', '{$target_storage['login_pw']}', '$trans_proxy_parameter', '$cur_time')");

	## 다운로드용 트랜스코딩 등록
	$down_mediaID= $mdb->queryOne("select media_id from media where content_id={$_POST['k_content_id']} and type = 'download'");
	if(empty($proxy_mediaID)) throw new Exception('디비에서 다운로드 용도 파일 정보 얻어오기를 실패했습니다.');

	$r = $db->exec("insert into task (media_id, type, status, priority, source, target, target_id, target_pw, parameter, creation_datetime) values ('$down_mediaID', '".ARIEL_TRANSCODER."', 'down_queue', $priority, '$watch_source', '$down_target_path', '{$target_storage['login_id']}', '{$target_storage['login_pw']}', '$trans_download_parameter', '$cur_time')");


	## 카탈로깅
	$target_storage = $mdb->queryRow("select * from storage where name = 'catalog'");
	if(empty($target_storage['path'])) throw new Exception('디비에서 카탈로그 스토리지 정보 얻어오기를 실패했습니다.');

	$catalog_target = $target_storage['path']."/".$save_path.'/Catalog';
	$catalog_target = addslashes(str_replace('\\', '/', $catalog_target));

	$r = $db->exec("insert into task (media_id, type, status, priority, source, target, target_id, target_pw, parameter, creation_datetime) values ('$media_id', ".ARIEL_CATALOG.", 'watchFolder', $priority, '$watch_source', '$catalog_target', '{$target_storage['login_id']}', '{$target_storage['login_pw']}','$catal_parameter', '$cur_time')");

	## contetn테이블에 user_id업데이트
	$update_id = $mdb->queryOne("update content set user_id = '$user_id' where content_id = {$_POST['k_content_id']}");

	echo json_encode(array(
		'success' => true,
	));
}
catch(Exception $e){
	switch($e->getCode()){
		case ERROR_QUERY:
			$msg = $e->getMessage().'( '.$db->last_query.' )';
		break;
		
		default:
			$msg = $e->getMessage();
		break;
	}
	
	die(json_encode(array(
		'success' => false,
		'msg' => $msg,
		'query' => $db->last_query
	)));
}

function buildSavePath(){
	return date('Y/m/d/H/is');
}
?>