<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
use \ProximaCustom\models\review\Review;

/*
$xml = <<<XML
<Request><Server_name>부조</Server_name>
<Ip_add>10.25.1.210</Ip_add>
<Ctr_port>557</Ctr_port>
<Ftp_port>2098</Ftp_port>
<ID Action = "added">%000ACAB</ID>
<XID>CL_0918모델링_트렌치</XID>
<Code>%000ACAB</Code>
<Video_Format>MPEG2 4:2:2</Video_Format>
<Video_N_VAlue>15</Video_N_VAlue>
<Video_M_Value>3</Video_M_Value>
<Video_Bit_Rate>12</Video_Bit_Rate>
<Start>00:00:00;40</Start>
<Duration>00:02:08;22</Duration><Hue>180</Hue><Audio_Tracks>4</Audio_Tracks>
<Aspect_Ratio>4:3</Aspect_Ratio><Kill_Date>2010-10-18</Kill_Date><TC_Type>DF</TC_Type>
<Record_DateTime>2010-09-19 오전 4:35:02</Record_DateTime>
<Codec_Recorded>192.168.92.12/CH4</Codec_Recorded><Video_Info>480i</Video_Info>
<User_Name>B-SCR</User_Name><Description></Description><Agency>셀렙샵</Agency>
<User_Field_1></User_Field_1><User_Field_2></User_Field_2><User_Field_3></User_Field_3>
<User_Field_4></User_Field_4><Modified_Timestamp>2010-09-18 오후 4:33:46</Modified_Timestamp>
<Video_QA_Status>None</Video_QA_Status></Request>
XML;

*/
$receive_xml = file_get_contents('php://input');
//$receive_xml = $xml;
$fileName = $_SERVER['DOCUMENT_ROOT'].'/log/add_harris_'.date('Ymd').'.html';
file_put_contents($fileName, date("H:i:s\t").$receive_xml."\r\n\r\n", FILE_APPEND);
$response = new SimpleXMLElement("<?xml version=\"1.0\" encoding=\"UTF-8\"?> \n<Response />");

if(empty($receive_xml))
{
	$error = $response->addChild('Result');
		$error->addAttribute('success', 'false');
		$error->addAttribute('msg', '요청 값이 없습니다.');
	die($response->asXML());
}

