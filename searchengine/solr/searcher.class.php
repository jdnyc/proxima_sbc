<?php
$path = dirname(__FILE__);

require_once($path.'/solr.class.php');
//require_once($_SERVER['DOCUMENT_ROOT'].'/searchengine/solr/solr.class.php');

class Searcher extends Solr
{
	private $db;

	function __construct($db)
	{
		$this->db = $db;
	}

	function search($q, $start=0, $limit=20, $sort = null, $highlighting = false)
	{
		return $this->solrQuery($q, $start, $limit, $sort, $highlighting);
	}

	function queryOne($id, $field)
	{
		$result = $this->query($id);

		$xml = simplexml_load_string($result);

		return $result[$field];
	}

	function add($id, $group_name)
	{
		$this->update($id, $group_name);
	}

	function update($id, $group_name=null)
	{
		global $arr_sys_code;

		$dummy_meta = array();

        //$content = $this->db->queryRow("select * from bc_content where content_id=".$id);
        $content = $this->db->queryRow("
            select a.*, b.category_title, 
            COALESCE( c.user_nm, a.reg_user_id ) as reg_user_nm
                    from (
                        select * 
                        from bc_content
                        where content_id=".$id."
                        ) a
                        left outer join bc_category b
                        on a.category_id=b.category_id
                        left outer join bc_member c
                        on a.reg_user_id=c.user_id
        ");
		if (empty($content))
		{
			throw new Exception(_text('MSG00052'));//MSG00052 등록할 수 없는 콘텐츠 입니다
		}

		$usr_meta_table = $this->db->queryOne("select ud_content_code from bc_ud_content where ud_content_id = ".$content['ud_content_id']."");
		$usr_meta_fields = $this->db->queryAll("select * from bc_usr_meta_field where ud_content_id = ".$content['ud_content_id']." and usr_meta_field_type != 'container'");

		$usr_meta_value_s = array();

		foreach($usr_meta_fields as $meta_field_row)
		{
			$usr_meta_value_s[] = 'USR_'.$meta_field_row['usr_meta_field_code'];
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
		if($arr_sys_code['shot_list_yn']['use_yn'] == 'Y') {
			$shot_query_select = " B.SHOT_LIST_TITLE,C.SHOT_LIST_COMMENTS, ";
			$shot_query_from = " LEFT OUTER JOIN (SELECT CONTENT_ID, STRING_AGG(TITLE,' ') AS SHOT_LIST_TITLE FROM BC_SHOT_LIST GROUP BY CONTENT_ID) B ON A.CONTENT_ID=B.CONTENT_ID 
				 LEFT OUTER JOIN (SELECT CONTENT_ID, STRING_AGG(COMMENTS,' ') AS SHOT_LIST_COMMENTS FROM BC_SHOT_LIST GROUP BY CONTENT_ID) C ON A.CONTENT_ID=C.CONTENT_ID  ";
		}
		if($arr_sys_code['mark_list_use_yn']['use_yn'] == 'Y') {
			$mark_query_select = "D.MARK_TITLE,E.MARK_COMMENTS,";
			$mark_query_from = " LEFT OUTER JOIN (SELECT CONTENT_ID, STRING_AGG(TITLE,' ') AS MARK_TITLE FROM BC_MARK GROUP BY CONTENT_ID) D ON A.CONTENT_ID=D.CONTENT_ID 
				 LEFT OUTER JOIN (SELECT CONTENT_ID, STRING_AGG(COMMENTS,' ') AS MARK_COMMENTS FROM BC_MARK GROUP BY CONTENT_ID) E ON A.CONTENT_ID=E.CONTENT_ID 
				  ";
		}
		$array_data_query = "
			SELECT
				".$shot_query_select.$mark_query_select."
				F.STORY_BOARD_TITLE,
				G.STORY_BOARD_COMMENTS
			FROM	VIEW_BC_CONTENT A 
				".$shot_query_from.$mark_query_from."
				 LEFT OUTER JOIN (SELECT CONTENT_ID, STRING_AGG(TITLE,' ') AS STORY_BOARD_TITLE FROM (SELECT B.CONTENT_ID, A.* FROM BC_STORY_BOARD A INNER JOIN BC_MEDIA B ON A.MEDIA_ID=B.MEDIA_ID) A GROUP BY CONTENT_ID) F ON A.CONTENT_ID=F.CONTENT_ID 
				 LEFT OUTER JOIN (SELECT CONTENT_ID, STRING_AGG(COMMENTS,' ') AS STORY_BOARD_COMMENTS FROM (SELECT B.CONTENT_ID, A.* FROM BC_STORY_BOARD A INNER JOIN BC_MEDIA B ON A.MEDIA_ID=B.MEDIA_ID) A GROUP BY CONTENT_ID) G ON A.CONTENT_ID=G.CONTENT_ID 
			WHERE  A.content_ID=".$id;
		$array_all_data = $this->db->queryRow($array_data_query);
		foreach($array_all_data as $col => $val) {
			$array_data[$col] = $val;
		}


        //2018-01-12 이승수. 카테고리가 지워진 경우 부모를 찾아갈 수 없으니, BC_CONTENT의 카테고리 정보를 활용.
        //$category_full_path = '/0'.$this->getCategoryFullPath_for_solr($content['category_id']);
        $category_full_path = $content['category_full_path'];
        $storage_type = '';
		$result_update = $this->solrUpdate($group_name, $storage_type, $id, $content, $usr_meta_value, $array_data);

		if ( !strstr($result_update, '<int name="status">0</int>') ) throw new Exception('solrUpdate failure : update>>'.$result_update);

		$this->commit();

		return $result_update;
	}

	function delete($id)
	{
		$this->solrDelete($id);

		$this->commit();
	}

	function commit()
	{
		$result_commit = $this->solrCommit();

		//echo $result_commit;

		if ( !strstr($result_commit, '<int name="status">0</int>') ) throw new Exception('solrUpdate failure');
	}

	function getTCDescs($content_id, $meta_table_id)
	{
		$tc_list = $this->db->queryAll("select * from meta_multi where content_id=".$content_id);

		$column = $this->getTCDescColumn($meta_table_id);

		$r = array();

		foreach ($tc_list as $tc)
		{
			$v = json_decode($tc['value'], true);

			array_push($r, $v[$column]);
		}

		return join("\t", $r);
	}

	function getTCDescColumn($meta_table_id)
	{
		switch ($meta_table_id)
		{
			case PRE_PRODUCE:
				$column = 'columnF';
			break;

			case CLEAN:
				$column = 'columnG';
			break;
		}

		return $column;
	}

	function getCategoryFullPath_for_solr($id)
	{
		global $db;
		if(!empty($id))
		{
			$parent_id = $db->queryOne("select parent_id from bc_category where category_id=".$id);
			if ($parent_id != -1 && $parent_id !== 0 && !empty($parent_id))
			{
				$self_id = $this->getCategoryFullPath_for_solr($parent_id);
			}

			return str_replace('//', '/', $self_id.'/'.$id.'/');
		}
	}
}
?>