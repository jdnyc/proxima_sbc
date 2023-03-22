<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');

//print_r($_SESSION);

$meta_table_id = $_POST['meta_table_id'];

$allowItemsAdminBrod = array(  ///////////관리자TV방송/////////
	array(
		'type'			=> 'textfield',
		'name'			=> '프로그램명', 
		'meta_field_id'	=> '81787',
		'default_value'	=> ''
	),
	array(
		'type'			=> 'textfield',
		'name'			=> '부제',
		'meta_field_id'	=> '81786',
		'default_value'	=> ''
	),
	array(
		'type'			=> 'datefield',
		'name'			=> '방송일자',
		'meta_field_id'	=> '4002618',
		'default_value'	=> ''
	),
	array(
		'type'			=> 'textarea',
		'name'			=> '주요내용',
		'meta_field_id'	=> '4002624',
		'default_value'	=> ''
	),
	array(
		'type'			=> 'textfield',
		'name'			=> '연출자',
		'meta_field_id'	=> '4002622',
		'default_value'	=> ''
	),
	array(
		'type'			=> 'combo',
		'name'			=> '매체',
		'meta_field_id'	=> '81783',
		'default_value'	=> 'TV;EBS플러스1;EBS플러스2;EBS플러스3;EBS u;인터넷 강의;'
	),
	array(
		'type'			=> 'combo',
		'name'			=> '포맷', 
		'meta_field_id'	=> '81778',
		'default_value'	=> '강담;강의;공개방송;공익;국내만화;다큐;다큐드라마;다큐종합구성;드라마;방화자막편집;보도;오락;외국영화;외화더빙;외화자막편집;일반구성;자연다큐;종합구성;좌담;중계;취재;취재/보도;캠페인;퀴즈;토론/토크;한국영화;해외만화;기타;FILLER;'
	),
	array(
		'type'			=> 'combo',
		'name'			=> '대상',
		'meta_field_id'	=> '81784',
		'default_value'	=> '유아;유치원;초등학생;초등1년;초등2년;초등3년;초등4년;초등5년;초등6년;중학생;중1년;중2년;중3년;고등학생;고1년;고2년;고3년;대학생;청소년;성인;주부;부모;일반;가족;노인;장애인;교사;어린이;여성;외국인;기타;'
	),
	array(
		'type'			=> 'combo',
		'name'			=> '자료형태',
		'meta_field_id'	=> '81877',
		'default_value'	=> 'HDCAM;BETACAM;DIGIBETACAM;FILE;'
	),
	array(
		'type'			=> 'combo',
		'name'			=> '방송등급',
		'meta_field_id'	=> '81782',
		'default_value'	=> '모든연령;7세이상;12세이상;15세이상;19세이상;등급대상제외;'
	),
	array(
		'type'			=> 'combo',
		'name'			=> '취급구분',
		'meta_field_id'	=> '4002637',
		'default_value'	=> '일반자료;저작권관련자료;특수자료;'
	),
	array(
		'type'			=> 'combo',
		'name'			=> '편성구분',
		'meta_field_id'	=> '81773',
		'default_value'	=> '정규;특집;임시;조정;기타;'
	),
	array(
		'type'			=> 'combo',
		'name'			=> '형태구분',
		'meta_field_id'	=> '81776',
		'default_value'	=> '녹화/녹음;생방송;'
	),
	array(
		'type'			=> 'textfield',
		'name'			=> '기술감독',
		'meta_field_id'	=> '4037522',
		'default_value'	=> ''
	),
	array(
		'type'			=> 'combo',
		'name'			=> '부가정보',
		'meta_field_id'	=> '4002640',
		'default_value'	=> '광고;수화방송;화면해설방송;'
	),
	array(
		'type'			=> 'textfield',
		'name'			=> '메타데이터 작업자',
		'meta_field_id'	=> '4037545',
		'default_value'	=> ''
	),
	array(
		'type'			=> 'textfield',
		'name'			=> '제작사',
		'meta_field_id'	=> '4002623',
		'default_value'	=> ''
	),
	array(
		'type'			=> 'combo',
		'name'			=> '제작주체',
		'meta_field_id'	=> '81775',
		'default_value'	=> '자체제작;외주제작;자체+외주제작;'
	),
	array(
		'type'			=> 'combo',
		'name'			=> '제작국가',
		'meta_field_id'	=> '81779',
		'default_value'	=> '한국;남아메리카;북아메리카;아프리카;오스트레일리아;유럽;아시아;남극;미국;영국;프랑스;중국;일본;기타;'
	),
	array(
		'type'			=> 'combo',
		'name'			=> '외화구분',
		'meta_field_id'	=> '81780',
		'default_value'	=> '외화;비외화;'
	),
	array(
		'type'			=> 'combo',
		'name'			=> '방송원고',
		'meta_field_id'	=> '4002626',
		'default_value'	=> '유;무;'
	),
	array(
		'type'			=> 'datefield',
		'name'			=> '입수일자',
		'meta_field_id'	=> '4002641',
		'default_value'	=> ''
	),
	array(
		'type'			=> 'combo',
		'name'			=> '언어',
		'meta_field_id'	=> '4037523',
		'default_value'	=> '한국어;독일어;라틴어;러시아어;스페인어;슬라브어;영어;이태리어;일본어;중국어;프랑스어;헝가리어;기타;'
	),
	array(
		'type'			=> 'textfield',
		'name'			=> '수상내역',
		'meta_field_id'	=> '4002638',
		'default_value'	=> ''
	),
	array(
		'type'			=> 'datefield',
		'name'			=> '수상일자',
		'meta_field_id'	=> '4002639',
		'default_value'	=> ''
	),
	array(
		'type'			=> 'textfield',
		'name'			=> 'Tape NO',
		'meta_field_id'	=> '4012943',
		'default_value'	=> ''
	),
//	array(
//		'카테고리', //시스템항목
//	),	
	array(
		'type'			=> 'textfield',
		'name'			=> '인코딩 장비번호',
		'meta_field_id'	=> '4037543',
		'default_value'	=> ''
	)
	,array(
		'type'			=> 'textfield',
		'name'			=> '인코딩 작업자',
		'meta_field_id'	=> '4037542',
		'default_value'	=> ''
	)
	,array(
		'type'			=> 'textarea',
		'name'			=> '비고',
		'meta_field_id'	=> '4002627',
		'default_value'	=> ''
	)	
	,array(
		'type'			=> 'listview',
		'name'			=> 'TC내용',
		'meta_field_id'	=> '4037607',
		'default_value'	=> ''
	)	
//	'코너명',	//메타필드에 존재하지않음
//	'출연자',	//메타필드에 존재하지않음
//	'촬영장소',	//메타필드에 존재하지않음		
);


