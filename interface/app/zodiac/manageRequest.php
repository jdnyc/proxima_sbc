<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/interface.class.php');
$server->register('manageRequest',
	array(
		'action' => 'xsd:string',
		'xml' => 'xsd:string',
		'fileList' => 'xsd:string',
		'ord_div_cd' => 'xsd:string'
	),
	array(
		'success' => 'xsd:string',
		'msg' => 'xsd:string',
		'code' => 'xsd:string'
	),
	$namespace,
	$namespace.'#manageRequest',
	'rpc',
	'encoded',
	'manageRequest'
);

function manageRequest($action, $xml, $fileList, $ord_div_cd) {
	global $db, $server;

	try{
        
        InterfaceClass::_LogFile(__FUNCTION__.'-'.date('Y-m-d').'.log','manageRequest request', file_get_contents('php://input') );

		//xml 항목 변환
		if(empty($xml)) {
			throw new Exception ('invalid request xml', 101 );
        }    

		libxml_use_internal_errors(true);
		$xml_data = simplexml_load_string($xml);
		$filelist_data = simplexml_load_string($fileList);
		
		$info = $xml_data->info;
		$edls = $xml_data->data;
		$videos = $xml_data->videodata;
		$graphics = $xml_data->graphicdata;
		$files = $filelist_data->data;
		
		$cur_date = date('YmdHis');
		
		// action 값에 따라 처리
		switch($action) {
			case 'add' :
				if(empty($info->id)) {
					throw new Exception ('invalid request id', 101 );
				}
				
				$ord_id = $info->id;
				
				if(in_array($ord_div_cd, array('001', '003', '005'))) {
					$ord_meta_cd = 'video';
				} else {
					$ord_meta_cd = 'graphic';
				}
				
				$ord_title = $db->escape($info->title);
				$ord_ctt = $db->escape($info->ctt);
				$ord_edl_title = $db->escape($info->edl_titl);
				
				// $r = $db->exec("
				// 		INSERT INTO TB_ORD
				// 			(ORD_ID, ORD_CTT, ORD_DIV_CD, INPUT_DTM, INPUTR_ID, ORD_META_CD, ORD_STATUS, TITLE, CH_DIV_CD,
				// 				ARTCL_ID, RD_ID, RD_SEQ, DEPT_CD, ARTCL_TITL)
				// 		VALUES
				// 			('$ord_id', '$ord_ctt', '$ord_div_cd', '$cur_date', '$info->request_user', '$ord_meta_cd', 'ready', '$ord_title', '$info->ch_div_cd',
				// 				'$info->artcl_id', '$info->rd_id', '$info->rd_seq', '$info->dept_cd', '$ord_edl_title')
                //     ");
                    
                $request = new \Api\Models\Request();
                $request->ord_id        = (string)$ord_id;
                $request->ord_ctt       = (string)$info->ctt;
                $request->ord_div_cd       = (string)$ord_div_cd;                

                $request->input_dtm     = $cur_date;
                $request->inputr_id     = (string)$info->request_user;
                
                $request->ord_meta_cd   = $ord_meta_cd;                
                $request->ord_status    = 'ready';

                $request->title         = (string)$info->title;
                $request->ch_div_cd     = (string)$info->ch_div_cd;
                $request->artcl_id      = (string)$info->artcl_id;
                $request->rd_id         = (string)$info->rd_id;
                $request->rd_seq        = (string)$info->rd_seq;
                $request->dept_cd       = (string)$info->dept_cd;
                $request->artcl_titl    = (string)$info->edl_titl;
                $request->save();

				//EDL 정보가 있을 경우 EDL 정보 입력
				if(!empty($edls)) {
					foreach($edls->record as $edl){
						$edl_ord_no = $edl->order_no;
						$edl_title = $db->escape($edl->title);
						$edl_media_id = $edl->media_id;
						$edl_in = $edl->in;
						$edl_out = $edl->out;
						
						$r = $db->exec("
								INSERT INTO TB_ORD_EDL
									(ORD_ID, MARK_IN, MARK_OUT, EDL_TITL, VIDEO_ID, ORD_NO)
								VALUES
									('$ord_id', '$edl_in', '$edl_out', '$edl_title', '$edl_media_id', $edl_ord_no)
							");
					}
				}
				// 파일정보가 있을 경우 파일 정보 입력
				if(!empty($files)) {
					foreach($files->record as $file){
						$file_path = $file->file_path;
						$file_nm = $db->escape($file->file_nm);
						
						$r = $db->exec("
								INSERT INTO TB_ORD_FILE
									(ORD_ID, FILE_NAME, FILE_PATH)
								VALUES
									('$ord_id', '$file_nm', '$file_path') 
							");
					}
				}
				
				// Video 정보가 있을 경우 Video 정보 입력
				if(!empty($videos)) {
					foreach($videos->record as $video){
						$video_id = $video->video_id;
						
						$r = $db->exec("
								INSERT INTO TB_ORD_VIDEO
									(ORD_ID, VIDEO_ID)
								VALUES
									('$ord_id', '$video_id')
								");
					}
				}
				// Graphic 정보가 있을 경우 Graphic 정보 입력
				if(!empty($graphics)) {
					foreach($graphics->record as $graphic){
						$graphic_id = $graphic->graphic_id;
					
						$r = $db->exec("
								INSERT INTO TB_ORD_GRPHC
									(ORD_ID, GRPHC_ID)
								VALUES
									('$ord_id', '$graphic_id')
							");
					}
				}
				
				$success = 'true';
				$msg = '의뢰정보 추가에 성공했습니다';
			break;
			case 'update' :
				if(empty($info->id)) {
					throw new Exception ('invalid request', 101 );
				}
				
				$ord_id = (string)$info->id;
				
				if(in_array($ord_div_cd, array('001', '003', '005'))) {
					$ord_meta_cd = 'video';
				} else {
					$ord_meta_cd = 'graphic';
				}
				
				$ord_ctt = $db->escape($info->ctt);
				$ord_title = $db->escape($info->title);
				$ord_edl_title = $db->escape($info->edl_titl);
				
				// $r = $db->exec("
				// 		UPDATE	TB_ORD
				// 		SET		ORD_CTT		= '$ord_ctt',
				// 				ORD_DIV_CD	= '$ord_div_cd',
				// 				UPDTR_ID	= '$info->usr_id',
				// 				UPDT_DTM	= '$cur_date',
				// 				ORD_META_CD	= '$ord_meta_cd',
				// 				TITLE		= '$ord_title',
				// 				CH_DIV_CD	= '$info->ch_div_cd',
				// 				ARTCL_ID	= '$info->artcl_id',
				// 				RD_ID		= '$info->rd_id',
				// 				RD_SEQ		= '$info->rd_seq',
				// 				DEPT_CD		= '$info->dept_cd',
				// 				ARTCL_TITL	= '$ord_edl_title'
				// 		WHERE	ORD_ID		= '$ord_id'
                //     ");

                                
                $query = \Api\Models\Request::query();
                $request = $query->find($ord_id);
                
                $request->ord_ctt       = (string)$info->ctt;
                $request->ord_div_cd       = (string)$ord_div_cd;

                $request->updt_dtm     = (string)$cur_date;
                $request->updtr_id     = (string)$info->usr_id;
                
                $request->ord_meta_cd   = (string)$ord_meta_cd;
                $request->title         = (string)$info->title;

                $request->ch_div_cd     = (string)$info->ch_div_cd;
                $request->artcl_id      = (string)$info->artcl_id;
                $request->rd_id         = (string)$info->rd_id;
                $request->rd_seq        = (string)$info->rd_seq;
                $request->dept_cd       = (string)$info->dept_cd;
                $request->artcl_titl    = (string)$info->edl_titl;

                $request->save();

				//EDL 정보가 있을 경우 EDL 정보 입력
				if(!empty($edls)) {
					$exist_edl = $db->queryOne("
									SELECT	COUNT(*)
									FROM	TB_ORD_EDL
									WHERE	ORD_ID = '$ord_id'
								");
					
					if($exist_edl > 0) {
						$r = $db->exec("
								DELETE FROM TB_ORD_EDL WHERE ORD_ID = '$ord_id'
							");
					}
					
					foreach($edls->record as $edl){
						$edl_ord_no = $edl->order_no;
						$edl_title = $db->escape($edl->title);
						$edl_media_id = $edl->media_id;
						$edl_in = $edl->in;
						$edl_out = $edl->out;
						
						$r = $db->exec("
								INSERT INTO TB_ORD_EDL
									(ORD_ID, MARK_IN, MARK_OUT, EDL_TITL, VIDEO_ID, ORD_NO)
								VALUES
									('$ord_id', '$edl_in', '$edl_out', '$edl_title', '$edl_media_id', $edl_ord_no)
							");
					}
				}
				// 파일정보가 있을 경우 파일 정보 입력
				if(!empty($files)) {
					$exist_file = $db->queryOne("
							SELECT	COUNT(*)
							FROM	TB_ORD_FILE
							WHERE	ORD_ID = '$ord_id'
							");
						
					if($exist_file > 0) {
						$r = $db->exec("
						DELETE FROM TB_ORD_FILE WHERE ORD_ID = '$ord_id'
						");
					}
					foreach($files->record as $file){
						$file_path = $file->file_path;
						$file_nm = $file->file_nm;
						
						$r = $db->exec("
								INSERT INTO TB_ORD_FILE
									(ORD_ID, FILE_NAME, FILE_PATH)
								VALUES
									('$ord_id', '$file_nm', '$file_path') 
							");
					}
				}
				
				// Video 정보가 있을 경우 Video 정보 입력
				if(!empty($videos)) {
					$exist_video = $db->queryOne("
								SELECT	COUNT(*)
								FROM	TB_ORD_VIDEO
								WHERE	ORD_ID = '$ord_id'
							");
					
					if($exist_video > 0) {
						$r = $db->exec("
							DELETE FROM TB_ORD_VIDEO WHERE ORD_ID = '$ord_id'
						");
					}
					foreach($videos->record as $video){
						$video_id = $video->video_id;
					
						$r = $db->exec("
								INSERT INTO TB_ORD_VIDEO
									(ORD_ID, VIDEO_ID)
								VALUES
									('$ord_id', '$video_id')
								");
					}
				}
				// Graphic 정보가 있을 경우 Graphic 정보 입력
				if(!empty($graphics)) {
					$exist_graphic = $db->queryOne("
							SELECT	COUNT(*)
							FROM	TB_ORD_GRPHC
							WHERE	ORD_ID = '$ord_id'
							");
					
					if($exist_file > 0) {
						$r = $db->exec("
							DELETE FROM TB_ORD_GRPHC WHERE ORD_ID = '$ord_id'
						");
					}
					foreach($graphics->record as $graphic){
						$graphic_id = $graphics->graphic_id;
					
						$r = $db->exec("
								INSERT INTO TB_ORD_GRPHC
									(ORD_ID, GRPHC_ID)
								VALUES
									('$ord_id', '$graphic_id')
								");
					}
				}
				
				$success = 'true';
				$msg = '의뢰정보  수정에 성공했습니다';
			break;
			case 'del' :
				// 삭제지만 실제로는 cancel
				if(empty($info->id)) {
					throw new Exception ('invalid request', 101 );
				}
				
				$ord_id = $info->id;
				
				$r = $db->exec("
						UPDATE	TB_ORD
						SET		ORD_STATUS = 'cancel',
								UPDTR_ID = '$info->usr_id',
								UPDTR_DTM = '$cur_date',
								ORD_REASON = '$info->reason',
						WHERE	ORD_ID = '$ord_id'
					");
				
				
				$success = 'true';
				$msg = '의뢰정보 삭제에 성공했습니다';
			break;
			default :
				throw new Exception ('invalid action', 101);
			break;
		}
        InterfaceClass::_LogFile(__FUNCTION__.'-'.date('Y-m-d').'.log','manageRequest return', $msg );
		return array(
				'success' => $success,
				'msg' => $msg
		);
	} catch(Exception $e) {
		$msg = $e->getMessage();
		$code = $e->getCode();
		$success = 'false';
        InterfaceClass::_LogFile(__FUNCTION__.'-'.date('Y-m-d').'.log','manageRequest Exception',$e->getMessage() );
		return array(
				'success' => $success,
				'msg' => $msg
		);
	}
}