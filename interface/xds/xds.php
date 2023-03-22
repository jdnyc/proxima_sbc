<?php
//phpinfo();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/mssql_connection.php');

$receive_xml = file_get_contents('php://input');
$response = new SimpleXMLElement("<?xml version=\"1.0\" encoding=\"UTF-8\"?> \n<Response />");

file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/'.basename(__FILE__).'.log', date('Y-m-d H:i:s').$receive_xml."\n", FILE_APPEND);


if(empty($receive_xml))
{
	$error = $response->addChild('Result');
	$error->addAttribute('success', 'false');
	$error->addAttribute('msg', '요청 값이 없습니다.');
	die($response->asXML());
}
try
{
	$xml = new SimpleXMLElement($receive_xml);

	$action = $xml->condition['action'];
		
	switch($action)
	{
		case 'ud_content':
		
			$result = $response->addChild('Result');
			$result->addAttribute('success', 'true');
			$result->addAttribute('msg', 'ok');


			$ud_content = $result->addChild('ud_content');	
			$ud_content->addAttribute('id', '4000283');
			$ud_content->addAttribute('name', '편집본');	

			die( $response->asXML());
		

		break;

		case 'category':

			$user_id = $xml->condition['user_id'];

			//$user_id = '20190';

			if( is_null($user_id ) )
			{
				throw new Exception('사용자 정보가 없습니다');
			}

			switch($user_id)
			{
					case 'all':
					$category_info = $db->queryAll("select * from bc_category where parent_id='0'");
					break;

					default:
					$category_info = $db->queryAll("select c.* from user_mapping u , bc_category c where c.category_id=u.category_id and  user_id='$user_id'");
					
			}
			
			

			$result = $response->addChild('Result');
			$result->addAttribute('success', 'true');
			$result->addAttribute('msg', 'ok');

//			$category = $result->addChild('Category');	
//			$category->addAttribute('id', 0);
//			$category->addAttribute('parentid', 0);	
//			$category->addAttribute('title', 'EBSNPS');		
//			$category->addAttribute('hasChild', 'true');
			
			foreach($category_info as $ca)
			{
				$category = $result->addChild('Category');	
				$category->addAttribute('id', $ca['category_id']);
				$category->addAttribute('parentid', $ca['parent_id']);	
				$category->addAttribute('title', $ca['category_title']);
				if(!$ca['no_children'])
				{
					$category->addAttribute('hasChild', 'true' );	
				}
				else
				{
					$category->addAttribute('hasChild', 'false' );	
				}
			}
			die( $response->asXML());

		break;

		case 'subprognm':		
		
			$category_id = $xml->condition['category_id'];

			//$category_id =  '4801738';

			//$path_info = $db->queryRow("select * from path_mapping where category_id='$category_id'");
			$whereinfo = $db->queryAll("select * from CATEGORY_PROGCD_MAPPING where category_id='$category_id'");

			$query_array = array();

			if( !empty($whereinfo) )
			{

				foreach($whereinfo as $info)
				{
					$category_id = $info['category_id'];
					$medcd = $info['medcd'];
					$progparntcd = $info['progparntcd'];
					$progcd = $info['progcd'];
					$prognm = $info['prognm'];
					$formbaseymd = $info['formbaseymd'];
					$brodstymd = $info['brodstymd'];
					$brodendymd = $info['brodendymd'];

					$forquery = " (
					select
						tm2.*,
						tb1.korname
					from
						tbbf002 tf2,
						tbbma02 tm2,
						tbpae01 tb1
					where
						tm2.pdempno=tb1.empno
					and tm2.medcd=tf2.medcd
					and tf2.progcd=tm2.progcd
					and tf2.formbaseymd=tm2.formbaseymd
					and tf2.brodgu='001'
					and tf2.medcd='$medcd'
					and tf2.formbaseymd='$formbaseymd'
					and tf2.progcd='$progcd') ";


					array_push($query_array , $forquery );
				}

				$query = join(' union all ', $query_array);

				$query = "select * from ( $query  ) t";

				$where = "";

				$query .= $where;
		
				$order = " order by t.brodymd desc";

				$sub_info = $db_ms->queryAll($query.$order);

			}

			$result = $response->addChild('Result');
			$result->addAttribute('success', 'true');
			$result->addAttribute('msg', 'ok');

			$prognm = $result->addChild('Prognm');			

			$prognm->addAttribute('category_id', $category_id);

			if(!empty($sub_info))
			{
				$prognm->addAttribute('startymd', $sub_info[count($sub_info)-1]['brodymd']);
				$prognm->addAttribute('endymd', $sub_info[0]['brodymd']);
			}

			if(!empty($sub_info))
			{
				foreach($sub_info as $sub)
				{
					$metaCtrl = $prognm->addChild('MetaCtrl');
					$metaCtrl->addAttribute('prognm', $sub['prognm']);
					//$metaCtrl->addAttribute('subprogcd', $sub['subprogcd']);
					$subprogcdint = (int)$sub['subprogcd'];
					$subprogname = $subprogcdint.'회 ';
					$metaCtrl->addAttribute('subprognm', $subprogname.$sub['subprognm']);
					$metaCtrl->addAttribute('pd', trim($sub['korname']));
					$metaCtrl->addAttribute('brodymd', $sub['brodymd'] );
					$metaCtrl->addAttribute('medcd', $sub['medcd'] );
					$metaCtrl->addAttribute('progcd', $sub['progcd'] );
					$metaCtrl->addAttribute('formbaseymd', $sub['formbaseymd'] );
					$metaCtrl->addAttribute('subprogcd', $sub['subprogcd'] );
					$metaCtrl->addAttribute('userid', $sub['pdempno'] );
				}
			}
			

			die( $response->asXML());


		break;


		default:
			 throw new Exception('action 정보가 없습니다');
		break;

	}

	//echo $response->asXML();

	file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/'.basename(__FILE__).'.log', date('Y-m-d H:i:s').$response->asXML()."\n", FILE_APPEND);
}
catch(Exception $e){
	$msg = $e->getMessage();
	switch($e->getCode()){
		case ERROR_QUERY:
			$msg .= '( '.$db->last_query.' )';
		break;
	}
	
	$result = $response->addChild("Result", $msg);
	$result->addAttribute('success', 'false');


	file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/'.basename(__FILE__).'.log', date('Y-m-d H:i:s').$response->asXML()."\n", FILE_APPEND);

	die($response->asXML());
}

