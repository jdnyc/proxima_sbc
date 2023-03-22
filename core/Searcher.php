<?php
namespace Proxima\core;

use Proxima\core\Logger;

/**
 * 검색 클래스 병합 URL을 env에
 */
class Searcher
{
    private $db;
    private $url;
    private $logger;
    private $logLevel = 'info';

    const group = 'proxima';

	function __construct($db , $url = null)
	{		
		$this->db = $db;
		if(empty($url)) {
            throw new \Exception('Search engine url should not empty.');
		}
        $this->url = $url;

        if( get_server_param('DOCUMENT_ROOT') ){
            $this->logger = new Logger(str_replace('\\','-',__CLASS__) , $this->logLevel);
        }else{            
            $this->logger = new Logger(str_replace('\\','-',__CLASS__).'-CMD' , $this->logLevel);
        }
    }

    function logger()
    {
        return $this->logger;
    }

    function setLogLevel($logLevel)
    {
        $this->logLevel = $logLevel;
        return $this->logLevel;
    }


    function getSelectField(){
       // return false;
       $fields = [ 
           'category_full_path',
           'content_type',
           'meta_type',
           'content_id',
           'content_id_int',
           'status',
           'title',
           'create_date',
           'is_deleted',
           'is_group',
           'is_hidden',
           'reg_user_id',
           'reg_user_nm',
           'category_id',
           'category_title',
           'media_id_t',
           'brdcst_stle_se_t',
           'vido_ty_se_t',
           'shooting_orginl_atrb_t',
           'progrm_code_t',
           'progrm_nm_t',
           'tme_no_t',
           'subtl_t',
           'origin_t',
           'cpyrhtown_t',
           'all_vido_at_t',
           'brdcst_de_t',
           'cn_t',
           'kwrd_t',
           'recptn_stle_t',
           'matr_knd_t',
           'regist_instt_t',
           'archive_status_s',
           'resolution_s',
           'archv_sttus_s',
           'catlg_sttus_s',
           'trnsmis_at_s',
           'trnsmis_sttus_s',
           'restore_at_s',
           'review_status_s',
           'mcr_trnsmis_sttus_s',
           'restore_sttus_s',
           'bfe_video_id_s',
           'dtl_archv_sttus_s'
        ];
        return $fields;
    }

	function solrQuery($q, $start=0, $rows=20, $sort = null, $highlighting = null, $fields = null, $fq=null)
	{
		if(empty($sort)) $sort = 'create_date+desc';
		$sort = "&sort=".$sort;
		
		if(!empty($highlighting)){
			$highlighting = "&hl=on&hl.fl=*&hl.simple.post=</span>&hl.simple.pre=<span>";
        }
        //$fields = $this->getSelectField();
        if( !empty($fields) ){
            $select = "&fl=".join(',' , $fields );
        }else{
            $select = '';
        }


        $query = "?q=".trim($q).$fq."&start=".$start."&rows=".$rows."&indent=on&wt=json".$sort.$highlighting.$select;
        $result = $this->request("", "solr/collection1/select".$query);
		
		return $result;
	}

	function solrQuery_bak($q, $start=0, $rows=20, $qf = null, $sort = null)
	{
		if(empty($sort)) $sort = 'create_date+desc';

		if(!empty($qf))
		{
			$qf = "&defType=dismax&qf=".$qf;
		}
		//$query = "?q=".trim(urlencode($q)).
		$query = "?q=".trim($q).
			"&version=3&start=$start&rows=$rows&indent=true&sort=$sort&wt=json";

		if($q != '')
			$result = $this->request("", "solr/collection1/select".$query);

		return $result;
	}

	function solrDelete($id)
	{
		$xml =  new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><delete />');

		if ( is_array($id) ){
			foreach ( $id as $i ){
				$field = $xml->addChild('id', $id);
			}
		}else{
			$field = $xml->addChild('id', $id);
		}

		$str = $xml->asXML();
		if($str)
			return $success = $this->request($str, 'solr/collection1/update');
		return 0;
	}

