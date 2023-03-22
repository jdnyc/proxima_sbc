<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');

$receive_xml = file_get_contents('php://input');

@file_put_contents('../log/update_system_meta_'.date('Ymd').'.html', date("Y-m-d H:i:s\t").$receive_xml."\n\n", FILE_APPEND);

$response = new SimpleXMLElement("<?xml version=\"1.0\" encoding=\"UTF-8\"?> \n<Response />");

if(empty($receive_xml)){
	$error = $response->addChild('Result');
	$error->addAttribute('success', 'false');
	$error->addAttribute('msg', '요청 값이 없습니다.');
	die($response->asXML());
}

libxml_use_internal_errors(true);
$xml = simplexml_load_string($receive_xml);
if(!$xml){
  foreach(libxml_get_errors() as $error){
    $err_msg .= $error->message . "\t";
  }
  $result = $response->addChild('Result');
  $result->addAttribute('success', 'false');
  $result->addChild('msg', 'xml 파싱 에러: '.$err_msg);

  echo $response->asXML();
  exit;
}
/*
<Request>
	<RegistMeta type="transcoder">
		<Content>
			<Title>C0007_XDCAMHD.mxf</Title>
		</Content>
		<Medias>
			<Media type="original" path="C0007_XDCAMHD.mxf" filesize="184034376" task_id="918"/>
		</Medias>
		<System contenttypeid="506" contenttypename="동영상">
			<MetaCtrl contentfieldid="507" name="재생길이">00:00:24.49</MetaCtrl>
			<MetaCtrl contentfieldid="508" name="비디오 비트레이트">50000 kb/s</MetaCtrl>
			<MetaCtrl contentfieldid="615" name="해상도">1920x1080 [PAR 1:1 DAR 16:9]</MetaCtrl>
			<MetaCtrl contentfieldid="616" name="프레임레이트">29.97 Frame/s</MetaCtrl>
			<MetaCtrl contentfieldid="58172" name="비디오 코덱">mpeg2video</MetaCtrl>
			<MetaCtrl contentfieldid="58173" name="오디오 코덱">pcm_s24le</MetaCtrl>
			<MetaCtrl contentfieldid="58192" name="오디오 비트레이트">1152 kb/s</MetaCtrl>
		</System>
	</RegistMeta>
</Request>
//////사운드 일때
<Request>
	<RegistMeta type="transcoder">
		<Content>
			<Title>월드뉴스-1부(101220월).mp2</Title>
		</Content>
		<Medias>
			<Media type="original" path="월드뉴스-1부(101220월).mp2" filesize="29328769" task_id="4142"/>
		</Medias>
		<System contentTypeID="515" contentTypeName="사운드">
			<MetaCtrl contentFieldID="57052" name="재생길이">00:10:11.01</MetaCtrl>
			<MetaCtrl contentFieldID="58210" name="오디오 비트레이트">384 kb/s</MetaCtrl>
			<MetaCtrl contentFieldID="57054" name="오디오 코덱">mp2</MetaCtrl>
		</System>
	</RegistMeta>
</Request>

*/
$regimetadata = $xml->RegistMeta;
$type_from = $xml->RegistMeta['type'];
$task_id = $xml->RegistMeta->Medias->Media['task_id'];
$size = $xml->RegistMeta->Medias->Media['filesize'];
$system = $xml->RegistMeta->{'System'};
$media_type = $xml->RegistMeta->Medias;
$title = $xml->RegistMeta->Content->Title;
$bs_content_id = (int)$xml->RegistMeta->{'System'}['contentTypeID'];
$sys_meta_title = (string)$xml->RegistMeta->{'System'}['contentTypeName'];


/*
20101207
박정근
타겟 경로 변경
*/
$ori_path = $xml->RegistMeta->Medias->Media['path'];

$media_id = $db->queryOne("select media_id from bc_task where task_id = $task_id");


