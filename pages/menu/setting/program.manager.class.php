<?php

$ANONYMOUS_GROUP = '';

class ProgramManager {

    private $db;

    function __construct($db) {
        $this->db = $db;
    }

    function getPrograms($parent_id) {
        $sql = "select c.category_id as id, c.category_title as title, p.path";
        $sql .= " from bc_category c, path_mapping p";
        $sql .= " where c.category_id=p.category_id";
        $sql .= " and c.parent_id=" . $parent_id;
        $sql .= " order by c.category_title";

        $data = $this->db->queryAll($sql);

        return $data;
    }

    function insertCategory($title, $parent_id = null) {
        $category_id = getSequence('SEQ_BC_CATEGORY_ID');
        $title = str_replace("'", "''", $title);

        $sql = "insert into bc_category (CATEGORY_ID, PARENT_ID, CATEGORY_TITLE, CODE, SHOW_ORDER, NO_CHILDREN, EXTRA_ORDER) ";
        $sql .= " values ";
        if ($parent_id) {
            $sql .= "($category_id, $parent_id, '$title', null, null, null, null)";
        } else {
            $sql .= "($category_id, 0, '$title', null, null, null, null)";
        }

        $this->db->exec($sql);

        return $category_id;
    }

    function insertEpisode($parent_category_id, $program_code, $episode_name, $episode_number = null) {

        if (is_null($episode_number)) {
            $path = $program_code;
        } else {
            $path = $program_code . '/' . $episode_number;
        }

        $exists_program = $this->existsProgram($path);
        if ( ! $exists_program) {
            $category_id = $this->insertCategory($episode_name, $parent_category_id);

            $this->insertPathMapping($category_id, $path);
            $this->insertUserMapping($category_id);
        } else {
            $category_id = $exists_program['category_id'];
        }

        return $category_id;
    }

    /**
     * 프로그램이 존제하는지 확인
     *
     * @param  [string] $id 프로그램 코드
     * @return [boolean]
     */
    function existsProgram($path) {

        $sql = "select * from path_mapping where path='$path'";
        $result = $this->db->queryRow($sql);

        return $result;
    }

    function delete($category_id) {
		global $db_type;
		if( $db_type == 'oracle' ){
			$sql = "
				SELECT CATEGORY_ID
				FROM BC_CATEGORY
				START WITH CATEGORY_ID = $category_id
				CONNECT BY PRIOR CATEGORY_ID = PARENT_ID
			";
		}else{
			$sql = "
				WITH RECURSIVE q AS (
					SELECT	ARRAY[po.CATEGORY_ID] AS HIERARCHY
							,po.CATEGORY_ID
							,po.CATEGORY_TITLE
							,po.PARENT_ID
							,1 AS LEVEL
					FROM	BC_CATEGORY po
					WHERE	po.CATEGORY_ID = $category_id
					AND		po.IS_DELETED = 0
					UNION ALL
					SELECT	q.HIERARCHY || po.CATEGORY_ID
							,po.CATEGORY_ID
							,po.CATEGORY_TITLE
							,po.PARENT_ID
							,q.level + 1 AS LEVEL
					FROM	BC_CATEGORY po
							JOIN q ON q.CATEGORY_ID = po.PARENT_ID
					WHERE	po.IS_DELETED = 0
				)
				SELECT	CATEGORY_ID
						,CATEGORY_TITLE
						,PARENT_ID
				FROM	q
				WHERE 	CATEGORY_ID != 0
				AND		PARENT_ID = 0
				ORDER BY HIERARCHY
			";
		}
        //$sql = "select c.* ";
        //$sql .= "from bc_category c ";
        //$sql .= "start with c.category_id = $category_id ";
        //$sql .= "connect by prior c.CATEGORY_ID=c.PARENT_ID";

        $items = $this->db->queryAll($sql);
        foreach ($items as $item) {
            $this->deleteCategory($item['category_id']);
            $this->deletePathMapping($item['category_id']);
            $this->deleteUserMapping($item['category_id']);
        }
    }

    function deleteCategory($category_id) {
        $sql = "delete from bc_category where category_id=" . $category_id;
        $this->db->exec($sql);
    }

    function deletePathMapping($category_id) {
        $sql = "delete from path_mapping where category_id=" . $category_id;
        $this->db->exec($sql);
    }

    function deleteUserMapping($category_id) {
        $sql = "delete from user_mapping where category_id=" . $category_id;
        $this->db->exec($sql);
    }

    function insertPathMapping($category_id, $path) {
        $sql = "insert into path_mapping (CATEGORY_ID, PATH, MEMBER_GROUP_ID, STORAGE_GROUP, USING_REVIEW, UD_STORAGE_GROUP_ID, TYPE, QUOTA, USAGE) ";
        $sql .= " values ";
        $sql .= "($category_id, '$path', null, null, null, null, null, null, null)";

        $this->db->exec($sql);
    }

    function insertUserMapping($category_id) {
        $users = $this->getUsers();
        foreach ($users as $user) {
            $sql = "insert into user_mapping (CATEGORY_ID, USER_ID) ";
            $sql .= " values ";
            $sql .= "($category_id, '{$user['id']}')";

            $this->db->exec($sql);
        }
    }

    function getUsers() {
        $sql = "select user_id as id from bc_member";

        return $this->db->queryAll($sql);
    }
}