<?php
// 2019.11.25 기준 사용하지 않은 클래스 같음(hkkim. sylee)
// require_once('Search.class.php');

// class Searcher extends Search
// {
// 	private $db;

// 	function __construct($db)
// 	{
// 		$this->db = $db;
// 	}

// 	function query($q, $start=0, $limit=20)
// 	{
// 		return $this->SearchQuery($q, $start, $limit);
// 	}

// 	function search($q, $start=0, $limit=20)
// 	{
// 		return $this->query($q, $start, $limit);
// 	}

// 	function queryOne($id, $field)
// 	{
// 		$result = $this->query($id);

// 		//print_r($result);

// 		$xml = simplexml_load_string($result);

// 		//print_r($xml->result->doc);

// 		return $result[$field];
// 	}

// 	function add($id, $group_name=null)
// 	{
// 		$this->update($id, 'update');
// 	}

// 	function update($id, $type = 'update')
// 	{
// 		$dummy_meta = array();

// 		//$type = 'add';

// 		$content = $this->db->queryRow("select c.*, cc.medcd, cc.progcd, cc.subprogcd, cc.brodymd, cc.formbaseymd, cc.on_off,cc.f_address from content c, content_code_info cc where c.content_id=cc.content_id(+) and c.content_id=$id");

// 		if (empty($content))
// 		{
// 			throw new Exception('존재하지 않는 콘텐츠입니다.');
// 		}

// 		//$meta_system = $db->queryAll("select * from content_value where content_id=".$content_id);
// 		$meta_user = $this->db->queryAll("select f.meta_field_id, f.type, v.value
// 											from meta_field f, meta_value v
// 											where v.content_id=$id
// 											and v.meta_field_id=f.meta_field_id");
// 		if($content['category_id'] != 0)
// 		{
// 			$category_full_path = getCategoryFullPath($content['category_id']);
// 		}
// 		else
// 		{
// 			$category_full_path = '/0';
// 		}

// 		$meta_data =  $this->getFieldMapping( $content['meta_table_id'] );

// 		foreach ( $meta_user as $v )
// 		{
// 			if ( isset($meta_data[$v['meta_field_id']]) )
// 			{
// 				if ($v['type'] == 'datefield')
// 				{
// 					$meta_data[$v['meta_field_id']] = date('YmdHis', strtotime($v['value']));
// 				}
// 				else
// 				{
// 					$meta_data[$v['meta_field_id']] = $v['value'];
// 				}

// 			}
// 		}

// 		$columns = $this->getTCDescColumn( $content['meta_table_id'] );

// 		if( is_array($columns ) )
// 		{
// 			foreach( $columns as $column )
// 			{
// 				$meta_data[$column] = $this->getTCDescs( $content['content_id'], $content['meta_table_id'] , $column );
// 			}
// 		}

// 		if( $content['meta_table_id'] == LIBRARY_IMG )//음반일때 곡 필드 추가
// 		{
// 			$meta_data2 =  $this->getFieldMapping( DOCS );

// 			foreach($meta_data as $key => $val)
// 			{
// 				$meta_data2 [$key] =  $val;
// 			}

// 			$meta_data = $meta_data2;
// 		}
// 		else if( $content['meta_table_id'] == DOCS  )//곡일때 음반 필드 추가
// 		{
// 			$meta_data2 =  $this->getFieldMapping( 81771 );

// 			foreach($meta_data2 as $key => $val)
// 			{
// 				$meta_data [$key] =  $val;
// 			}
// 		}

// 		//자막 정보
// 		if($content['meta_table_id'] == PRE_PRODUCE ){
// 			$root_path ='';
// 			$captionPath = $this->db->queryOne("select path from media where content_id='$id' and type='caption' and COALESCE(status,0)=0 ");
// 			if( !empty($captionPath) ){
// 			 $content['closedcaption'] = $root_path.'/'.$captionPath;
// 			}
// 		}
// 		//echo $meta_data['TCDESC']."\n";

// 		//print_r($meta_data);exit;

// 		$result_update = $this->SearchUpdate(	$type,
// 										$category_full_path,
// 										$content['content_type_id'],
// 										$content['meta_table_id'],
// 										$id,
// 										$content['status'],
// 										$content['title'],
// 										$meta_data,
// 										$content//콘텐츠 코드정보 추가
// 										);

// 		if ( !strstr($result_update, '<int name="status">0</int>') ) throw new Exception('Update failure');

// 	}

// 	function delete($id )//검색데이터 삭제
// 	{
// 		$meta_table_id = $this->db->queryOne("select meta_table_id from content where content_id='$id'");