$allowItemsAdminMaterial = array( ///////////관리자소재영상/////////
	array(
		'type'			=> 'textfield',
		'name'			=> '자료명', 
		'meta_field_id'	=> '12435039',
		'default_value'	=> ''
	),
	//수록내용  //메타필드에 존재하지않음
	array(
		'type'			=> 'textfield',
		'name'			=> '출연자',
		'meta_field_id'	=> '81856',
		'default_value'	=> ''
	),
	array(
		'type'			=> 'textfield',
		'name'			=> '촬영장소',
		'meta_field_id'	=> '4002659',
		'default_value'	=> ''
	),
	array(
		'type'			=> 'datefield',
		'name'			=> '촬영일자',
		'meta_field_id'	=> '4002660',
		'default_value'	=> ''
	),
	array(
		'type'			=> 'combo',
		'name'			=> '계절',
		'meta_field_id'	=> '4002661',
		'default_value'	=> '봄;여름;가을;겨울;'
	),
	array(
		'type'			=> 'combo',
		'name'			=> '촬영기법',
		'meta_field_id'	=> '4002662',
		'default_value'	=> '일반촬영;항공촬영;특수촬영;수중촬영;'
	),
	array(
		'type'			=> 'combo',
		'name'			=> '촬영구분', 
		'meta_field_id'	=> '4002658',
		'default_value'	=> '국내;해외;'
	),
	array(
		'type'			=> 'combo',
		'name'			=> '소재구분',
		'meta_field_id'	=> '81849',
		'default_value'	=> '1:1편집본;2:1편집본;완성편집본;VCR편집본;방송본;스튜디오촬영본;촬영본;6MM촬영본;COPY본;코너자료;인서트모음;FILLER;'
	),
	array(
		'type'			=> 'textfield',
		'name'			=> '프로그램명',
		'meta_field_id'	=> '81851',
		'default_value'	=> ''
	),
	array(
		'type'			=> 'textfield',
		'name'			=> '부제',
		'meta_field_id'	=> '81853',
		'default_value'	=> ''
	),
	array(
		'type'			=> 'datefield',
		'name'			=> '방송일자',
		'meta_field_id'	=> '81854',
		'default_value'	=> ''
	),
	array(
		'type'			=> 'textfield',
		'name'			=> '연출자',
		'meta_field_id'	=> '4021168',
		'default_value'	=> ''
	),
	array(
		'type'			=> 'textfield',
		'name'			=> '제작자(사)',
		'meta_field_id'	=> '81855',
		'default_value'	=> ''
	),
	array(
		'type'			=> 'combo',
		'name'			=> '자료형태',
		'meta_field_id'	=> '81844',
		'default_value'	=> 'BETACAM;DIGIBETACAM;HDCAM;SUPER VHS;VHS;FILE;'
	),
	//'카테고리',	//시스템항목
	array(
		'type'			=> 'combo',
		'name'			=> '보관기간',
		'meta_field_id'	=> '4002673',
		'default_value'	=> '1년;3년;5년;10년;영구;기타;'
	),
	array(
		'type'			=> 'textfield',
		'name'			=> '신청인',
		'meta_field_id'	=> '7194771',
		'default_value'	=> ''
	),
	array(
		'type'			=> 'combo',
		'name'			=> '언어',
		'meta_field_id'	=> '4037562',
		'default_value'	=> '한국어;독일어;라틴어;러시아어;스페인어;슬라브어;영어;이태리어;일본어;중국어;프랑스어;헝가리어;기타;'
	),
	array(
		'type'			=> 'combo',
		'name'			=> '입수구분',
		'meta_field_id'	=> '4002669',
		'default_value'	=> '자체;구입;복사;수증;교환;기타;'
	),
	array(
		'type'			=> 'combo',
		'name'			=> '자료등급',
		'meta_field_id'	=> '4002672',
		'default_value'	=> '특;가;나;다;라;'
	),
	array(
		'type'			=> 'textfield',
		'name'			=> '촬영자',
		'meta_field_id'	=> '4002663',
		'default_value'	=> ''
	),
	array(
		'type'			=> 'combo',
		'name'			=> '취급구분',
		'meta_field_id'	=> '4002668',
		'default_value'	=> '일반자료;저작권자료;특수자료;'
	),
	array(
		'type'			=> 'textfield',
		'name'			=> '화질상태',
		'meta_field_id'	=> '4037564',
		'default_value'	=> ''
	),
	array(
		'type'			=> 'textfield',
		'name'			=> '색인어',
		'meta_field_id'	=> '4037563',
		'default_value'	=> ''
	),
	array(
		'type'			=> 'textfield',
		'name'			=> 'Tape NO',
		'meta_field_id'	=> '81878',
		'default_value'	=> ''
	),
	array(
		'type'			=> 'textfield',
		'name'			=> '메타데이터 작업자',
		'meta_field_id'	=> '4037581',
		'default_value'	=> ''
	)
	,array(
		'type'			=> 'listview',
		'name'			=> 'TC내용',
		'meta_field_id'	=> '11879136',
		'default_value'	=> ''
	)	
);