	function solrUpdate($group_name, $storage_type, $content_id, $content, $usr_meta_value, $array_data)
	{
		$add =  new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><add />');

		$doc = $add->addChild('doc');

		$field = $doc->addChild('field', $group_name);
		$field->addAttribute('name', 'group');

		$field = $doc->addChild('field', trim($content['category_full_path']));
		$field->addAttribute('name', 'category_full_path');

		$field = $doc->addChild('field', trim($content['bs_content_id']));
		$field->addAttribute('name', 'content_type');

		$field = $doc->addChild('field', trim($content['ud_content_id']));
		$field->addAttribute('name', 'meta_type');

		$field = $doc->addChild('field', trim($content_id));
		$field->addAttribute('name', 'content_id');
		$field = $doc->addChild('field', trim($content_id));
		$field->addAttribute('name', 'content_id_int');

		$field = $doc->addChild('field', trim($content['status']));
		$field->addAttribute('name', 'status');

		$field = $doc->addChild('field', $this->esc(trim($content['title'])));
		$field->addAttribute('name', 'title');

		$field = $doc->addChild('field', $content['created_date']);
		$field->addAttribute('name', 'create_date');

		$field = $doc->addChild('field', $content['is_deleted']);
		$field->addAttribute('name', 'is_deleted');

		$field = $doc->addChild('field', $content['is_group']);
		$field->addAttribute('name', 'is_group');
		
		if($content['is_hidden'] == '1')
		{
			$hiddden_val = 'Y';
		}else
		{
			$hiddden_val = 'N';
		}
		$field = $doc->addChild('field', $hiddden_val);
		$field->addAttribute('name', 'is_hidden');

		$field = $doc->addChild('field', $this->esc($content['reg_user_id']));
		$field->addAttribute('name', 'reg_user_id');

		$field = $doc->addChild('field', $this->esc($content['reg_user_nm']));
		$field->addAttribute('name', 'reg_user_nm');

		$field = $doc->addChild('field', $content['category_id']);
		$field->addAttribute('name', 'category_id');

		$field = $doc->addChild('field', $this->esc($content['category_title']));
		$field->addAttribute('name', 'category_title');

		// $field = $doc->addChild('field', $this->esc($content['content_status_nm']));
        // $field->addAttribute('name', 'content_status_nm');

		if(!empty($usr_meta_value)){
			foreach ($usr_meta_value as $k=>$v)
			{
				$field = $doc->addChild('field', $this->esc(trim($v)) );
				$field->addAttribute('name', $k.'_t');

				$field2 = $doc->addChild('field', $this->esc(trim($v)) );
				$field2->addAttribute('name', $k.'_s');
			}
		}

		if(!empty($array_data)) {
			foreach($array_data as $k => $v) {
				$field = $doc->addChild('field', $this->esc(trim($v)) );
				$field->addAttribute('name', $k.'_s');
			}
		}

		$str = $add->asXML();
		if($str){
			$success = $this->request($str, "solr/collection1/update");
			return $success;
		}
		return 0;
    }

    function updateFieldMap($group_name, $content_id, $content, $usr_meta_value, $array_data){

        $data = [
            'group' =>  $group_name,
            'category_full_path' =>  $content['category_full_path'],
            'content_type' =>  $content['bs_content_id'],
            'meta_type' =>  $content['ud_content_id'],
            'content_id' =>  $content_id,
            'content_id_int' =>  $content_id,
            'status' => $content['status'],
            'title' => $content['title'],
            'create_date' =>  $content['created_date'],
            'is_deleted' =>  $content['is_deleted'],
            'is_group' =>  $content['is_group'],
            'reg_user_id' =>  $content['reg_user_id'],
            'reg_user_nm' =>  $content['reg_user_nm'],
            'category_id' =>  $content['category_id'],
            'category_title' =>  $content['category_title']

        ];
        if($content['is_hidden'] == '1')
		{
			$hiddden_val = 'Y';
		}else
		{
			$hiddden_val = 'N';
		}
        $data['is_hidden'] = $hiddden_val;

		if(!empty($usr_meta_value)){
			foreach ($usr_meta_value as $k=>$v)
			{                
                $data[$k.'_t'] = $v;
                $data[$k.'_s'] = $v;
			}
		}

		if(!empty($array_data)) {
			foreach($array_data as $k => $v) {
                $data[$k.'_s'] = $v;
			}
		}

        return $data;
    }
    