// 		$result = $this->SearchDelete($id , $meta_table_id);

// 		return $result;
// 	}

// 	function commit()//사용안함
// 	{
// 		$result_commit = $this->SearchCommit();

// 		if ( !strstr($result_commit, '<int name="status">0</int>') ) throw new Exception('Update failure');
// 	}


// 	function getFieldMapping($meta_table_id)//검색엔진에 보낼 XML에 메타데이터 매핑
// 	{
// 		switch ($meta_table_id)
// 		{//순서 마춰야됨
// 			case PRE_PRODUCE:
// 				$columns = array(
// 					'81787' => ''
// 					,'81786' => ''
// 					,'4002618' => ''
// 					,'4002622' => ''
// 					,'4002623' => ''
// 					,'4002624' => ''
// 					,'4012943' => ''
// 					,'22951102' => ''
// 					,'81783' => ''
// 				);
// 			break;

// 			case CLEAN:
// 				$columns = array(
// 					'12435039' => ''
// 					,'81853' => ''
// 					,'4021168' => ''
// 					,'81855' => ''
// 					,'81878' => ''
// 					,'81851' => ''
// 					,'22951123' => ''
// 					,'4002668' => ''
// 					//,'4002671' => ''
// 					,'22951304' => ''
// 				);
// 			break;


// 			case PAST_BROADCAST:
// 				$columns = array(
// 					'81876' => ''
// 					,'81875' => ''
// 					,'81874' => ''
// 					,'4002695' => ''
// 					,'4002696' => ''
// 					,'81879' => ''
// 					,'81866' => ''
// 					,'22951216' => ''
// 				);

// 			break;


// 			case 81770:
// 				$columns = array(
// 					'22894024' => ''
// 					,'22894025' => ''
// 					,'22894026' => ''
// 					,'22894266' => ''
// 					,'22893506' => ''
// 					,'22893507' => ''
// 					,'22893511' => ''
// 					,'22893692' => ''
// 					,'22893736' => ''
// 					,'22951150' => ''
// 					,'22894263' => ''
// 					,'22894268' => ''
// 				);
// 			break;

// 			case 4023846:
// 				$columns = array(
// 					'6255174' => ''
// 					,'6255184' => ''
// 					,'6255209' => ''
// 					,'6252478' => ''
// 					,'6255226' => ''
// 					,'6255227' => ''
// 					,'6255230' => ''
// 					,'6255231' => ''
// 					,'22951160' => ''
// 				);
// 			break;

// 			case 4023848:
// 				$columns = array(
// 				'22951170' => ''
// 				,'22951176' => ''
// 				,'22951177' => ''
// 				,'22951174' => ''
// 				,'22951175' => ''
// 				,'22951173' => ''
// 				,'22951217' => ''
// 				,'22951171' => ''
// 				,'22951179' => ''
// 				);
// 			break;

// 			case LIBRARY_IMG:
// 				$columns = array(
// 					'22950994' => ''
// 					,'22950996' => ''
// 					,'22950995' => ''
// 					,'22950993' => ''
// 					,'22950992' => ''
// 					,'22951037' =>''
// 					,'22951024' => ''
// 					//,'22951038' => ''
// 				);
// 			break;

// 			case DOCS:
// 				$columns = array(
// 					'22951310' => ''//추가
// 					,'22950999' => ''
// 					,'22951288' => ''
// 					,'22951000' => ''
// 					,'22951047' => ''
// 					,'22951048' => ''
// 					,'22951052' => ''
// 					,'22951050' => ''
// 					,'22951055' => ''
// 				);
// 			break;

// 			case 81773:
// 				$columns = array(
// 					'22951007' => ''
// 					,'22951010' => ''
// 					,'22951009' => ''
// 					,'22951004' => ''
// 					,'22951005' => ''
// 					,'22951006' => ''
// 					,'22951008' => ''
// 					,'22951057' => ''
// 					,'22951059' => ''
// 					,'22951061' => ''
// 					,'22951064' => ''
// 					,'22951063' => ''
// 					,'22951058' => ''
// 					,'22951081' => ''
// 				);
// 			break;

// 			case 81774:
// 				$columns = array(
// 					'22951014' => ''
// 					//,'22951017' => ''
// 					,'22951013' => ''
// 					,'22951012' => ''
// 					,'22951015' => ''
// 					,'22951303' => ''
// 					,'22951088' => ''
// 					,'22951016' => ''
// 				);
// 			break;