$allowItemsAnonymousBrod = array(   /////////////////사용자TV방송////////////////
	array(
		'type'			=> 'textfield',
		'name'			=> '프로그램명', 
		'meta_field_id'	=> '81787',
		'default_value'	=> ''
	),
	array(
		'type'			=> 'textfield',
		'name'			=> '부제',
		'meta_field_id'	=> '81786',
		'default_value'	=> ''
	),
	array(
		'type'			=> 'datefield',
		'name'			=> '방송일자',
		'meta_field_id'	=> '4002618',
		'default_value'	=> ''
	),
	array(
		'type'			=> 'textarea',
		'name'			=> '주요내용',
		'meta_field_id'	=> '4002624',
		'default_value'	=> ''
	),
	array(
		'type'			=> 'textfield',
		'name'			=> '연출자',
		'meta_field_id'	=> '4002622',
		'default_value'	=> ''
	),
	array(
		'type'			=> 'combo',
		'name'			=> '매체',
		'meta_field_id'	=> '81783',
		'default_value'	=> 'TV;EBS플러스1;EBS플러스2;EBS플러스3;EBS u;인터넷 강의;'
	),
	//	'출연자',	//메타필드에 존재하지않음
	//	'코너명',	//메타필드에 존재하지않음
	array(
		'type'			=> 'combo',
		'name'			=> '포맷', 
		'meta_field_id'	=> '81778',
		'default_value'	=> '강담;강의;공개방송;공익;국내만화;다큐;다큐드라마;다큐종합구성;드라마;방화자막편집;보도;오락;외국영화;외화더빙;외화자막편집;일반구성;자연다큐;종합구성;좌담;중계;취재;취재/보도;캠페인;퀴즈;토론/토크;한국영화;해외만화;기타;FILLER;'
	),
	array(
		'type'			=> 'combo',
		'name'			=> '대상',
		'meta_field_id'	=> '81784',
		'default_value'	=> '유아;유치원;초등학생;초등1년;초등2년;초등3년;초등4년;초등5년;초등6년;중학생;중1년;중2년;중3년;고등학생;고1년;고2년;고3년;대학생;청소년;성인;주부;부모;일반;가족;노인;장애인;교사;어린이;여성;외국인;기타;'
	),
	array(
		'type'			=> 'combo',
		'name'			=> '자료형태',
		'meta_field_id'	=> '81877',
		'default_value'	=> 'HDCAM;BETACAM;DIGIBETACAM;FILE;'
	),
//	array(
//		'카테고리', //시스템항목
//	),
	array(
		'type'			=> 'textfield',
		'name'			=> 'Tape NO',
		'meta_field_id'	=> '4012943',
		'default_value'	=> ''
	)
);

