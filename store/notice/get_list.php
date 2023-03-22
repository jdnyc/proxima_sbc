<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');
session_start();
fn_checkAuthPermission($_SESSION);

$type = $_POST['type'];
if(empty($type)){
	$type = $_GET['type'];
}
$data = array();

try
{
	$user_id = $_SESSION['user']['user_id'];
    $is_admin = $_SESSION['user']['is_admin'];
    if($is_admin != 'Y') $is_admin = 'N';

	if(is_null($user_id) )
	{
		throw new Exception(_text('MSG02041'));//세션이 만료되어 로그인이 필요합니다.
	}

	switch($type)
	{
		case 'group':

			if( false )
			{
				$query = "
				select
					m.member_group_name name, m.member_group_id value
				from
				path_mapping p,
				bc_category c,
				bc_member_group m
				where
					c.category_id=p.category_id
				and m.member_group_id=p.member_group_id
				order by c.show_order";
			}
			else
			{
				$query = "
				select
					m.member_group_name name, m.member_group_id value
				from
				user_mapping u ,
				path_mapping p,
				bc_category c,
				bc_member_group m
				where
				u.category_id=p.category_id
				and c.category_id=u.category_id
				and u.user_id='$user_id'
				and m.member_group_id=p.member_group_id
				order by c.show_order
				";
			}

			$data = $db->queryAll($query);


		break;

		case 'user':

			if( false )
			{
				$query = "
				select
					m.user_id value, m.user_nm name
				from
				bc_member_group_member mgm,
				bc_member m,
				path_mapping p
				where m.member_id=mgm.member_id
				and p.member_group_id=mgm.member_group_id
				";
			}
			else
			{
				$query = "
				select
					m.user_id value, m.user_nm name
				from
				bc_member_group_member mgm,
				bc_member m,
				( select
					p.member_group_id
				from
				user_mapping u ,
				path_mapping p
				where
				u.category_id=p.category_id
				and u.user_id='$user_id' ) mem
				where
				mem.member_group_id=mgm.member_group_id
				and m.member_id=mgm.member_id
				";
			}

			$data = $db->queryAll($query);
		break;


		case 'insert':
			$meta = json_decode($_POST['meta'], true );
			$title = $db->escape($meta['title']);
			$contents = $db->escape($meta['contents']);
			// 공지팝업 여부
			$noticePopupAt = 'N';
			if(isset($meta['notice_popup_at'])) {
				$noticePopupAt = $meta['notice_popup_at'];
			}

            if(trim($title) == '') {
                throw new Exception(_text('MSG00090'));//제목을 입력해주세요
            }

			$target_type = $meta['target_type'];// notice type
			$target_list = $meta['target_list'];// notice Recipients
			$to_list_ids = $meta['to_list_ids'];// notice Recipents ids(user : member_id, group : group_id)
			$notice_id = getSequence('NOTICE_SEQ');
			if( $target_type == 'all' ){
				$fst_order = 1;
			}else{
				$fst_order = 0;
			}

			//2016-02-24 INSERT QUERY 수정
			$insert_data = array(
				'NOTICE_ID'				=>	 $notice_id,
				'NOTICE_TITLE'		=>	 $title,
				'NOTICE_CONTENT_C'	=>	 ':notice_content_c',
				'CREATED_DATE'		=>	 date("YmdHis"),
				'NOTICE_TYPE'			=>	 $target_type,
				'FROM_USER_ID'		=> $_SESSION['user']['user_id'],
				'FST_ORDER'			=> $fst_order,
				'NOTICE_START'		=> date("Ymd", strtotime($meta['start_date']))."000000",
				'NOTICE_END'			=> date("Ymd", strtotime($meta['end_date']))."235959",
				'NOTICE_POPUP_AT'	=> $noticePopupAt
			);

			 $query_insert_notice = $db->insert('BC_NOTICE', $insert_data, 'not exec');
			 $query_insert_notice_clob = str_replace("':notice_content_c'", ":notice_content_c", $query_insert_notice);


			 $db->clob_exec($query_insert_notice_clob,':notice_content_c',$contents, -1);

			if( $target_type == 'all' ){
			}else{
				$user_info = explode(',', $meta['to_user_ids']);
				if( count($user_info) > 0 ){
					foreach( $user_info as $info_id ){
						$noatice_recipient_id = getSequence('SEQ_BC_NOTICE_RECIPIENT_ID');
						$insert_data_user = array(
							'NOTICE_RECIPIENT_ID'	=>	 $noatice_recipient_id,
							'NOTICE_ID'					=>	 $notice_id,
							'MEMBER_ID'				=>	 $info_id
						);

						 $db->insert('BC_NOTICE_RECIPIENTS', $insert_data_user);
					}
				}

				$group_info = explode(',', $meta['to_group_ids']);
				if( count($group_info) > 0 ){
					foreach( $group_info as $info_id_group ){
						$noatice_recipient_id = getSequence('SEQ_BC_NOTICE_RECIPIENT_ID');
						$insert_data_group = array(
							'NOTICE_RECIPIENT_ID'	=>	 $noatice_recipient_id,
							'NOTICE_ID'					=>	 $notice_id,
							'MEMBER_GROUP_ID'		=>	 $info_id_group
						);

						 $db->insert('BC_NOTICE_RECIPIENTS', $insert_data_group);
					}
				}
			}

			 if(isset($_FILES) && $_FILES['FileAttach']['size'] > 0){
				$fileid = "ATTACH_".$notice_id;
				$extension = pathinfo($_FILES['FileAttach']['name'], PATHINFO_EXTENSION );

				$filename = $fileid.'.'.$extension;
				$tmp_filename = $_FILES['FileAttach']['tmp_name'];
				$file_path = stripslashes(NOTICE_ATTACH_ROOT);
				$new_name = $file_path.'/'.$filename;

				if(move_uploaded_file($tmp_filename, $new_name)){
					$noatice_file_id = getSequence('SEQ_BC_NOTICE_FILES');
					$insert_data_file = array(
						'NOTICE_FILE_ID'	=>	 $noatice_file_id,
						'NOTICE_ID'			=>	 $notice_id,
						'FILE_NAME'			=>	 $filename,
						'FILE_PATH'			=>	 $db->escape($_FILES['FileAttach']['name'])
					);
					 $db->insert('BC_NOTICE_FILES', $insert_data_file);
				} else {
					//throw new Exception($tmp_filename.'파일 등록 실패'.$new_name.' : '.$_FILES['FileAttach']['size']);
					throw new Exception(print_r($_FILES, true));
				}
			 }
			//$msg = _text('MSG00104');//'등록이 완료되었습니다.';
		break;

		case 'admin_list':
				$start = empty($_POST['start']) ? 0 : $_POST['start'];
				$limit = empty($_POST['limit']) ? 20 : $_POST['limit'];
				$order = " ORDER BY  NOTICE.CREATED_DATE DESC ";
				$array_where = array();
				if ($_POST['search_text'] != null) {
					array_push($array_where, " (NOTICE_TITLE LIKE '%".$_POST['search_text']."%' or NOTICE_CONTENT LIKE '%".$_POST['search_text']."%' or NOTICE_CONTENT_C LIKE '%".$_POST['search_text']."%')");
				}
				//array_push($array_where, " NOTICE.CREATED_DATE > TO_CHAR(SYSDATE -180, 'YYYYMMDD')");
				$filter_admin_yn = "(
										('Y' = '$is_admin')
										OR
										(
										'N' = '$is_admin'
										AND		NOTICE_ID IN (
																SELECT	N2.NOTICE_ID
																FROM	BC_NOTICE N2
																		LEFT OUTER JOIN BC_NOTICE_RECIPIENTS R1 ON(R1.NOTICE_ID = N2.NOTICE_ID)
																WHERE 	(
																		N2.NOTICE_TYPE	= 'all'
																		OR
																		(N2.NOTICE_TYPE	= 'group' AND R1.MEMBER_GROUP_ID IN (
																											SELECT	MEMBER_GROUP_ID
																											FROM	BC_MEMBER_GROUP_MEMBER
																											WHERE	MEMBER_ID = (SELECT MEMBER_ID FROM BC_MEMBER WHERE USER_ID = '".$user_id."')
																											))
																		OR
																		(N2.NOTICE_TYPE	= 'user' AND R1.MEMBER_ID = (SELECT MEMBER_ID FROM BC_MEMBER WHERE USER_ID = '".$user_id."'))
																		)
																)
										)
									)";
				array_push($array_where, $filter_admin_yn);

				$query ="select
							1 AS READ_FLAG,
							NOTICE.NOTICE_ID,
							NOTICE.NOTICE_TITLE,
							NOTICE.NOTICE_CONTENT,
							NOTICE.CREATED_DATE,
							NOTICE.NOTICE_TYPE,
							NOTICE.FROM_USER_ID,
							NOTICE.TO_USER_ID,
							NOTICE.MEMBER_GROUP_ID,
							NOTICE.DEPCD,
							NOTICE.FST_ORDER,
							NOTICE.NOTICE_START,
							NOTICE.NOTICE_END,
							NOTICE.NOTICE_CONTENT_C,
							NOTICE.NOTICE_POPUP_AT,
							(
								SELECT	COUNT(NOTICE_ID) AS FILE_C
								FROM		BC_NOTICE_FILES
								WHERE	NOTICE_ID = NOTICE.NOTICE_ID
							) AS FILE_FLAG,
				(select mg.member_group_name from bc_member_group mg where mg.member_group_id=NOTICE.member_group_id ) member_group_name,
				(select m.user_nm from bc_member m where m.user_id=NOTICE.from_user_id ) from_user_nm,
				(select m.user_nm from bc_member m where m.user_id=NOTICE.to_user_id ) to_user_nm
				from bc_notice NOTICE";

				if ( count($array_where) != 0){
					$v_where = " WHERE ".join(' AND ', $array_where);
				}

				$new_total = 0;

				$total = $db->queryOne(" select count(*) from ( $query $v_where) cnt ");
				$db->setLimit($limit, $start);
				$db->setLoadNEWCLOB(true);
				//$data = $db->queryAll($query.$order);
				$data = $db->queryAll($query.$v_where.$order);

				foreach($data as $key => $val)
				{
					if($val['notice_type'] == 'all')
					{
						$data[$key]['notice_type_text'] = _text('MN00008');//'전체';
					}
					else if($val['notice_type'] == 'group')
					{
						$data[$key]['notice_type_text'] = _text('MN00111');//'그룹';
					}
					else if($val['notice_type'] == 'user')
					{
						$data[$key]['notice_type_text'] = _text('MN02161');//'개인';
					}
				}

				echo json_encode(array(
					'success' => true,
					'data' => $data,
					'total' => $total,
					'new_total' => $new_total
				));
				exit;
		break;

		case 'list':

			$start = empty($_POST['start']) ? 0 : $_POST['start'];
			$limit = empty($_POST['limit']) ? 20 : $_POST['limit'];
            $order = " ORDER BY  NOTICE.CREATED_DATE DESC ";
            /* HUIMAI, log에서 count조회하는것 제거
            (
                SELECT	COUNT(LOG_ID) AS LOG_ID_C
                FROM		BC_LOG
                WHERE	ACTION = 'read_notice'
                    --AND	CONTENT_ID= TO_CHAR(NOTICE.NOTICE_ID, '')
                    AND	CONTENT_ID= NOTICE.NOTICE_ID
                    AND USER_ID = '".$user_id."'
            ) */
			$query = "
				SELECT	1 AS READ_FLAG,
							(
								SELECT	COUNT(NOTICE_ID) AS FILE_C
								FROM		BC_NOTICE_FILES
								WHERE	NOTICE_ID = NOTICE.NOTICE_ID
							) AS FILE_FLAG,
							NOTICE.NOTICE_ID,
							NOTICE.NOTICE_TITLE,
							NOTICE.NOTICE_CONTENT,
							NOTICE.CREATED_DATE,
							NOTICE.NOTICE_TYPE,
							NOTICE.FROM_USER_ID,
							COALESCE(M.USER_NM, '관리자')  AS FROM_USER_NM,						
							NOTICE.TO_USER_ID,
							NOTICE.MEMBER_GROUP_ID,
							NOTICE.DEPCD,
							NOTICE.FST_ORDER,
							NOTICE.NOTICE_START,
							NOTICE.NOTICE_END,
							NOTICE.NOTICE_CONTENT_C,
							NOTICE.NOTICE_POPUP_AT
				FROM		BC_NOTICE NOTICE
							LEFT OUTER JOIN BC_MEMBER M ON M.USER_ID = NOTICE.FROM_USER_ID
			";

			//$db->setLoadCLOB(true);
            //$list = $db->queryRow("select x.content_id,x.link_cms_id,x.xml.getCLOBVal() xml from LINK_CMS x where task_id='$task_id'");
			$array_where = array();
			/**
			 * 사용자 요구에 따라 홈화면에서도 지난 공지사항 볼 수 있도록 수정 -2018.02.06 Alex
			*/
			//array_push($array_where, " 1 = 1");
			array_push($array_where, " NOTICE.NOTICE_START <= '".date("YmdHis")."' AND NOTICE.NOTICE_END >= '".date("YmdHis")."' ");
			//array_push($array_where, " NOTICE.CREATED_DATE > TO_CHAR(SYSDATE -180, 'YYYYMMDD')");

			if(false){
			}else{
				$user_group = join(',', $_SESSION['user']['groups']);
				$array_query = array();
				$query_all = "
					SELECT	NOTICE_ID
					FROM		BC_NOTICE
					WHERE	NOTICE_TYPE = 'all'
				";
				array_push($array_query, $query_all);
				if( !empty($user_group) ){
					$query_group = "
						SELECT  N.NOTICE_ID
						FROM    BC_NOTICE N, BC_NOTICE_RECIPIENTS R, BC_MEMBER_GROUP G
						WHERE   G.MEMBER_GROUP_ID = R.MEMBER_GROUP_ID
						AND     R.NOTICE_ID = N.NOTICE_ID
						AND     R.MEMBER_GROUP_ID IN(".$user_group.")
					";
					array_push($array_query, $query_group);
				}
				if( !empty($user_id) ){
					$query_user = "
						SELECT  N.NOTICE_ID
						FROM		BC_NOTICE N, BC_NOTICE_RECIPIENTS R, BC_MEMBER M
						WHERE   M.MEMBER_ID = R.MEMBER_ID
						AND		R.NOTICE_ID = N.NOTICE_ID
						AND		M.USER_ID = '".$user_id."'
					";
					array_push($array_query, $query_user);
				}

				$where = "
					   NOTICE.NOTICE_ID IN(
									".join(' UNION ALL ',$array_query)."
								)
				";
				array_push($array_where, $where);
      }
      
      $row_query = $query." WHERE ".join(' AND ', $array_where);

			$total = $db->queryOne("
				SELECT	COUNT(*) CNT
				FROM		(".$row_query.")T
      ");
      
      
      $db->setLoadNEWCLOB(true);      
      $db->setLimit($limit, $start);
			$data = $db->queryAll($row_query.$order);

			echo json_encode(array(
					'success' => true,
					'data' => $data,
					'total' => $total,
					'query' => $row_query.$order,
					'new_total' => $new_total
			));
			exit;

		break;

		case 'edit':
			$meta = json_decode($_POST['meta'], true);
			$notice_id = $_POST['notice_id'];
			$notice_title = $db->escape($meta['title']);
			$notice_content = $db->escape($meta['contents']);

			// 공지팝업 여부
			$noticePopupAt = 'N';
			if(isset($meta['notice_popup_at'])) {
				$noticePopupAt = $meta['notice_popup_at'];
			}

            if(trim($notice_title) == '') {
                throw new Exception(_text('MSG00090'));//제목을 입력해주세요
            }
			$query = "
				UPDATE	BC_NOTICE	SET
					NOTICE_TITLE = '".$notice_title."',
					NOTICE_CONTENT_C = :notice_content_c,
					NOTICE_TYPE = '".$meta['target_type']."',
					NOTICE_START = '".date('Ymd', strtotime($meta['start_date'])).'000000'."',
					NOTICE_END = '".date('Ymd', strtotime($meta['end_date'])).'235959'."',
					NOTICE_POPUP_AT = '".$noticePopupAt."'
				WHERE	NOTICE_ID = ".$_POST['notice_id']."
			";
			$query_delete_to = "
				DELETE	FROM	 BC_NOTICE_RECIPIENTS
				WHERE	NOTICE_ID = ".$notice_id."
			";
			$db->exec($query_delete_to);
			if( empty($_POST['fileAttachFakePath']) ){
				$query_delete_file = "
					DELETE	FROM	 BC_NOTICE_FILES
					WHERE	NOTICE_ID = ".$notice_id."
				";
				$db->exec($query_delete_file);
			}

			if(isset($_FILES) && $_FILES['FileAttach']['size'] > 0){

				$query_delete_file = "
					DELETE	FROM	 BC_NOTICE_FILES
					WHERE	NOTICE_ID = ".$notice_id."
				";
				$db->exec($query_delete_file);
				$fileid = "ATTACH_".$notice_id;
				$extension = pathinfo($_FILES['FileAttach']['name'], PATHINFO_EXTENSION );

				$filename = $fileid.'.'.$extension;
				$tmp_filename = $_FILES['FileAttach']['tmp_name'];
				$file_path = stripslashes(NOTICE_ATTACH_ROOT);
				$new_name = $file_path.'/'.$filename;

				if(move_uploaded_file($tmp_filename, $new_name)){
					$noatice_file_id = getSequence('SEQ_BC_NOTICE_FILES');
					$insert_data_file = array(
						'NOTICE_FILE_ID'	=>	 $noatice_file_id,
						'NOTICE_ID'			=>	 $notice_id,
						'FILE_NAME'			=>	 $filename,
						'FILE_PATH'			=>	 $db->escape($_FILES['FileAttach']['name'])
					);
					 $db->insert('BC_NOTICE_FILES', $insert_data_file);
				} else {
					throw new Exception($tmp_filename.'파일 등록 실패'.$new_name);
					//throw new Exception(print_r($_FILES, true));
				}
			}

			$user_info = explode(',', $meta['to_user_ids']);
			if( count($user_info) > 0 ){
				foreach( $user_info as $info_id ){
					$noatice_recipient_id = getSequence('SEQ_BC_NOTICE_RECIPIENT_ID');
					$insert_data_user = array(
						'NOTICE_RECIPIENT_ID'	=>	 $noatice_recipient_id,
						'NOTICE_ID'					=>	 $notice_id,
						'MEMBER_ID'				=>	 $info_id
					);

					 $db->insert('BC_NOTICE_RECIPIENTS', $insert_data_user);
				}
			}

			$group_info = explode(',', $meta['to_group_ids']);
			if( count($group_info) > 0 ){
				foreach( $group_info as $info_id_group ){
					$noatice_recipient_id = getSequence('SEQ_BC_NOTICE_RECIPIENT_ID');
					$insert_data_group = array(
						'NOTICE_RECIPIENT_ID'	=>	 $noatice_recipient_id,
						'NOTICE_ID'					=>	 $notice_id,
						'MEMBER_GROUP_ID'		=>	 $info_id_group
					);

					 $db->insert('BC_NOTICE_RECIPIENTS', $insert_data_group);
				}
			}

			$exec_query = $db->clob_exec($query,':notice_content_c',$notice_content, -1);

			//$r = $db->exec($query);
			$msg = _text('MSG00087');//'수정이 완료되었습니다.';
		break;

		case 'download':
			$query_info = "
				SELECT	*
				FROM		BC_NOTICE_FILES
				WHERE	NOTICE_ID = ".$_GET['notice_id']."
			";
			$info_notice = $db->queryRow($query_info);
			$server_filename = NOTICE_ATTACH_ROOT.$info_notice['file_name'];

			//echo $server_filename;exit;
			$dw_file_name = $info_notice['file_path'];;



			// send_attachment($filename, $server_filename, $expires = 0, $speed_limit = 0)

			//if(eregi("(MSIE 5.5|MSIE 6.0|MSIE 7.0|MSIE 8.0|MSIE 11.0)", $_SERVER["HTTP_USER_AGENT"])
							//&& !eregi("(Opera|Netscape)", $_SERVER["HTTP_USER_AGENT"])) {
					//$dw_file_name = iconv("UTF-8", "EUC-KR", $dw_file_name);
					//header("Content-Description: File Transfer");
					//header("Content-Type: application/octet-stream");
					//header("Content-Length: ".filesize($server_filename));
					//header("Content-Disposition: attachment; filename=\"".$dw_file_name."\"");
					//header("Content-Transfer-Encoding: Binary");
					//header("Pragma: no-cache; public");
					//header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
					//header("Expires: 0");
			//}
			//else
			{
					header("Content-Description: File Transfer");
					header("Content-Type: file/unknown");
					header("Content-Length: ".filesize($server_filename));
					header("Content-Disposition: attachment; filename=\"".$dw_file_name."\"");
					header("Content-Description: PHP Generated Data");
					header("Pragma: no-cache; public");
					header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
					header("Expires: 0");
			}

			ob_clean();
			flush();
			readfile($server_filename);
			//$result = send_attachment($info_notice['file_path'], $server_filename);
			//if( !$result ){
				//throw new Exception(_text('MSG02012'));//다운로드 할 파일을 찾을 수 없습니다.
			//}
		break;

        case 'manual':
            $manual_type = $_GET['manual_type'];
            $array_manual = array(
                'cms'	=>	 array('manual_cms.pdf',_text('MN02210'), 'pdf'),
                'filer'	=>	 array('manual_filer.pptx',_text('MN02211'), 'pptx'),
                'nle'	=>	 array('manual_nle.doc',_text('MN02212'), 'doc'),
                'chromium' => array('mini_installer.exe','Chromium 브라우저', 'exe')
            );
            $server_filename = $_SERVER['DOCUMENT_ROOT'].'/../resources/files/manual/'.$array_manual[$manual_type][0];
            $name_download = preg_replace("/\s+/", "", $array_manual[$manual_type][1]).'.'.$array_manual[$manual_type][2];
            send_attachment($name_download, $server_filename);
            exit;
		break;

		default:
		break;
	}

	echo json_encode(array(
		'success' => true,
		'data' => $data,
		'msg' => $msg,
		'total' => $total
	));
}
catch(Exception $e)
{
	echo json_encode(array(
		'success' => false,
		'msg' => $e->getMessage(),
		'data' => $e->getMessage()
	));
}

?>