// 			case 4023847 :
// 				$columns = array(
// 					'22951188' => '',
// 					'22951189' => '',
// 					'22951190' => '',
// 					'22951191' => '',
// 					'22951192' => '',
// 					'22951193' => '',
// 					'22951204' => '',
// 					'22951195' => '',
// 					'22951205' => '',
// 					'22951197' => ''
// 				);
// 			break;
// 		}

// 		return $columns;
// 	}

// 	function getTCDescs($content_id, $meta_table_id , $column )//컬럼별로 추출
// 	{
// 		$tc_list = $this->db->queryAll("select * from meta_multi_xml where content_id=".$content_id);

// 		//$columns = $this->getTCDescColumn($meta_table_id);

// 		$r = array();

// 		foreach ($tc_list as $tc)
// 		{
// 			$doc = simplexml_load_string($tc['val']);
// 			//foreach ($columns as $column)
// 			//{
// 				$a = $doc->xpath('//'.$column);
// 				array_push($r, $a[0]);
// 			//}
// 		}

// 		return join("\t", $r);
// 	}

// 	function getTCDescColumn($meta_table_id)//테이블 메타데이터 형식의 검색대상 컬럼 목록
// 	{
// 		switch ($meta_table_id)
// 		{
// 			case PRE_PRODUCE:
// 				$columns = array(
// 					'columnF'
// 					,'columnG'
// 					,'columnH'
// 					,'columnI'
// 				);
// 			break;

// 			case CLEAN:
// 				$columns = array(
// 					'columnK'
// 					,'columnN'
// 					,'columnG'
// 					,'columnH'
// 					,'columnJ'
// 					,'columnQ'

// 				);
// 			break;

// 			case PAST_BROADCAST :
// 				$columns =  array(
// 					'columnE'
// 				);
// 			break;
// 		}

// 		return $columns;
// 	}

// 	function getSearchFieldName()
// 	{
// 			$meta = array(
// 				'mapping' => array(
// 					// 방송프로그램(81722)

// 					'F81787' => '프로그램명'
// 					,'F81786' => '부제'
// 					,'F4002618' => '방송일자'
// 					,'F4002622' => '연출자'
// 					,'F4002623' => '제작사'
// 					,'F4002624' => '주요내용'
// 					,'F4012943' => 'Tape NO'
// 					,'F22951102' => '아카이브 ID'
// 					,'F81783' => '매체'
// 					,'CLOSEDCAPTION' => '자막'

// 					// 소재영상(81767)
// 					,'F12435039' => '자료명'
// 					,'F81853' => '부제'
// 					,'F4021168' => '연출자'
// 					,'F81855' => '제작자(사)'
// 					,'F81878' => 'Tape NO'
// 					,'F81851' => '프로그램명'
// 					,'F22951123' => '아카이브 ID'
// 					,'F4002668' => '취급구분'
// 					,'F22951304' => '입수일자'


// 					// 클립영상(81770)
// 					,'F22894024' => '내용'
// 					,'F22894025' => '출연자'
// 					,'F22894026' => '촬영장소'
// 					,'F22894266' => '촬영자'
// 					,'F22893506' => '프로그램명'
// 					,'F22893507' => '부제'
// 					,'F22893511' => '연출자'
// 					,'F22893692' => '제작사'
// 					,'F22893736' => 'Tape NO'
// 					,'F22951150' => '아카이브 ID'
// 					,'F22894263' => '촬영일자'
// 					,'F22894268' => '색인어'

// 					//R.방송프로그램 4023846
// 					,'F6255174' => '프로그램명'
// 					,'F6255184' => '부제'
// 					,'F6255209' => '방송일자'
// 					,'F6252478' => 'Tape NO'
// 					,'F6255226' => '연출자'
// 					,'F6255227' => '주요내용'
// 					,'F6255230' => '진행자'
// 					,'F6255231' => '출연자'
// 					,'F22951160' => '아카이브 ID'

// 					//EDRB 4023848
// 					,'F22951170' => '원본명'
// 					,'F22951176' => '주요내용'
// 					,'F22951177' => '출연자'
// 					,'F22951174' => '연출자'
// 					,'F22951175' => '제작사'
// 					,'F22951173' => '방송일자'
// 					,'F22951217' => 'EDRB ID'
// 					,'F22951171' => '원본 ID'
// 					,'F22951179' => '아카이브 ID'


// 					//참조영상 81768
// 					,'F81876' => '한글표제명'
// 					,'F81875' => '원어표제명'
// 					,'F81874' => '총서명'
// 					,'F4002695' => '주요내용'
// 					,'F4002696' => '출연자'
// 					,'F81879' => '제작사'
// 					,'F81866' => 'Tape NO'
// 					,'F22951216' => '아카이브 ID'