$allowItemsAnonymousMaterial = array( /////////////////사용자소재영상////////////////
	array(
		'type'			=> 'textfield',
		'name'			=> '자료명', 
		'meta_field_id'	=> '12435039',
		'default_value'	=> ''
	),
	//수록내용  //메타필드에 존재하지않음
	array(
		'type'			=> 'textfield',
		'name'			=> '출연자',
		'meta_field_id'	=> '81856',
		'default_value'	=> ''
	),
	array(
		'type'			=> 'textfield',
		'name'			=> '촬영장소',
		'meta_field_id'	=> '4002659',
		'default_value'	=> ''
	),
	array(
		'type'			=> 'datefield',
		'name'			=> '촬영일자',
		'meta_field_id'	=> '4002660',
		'default_value'	=> ''
	),
	array(
		'type'			=> 'combo',
		'name'			=> '계절',
		'meta_field_id'	=> '4002661',
		'default_value'	=> '봄;여름;가을;겨울;'
	),
	array(
		'type'			=> 'combo',
		'name'			=> '촬영기법',
		'meta_field_id'	=> '4002662',
		'default_value'	=> '일반촬영;항공촬영;특수촬영;수중촬영;'
	),
	array(
		'type'			=> 'combo',
		'name'			=> '촬영구분', 
		'meta_field_id'	=> '4002658',
		'default_value'	=> '국내;해외;'
	),
	array(
		'type'			=> 'combo',
		'name'			=> '소재구분',
		'meta_field_id'	=> '81849',
		'default_value'	=> '1:1편집본;2:1편집본;완성편집본;VCR편집본;방송본;스튜디오촬영본;촬영본;6MM촬영본;COPY본;코너자료;인서트모음;FILLER;'
	),
	array(
		'type'			=> 'textfield',
		'name'			=> '프로그램명',
		'meta_field_id'	=> '81851',
		'default_value'	=> ''
	),
	array(
		'type'			=> 'textfield',
		'name'			=> '부제',
		'meta_field_id'	=> '81853',
		'default_value'	=> ''
	),
	array(
		'type'			=> 'datefield',
		'name'			=> '방송일자',
		'meta_field_id'	=> '81854',
		'default_value'	=> ''
	),
	array(
		'type'			=> 'textfield',
		'name'			=> '연출자',
		'meta_field_id'	=> '4021168',
		'default_value'	=> ''
	),
	array(
		'type'			=> 'textfield',
		'name'			=> '제작자(사)',
		'meta_field_id'	=> '81855',
		'default_value'	=> ''
	),
	array(
		'type'			=> 'combo',
		'name'			=> '자료형태',
		'meta_field_id'	=> '81844',
		'default_value'	=> 'BETACAM;DIGIBETACAM;HDCAM;SUPER VHS;VHS;FILE;'
	),
	//'카테고리',	//시스템항목
	array(
		'type'			=> 'textfield',
		'name'			=> 'Tape NO',
		'meta_field_id'	=> '81878',
		'default_value'	=> ''
	),
	array(
		'type'			=> 'textfield',
		'name'			=> '색인어',
		'meta_field_id'	=> '4037563',
		'default_value'	=> ''
	)	
);

