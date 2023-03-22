<?php

use Api\Types\UdContentType;
/**
 * get_meta의 json 버전
 */
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/path.php');
session_start();

//$receive_xml = iconv('euc-kr', 'utf-8', file_get_contents('php://input'));
$receive_xml = $_REQUEST;

//$logger->log($xml_source);
//@file_put_contents('../log/get_meta.'.date('Ymd').'.log', print_r($receive_xml,true)."\n", FILE_APPEND);
//@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/get_meta_'.date('Ymd').'.log', 'get_meta start' . "\n", FILE_APPEND);
//@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/get_meta_'.date('Ymd').'.log', 'receive_xml:::' . print_r($receive_xml, true) . "\n", FILE_APPEND);

$response = array(
	'success' => '',
	'msg' => '',
	'data' => array()
);

$info = $_REQUEST['kind'];



switch( strtolower($info) )
{
	//수정일 : 2011.12.05
	//작성자 : 김형기
	//내용 : 다운로드를 하기 위한 정보를 준다.
	case 'mastering_workflow':			//종편 전송을 위하여 새롭게 추가됨. 20120827 by 이도훈

		$response['success'] = true;
		$response['msg'] = 'ok';

		$flag = $_REQUEST['flag'];

		if( $flag  == 'edius' ){
			$codename = 'ED_to_tapeless';
			array_push($response['data'], array(
				'channel' => 'edius_plugin',
				'id' => 'edius_plugin',
				'name' => 'EDIUS에서 NPS 등록'
			));
		}else if(  $flag  == 'fcp' ){
			$codename = 'FCP_to_subcontrol';

			array_push($response['data'], array(
				'channel' => 'fcp',
				'id' => 'fcp',
				'name' => 'FCP에서 NPS 등록'
			));
			// 워크플로우 코드정보
			$workflow_info = getCodeInfo($codename);

			foreach($workflow_info as $infos)
			{
				array_push($response['data'], array(
					'channel' => $infos['code'],
					'id' => $infos['code'],
					'name' => $infos['name']
				));
			}

			$codename = 'FCP_to_tapeless';
		}else if(  $flag  == 'subcontrol' ){
			$codename = 'SCTL_to_NPS';
		}else{
			$codename = 'NPS_to_tapeless';
		}

		// 워크플로우 코드정보
		$workflow_info = getCodeInfo($codename);

		foreach($workflow_info as $infos)
		{
			array_push($response['data'], array(
				'channel' => $infos['code'],
				'id' => $infos['code'],
				'name' => $infos['name']
			));
		}

		echo json_encode($response);


	break;

	case 'storage':
		$result = $response->addChild('Result');
		$result->addAttribute('success', 'true');
		$result->addAttribute('msg', 'ok');

		$app_name = (String)$xml->GetInfo['app_name'];
		if(empty($app_name))
		{
			//어플리케이션 이름이 없으면 기존처럼(호환성 유지)
			$ud_content_storage_info = $mdb->queryAll("select * from bc_ud_content");
			foreach ($ud_content_storage_info as $info)
			{
				$storage = $mdb->queryRow("select * from bc_storage where storage_id=".$info['storage_id']);

				$storageInfo = $result->addchild("Storage");
				$storageInfo->addAttribute('storage_id', $storage['storage_id']);
				$storageInfo->addAttribute('ud_content_id', $info['ud_content_id']);
				$storageInfo->addAttribute('type', $storage['type']);
				$storageInfo->addAttribute('id', $storage['login_id']);
				$storageInfo->addAttribute('Pw', $storage['login_pw']);
				$storageInfo->addAttribute('name', $storage['name']);
				$storageInfo->addAttribute('Path', $storage['path']);
			}
		}
		else
		{
			//있으면 매핑 테이블에서 조회한 결과를 준다.
			$query = "select * from BC_UD_CONTENT_STORAGE where us_type = '$app_name'";
			$rows = $db->queryAll($query);

			if(!count($rows))
			{
				echo $response->asXML();
				exit;
			}
			foreach($rows as $row)
			{
				$storage = $mdb->queryRow("select * from bc_storage where storage_id=".$row['storage_id']);

				$storageInfo = $result->addchild("Storage");
				$storageInfo->addAttribute('storage_id', $storage['storage_id']);
				$storageInfo->addAttribute('ud_content_id', $row['ud_content_id']);
				$storageInfo->addAttribute('type', $storage['type']);
				$storageInfo->addAttribute('id', $storage['login_id']);
				$storageInfo->addAttribute('Pw', $storage['login_pw']);
				$storageInfo->addAttribute('name', $storage['name']);
				$storageInfo->addAttribute('Path', $storage['path']);
			}
		}

		echo $response->asXML();
	break;

	case 'category_mapping':
		$mappings = $mdb->queryAll("select * from bc_category_mapping");

		$result = $response->addChild('Result');
		$result->addAttribute('success', 'true');
		$result->addAttribute('msg', 'ok');

		foreach( $mappings as $mapping ){
			$cate = $result->addChild("CategoryMapping");
			$cate->addAttribute('meta_table_id', $mapping['ud_content_id']);
			$cate->addAttribute('category_id', $mapping['category_id']);
		}
		echo $response->asXML();
	break;

	case 'meta_group_mapping':
		$mappings = $mdb->queryAll("select a.page_order, a.meta_group_name, a.meta_group_title, b.meta_type, b.content_meta_name, b.content_meta_title, ".
										"b.content_field_id, b.meta_field_id, b.tab_order, b.allow_app, b.extend_control_id ".
									"from meta_group a, meta_group_mapping b ".
									"where a.meta_group_id = b.meta_group_id order by a.page_order asc, b.tab_order asc");

		$response['success'] = true;
		$response['msg'] = 'ok';

		foreach( $mappings as $mapping ){
			array_push($response['data'], array(
				'page_order' => $mapping['page_order'],
				'meta_group_name' => $mapping['meta_group_name'],
				'meta_group_title' => $mapping['meta_group_title'],
				'meta_type' => $mapping['meta_type'],
				'content_meta_name' => $mapping['content_meta_name'],
				'content_meta_title' => $mapping['content_meta_title'],
				'content_field_id' => $mapping['content_field_id'],
				'meta_field_id' => $mapping['meta_field_id'],
				'tab_order' => $mapping['tab_order'],
				'allow_app' => $mapping['allow_app'],
				'extend_control_id' => $mapping['extend_control_id']
			));
		}

		die( json_encode($response));

	break;
	case 'category':
		$id= $xml->GetInfo['id'];
		if (empty($id)) {
			$categories = $mdb->queryAll("select * from bc_category where parent_id = 0");
		} else {
			if ($id == 'all') {
				$categories = $mdb->queryAll("select * from bc_category order by extra_order desc, category_title asc");
			} else {
				$categories = $mdb->queryAll(sprintf("select * from bc_category where parent_id=%d", $id));
			}
		}
		//echo $mdb->last_query;
		$result = $response->addChild('Result');
		$result->addAttribute('success', 'true');
		$result->addAttribute('msg', 'ok');

		foreach( $categories as $category ){
			$cate = $result->addChild("Category");
			$cate->addAttribute('title', $category['category_title']);
			$cate->addAttribute('id', $category['category_id']);
			$cate->addAttribute('parentid', $category['parent_id']);

			$has_child = $mdb->queryOne("select count(*) from bc_category where parent_id={$category['category_id']}");
			if( $has_child > 0 ){
				$cate->addAttribute('hasChild', 'true');
			}else{
				$cate->addAttribute('hasChild', 'false');
			}
		}
		echo $response->asXML();
	break;

	case 'ud_content':
		$response['success'] = true;
		$response['msg'] = 'ok';
        $user_id = $_SESSION['user']['user_id'];
        
        $bs_content_id = $_POST['bs_content_id'];
        $flag = $_REQUEST['flag'] ?? null;

		$ud_contents = $db->queryAll("
							SELECT	*
							FROM	BC_UD_CONTENT order by show_order asc
						");

        $allowUdContents = array();

        //플래그에 따라 필터링
        //조건이 없으면 전체
        $flagMap = [
            'SAR' => [ 
                UdContentType::AUDIO
            ],
            'fcp' => [ 
                UdContentType::INGEST,
                UdContentType::CLIP,
                UdContentType::CLEAN,
                UdContentType::MASTER,
                UdContentType::NEWS,
                UdContentType::CG
            ],
            'fcpx' => [ 
                UdContentType::INGEST,
                UdContentType::CLIP,
                UdContentType::CLEAN,
                UdContentType::MASTER,
                UdContentType::NEWS,
                UdContentType::CG
            ],
            'NLE' => [ 
                UdContentType::INGEST,
                UdContentType::CLIP,
                UdContentType::CLEAN,
                UdContentType::MASTER,
                UdContentType::NEWS,
                UdContentType::CG
            ],
            'vs2ingest' => [ 
                UdContentType::INGEST,
                UdContentType::CLIP,
                UdContentType::CLEAN,
                UdContentType::MASTER,
                UdContentType::NEWS,
                UdContentType::CG
            ]
        ];

		foreach($ud_contents as $ud_content){
            if( !empty( $bs_content_id ) ){
                if( $bs_content_id != $ud_content['bs_content_id'] ){
                    continue;
                }
            }
			$ud_content_id = $ud_content['ud_content_id'];
            // if (!checkAllowUdContentGrant($user_id, $ud_content_id, GRANT_CREATE)) {
            // }continue;
            $allowUdContents [ ] = $ud_content;				
			
        }
        if( !empty($flagMap[$flag]) ){
            $filterUdContents = $flagMap[$flag];
        }else{
            $filterUdContents = [];
        }

        $data = [];       
    
        if( !empty($filterUdContents) ){
            foreach($allowUdContents as $ud_content)
            {
                foreach($filterUdContents as $filterUdContent)
                {
                    if($filterUdContent == $ud_content['ud_content_id'] ){
                        array_push( $data, $ud_content);
                    }
                }
            }
        }else{
            $data = $allowUdContents;
        }

       
        
        $response['data'] = $data;

		die(json_encode($response));

	break;

	case 'ud_content_tab':
		$response['success'] = true;
		$response['msg'] = 'ok';

		$metaType = $mdb->queryAll("select c.*
			from bc_code c, bc_code_type ct
			where c.code_type_id=ct.id
			  and ct.code='ud_content_tab'");
		foreach( $metaType as $userMeta )
		{
			array_push($response['data'], array(
				'id' => $userMeta['code'],
				'name' => $userMeta['name']
			));
		}
		die( json_encode($response));
	break;

	case 'metactrl':
		$result = $response->addchild('Result');
		$result->addAttribute('success', 'true');
		$result->addAttribute('msg', 'ok');

		$metaType = $mdb->queryAll("select * from bc_ud_content order by show_order asc");
		foreach( $metaType as $userMeta )
		{
			$conType = $response->addchild('UserContentType');
			$conType->addAttribute('name', $userMeta['ud_content_title']);
			$conName = $mdb->queryOne("select bs_content_title from bc_bs_content where bs_content_id = {$userMeta['bs_content_id']}");
			if($userMeta['allowed_extension']){
				$word= $userMeta['allowed_extension'];
				$word= trim($word);
				$add_word= substr($word, -1);
				if($add_word != ';'){
					$word = $word.';';
				}
				$word= preg_replace("/\s+/","",$word);
				//	$word= explode(';', $word, -1);
				//print_r($word);
				//	for($i=0; $i<count($word); $i++){
				//	$ext = $fileType->addchild('Ext', '.'.$word[$i]);
				$fileType = $conType->addchild('FileType', $word);
			}
			else
			{
				$fileType = $conType->addchild('FileType', '');
			}
			#확정된 컨텐츠 테이블 메타 추가
			//$getContent= $mdb->queryRaw("");
			$add_content= $conType->addChild('Content');
			$add_content->addAttribute('categoryID',"");
			$add_content->addAttribute('contentID',"");
			$add_content->addAttribute('title',"");
			$add_content->addAttribute('is_deleted',"");
			$add_content->addAttribute('is_hidden',"");
			$add_content->addAttribute('userID',"");
			$add_content->addAttribute('expireDate',"");
			$add_content->addAttribute('createdTime',"");

			$add_system= $conType->addChild('System');
			$add_system->addAttribute('contentTypeID', $userMeta['bs_content_id']);
			$add_system->addAttribute('contentTypeName', $conName);

			#콘텐츠 타입별 시스템 메타 추가
			$conField= $mdb->queryAll("select * from bc_sys_meta_field where bs_content_id={$userMeta['bs_content_id']}");
			foreach( $conField as $conFd )
			{
				$add_sysmeta= $add_system->addChild('MetaCtrl');
				//$add_sysmeta->addAttribute('contentTypeID',$conFd['content_type_id']);
				$add_sysmeta->addAttribute('contentFieldID', $conFd['sys_meta_field_id']);
				$add_sysmeta->addAttribute('name', $conFd['sys_meta_field_title']);
				//김형기 수정 시작
				$add_sysmeta->addAttribute('type', 'textfield');
				//$add_sysmeta->addAttribute('type', $conFd['sys_meta_field_type']);
				//김형기 수정 끝
				//$add_sysmeta->addAttribute('name', $conFd['sort']);
				$add_sysmeta->addAttribute('is_required', $conFd['is_required']);
				$add_sysmeta->addAttribute('editable', $conFd['is_editable']);
				//김형기 수정 시작
				$add_sysmeta->addAttribute('visible', '1');
				//$add_sysmeta->addAttribute('visible', $conFd['is_show']);
				//김형기 수정 끝
				$add_sysmeta->addAttribute('is_search', $conFd['is_search_reg']);

				if( $conFd['sys_meta_field_type'] == 'combo' ){

					$combo_op = trim($conFd['default_value']);
					$check_op = substr($combo_op, -1);

					if($check_op != ';'){
						$combo_op = $combo_op.';';
					}
					$combo_op = preg_replace("/\s+/", "", $combo_op);
					$combo_op = explode('(default)', $combo_op);

					if( count($combo_op) > 1){
						$add_sysmeta->addAttribute('default',$combo_op[0]);
						$item_op = explode(';', $combo_op[1], -1 );
						for( $v=0; $v<count($item_op); $v++ ){
							$item = $add_sysmeta->addChild('Item', $item_op[$v]);
							$item->addAttribute('id', $v);
						}
					}else{
						$add_sysmeta->addAttribute('default',$combo_op[0]);
					}

				}else{
					$add_sysmeta->addAttribute('default', $conFd['default_value']);
				}
			}

			#콘텐츠 타입별 사용자 메타 추가
			$custom = $conType->addchild('Custom');
			$custom->addAttribute('metaTableID', $userMeta['ud_content_id']);

			#meta_field 데이터를 속성값에 추가.
			$userMetaField= $db->queryAll("select * from bc_usr_meta_field where ud_content_id='{$userMeta['ud_content_id']}' order by depth, show_order");  //dohoon 10.11.28 뎁스로 소팅도도록 수정됨.
			foreach ( $userMetaField as $key => $meta_fd )
			{
				$add_userMt= $custom->addChild('MetaCtrl');
				//				$add_userMt->addAttribute('name',$userMt['allow_extension']);
				$add_userMt->addAttribute('metaFieldID', $meta_fd['usr_meta_field_id']);
				$add_userMt->addAttribute('name', $meta_fd['usr_meta_field_title']);
				$add_userMt->addAttribute('type', $meta_fd['usr_meta_field_type']);
				$add_userMt->addAttribute('is_required', $meta_fd['is_required']);
				$add_userMt->addAttribute('editable', $meta_fd['is_editable']);
				$add_userMt->addAttribute('visible', $meta_fd['is_show']);
				$add_userMt->addAttribute('is_search', $meta_fd['is_search_reg']);
				$add_userMt->addAttribute('containerID', $meta_fd['container_id']);
				$add_userMt->addAttribute('depth', $meta_fd['depth']);                   //dohoon 10.1128 컨테이너 추가로 아이디와  뎁스 코드 추가함.

				if ( $meta_fd['usr_meta_field_type'] == 'combo' )
				{
					$combo_op = trim($meta_fd['default_value']);

					$check_op = substr($combo_op, -1);

					if ($check_op != ';')
					{
						$combo_op = $combo_op.';';
					}
					$combo_op = preg_replace("/\s+/", "", $combo_op);
					$combo_op = explode('(default)', $combo_op);

					if ( count($combo_op) > 1 )
					{
						$add_userMt->addAttribute('default',$combo_op[0]);
						$item_op = explode(';', $combo_op[1], -1 );
						$i=1;
						foreach($item_op as $value)
						{
							$item = $add_userMt->addChild('Item', $value);
							$item->addAttribute('id', $i++);
						}
					}
					else
					{
						$add_userMt->addAttribute('default',$combo_op[0]);
					}

				}
				else if ( $meta_fd['usr_meta_field_type'] == 'listview' )
				{
					$columns = trim($meta_fd['default_value'], ';');
					$columns = explode(';', $columns);

					$eleColumns = $add_userMt->addChild('columns');
						$eleColumns->addAttribute('count', count($columns));

					foreach ( $columns as $column )
					{
						$eleColumn = $eleColumns->addChild('column');
							$eleColumn->addAttribute('name', $column);
					}
				}
				else
				{
					$add_userMt->addAttribute('default', $meta_fd['default_value']);
				}


				//*섬네일 뽑을수 있게 마지막에 필드 추가 2012-08-28  이성용  *//
				if( (($key+1) == count($userMetaField)) && ($userMeta['bs_content_id'] == DOCUMENT )  )
				{
					$add_userMt= $custom->addChild('MetaCtrl');
					$add_userMt->addAttribute('metaFieldID', 1);
					$add_userMt->addAttribute('name', '섬네일');
					$add_userMt->addAttribute('type', 'thumbattach');
					$add_userMt->addAttribute('is_required', $meta_fd['is_required']);
					$add_userMt->addAttribute('editable', $meta_fd['is_editable']);
					$add_userMt->addAttribute('visible', $meta_fd['is_show']);
					$add_userMt->addAttribute('is_search', $meta_fd['is_search_reg']);
					$add_userMt->addAttribute('containerID', $meta_fd['container_id']);
					$add_userMt->addAttribute('depth', $meta_fd['depth']);
				}
			 }
		}

		echo $response->asXML();
	break;

	case 'watchfolder':
		$result = $response->addchild('Result');
		$result->addAttribute('success', 'true');
		$result->addAttribute('msg', 'ok');

		$metaType = $mdb->queryRow("select name from content_type where content_type_id = '". MOVIE ."'");

		foreach($metaType as $sysMeta => $k){
			$conType = $response->addchild('Content');
			$conType->addAttribute('name', $k);
			$conType->addAttribute('contentTypeID', MOVIE);

		#콘텐츠테이블 allow extention필드 데이터를 단어별파싱하여 for구문으로 파싱된숫자만큼 생성#
			$type = $mdb->queryOne("select allow_extension from meta_table where content_type_id = '". MOVIE ."'");
			if($type){
				$word = trim($type);
				$add_word = substr($word, -1);
				if($add_word != ';'){
					$word = $word.';';
				}
				$word= preg_replace("/\s+/", "", $word);
						//	$word= explode(';', $word, -1);
						//print_r($word);
						//	for($i=0; $i<count($word); $i++){
						//	$ext = $fileType->addchild('Ext', '.'.$word[$i]);
			}
		$fileType = $conType->addChild('FileType', $word);  //fileType태그 추가.

		#파일패스태그 추가할 부분////////////////
		$storage = $mdb->queryAll("select * from storage");
		foreach($storage as $getStorage){
			$kindStorage = $conType->addChild('Storage');
			$kindStorage->addAttribute('storage_ID', $getStorage['storage_id']);
			$kindStorage->addAttribute('type', $getStorage['type']);
			$kindStorage->addAttribute('login_id', $getStorage['login_id']);
			$kindStorage->addAttribute('login_pw', $getStorage['login_pw']);
			$kindStorage->addAttribute('path', $getStorage['path']);
			$kindStorage->addAttribute('name', $getStorage['name']);
		}

		#확정된 컨텐츠 테이블 메타 추가
			//$getContent= $mdb->queryRaw("");
			$add_content= $conType->addChild('Metadata');
			$add_content->addAttribute('categoryID',"");
			$add_content->addAttribute('contentID',"");
			$add_content->addAttribute('title',"");
			$add_content->addAttribute('is_deleted',"");
			$add_content->addAttribute('is_hidden',"");
			$add_content->addAttribute('userID',"");
			$add_content->addAttribute('expireDate',"");
			$add_content->addAttribute('createdTime',"");

	///////////////////////////////////////////////////////////////////
		$add_system= $response->addChild('System');

		#콘텐츠 타입별 시스템 메타 추가
		$conField= $mdb->queryAll("select * from content_field where content_type_id= '". MOVIE ."'");
			foreach($conField as $conFd){
				$add_sysmeta= $add_system->addChild('MetaCtrl');
				$add_sysmeta->addAttribute('contentFieldID',$conFd['content_field_id']);
				$add_sysmeta->addAttribute('name',$conFd['name']);
				$add_sysmeta->addAttribute('type',$conFd['type']);
//				$add_sysmeta->addAttribute('name',$conFd['sort']);
				$add_sysmeta->addAttribute('is_required',$conFd['is_required']);
				$add_sysmeta->addAttribute('editable',$conFd['editable']);
				$add_sysmeta->addAttribute('visible',$conFd['is_show']);
				$add_sysmeta->addAttribute('is_search',$conFd['search_allow']);

				if($conFd['type'] == 'combo'){
					$combo_op = trim($conFd['default_value']);
					$check_op = substr($combo_op, -1);
					if($check_op != ';'){
						$combo_op = $combo_op.';';
					}
					$combo_op = preg_replace("/\s+/","",$combo_op);
					$combo_op = explode('(default)', $combo_op );

					if( count($combo_op) > 1){
						$add_sysmeta->addAttribute('default',$combo_op[0]);
						$item_op = explode(';', $combo_op[1], -1 );
						for($i=0; $i<count($item_op); $i++){
							$item = $add_sysmeta->addchild('Item', $item_op[$i]);
							$item->addAttribute('id', $i);
						}
					}else{
						$add_sysmeta->addAttribute('default',$combo_op[0]);
					}
				}else{
					$add_sysmeta->addAttribute('default', $conFd['default_value']);
				}
			}
		}
		$custom = $response->addchild('Custom');
		$metatableID = $mdb->queryOne("select meta_table_id from meta_table where content_type_id = '". MOVIE ."'");
		$custom->addAttribute('metaTableID', $metatableID);

		echo $response->asXML();
	break;

	case 'user_category'://nps용 사용자별 카테고리 매핑
		$user_id= $xml->GetInfo['user_id'];

		$categories = $mdb->queryAll("select c.* from bc_category c , user_mapping u where c.category_id=u.category_id and c.parent_id ='0' and u.user_id='$user_id'  order by c.category_title ");

		echo $mdb->last_query;
		$result = $response->addChild('Result');
		$result->addAttribute('success', 'true');
		$result->addAttribute('msg', 'ok');

		foreach( $categories as $category ){
			$cate = $result->addChild("Category");
			$cate->addAttribute('title', $category['category_title']);
			$cate->addAttribute('id', $category['category_id']);
			$cate->addAttribute('parentid', $category['parent_id']);

			$cate->addAttribute('hasChild', 'false');

		}
		echo $response->asXML();
	break;

	default:
		echo "존재하지 않는 유형입니다($info)";
	break;
}
//@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/get_meta_'.date('Ymd').'.log', 'get_meta end' . "\n", FILE_APPEND);
//@file_put_contents('../log/get_meta_send.'.date('Ymd').'.html $response->asXML()."\n", FILE_APPEND);

/*
<Request>
 <GetInfo kind="metactrl" />
</Request>

				if($conFd['type'] == 'combo'){
					$combo_op = trim($conFd['default_value']);
					$check_op = substr($combo_op, -1);
					if($check_op != ';'){
						$combo_op = $combo_op.';';
					}
					$combo_op = preg_replace("/\s+/","",$combo_op);
					$combo_op = explode(';', $combo_op, -1);

					for($i=0; $i<count($combo_op); $i++){
						$item = $add_sysmeta->addchild('Item', $combo_op[$i]);
						$item->addAttribute('id', $i);
					}
				}

*/

function findBaseContent($baseContents, $baseContentId)
{
	foreach($baseContents as $baseContent) {
		if ($baseContent['bs_content_id'] == $baseContentId) {
			return $baseContent;
		}
	}
	return null;
}