// 					//이미지  4023847
// 					,'F22951188' => '제목'
// 					,'F22951189' => '내용'
// 					,'F22951190' => '인물정보'
// 					,'F22951191' => '프로그램명'
// 					,'F22951192' => '행사명'
// 					,'F22951193' => '촬영일자'
// 					,'F22951204' => '신청자'
// 					,'F22951195' => '촬영(제작)자'
// 					,'F22951205' => '신청일자'
// 					,'F22951197' => '아카이브 ID'



// 					//음반	81771
// 					,'F22950994' => '등록번호'
// 					,'F22950996' => '분류번호'
// 					,'F22950995' => '총서명'
// 					,'F22950993' => '원어표제명'
// 					,'F22950992' => '한글표제명'

// 					,'F22951037' => '앨범설명'
// 					,'F22951024' => '제작사'
// 					,'F22951038' => '아카이브id'

// 					//곡	81772
// 					,'F22951048' => '작곡자'
// 					,'F22951310' => '분류번호'
// 					,'F22951052' => '연주단체'
// 					,'F22951047' => '작사자'
// 					,'F22951050' => '연주자'
// 					,'F22950999' => '원어곡명'
// 					,'F22951288' => '한글곡명'
// 					,'F22951000' => '가수'
// 					,'F22951055' => '아카이브ID'

// 					//단행본	81773
// 					,'F22951007' => '등록번호'
// 					,'F22951010' => '청구기호'
// 					,'F22951009' => '총서명'
// 					,'F22951004' => '서명'
// 					,'F22951005' => '원서명'
// 					,'F22951006' => '저자'
// 					,'F22951008' => 'ISBN'
// 					,'F22951057' => '대등서명'
// 					,'F22951059' => '부서명'
// 					,'F22951061' => '공저자'
// 					,'F22951064' => '역자'
// 					,'F22951063' => '편자'
// 					,'F22951058' => '자료형태'
// 					,'F22951081' => '초록'

// 					//정간물	81774
// 					,'F22951014' => '등록번호'
// 					//,'F22951017' => '구독번호' //삭제
// 					,'F22951013' => '원어정간물명'
// 					,'F22951012' => '한글정간물명'
// 					,'F22951015' => 'ISSN'
// 					,'F22951303' => 'Issue NO.'
// 					,'F22951088' => 'Volume NO.'
// 					,'F22951016' => '목차사항'
// 				),
// 				//TV방송 TC정보
// 				PRE_PRODUCE => array(
// 					'columnF' => '코너명',
// 					'columnG' => 'TC내용',
// 					'columnH' => '출연자',
// 					'columnI' => '촬영장소'
// 				),
// 				//소재영상 TC정보
// 				CLEAN => array(
// 					'columnG' => 'TC내용',
// 					'columnH' => '출연자',
// 					'columnJ' => '촬영장소',
// 					'columnQ' => '색인어',
// 					'columnK' => '촬영일자',
// 					'columnN' => '촬영자'
// 				),
// 				//참조영상 TC정보
// 				PAST_BROADCAST => array(
// 					'columnE' => 'TC내용'
// 				)
// 			);

// 		return $meta;
// 	}