if ( $_SESSION['user'] && ($_SESSION['user']['is_admin'] == 'Y' || in_array(ADMIN_GROUP, $_SESSION['user']['groups'])) )
{
	if ( $meta_table_id == PRE_PRODUCE ) 
	{
		$allowItems = $allowItemsAdminBrod;
	}
	else if ( $meta_table_id == CLEAN)
	{
		$allowItems = $allowItemsAdminMaterial;
	}
}
else
{
	if ( $meta_table_id == PRE_PRODUCE ) 
	{
		$allowItems = $allowItemsAnonymousBrod;
	}
	else if ( $meta_table_id == CLEAN )
	{
		$allowItems = $allowItemsAnonymousMaterial;
	}
}

//print_r($allowItems);

//$meta_table_id = 81767;
//$data = $db->queryAll("select meta_field_id, name, type, default_value from meta_field where type !='container' and meta_table_id=".$meta_table_id." ");
//
//print_r($data);
//for ($i=0; $i<count($data); $i++)
//{
//	if ( in_array($data[$i]['name'], $allowItems) )
//	{
//		$combo = trim($data[$i]['default_value']);
//		$combo = explode('(default)',$combo);
//		$data[$i]['default_value'] = $combo[1];
////		$data[$i]['default_value'] = preg_replace('#\(default\)#', '', trim($data[$i]['default_value'], ';'));
//		$filteredData[] = $data[$i];
//	}
//}


//print_r($filteredData);

echo json_encode(array(
	'success' => true,
//	'data' => $filteredData
	'data' => $allowItems
));

?>