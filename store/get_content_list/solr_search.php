<?php

if(!defined('DS'))
	define('DS', DIRECTORY_SEPARATOR);

$rootDir = dirname(dirname(__DIR__));	
require_once($rootDir . DS .'lib' . DS . 'config.php');
require_once($rootDir . DS .'lib' . DS . 'functions.php');
//require_once($rootDir . DS .'searchengine' . DS . 'solr' . DS . 'searcher.class.php');

use Proxima\core\Session;
use Proxima\models\content\Category;
use Api\Services\DataDicCodeItemService;
Session::init();

try {
    $engine = app()->getContainer()['searcher'];
    
    //$engine->logger()->info(__LINE__.' : ' );
	$searchValue = [
		// 검색 키워드
		'search_q',
		'filter_value' => [
			// 좌측 카테고리 노드 경로
			'field' => 'category_full_path'
		],
		'filters' => [
			// 검색 파라미터가 일치하는 곳
			'equal' =>[
				'content_status' => [
					// 콘텐츠 상태
					'field' => 'status'
				],
				'content_review_status' => [
					// 콘텐츠 심의상태 (비활성화) 상태임
					'field' => 'review_status_s'
				],
				'brdcst_stle_se' => [
					// 방송형태
					'field' => 'brdcst_stle_se_t'
				],
				'matr_knd' => [
					// 소재종류
					'field' => 'matr_knd_t'
				],
				'vido_ty_se' => [
					// 프로그램 유형
					'field' => 'vido_ty_se_s'
				],
				'dwld_posbl_at' => [
					// 포털 다운로드 기능
					'field' => 'dwld_posbl_at_s'
				],
				'tme_no' => [
					// 회차
					'field' => 'tme_no_s'
				]
			],
			'like' => [
				'hono' => [
					// 호수
					'field' => 'hono_s'
				],
				'manage_no' => [
					// 관리번호
					'field' => 'manage_no_s'
				],
				'prod_pd_nm' => [
					// 제작PD
					'field' => 'prod_pd_nm_s'
				],
			],
			//like 검색으로 수정할까 생각중
			'text_equal' => [
				//ex)
				//'dwld_posbl_at' => [
				//	'field' => 'dwld_posbl_at_s'
				//]
			],
			'date_between' => [
				'category_date' => [
					// 카테고리 년도별 클릭 검색 했을때
					// 제작일정 검색종료 날짜
					'start_date_param' => 'category_start_date',
					'end_date_param'  => 'category_end_date',
					'field' => 'prod_de_t'
				],
				'created_date' =>[
					// 날짜 검색 사용자 지정으로 선택했을때
					'start_date_param' => 'user_set_start_date',
					'end_date_param'  => 'user_set_end_date',
					'field' => 'create_date'
				]
			],
			'beside' => [
				'content_archive_status' => [
					// 아카이브 여부
					'field' => 'archive_status_s'
				],
				'created_date' => [
					// 등록일자
					'field' => 'create_date'
				],
				'restore_at' => [
					// 리스토어 여부
					'field' => 'restore_at_s'
                ],
                'use_prhibt_at' => [
					// 리스토어 여부
					'field' => 'use_prhibt_at_s'
				],
				'kwrd' => [
					// 키워드
					'field' => 'kwrd_s'
				],
				'is_hidden' => [
					//콘텐츠 숨김 여부
					'field' => 'is_hidden'
                ],
                'resolution' => [
					//콘텐츠 숨김 여부
					'field' => 'resolution_s'
				],
			]
		],
		'instt' => [
			// 좌측 카테고리 에서 부처영상 클릭일 경우
			// 부처영상 코드값
			'field' => 'regist_instt_s'
		]
	];
	// _t  자연어
	// _s  like~ 나 equal 검색
	$search_q = $_POST['search_q'];
	$category_path = $_REQUEST['filter_value'];
	$start = (empty($_POST['start']))? 0 : $_POST['start'];
	$limit = (empty($_POST['limit']))? 20 : $_POST['limit'];

	$sdate = $_REQUEST['sdate'];
	$edate = $_REQUEST['edate'];

	$instt = $_POST['instt'];
    $telecineTySe = $_POST['telecine_ty_se'];

	if(!is_null($_POST['search_tbar'])){
        $searchTbar = json_decode($_POST['search_tbar']);
	}

	$sort = (empty($_POST['sort']))? 'created_date' : $_POST['sort'];
	$dir = (empty($_POST['dir']))? 'DESC' : $_POST['dir'];

	$ud_content_id = $_REQUEST['ud_content_id'];

	/*싱글만 지원되므로 아래와 같이 처리 2017.12.22 Alex */
	$tag_category_id = $_REQUEST['tag_category_id'];

	$filters = json_decode($_REQUEST['filters'], true);

	$search_p_arr = array();
	$search_p_ud_arr = array();
	$searchkey_p_arr = array();
    $search_fq_arr = array();

	$ud_total_list = array();

	/**
	 * 검색엔진으로 통합검색시 카테고리 명도 검색이 되도록 수정
	 * 검색시 검색어 searchkey에 걸리거나 카테고리 타이틀에 걸리거나 검색되도록 수정 - 2018.03.07 Alex
	 */

	if(!empty($search_q)){
		$search_q = solr_encode($search_q);
        $searchkey_q = array();
        $search_q_arr = explode(" ", $search_q);
        foreach($search_q_arr as $key)
        {                
            if(empty(trim($key))) continue;
            $searchkey_q [] = "(searchkey:*".$key." OR searchkey:".$key."* OR category_title:*".$key." OR category_title:".$key."*)";
        }

        $search_key = join(' AND ',$searchkey_q);
	}else{
        //$searchkey_q [] = 'searchkey:*';
        //$search_key = join(' AND ',$searchkey_q);
    }

	if(!empty($search_key)) {
		$search_p_arr[] = $search_key;
	}
    
	/* 기본검색은 -3(콘텐츠 입수 이전 상태)은 제외하고 검색 2017.12.21 Alex */	
	// 필터 에 따라 조건을 넣도록 변경 khk
	if(!empty($filters)) {
		
		foreach($searchValue['filters'] as $type => $params){            
			if($type == 'equal'){
				foreach($params as $param => $attr){
                    $filters[$param] = solr_encode($filters[$param]);
					$field = $attr['field'];
					if(($filters[$param] !== null) && ($filters[$param] !== 'All') && ($filters[$param] !== '전체') && ($filters[$param] !== "")){
						$search_p_arr[] = ""."{$field}:{$filters[$param]}";	    
					}
				};
			}

			if($type == 'like'){
				foreach($params as $param => $attr){
                    $filters[$param] = solr_encode($filters[$param]);
					$field = $attr['field'];
					if(($filters[$param] !== null) && ($filters[$param] !== 'All') && ($filters[$param] !== '전체') && ($filters[$param] !== "")){
						$search_p_arr[] = "({$field}:*{$filters[$param]} OR {$field}:{$filters[$param]}*)";	    
					}
				}
			}
		
			if($type == 'text_equal'){
				foreach($params as $param => $attr){
                    $filters[$param] = solr_encode($filters[$param]);
					$field = $attr['field'];
					if(($filters[$param] !== null) && ($filters[$param] !== 'All') && ($filters[$param] !== '전체') &&($filters[$param] !== "")){
							$search_p_arr[] = "{$field}:\"{$filters[$param]}\"";	    
					}
				};
			}
			
			if($type == 'date_between'){
				foreach($params as $param => $dateParams){
					$startDate = $filters[$dateParams['start_date_param']];
					$endDate = $filters[$dateParams['end_date_param']];
					$field =$dateParams['field'];
					
					
					if($param == 'created_date'){
						// 
						if($filters['created_date'] == 'user_set_created_date'){
							$startDate = date('Ymd',strtotime($startDate)).'000000';
							$endDate = date('Ymd',strtotime($endDate)).'240000';
						};
					}
					
					// TODO: shooting_de_t Indexing
					
					if(($startDate !== null) && ($endDate !== null)){
						if($param == 'category_date') {
							$search_p_arr[] = "((prod_de_t:[{$startDate} TO {$endDate}]) OR (!prod_de_t:['' TO *] AND brdcst_de_t:[{$startDate} TO {$endDate}]) OR (!prod_de_t:['' TO *] AND !brdcst_de_t:['' TO *] AND shooting_de_t:[{$startDate} TO {$endDate}]) OR (!prod_de_t:['' TO *] AND !brdcst_de_t:['' TO *] AND !shooting_de_t:['' TO *] AND create_date:[{$startDate} TO {$endDate}]))";
						} else {
							$search_p_arr[] ="{$field}:[{$startDate} TO {$endDate}]";
						}
					}
				};
			}
			
			if($type == 'beside'){
				foreach($params as $param  => $attr){
					
					
					if($param == 'content_archive_status'){
                        $filters[$param] = solr_encode($filters[$param]);
						$field = $params[$param]['field'];
						if(($filters[$param] !== null) && ($filters[$param] !== 'All') && ($filters[$param] !== '전체')){
							if($filters[$param] == 'Y'){
								$search_p_arr[] = "{$field}:[\"1\" TO \"3\"]";
							}else{
								$search_p_arr[] = "!{$field}:[\"1\" TO \"3\"]";
							}
						}
					}
		
					if($param == 'created_date'){
						$field = $params[$param]['field'];
						
						if($filters[$param] != 'user_set_created_date'){
							if(($filters[$param] !== null) && ($filters[$param] !== 'All') && ($filters[$param] !== '전체')) {
								$today = date('Ymd').'240000';
									
								if(strlen($filters[$param]) === 14){
									$fromDate = $filters[$param];
									$search_p_arr[] = "{$field}:[{$fromDate} TO {$today}]";
								}else{
									$fromDate = date('Ymd', strtotime("-{$filters[$param]} day"));
									$search_p_arr[] =  "{$field}:[{$fromDate}000000 TO {$today}]";
								};
							}
						}
					}

					if($param == 'restore_at'){
						$field = $params[$param]['field'];
						if(($filters[$param] !== null) && ($filters[$param] !== 'All') && ($filters[$param] !== '전체') ){
							// 리스토어 이면 1 
							if($filters[$param] == 'Y'){
								$search_p_arr[] = "{$field}:1";
							}
							// 리스토어가 아니면 1이 아닌거
							if($filters[$param] == 'N'){
								$search_p_arr[] = "!{$field}:1";
							}
						}
                    }
                    
                    if($param == 'use_prhibt_at'){
						$field = $params[$param]['field'];
						if(($filters[$param] !== null) && ($filters[$param] !== 'All') && ($filters[$param] !== '전체') ){
							// 사용금지 이면 1 
							if($filters[$param] == 'Y'){
								$search_p_arr[] = "{$field}:Y";
							}
							// 사용금지 아니면 1이 아닌거
							if($filters[$param] == 'N'){
								$search_p_arr[] = "!{$field}:Y";
							}
						}
					}

					if($param == 'is_hidden'){
						$field = $params[$param]['field'];
						if($filters[$param] == null){
							// 권한이 없어서 필득가 없고 널 값이 올경우 기본 N
							$search_p_arr[] = "{$field}:N";
						}else{
							if(($filters[$param] !== 'All') && ($filters[$param] !== '전체')){
								// 검색을 했을때 전체가 아니면 쿼리를 넣어준다
								$search_p_arr[] = "{$field}:{$filters[$param]}";	 
							}
						};
                    }
                    
                    if($param == 'resolution'){
						$field = $params[$param]['field'];
						if($filters[$param] == null){
						}else{
							if(($filters[$param] !== 'All') && ($filters[$param] !== '전체')){
								// 검색을 했을때 전체가 아니면 쿼리를 넣어준다
								$search_p_arr[] = "{$field}:{$filters[$param]}";	 
							}
						};
					}
				};
				
				
                $param = 'kwrd';
                $filters[$param] = solr_encode($filters[$param]);
				$keywordStr = $filters[$param];
				if($keywordStr != "" && !is_null($keywordStr)){

					$field = $params[$param]['field'];
					
					$hashCheck = substr($keywordStr,0,1);
					if($hashCheck == "#"){
						$keywordStr = substr($keywordStr,1);
					}
					$keywordArray = explode('#',$keywordStr);


					$keywordCount = 1;
					$keywordQuery = "(";
					foreach($keywordArray as $keyword){
						// $keywordQuery = $keywordQuery."{$field}:*#{$keyword}*";
						$keywordQuery = $keywordQuery."( {$field}:{$keyword}* OR {$field}:*{$keyword})";
						if($keyword != "" && $keyword != "#"){
							if(count($keywordArray) != $keywordCount){
								$keywordQuery .= " OR ";
							}else{
								$keywordQuery .= ")";
							}
						}
			
						$keywordCount++;
					}
					$search_p_arr[] = "$keywordQuery";
				}
				
			}
			
			
		}
		
		if(!empty($instt) ){
			
			// 부처영상 클릭검색일때
            $category_path = '/0/100/203';
            $container = app()->getContainer();     
            $dataDicCodeItemService = new DataDicCodeItemService($container);
            $insttChildren = $dataDicCodeItemService->findChildrenByCodeItemCode($instt);
            $insttList = $insttChildren->toArray();
            $instts = [];
            foreach($insttChildren as $child)
            {
                $instts [] = $child->code_itm_code;
            }
			$search_p_arr[] = "regist_instt_s:(".join(' OR ', $instts).") ";
		}else if( !empty($telecineTySe) ){

            $category_path = '/0/100/205';
            $container = app()->getContainer();     
            $dataDicCodeItemService = new DataDicCodeItemService($container);
            $telecineTySeInfo = $dataDicCodeItemService->findCodeItemByCodeSetId(171,$telecineTySe);
            $telecineTySeText = $telecineTySeInfo->code_itm_nm;
			$search_p_arr[] = "telecine_ty_se_s:(".$telecineTySeText.") ";
        }else{
            if($filters['category_id'] !== null) {
				$category_path = Category::getPath($filters['category_id']);		
			}
		}

		if(defined('CUSTOM_ROOT') && class_exists('\ProximaCustom\core\SolrSearchCustom')) {			
			$emptyResponse = ['success' => true,
						'total' => 0,
						'q' => $search_q,
						'data' => $data,
						'ud_content_id' => $ud_content_id,
						'results' => array(),
						'search'=>'solr zero',
						'ud_total_list' => $ud_total_list];
			$searchConditions = \ProximaCustom\core\SolrSearchCustom::getFilterSearchCondition($filters, $emptyResponse);
			if(!empty($searchConditions)) {
				$search_p_arr[] = '('.implode(' OR ', $searchConditions).')';
			}
		}

	}else{
        $search_p_arr[] = "is_hidden:N";
    } 

	/* Tag ID가 있으면 해당 태그에 해당되는 콘텐츠 조건 추가 2017.12.22 Alex */
	if(!empty($tag_category_id)) {
		$tag_contents = $db->queryAll("
						SELECT	CONTENT_ID
						FROM	BC_TAG
						WHERE	TAG_CATEGORY_ID = $tag_category_id
					");
		
		if(count($tag_contents) > 0) {
			$tag_search_contents = array();
			foreach($tag_contents as $content) {
				$tag_content_id = $content['content_id'];
				array_push($tag_search_contents, 'content_id:'.$tag_content_id);
			}

			$search_p_arr[] = '('.implode(' OR ', $tag_search_contents).')';
		} else {
			$ud_total_list[$ud_content_id] = 0;
			die(json_encode(array(
				'success' => true,
				'total' => 0,
				'q' => $search_q,
				'data' => $data,
				'results' => array(),
				'search'=>'solr zero',
				'ud_total_list' =>$ud_total_list
			)));
		}
	}

	$search_fq_arr[] = 'fq=is_deleted:N';
	$search_fq_arr[] = 'fq=!is_group:C';
	if(!empty($category_path) && $category_path != '/0/') {
		$search_category_path = str_replace('/', '\/', $category_path).'*';
		$search_fq_arr[] = 'fq=category_full_path:'.$search_category_path;
	}
	if($ud_content_id != 'all') $search_p_ud_arr[] = 'fq=meta_type:'.$ud_content_id;

	if(!empty($sdate) && !empty($edate))
	{
		$sdate = date('Ymd', strtotime($sdate)).'000000';
		$edate = date('Ymd', strtotime($edate)).'235959';
		
		$search_p_arr[] = 'create_date:['.$sdate.' TO '.$edate.']';
	}

	if (!is_null($searchTbar) && !is_null($searchTbar->start_date) && !is_null($searchTbar->end_date) && ($searchTbar->start_date != "All")) {
		$search_p_arr[] = 'create_date:['.$searchTbar->start_date.' TO '.$searchTbar->end_date.']';
    }
       
	// -3인 콘텐츠 검색 안되도록
    $search_fq_arr[] = 'fq=!status:\-3';

    $search_p_ud = join('&', $search_p_ud_arr);
    
    $search_fq = join('&', $search_fq_arr);
	
	$search_q_common = join(' AND ', $search_p_arr);

    
	$search_q = $search_q_common;


	$sort_str = 'create_date+DESC';
	if(!empty($sort) && !empty($dir)) {
		if($sort == 'created_date'){
			$sort = 'create_date';
			$sort_str = $sort.'+'.$dir;
		}
		else if($sort == 'title'){
			$sort_str = $sort.'+'.$dir;
		}
		else if($sort == 'content_id'){
			$sort = 'content_id_int';
			$sort_str = $sort.'+'.$dir;
		}
		else if($sort == 'category_title' || $sort == 'category'){
			$sort = 'category_title';
			$sort_str = $sort.'+'.$dir;
		}
		else {
			$sort_str = $sort.'_t+'.$dir;
		}
	}
    
    $engine = app()->getContainer()['searcher'];
    
    //$engine->logger()->info(__LINE__.' : '.print_r($search_q,true) );
    //$engine->logger()->info(__LINE__.' : '.print_r( $search_fq.'&'.$search_p_ud,true) );

	if (empty(trim($search_q))) {
		$search_q = 'is_deleted:N';
	}

    $data = $engine->search(urlencode($search_q), $start, $limit, $sort_str,null, null, '&'.$search_fq.'&'.$search_p_ud);

	$data = json_decode($data);
	$total = $data->response->numFound;
    
	$map_categories = getCategoryMapInfo();
	$mapping_category = json_encode($map_categories);
    //$engine->logger()->info(__LINE__.' : '.print_r($map_categories,true)  );
	if($total > 0){
		$content_list = mapping_meta($data->response->docs);
        $contents = $content_list;
        //$engine->logger()->info(__LINE__.' : fetchMetadata start' );
		$contents = fetchMetadata($content_list);	
        //$engine->logger()->info(__LINE__.' : fetchMetadata end' );
		if( $ud_content_id != 'all' ){
			$ud_total_q = array();
			$ud_data = array();
          
				/**
				 * Root Category ID가 같을 경우에는 category_full_path도 포함해서 검색하도록 수정
				 * 2018.01.23 Alex
				 */

                //$engine->logger()->info(__LINE__.' : '.print_r($ud_search_q,true)  );
                //$engine->logger()->info(__LINE__.' : '.print_r($search_q,true) );
                $ud_data = $engine->search(urlencode($search_q), 0, 0,null,null, null,'&'.$search_fq.'&facet=true&facet.field=meta_type');
                //$engine->logger()->info(__LINE__.' : '  );
                $ud_data = json_decode($ud_data,true);
                //$engine->logger()->info(__LINE__.' : '.print_r($ud_data,true) );
                if(isset($ud_data["facet_counts"]) && isset($ud_data["facet_counts"]["facet_fields"])&& isset($ud_data["facet_counts"]["facet_fields"]["meta_type"])){
                    $totalEach = $ud_data["facet_counts"]["facet_fields"]["meta_type"];
                    $nextIdx = null;
                    $nextKey = null;
                    // foreach($totalEach as $eKey => $val) {
                    //     $engine->logger()->info(__LINE__.' : '.$eKey.' '.$val );
                    //     if(!is_null($nextKey)){
                    //         $nextIdx = null;
                    //         $ud_total_list [$nextKey] = $val;
                    //     }
                    //     if($nextIdx == null){
                    //         $nextKey = $val;
                    //     }

                    // }

                    foreach($totalEach as $eKey => $val)
                    {
                        if(is_int($val)){
                            $ud_total_list[$totalEach[$eKey-1]] = $val;
                        }
                    }
                } 
                //$engine->logger()->info(__LINE__.' : '.print_r($ud_total_list,true) );


		}
 
		$ud_total_list[$ud_content_id] = $total;

		echo json_encode(array(
			'success' => true,
			'total' => $total,
			'q' => $search_q,
			'total_q' => $ud_search_q,
			'data' => $data,
			'results' => $contents,
			'search'=>'solr',
			'ud_total_list' =>$ud_total_list
		));
	}
	else{
		if( $ud_content_id != 'all' ){
			$ud_total_q = array();
			$ud_data = array();


            $searchResults = $engine->search(urlencode($search_q), 0, 0,null,null, null,'&'.$search_fq.'&facet=true&facet.field=meta_type');
            //$engine->logger()->info(__LINE__.' : '  );
            $ud_data = json_decode($searchResults,true);
            if(isset($ud_data["facet_counts"]) && isset($ud_data["facet_counts"]["facet_fields"])&& isset($ud_data["facet_counts"]["facet_fields"]["meta_type"])){
                $totalEach = $ud_data["facet_counts"]["facet_fields"]["meta_type"];
                $nextIdx = null;
                $nextKey = null;
                // foreach($totalEach as $eKey => $val) {
                //     $engine->logger()->info(__LINE__.' : '.$eKey.' '.$val );
                //     if(!is_null($nextKey)){
                //         $nextIdx = null;
                //         $ud_total_list [$nextKey] = $val;
                //     }
                //     if($nextIdx == null){
                //         $nextKey = $val;
                //     }
                // }

                foreach($totalEach as $eKey => $val)
                {
                    if(is_int($val)){
                        $ud_total_list[$totalEach[$eKey-1]] = $val;
                    }
                }
            }        
		}
 
		$ud_total_list[$ud_content_id] = $total;
		
		die(json_encode(array(
			'success' => true,
			'total' => 0,
			'q' => $search_q,
			'total_q' => $ud_search_q,
			'data' => $data,
			'results' => array(),
			'search'=>'solr zero',
			'ud_total_list' =>$ud_total_list
		)));
	}
}
catch(Exception $e){
	die(json_encode(array(
		'success' => false,				
		'msg'=>$e->getMessage()
	)));
}

function mapping_meta($datas){
	global $db;
	$ud_content_id = 46;

    $result = array();
    $content_ids = array();
    
	foreach ($datas as $data) {
		array_push($content_ids, $data->content_id);
	}

    
    $contents = \Api\Models\Content::whereIn('content_id', $content_ids )->get();

	if(!empty($datas)){

		$i = 0;
		foreach($datas as $key => $data){

            foreach ($contents as $content) {
                if ($data->content_id == $content->content_id) {
                    foreach ($content->toArray() as $field => $value) {
                        $result[$key][ $field ] = $value;
                    }
                }
            }
 
			// $content = $db->queryRow("
			// 	select	c.*, ud.ud_content_title
			// 	from	bc_content c
			// 			left outer join bc_ud_content ud on ud.ud_content_id = c.ud_content_id
			// 	where	content_id =".$data->content_id
			// );
			// // $usrMetaContent = $db->queryRow("
			// // 	SELECT USE_PRHIBT_AT, EMBG_RELIS_DT 
			// // 	FROM BC_USRMETA_CONTENT 
			// // 	WHERE USR_CONTENT_ID =".$data->content_id
			// // );
            
			//  foreach($content as $k => $v){
			//  	$result[$i][$k] = $v;
			// // 	$result[$i]['usr_meta'] = $usrMetaContent;
			//  }
	
			foreach($data as $k => $v){
				if(substr($k, -2, 2) == '_t' || substr($k, -2, 2) == '_i'){
					$k = substr($k, 0, strlen($k)-2);
				}
				$result[$i][$k] = $v;
				if($k == 'create_date') $result[$i]['created_date'] = $v;
			}
			$i++;
		}
	}


	return $result;
}

function ordutf8($string, &$offset) {
	$code = ord(substr($string, $offset,1)); 
	if ($code >= 128) {		//otherwise 0xxxxxxx
		if ($code < 224) $bytesnumber = 2;				//110xxxxx
		else if ($code < 240) $bytesnumber = 3;		//1110xxxx
		else if ($code < 248) $bytesnumber = 4;	//11110xxx
		$codetemp = $code - 192 - ($bytesnumber > 2 ? 32 : 0) - ($bytesnumber > 3 ? 16 : 0);
		for ($i = 2; $i <= $bytesnumber; $i++) {
			$offset ++;
			$code2 = ord(substr($string, $offset, 1)) - 128;		//10xxxxxx
			$codetemp = $codetemp*64 + $code2;
		}
		$code = $codetemp;
	}
	$offset += 1;
	if ($offset >= strlen($string)) $offset = -1;
	return $code;
}

function solr_encode($string) {
		//escape필요한 특수문자 : + - && || ! ( ) { } [ ] ^ " ~ * ? : \ /
		$illegal = array(
			"+","-","&&","||","!",
			"(",")","{","}","[",
			"]","^",'"',"~","*",
			"?",":","/",";");
	
		//2017-11-01 이승수. 특수문자 검색 안됨.
		//검색 되는 항목은 현재 +, -, &&, / 4가지. 나머지는 검색어에서 제외
	
		$replace = array(
			"\+","\-","\&\&"," "," ",
			" "," "," "," "," ",
			" "," "," "," "," ",
			" "," ","\/"," ");//검색되는 4개만 역슬래쉬 넣음 
	
		//역슬래쉬 먼저 변경. 배열비교방식에서 변경하면 두번 변경되므로
		//$string = str_replace("\\", "\\\\", $string);//역슬래쉬 검색 될 경우 처리
		$string = str_replace(".", " ", $string);//닷(.)을 공백으로 치환
		$string = str_replace("\\", " ", $string);//역슬래쉬도 검색 안되므로 제외
		$string = str_replace($illegal, $replace, $string);
	
		return $string;
	}
?>