libxml_use_internal_errors(true);
$xml = simplexml_load_string($receive_xml);
if(!$xml)
{
	foreach(libxml_get_errors() as $error)
	{
		$err_msg .= $error->message . "\t";
	}
	$result = $response->addChild('Result');
	$result->addAttribute('success', 'false');
	$result->addChild('msg', 'xml 파싱에러: ' .$err_msg);
	
	file_put_contents($fileName, date("H:i:s\t").$response->asXML()."\r\n\r\n", FILE_APPEND);
	echo $response->asXML();
	exit;
}
//content_id 를등록시 입력안되게 변경 2010.10.21 김성민 
//$content_id = getNextSequence();
try{
	//step1 . 서버 네임으로 채널운영실인지 부조인지 구별시킴
	$server_name = $xml->Server_name;

	$check_server = $db->queryOne("select server_uid from harris_setting where server_name = '$server_name'");
	if(empty($check_server)){
		$empty = $response->addChild('Result');		
		$empty->addAttribute('success', 'false');
		$empty->addAttribute('msg', 'MAM에 등록되어 있지 않은 서버입니다.');
		die($response->asXML());
	}
	//step2 . 해당 서버uid를 harris테이블에 insert

    $check_id = $xml->ID;
    $xid = $xml->XID;
	$action = $xml->ID['Action'];
	
	if($action == 'deleted')
	{
		$delete = $db->exec("delete from harris where id = '$check_id' and server_uid = $check_server");
		
		$regist = $response->addChild('Result');
		$regist->addAttribute('success', 'true');
		$regist->addAttribute('msg', $db->last_query);

		die($response->asXML());
	}
	
	$compare = $db->queryRow("select id, record_datetime from harris where id='$check_id' and server_uid = $check_server");
    $ignore_colums = array('Listing_StartTime', 'Listing_EndTime', 'Server_name', 'Ip_add', 'Loop', 'Ctr_port', 'Ftp_port', 'Department', 'Video_ARC', 'Type', 'Audio_Format', 'Audio_Bits', 'Media_Type', 'Delete_Protected');

    //2018-01-05 이승수, CJO, 같은 XID로 덮어쓸 시 HarrisID가 바뀌므로 기존에 있던건 deleted 처리를 해준다.
    //조건은 insert전에 구하고 DB에 넣는건 insert후에
	$xidReject = $xid . Review::$rejectSuffix;
	$xidPostpone = $xid . Review::$postponeSuffix;
	$xidExpired = $xid . Review::$expiredSuffix;
    $check_same_xid = $db->queryRow("select * from harris where xid in ('".$xid."','".$xidReject."','".$xidPostpone."','".$xidExpired."') and server_uid=".$check_server." and refresh_status='sync'");

	if(empty($compare))
	{
		foreach($xml as $colum => $value)
		{
            if (in_array($colum, $ignore_colums)) {
                continue;
            }
			$condition[] = strtolower($colum);
			$values .= "'".addslashes($value)."',";
		}
	
		#### ariel_uid 컬럼에 콘텐츠아이디 값 추가
		$condition = join(', ', $condition).', server_uid, created_datetime';
        $condition = strtoupper($condition);

		// content_id 부여 절차 변경으로 content_id(ariel_uid)삽입부분 제거..
//		$values = $values." '".$content_id."', '".$check_server."'";
		$values = $values."'".$check_server."','".date('YmdHis')."'";
file_put_contents($fileName, date("H:i:s\t")."insert into harris ($condition) values ($values)"."\r\n\r\n", FILE_APPEND);	
        $insert = $db->exec("insert into harris ($condition) values ($values)");
        
        if(!empty($check_same_xid)) {
            $db->exec("update harris set refresh_status='deleted' where xid in ('".$xid."','".$xidReject."') and server_uid=".$check_server." and refresh_status='sync' and id!='".$check_id."'");
        }

		$msg = "inserted";
	}
	else
	{
		foreach($xml as $colum => $value)
		{
            //Not include these items.
            if( in_array(strtolower($colum), array('server_name','ip_add','ctr_port','ftp_port','listing_starttime','listing_endtime')) ) {
                continue;
            }
            if (in_array($colum, $ignore_colums)) {
                continue;
            }
            if ($colum == 'Record_DateTime') {
                $record_datetime = addslashes($value);
            }
            
			$condition .= strtolower($colum)." = '".addslashes($value)."',";
		}
        $condition .= "refresh_status = 'sync' ";
        
        //2012.02.23 김형기 아이디가 중복되는 것 중에 녹화 시간이 변경된거는 cms싱크 정보를 다 지운다.
        if ($record_datetime != '' && $compare['record_datetime'] != $record_datetime) {
            $condition .= ", mam_ingest = null, ariel_uid = null ";
        }

		//$condition = substr("$condition", 0, -1);
        $update = $db->exec("update harris set $condition where id = '$check_id' and server_uid = $check_server");
        
        if(!empty($check_same_xid)) {
            $db->exec("update harris set refresh_status='deleted' where xid in ('".$xid."','".$xidReject."') and server_uid=".$check_server." and refresh_status='sync' and id!='".$check_id."'");
        }

        //2018-01-08 이승수. harris_changed_metadata테이블에 complete상태 추가
        $changed_meta_all = $db->queryAll("select * from harris_changed_metadata where harris_id='".$check_id."' and status='send'");
        foreach($changed_meta_all as $cma) {
            //메타수정이 아닌항목은 바로 complete로
            //컬럼이 같을시엔 값도 같으면 complete로
            if(in_array(strtolower($cma['field_name']), array('type','not_ready_to_play'))) {
                $db->exec("update harris_changed_metadata set status='complete'
                    where harris_id='".$check_id."' and field_name='".$cma['field_name']."'");
            }
            else {
                foreach($xml as $colum => $value)
                {
                    if(strtolower($colum) == strtolower($cma['field_name']) && $value == $cma['changed_value'] ) {
                        $db->exec("update harris_changed_metadata set status='complete'
                            where harris_id='".$check_id."' and field_name='".$cma['field_name']."'");
                    }
                }
            }
        }

		$msg = "updated";
	}


	$regist = $response->addChild('Result');
	$regist->addAttribute('success', 'true');
	$regist->addAttribute('msg', $msg);
	
	file_put_contents($fileName, date("H:i:s\t").$response->asXML()."\r\n", FILE_APPEND);
	die($response->asXML());
}
catch(Exception $e)
{
	$msg = $e->getMessage();
	switch($e->getCode())
	{
		case ERROR_QUERY:
			$msg += '( '.$db->last_query.' )';
		break;
	}
	
	$error = $response->addChild('Result');		
		$error->addAttribute('success', 'false');
        $error->addAttribute('msg', $msg);
    file_put_contents($fileName, date("H:i:s\t").$db->last_query."\n", FILE_APPEND);
	file_put_contents($fileName, date("H:i:s\t").$msg."\n", FILE_APPEND);
	die($response->asXML());
}


?>