// 	function getSearchDataIndex($meta_table_id)
// 	{
// 		$meta = array(
// 			PRE_PRODUCE => array(		//방송프로그램
// 				'프로그램명'	=> 'F81787',
// 				'부제'			=> 'F81786',
// 				'방송일자'		=> 'F4002618',
// 				'연출자'		=> 'F4002622',
// 				'제작사'		=> 'F4002623',
// 				'주요내용'		=> 'F4002624',
// 				'tape_no'		=> 'F4012943',
// 				'아카이브id'	=> 'F22951102',
// 				'매체'			=> 'F81783',
// 				'자막'			=> 'CLOSEDCAPTION'
// 			),
// 			CLEAN => array(		//소재영상
// 				'자료명'		=> 'F12435039',
// 				'부제'			=> 'F81853',
// 				'연출자'		=> 'F4021168',
// 				'제작자(사)'	=> 'F81855',
// 				'tape_no'		=> 'F81878',
// 				'프로그램명'	=> 'F81851',
// 				'아카이브_id'	=> 'F22951123',
// 				'취급구분'		=> 'F4002668',
// 				'입수일자'		=> 'F22951304'
// 			),
// 			FIRST_IMG => array(		//클립영상
// 				'내용'			=> 'F22894024',
// 				'출연자'		=> 'F22894025',
// 				'촬영장소'		=> 'F22894026',
// 				'촬영자'		=> 'F22894266',
// 				'프로그램명'	=> 'F22893506',
// 				'부제'			=> 'F22893507',
// 				'연출자'		=> 'F22893511',
// 				'제작사'		=> 'F22893692',
// 				'tape_no'		=> 'F22893736',
// 				'아카이브id'	=> 'F22951150',
// 				'촬영일자'		=> 'F22894263',
// 				'색인어'		=> 'F22894268'
// 			),
// 			4023846 => array(	//R.방송프로그램
// 				'프로그램명'	=> 'F6255174',
// 				'부제'			=> 'F6255184',
// 				'방송일자'		=> 'F6255209',
// 				'tape_no'		=> 'F6252478',
// 				'연출자'		=> 'F6255226',
// 				'주요내용'		=> 'F6255227',
// 				'진행자'		=> 'F6255230',
// 				'출연자'		=> 'F6255231',
// 				'아카이브id'	=> 'F22951160'
// 			),
// 			4023848 => array(	//EDRB
// 				'원본명'		=> 'F22951170',
// 				'주요내용'		=> 'F22951176',
// 				'출연자'		=> 'F22951177',
// 				'연출자'		=> 'F22951174',
// 				'제작사'		=> 'F22951175',
// 				'방송일자'		=> 'F22951173',
// 				'edrb_id'		=> 'F22951217',
// 				'원본id'		=> 'F22951171',
// 				'아카이브_id'	=> 'F22951179'
// 			),
// 			PAST_BROADCAST => array(		//참조영상
// 				'한글표제명'	=> 'F81876',
// 				'원어표제명'	=> 'F81875',
// 				'총서명'		=> 'F81874',
// 				'주요내용'		=> 'F4002695',
// 				'출연자'		=> 'F4002696',
// 				'제작사'		=> 'F81879',
// 				'tape_no'		=> 'F81866',
// 				'아카이브id'	=> 'F22951216'
// 			),
// 			4023847 => array(	//이미지
// 				'제목'			=> 'F22951188',
// 				'내용'			=> 'F22951189',
// 				'인물정보'		=> 'F22951190',
// 				'프로그램명'	=> 'F22951191',
// 				'행사명'		=> 'F22951192',
// 				'촬영일자'		=> 'F22951193',
// 				'신청자'		=> 'F22951204',
// 				'촬영(제작)자'	=> 'F22951195',
// 				'신청일자'		=> 'F22951205',
// 				'아카이브 ID'	=> 'F22951197'
// 			),
// 			LIBRARY_IMG => array(		//음반
// 				'등록번호'		=> 'F22950994',
// 				'분류번호'		=> 'F22950996',
// 				'총서명'		=> 'F22950995',
// 				'원어표제명'	=> 'F22950993',
// 				'한글표제명'	=> 'F22950992',

// 				'앨범설명'		=> 'F22951037',
// 				'제작사'		=> 'F22951024'//,

// 				//'아카이브id'	=> 'F22951038'

// 			),
// 			DOCS => array(		//곡

// 				'작사자'		=> 'F22951047',
// 				'한글곡명'		=> 'F22951288',
// 				'가수'			=> 'F22951000',
// 				'원어곡명'		=> 'F22950999',
// 				'가수'			=> 'F22951000',
// 				'작곡자'		=> 'F22951048',
// 				'아카이브id'	=> 'F22951055'
// 			),
// 			81773 => array(		//단행본
// 				'등록번호'		=> 'F22951007',
// 				'청구기호'		=> 'F22951010',
// 				'총서명'		=> 'F22951009',
// 				'서명'			=> 'F22951004',
// 				'원서명'		=> 'F22951005',
// 				'저자'			=> 'F22951006',
// 				'isbn'			=> 'F22951008',
// 				'대등서명'		=> 'F22951057',
// 				'부서명'		=> 'F22951059',
// 				'공저자'		=> 'F22951061',
// 				'역자'			=> 'F22951064',
// 				'편자'			=> 'F22951063',
// 				'초록'			=> 'F22951081'
// 			),
// 			81774 => array(		//정간물
// 				'등록번호'		=> 'F22951014',
// 				'원어정간물명'	=> 'F22951013',
// 				'한글정간물명'	=> 'F22951012',
// 				'issn'			=> 'F22951015',
// 				'volissno'	=> 'F22951088',
// 				'목차사항'		=> 'F22951016'
// 			)

// 		);
// 		return $meta;
// 	}
// }
// ?>