/*
=====================================


---------------------------
*/
/*
===============================

<request>
<condition action="ud_content" />
</request>
--------------------------------

<Response>
<Result success="true" msg="ok">
<ud_content id="4000283" name="편집본"/>
<ud_content id="4000285" name="마스터본"/>
</Result>
</Response>

================================
<request>
<condition action="category" user_id="20190" />
</request>

-------------------------------

<Response>
<Result success="true" msg="ok">
<Category id="0" parentid="0" title="EBSNPS" hasChild="true"/>
<Category id="4164394" parentid="0" title="스페이스공감" hasChild="false"/>
</Result>
</Response>
================================

===============================


<request>
	<condition action="subprognm"  category_id="4801738"/>
</request>


-------------------------------

<Response>
<Result success="true" msg="ok">
<Prognm prognm="#신기한 스쿨 버스">
<MetaCtrl subprognm="날아라 스쿨버스" pd="도급PD(이용준)" brodymd="20100823"/>
<MetaCtrl subprognm="흡혈귀 소동" pd="도급PD(이용준)" brodymd="20100824"/>
<MetaCtrl subprognm="에너지를 찾아라" pd="도급PD(이용준)" brodymd="20100830"/>
<MetaCtrl subprognm="우주전함 스쿨버스" pd="도급PD(이용준)" brodymd="20100831"/>
<MetaCtrl subprognm="사라진 리즈" pd="도급PD(이용준)" brodymd="20100906"/>
<MetaCtrl subprognm="괴물의 정체를 밝혀라" pd="도급PD(이용준)" brodymd="20100907"/>
<MetaCtrl subprognm="날씨맨의 모험" pd="도급PD(이용준)" brodymd="20100913"/>
<MetaCtrl subprognm="다시 찾은 호두까기 인형" pd="도급PD(이용준)" brodymd="20100914"/>
<MetaCtrl subprognm="북극에서의 탈출" pd="도급PD(이용준)" brodymd="20100920"/>
<MetaCtrl subprognm="거미가 된 스쿨버스" pd="도급PD(이용준)" brodymd="20100921"/>
<MetaCtrl subprognm="건축의 기초" pd="도급PD(이용준)" brodymd="20100927"/>
<MetaCtrl subprognm="벌이 되어서" pd="도급PD(이용준)" brodymd="20100928"/>
<MetaCtrl subprognm="빛과 그림자" pd="도급PD(이용준)" brodymd="20101004"/>
<MetaCtrl subprognm="고고학자라면" pd="도급PD(이용준)" brodymd="20101005"/>
<MetaCtrl subprognm="연어가 돌아올 때" pd="도급PD(이용준)" brodymd="20101011"/>
<MetaCtrl subprognm="선생님 체육왕대회" pd="도급PD(이용준)" brodymd="20101012"/>
<MetaCtrl subprognm="피비와 콩줄기" pd="도급PD(이용준)" brodymd="20101018"/>
<MetaCtrl subprognm="열대우림 속에서" pd="도급PD(이용준)" brodymd="20101019"/>
<MetaCtrl subprognm="물의 힘" pd="도급PD(이용준)" brodymd="20101025"/>
<MetaCtrl subprognm="병아리 알에서 깨어나다" pd="도급PD(이용준)" brodymd="20101026"/>
<MetaCtrl subprognm="홍합의 서식처" pd="도급PD(이용준)" brodymd="20101101"/>
<MetaCtrl subprognm="공기가 하는 일" pd="도급PD(이용준)" brodymd="20101102"/>
<MetaCtrl subprognm="습지의 비밀" pd="도급PD(이용준)" brodymd="20101108"/>
<MetaCtrl subprognm="아놀드의 시상식" pd="도급PD(이용준)" brodymd="20101109"/>
<MetaCtrl subprognm="몰리큘을 만나요" pd="도급PD(이용준)" brodymd="20101115"/>
<MetaCtrl subprognm="도로시앤의 생일선물" pd="도급PD(이용준)" brodymd="20101116"/>
<MetaCtrl subprognm="피비의 슬램덩크" pd="도급PD(이용준)" brodymd="20101122"/>
<MetaCtrl subprognm="발렌타인데이 소동" pd="도급PD(이용준)" brodymd="20101123"/>
<MetaCtrl subprognm="코를 찌르는 냄새" pd="도급PD(이용준)" brodymd="20101129"/>
<MetaCtrl subprognm="해적의 보물을 찾아" pd="도급PD(이용준)" brodymd="20101130"/>
<MetaCtrl subprognm="도시의 야생동물" pd="도급PD(이용준)" brodymd="20101206"/>
<MetaCtrl subprognm="컴퓨터의 속사정" pd="도급PD(이용준)" brodymd="20101207"/>
<MetaCtrl subprognm="우주미아가 된 스쿨버스" pd="도급PD(이용준)" brodymd="20101213"/>
<MetaCtrl subprognm="스쿨버스 잡아 먹히다" pd="도급PD(이용준)" brodymd="20101214"/>
<MetaCtrl subprognm="마찰 없는 야구장" pd="도급PD(이용준)" brodymd="20110214"/>
<MetaCtrl subprognm="프리즐 선생님의 생일파티" pd="도급PD(이용준)" brodymd="20110215"/>
<MetaCtrl subprognm="피비의 꽃" pd="도급PD(이용준)" brodymd="20110221"/>
<MetaCtrl subprognm="개미집으로 들어간 스쿨버스" pd="도급PD(이용준)" brodymd="20110222"/>
</Prognm>
</Result>
</Response>
=============================

*/
?>