    function solrUpdateList($lists)
    {
        $add =  new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><add />');

        foreach($lists as $idx => $list)
        {
            $doc = $add->addChild('doc');
            foreach($list as $fieldName => $value)
            {
                $value = $this->esc(trim($value));
                $value = strip_tags( $value );
                $field = $doc->addChild('field', $value);
                $field->addAttribute('name', $fieldName );
            }
        }

		$str = $add->asXML();
		if($str){
			$success = $this->request($str, "solr/collection1/update");
			return $success;
		}
		return 0;
    }

	function solrCommit()
	{
		return $this->request("<commit/>", "solr/collection1/update");
	}

	function request($reqData, $type)
	{
        $logging = true;        
        
		if($type == "solr/collection1/update"){
            $header[] = "Content-type: text/xml; charset=UTF-8";
            $type = $type.'?commitWithin=1000&overwrite=true';
		}
		else if($type == "solr/gmSearch"){
			$header[] = "Content-type: text/xml; charset=UTF-8";
		}
		else{
			$header[] = "";//"Content-type: text/xml; charset=UTF-8";
        }
      
        $url = $this->url.$type;
        if ($logging) {
            $this->logger->info("url: ". $url);
            $this->logger->debug(" reqData: ". $reqData);
        }
		$session = curl_init();
		curl_setopt($session, CURLOPT_HEADER,         false);
		curl_setopt($session, CURLOPT_HTTPHEADER,     $header);
		curl_setopt($session, CURLOPT_URL,            $url);
		curl_setopt($session, CURLOPT_POSTFIELDS,     $reqData);
		curl_setopt($session, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($session, CURLOPT_POST,           1);

        $response = curl_exec($session);
        if ($logging && $type == "solr/collection1/update") {
            $this->logger->info( "response:". $response );
            if ( !strstr($response, '<int name="status">0</int>') ){
                $this->logger->error( "type: ". $type .", reqData: ". $reqData );
                $this->logger->error('response:'. $response );
            }
        }
		if ($logging && $response === false)
		{
            $this->logger->error( "type: ". $type .", reqData: ". $reqData );
            $this->logger->error('response: '.'false' );
			throw new \Exception('검색엔진 오류: '.curl_error($session));
		}

		curl_close($session);

//		return '<int name="status">0</int>';

		return $response;
	}

	function esc($str)
	{
		$str = str_replace('&', '&amp;',  $str);
		$str = str_replace('<', '&lt;',   $str);
		$str = str_replace('>', '&gt;',   $str);
		$str = str_replace('"', '&quot;', $str);
        $str = str_replace('\'', '&#39;', $str);
        
        //보이지않는 문자열 제거 처리
        $str = preg_replace('/[^[:print:]]/u', '', $str);

		return $str;
	}

	function search($q, $start=0, $limit=20, $sort = null, $highlighting = false,$fields =null, $fq = null)
	{
		return $this->solrQuery($q, $start, $limit, $sort, $highlighting,$fields, $fq);
	}

	function add($id, $group_name)
	{
		$this->update($id, $group_name);
	}

	function updateEach($id, $group_name=null)
	{
        global $arr_sys_code;

        $group_name = $group_name ?? self::group;
        
        if( empty($id) ) return true;

		$dummy_meta = array();

        //$content = $this->db->queryRow("select * from bc_content where content_id=".$id);
        $content = $this->db->queryRow(" 
            SELECT             
            (SELECT USER_NM FROM BC_MEMBER WHERE USER_ID=C.REG_USER_ID ) USER_NM,
            (SELECT CATEGORY_TITLE FROM BC_CATEGORY WHERE CATEGORY_ID=C.CATEGORY_ID ) CATEGORY_TITLE,
            c.* 
            FROM
            BC_CONTENT c
            WHERE CONTENT_ID=$id");
		if (empty($content))
		{
			throw new \Exception(_text('MSG00052'));//MSG00052 등록할 수 없는 콘텐츠 입니다
		}

		$usr_meta_table = $this->db->queryOne("select ud_content_code from bc_ud_content where ud_content_id = ".$content['ud_content_id']."");
		$usr_meta_fields = $this->db->queryAll("select * from bc_usr_meta_field where ud_content_id = ".$content['ud_content_id']." and usr_meta_field_type != 'container'");

		$usr_meta_value_s = array();

		foreach($usr_meta_fields as $meta_field_row)
		{
           if ($meta_field_row ['is_search_reg'] == 1) {
               $usr_meta_value_s[] = $meta_field_row['usr_meta_field_code'];
           }
		}

		if(!empty($usr_meta_value_s)){
			$usr_meta_value = $this->db->queryRow("select ".join($usr_meta_value_s, ',')." from bc_usrmeta_".$usr_meta_table." where usr_content_id = ".$id);
		}

		if ( empty($usr_meta_value) || empty($usr_meta_value_s) ) {
			return;
        }
		//2016-11-29 SSLee
		//Add search engine, 6 fields.
		//Default story board information.
		//Shot list and Mark information will be added when use_yn is Y
		$array_data = array();
		// if($arr_sys_code['shot_list_yn']['use_yn'] == 'Y') {
		// 	$shot_query_select = " B.SHOT_LIST_TITLE,C.SHOT_LIST_COMMENTS, ";
		// 	$shot_query_from = " LEFT OUTER JOIN (SELECT CONTENT_ID, TITLE AS SHOT_LIST_TITLE FROM BC_SHOT_LIST GROUP BY CONTENT_ID) B ON A.CONTENT_ID=B.CONTENT_ID 
		// 		 LEFT OUTER JOIN (SELECT CONTENT_ID, COMMENTS AS SHOT_LIST_COMMENTS FROM BC_SHOT_LIST GROUP BY CONTENT_ID) C ON A.CONTENT_ID=C.CONTENT_ID  ";
		// }
		// if($arr_sys_code['mark_list_use_yn']['use_yn'] == 'Y') {
		// 	$mark_query_select = "D.MARK_TITLE,E.MARK_COMMENTS,";
		// 	$mark_query_from = " LEFT OUTER JOIN (SELECT CONTENT_ID, TITLE AS MARK_TITLE FROM BC_MARK GROUP BY CONTENT_ID) D ON A.CONTENT_ID=D.CONTENT_ID 
		// 		 LEFT OUTER JOIN (SELECT CONTENT_ID, COMMENTS AS MARK_COMMENTS FROM BC_MARK GROUP BY CONTENT_ID) E ON A.CONTENT_ID=E.CONTENT_ID 
		// 		  ";
		// }
		// $array_data_query = "
		// 	SELECT
		// 		".$shot_query_select.$mark_query_select."
		// 		F.STORY_BOARD_TITLE,
		// 		G.STORY_BOARD_COMMENTS
		// 	FROM	VIEW_BC_CONTENT A 
		// 		".$shot_query_from.$mark_query_from."
		// 		 LEFT OUTER JOIN (SELECT CONTENT_ID, TITLE AS STORY_BOARD_TITLE FROM (SELECT B.CONTENT_ID, A.* FROM BC_STORY_BOARD A INNER JOIN BC_MEDIA B ON A.MEDIA_ID=B.MEDIA_ID) A GROUP BY CONTENT_ID) F ON A.CONTENT_ID=F.CONTENT_ID 
		// 		 LEFT OUTER JOIN (SELECT CONTENT_ID, COMMENTS AS STORY_BOARD_COMMENTS FROM (SELECT B.CONTENT_ID, A.* FROM BC_STORY_BOARD A INNER JOIN BC_MEDIA B ON A.MEDIA_ID=B.MEDIA_ID) A GROUP BY CONTENT_ID) G ON A.CONTENT_ID=G.CONTENT_ID 
		// 	WHERE  A.content_ID=".$id;
		// $array_all_data = $this->db->queryRow($array_data_query);
		// foreach($array_all_data as $col => $val) {
		// 	$array_data[$col] = $val;
        // }

        //콘텐츠 상태 업데이트
        $contentStatus = $this->db->queryRow("SELECT 
        content_id, archive_status,resolution,archv_sttus,catlg_sttus,trnsmis_at,trnsmis_sttus,restore_at,review_status,mcr_trnsmis_sttus,scr_trnsmis_sttus,trnsmis_at,restore_sttus,bfe_video_id,dtl_archv_sttus 
        FROM BC_CONTENT_STATUS 
        WHERE CONTENT_ID=$id ");

        if( !empty($contentStatus) ){
            foreach($contentStatus as $statusKey => $statusVal ){
                if( $statusKey == 'content_id') continue;
                $array_data[$statusKey] = $statusVal;
            }
        }

        
        // $storyQuery = "SELECT 
        // A.title || ' ' || a.COMMENTS AS story
        // FROM BC_STORY_BOARD A 
        // JOIN 
        // BC_MEDIA B 
        // ON (A.MEDIA_ID=B.MEDIA_ID) WHERE a.IS_DELETED='N' AND B.CONTENT_ID=$id";
        // $story = $this->db->queryAll($storyQuery);
        // if( !empty($story) ){
        //     $storyData = [];
        //     foreach($story as $row){
        //         $storyData [] =$row['story'];
        //     }
        //     $storyData = join (' ',$storyData );
        //     $array_data['story'] = $storyData;
        // }
        //2018-01-12 이승수. 카테고리가 지워진 경우 부모를 찾아갈 수 없으니, BC_CONTENT의 카테고리 정보를 활용.
        //$category_full_path = '/0'.$this->getCategoryFullPath_for_solr($content['category_id']);
        $category_full_path = $content['category_full_path'];
        $storage_type = '';
		$result_update = $this->solrUpdate($group_name, $storage_type, $id, $content, $usr_meta_value, $array_data);

		//if ( !strstr($result_update, '<int name="status">0</int>') ) throw new \Exception('update failure : update>>'.$result_update);

		$this->commit();

		return $result_update;
    }
    
    
	function update($id, $group_name=null)
	{
        global $arr_sys_code;

        $group_name = $group_name ?? self::group;
        
        if( empty($id) ) return true;

        $dummy_meta = array();

        $udContentId = 3;

        if(is_array($id)){
            $contentIds = $id;
        }else{
            $contentIds = [$id];
        }
        
        $codes = $this->db->queryAll("
        SELECT cs.CODE_SET_CODE, ci.CODE_ITM_CODE AS key,
                    ci.CODE_ITM_NM AS val,
                    ci.SORT_ORDR
                    FROM DD_CODE_SET CS 
                    JOIN DD_CODE_ITEM CI 
                    ON (cs.ID=ci.CODE_SET_ID) 
                    WHERE cs.DELETE_DT IS NULL 
                    AND ci.DELETE_DT IS NULL ");
        $codeList = [];
        foreach($codes as $code)
        {
            $codeList[$code['code_set_code']][$code['key']] = $code['val'];
        }

        
		$usr_meta_table = $this->db->queryOne("select ud_content_code from bc_ud_content where ud_content_id = $udContentId ");
        $usr_meta_fields = $this->db->queryAll("select * from bc_usr_meta_field where ud_content_id =$udContentId and usr_meta_field_type != 'container'");
        
        //메타데이터 목록
        $requestData = [];
        foreach($contentIds as $id)
        {
            //$content = $this->db->queryRow("select * from bc_content where content_id=".$id);
            $content = $this->db->queryRow(" 
                SELECT             
                (SELECT USER_NM FROM BC_MEMBER WHERE USER_ID=C.REG_USER_ID ) REG_USER_NM,
                (SELECT CATEGORY_TITLE FROM BC_CATEGORY WHERE CATEGORY_ID=C.CATEGORY_ID ) CATEGORY_TITLE,
                c.* 
                FROM
                BC_CONTENT c
                WHERE CONTENT_ID=$id");
            if (empty($content))
            {
                
                $this->logger->error('empty content:' . _text('MSG00052') );
                throw new \Exception(_text('MSG00052'));//MSG00052 등록할 수 없는 콘텐츠 입니다
            }
            $usr_meta_value_s = array();
            
            $comboList =   [];
            foreach($usr_meta_fields as $meta_field_row)
            {
            if ($meta_field_row ['is_search_reg'] == 1) {
                $usr_meta_value_s[] = $meta_field_row['usr_meta_field_code'];
            }
            if( $meta_field_row ['usr_meta_field_type'] == 'combo' ){
                    $comboList [] = $meta_field_row['usr_meta_field_code'];
            }
            }

            if(!empty($usr_meta_value_s)){
                $usr_meta_value = $this->db->queryRow("select ".join($usr_meta_value_s, ',')." from bc_usrmeta_".$usr_meta_table." where usr_content_id = ".$id);
            }

            if ( empty($usr_meta_value) || empty($usr_meta_value_s) ) {
                return;
            }

            if( !empty($comboList) ){
                foreach($usr_meta_value as $code => $value)
                {
                    $renVal = $codeList[strtoupper($code)][$value];
                    if(!is_null($renVal)){
                        $usr_meta_value[$code] = $renVal;
                    }
                }
            }
            //2016-11-29 SSLee
            //Add search engine, 6 fields.
            //Default story board information.
            //Shot list and Mark information will be added when use_yn is Y
            $array_data = array();
            //콘텐츠 상태 업데이트
            $contentStatus = $this->db->queryRow("SELECT 
            content_id, archive_status,resolution,archv_sttus,catlg_sttus,trnsmis_at,trnsmis_sttus,restore_at,review_status,mcr_trnsmis_sttus,scr_trnsmis_sttus,trnsmis_at,restore_sttus,bfe_video_id,dtl_archv_sttus 
            FROM BC_CONTENT_STATUS 
            WHERE CONTENT_ID=$id ");
            if( !empty($contentStatus) ){
                foreach($contentStatus as $statusKey => $statusVal ){
                    if( $statusKey == 'content_id') continue;
                    $array_data[$statusKey] = $statusVal;
                }
            }

            $category_full_path = $content['category_full_path'];
            $storage_type = '';

            $requestData [] = $this->updateFieldMap($group_name, $id, $content, $usr_meta_value, $array_data);
          
            //if ( !strstr($result_update, '<int name="status">0</int>') ) throw new \Exception('update failure : update>>'.$result_update);
            
        }

        $result = $this->solrUpdateList($requestData);
        $this->commit();        
        //if ( !strstr($result, '<int name="status">0</int>') ) throw new \Exception('update failure : update>>'.$result);

		return $result;
	}


	function delete($id)
	{
		$this->solrDelete($id);
        $this->solrCommit();
        return true;
	}

	function commit()
	{
        //$this->solrCommit();
        return true;
	}

	function getCategoryFullPath_for_solr($id)
	{
		if(!empty($id))
		{
			$parent_id = $this->db->queryOne("select parent_id from bc_category where category_id=".$id);
			if ($parent_id != -1 && $parent_id !== 0 && !empty($parent_id))
			{
				$self_id = $this->getCategoryFullPath_for_solr($parent_id);
			}

			return str_replace('//', '/', $self_id.'/'.$id.'/');
		}
	}
}
?>