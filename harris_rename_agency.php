<?php
/**
 * Harris MetaUpdate에서 메타데이터 변경 작업을 가져가기 위한 페이지
 * 작업 완료 후 완료 업데이트(success, fail)를 치지만 ping-pong만 할 뿐 실제 DB에는 아무 작업도 하지 않는다.
 * 이 부분은 향후 개선할 필요가 있다.
 */

require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/interface/harris/libs/HarrisMetadata.php');

try {
	$receive_xml = file_get_contents('php://input');
	file_put_contents('log/rename_harris'.date('Ymd').'.html', date("Y-m-d H:i:s\t").$receive_xml."\n\n", FILE_APPEND);

    $harrisMetadata = new HarrisMetadata($db);
    $harrisMetadata->setQueueNotChangedJob();//2018-01-09 이승수, 메타 안바뀐 항목들 다시 queue로
    $response = $harrisMetadata->getChangeMetadataJob($receive_xml);
    

	echo $response->asXML();

	file_put_contents('log/rename_harris'.date('Ymd').'.html', date("Y-m-d H:i:s\t").$response->asXML()."\n\n", FILE_APPEND);
} catch (Exception $e) {
	$response = new SimpleXMLElement("<?xml version=\"1.0\" encoding=\"UTF-8\"?> \n<Response />");
    $result = $response->addChild('Result');
    $result->addAttribute('success', 'false');
    $result->addAttribute('msg', $e->getMessage());
    return $response;
}



/*
Rename 작업 요청
2010/10/10. agency는 metadata로 변경됨.
<Request><Action>rename</Action><Server>채널 운영실</Server></Request>

<Request><Action>metadata</Action><Server>채널 운영실</Server></Request>

<Request>
    <Action>metadata</Action>
	<Server>채널 운영실</Server>  *부조 는 공백없슴 *
</Request>

metadata 작업 답변
<Response>
	<Result>
	   <ID>%0000992</ID>
	   <NewData Code="13">테스트파일</NewData> 
	</Result>
</Response>

rename 작업 답변
<Response>
	<Result>
	   <ID>%0000992</ID>
	   <NewData>테스트파일</NewData> 
	</Result>
</Response>



Rename 작업 상태 :: metadata일경우 <Code>추가.
--성공시
<Request>
	<Action>Success</Action>
	<Server>채널 운영실</Server>
    <ID>%0000003</ID>
	<Code></Code>
    <Status>RENAME_SUCCESS</Status>
</Request>
--실패시
<Request>
	<Action>Fail</Action>
	<Server>채널 운영실</Server>
    <ID>%0000003</ID>
    <Status>RENAME_FAIL</Status>
</Request>


success를 받았을때/
<Response>
  <Result success="true" msg="renamed"></Result> 
</Response>
*/
#################### Code값 ######################
/*
Codec Where Recorded		1
Source 8-byte ID Handle		2
UMID						3
Video Info					4
Source Video Parameters		5
GUID						6
User Name					7
Department					8
Reserved					9
Reserved					10
Link						11
Description					12
Agency						13
User-definable Field #1		14
User-definable Field #2		15
User-definable Field #3		16
User-definable Field #4		17
External Controller UID		18
Video ARC					19
Modified					20
Timestamp					21
Video QA Status				22
User Segments In Use		23
Audio Track Info			24
*/
?>