## 1080i(리랩핑한) 콘텐츠 아이디일 경우에는 트랜스코딩에서 보낸 xml을 쓰지않는다.
$query =  "select m.content_id, t.media_id from bc_media m, bc_task t where m.media_id = t.media_id and t.task_id = $task_id";
$get_ids = $db->queryRow($query);
$content_id = $get_ids['content_id'];
$media_type = $get_ids['media_type'];
if($media_type == 'thumb'){
	$regist = $response->addChild('Result');
	$regist->addAttribute('success', 'true');
	$regist->addAttribute('msg', 'continue');

	die($response->asXML());
}


	try
	{
		$sys_IdtoValueArray = array();

			foreach($regimetadata as $regimeta)
			{
				foreach($regimeta as $item)
				{
					foreach($item as $v)
					{
						$c .= ' '.strip_tags($v);
					}
				}

				unset($c);

				//2012.02.06 김형기 수정
				//변경된 워크플로우에 적합하도록 수정
				//				$ori_path = $db->queryOne("select source from bc_task where task_id = $task_id");
				//
				//				$query = "select media_id from bc_media where content_id = $content_id and media_type ='original' and deleted_date is null and flag is null and path = '{$ori_path}'";
				//				$find_ori_media_id = $db->queryOne($query);

				//				if($find_ori_media_id != '')
				//				{
				//					$update_size = $db->exec("update bc_media set filesize = '$size' where media_id = $find_ori_media_id");
				//				}

				// 시퀀스와 이미지 하드코딩 2012.01.25 by 허광회//
				if($bs_content_id == IMAGE)
				{
					$query ="select bc.bs_content_id from
					bc_content bc where bc.content_id = (select content_id from bc_media where media_id in (select t.media_id from bc_task t where t.task_id = $task_id))";
					
					$input_bs_content_id = $db->queryOne($query);
				}

				if($input_bs_content_id == SEQUENCE)
				{
					//시퀀스로 등록해야한다.
					foreach ( $system as $sysMeta )
					{
						foreach ( $sysMeta as $sMeta => $value)
						{
							$meta_title = $value['name'];
							$value = addslashes($value);
							//$value = $db->escape($value);

							$query ="select sys_meta_field_id from bc_sys_meta_field where sys_meta_field_title = '$meta_title' and bs_content_id = $input_bs_content_id";

							$sys_meta_field_id = $db->queryOne($query);


							if($sys_meta_field_id)
							{
								$sys_IdtoValueArray [$sys_meta_field_id] = $value ;


							}
						}
					}
				}
				else
				{//콘텐츠바울에 메타데이터 입력
				
					foreach ( $system as $sysMeta )
					{
						foreach ( $sysMeta as $sMeta => $value )
						{

							$conFd_id = (string)$value['contentFieldID'];								
							$value = (string)$value;							
							$value = addslashes($value);
							$sys_IdtoValueArray [$conFd_id] = $value ;

						}
					}
				}

                //2019-03-27 이승수, 이미지는 트랜스코딩과 썸네일을 같은 작업으로 돌기에,
                //프록시의 미디어정보가 시스템메타에 들어가는 증상이 있음.
                //원본파일의 미디어정보만 시스템메타에 넣도록(proxy가 생성되면 원본이라고 판단...)
                $is_original = $db->queryRow("select * from bc_media where media_id=".$media_id);
                if($is_original['media_type'] == 'proxy') {

                    $filePath = $xml->RegistMeta->Medias->Media['path'];
                    $fileExt = null;
                    if( !empty($filePath) ){
                        $filePathList = explode('.', $filePath);
                        $fileExt = array_pop($filePathList);
                        $fileExt = strtoupper($fileExt);
                    }
                    if(!empty($fileExt)){
                        $sys_IdtoValueArray['266'] = $fileExt;
                    }

                    //미디어정보 코드화
                    if( $bs_content_id == MOVIE ){                               
                        $resolutionCode = resolutionCustom( $sys_IdtoValueArray['615'], $sys_IdtoValueArray['58172'], $fileExt);
                        $r = $db->update("BC_CONTENT_STATUS",[ 'resolution' => $resolutionCode ] , "content_id=".$content_id );                            
                    }
                    insertSysMeta($sys_IdtoValueArray, $bs_content_id , $content_id );
                }

				//2010/12/07 :: 미디어테이블의 오리지날 타입은 사이즈를 무조건 업데이트 시킴
				//2012.02.06 두 번 업데이트 하는것
				//$update_original_size = "update bc_media set filesize = '$size' where content_id = '$content_id' and media_type = 'original'";
				//$r = $db->exec($update_original_size);



				$regist = $response->addChild('Result');
				$regist->addAttribute('success', 'true');
				$regist->addAttribute('msg', 'ok');
				@file_put_contents('../log/update_system_meta_'.date('Ymd').'.html', date("Y-m-d H:i:s\t").$response->asXML()."\n\n", FILE_APPEND);
				die($response->asXML());
			}
	} catch(Exception $e) {
		$msg = $e->getMessage();
		switch($e->getCode())
		{
			case ERROR_QUERY:
				$msg .= $db->last_query;
				_debug(basename(__FILE__), $msg);
			break;
		}

		$error = $response->addChild('Result');
		$error->addAttribute('success', 'false');
		$error->addAttribute('msg', $msg);
		@file_put_contents('../log/update_system_meta_'.date('Ymd').'.html', date("Y-m-d H:i:s\t").$response->asXML()."\n\n", FILE_APPEND);
		die($response->asXML());
	}

	function check_sysmeta_field($content_id ,$sys_meta_field_id)
	{
		global $db;
		$query = "select content_id from bc_sys_meta_value where content_id = {$content_id} and sys_meta_field_id = {$sys_meta_field_id}";
		$r=$db->queryOne($query);
		if(!$r) return 0;
		else return $r;
	}

	function insertSysMeta($metaValues, $meta_table_id , $content_id ){
		global $db;
		$table_type = 'sys';
		$fieldKey = array();
		$fieldValue = array();
		//필드 목록 배열
		$metaFieldInfo = MetaDataClass::getMetaFieldInfo ($table_type , $meta_table_id );
		//필드의 id => name
		$fieldNameMap = MetaDataClass::getFieldIdtoNameMap($table_type , $meta_table_id );
		//테이블 명
		$tablename = MetaDataClass::getTableName($table_type, $meta_table_id );
        //기본 데이터유형 변환
		$metaValues = MetaDataClass::getDefValueRender($table_type , $meta_table_id , $metaValues);

		foreach($fieldNameMap as $usr_meta_field_id => $name )
		{
			$value = $metaValues[$usr_meta_field_id];
			$value = $db->escape($value);
			//공백인경우도 쿼리 생성하도록 
			array_push($fieldKey, $name );
			array_push($fieldValue, $value);
		}
        
		if( MetaDataClass::isNewMeta($table_type, $meta_table_id , $content_id) ){
			//신규 등록
			array_push($fieldKey, 'sys_content_id' );
			array_push($fieldValue, $content_id );
			$query = $db->InsertQuery($tablename ,$fieldKey, $fieldValue);


		}else{
			//업데이트
			$query = $db->UpdateQuery($tablename ,$fieldKey, $fieldValue, "sys_content_id='$content_id'" );
		}
		$r = $db->exec($query);
		return true;
	}

?>