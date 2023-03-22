<?php
    require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
    require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');

    global $db;

    $cat_id = $_POST['category_id'];
    $abr_method = $_POST['abr_method'];
    switch($abr_method)
    {
        case 'A' :
            $abr_period = $_POST['abr_period'];
        break;
        case 'M' :
            $abr_period = '';
        break;
        case 'N' :
            $abr_period = '';
            $abr_method = '';
        break;
    }
    $cur_time = date('YmdHis');

	if( DB_TYPE == 'oracle' ){
		$query = "
			SELECT CATEGORY_ID
			FROM BC_CATEGORY
			START WITH CATEGORY_ID = $cat_id
			CONNECT BY PRIOR CATEGORY_ID = PARENT_ID
		";
	}else{
		$query = "
			WITH RECURSIVE q AS (
				SELECT	ARRAY[po.CATEGORY_ID] AS HIERARCHY
						,po.CATEGORY_ID
						,po.CATEGORY_TITLE
						,po.PARENT_ID
						,1 AS LEVEL
				FROM	BC_CATEGORY po
				WHERE	po.CATEGORY_ID = $cat_id
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

    //$query = "select category_id from bc_category start with category_id = $cat_id connect by prior category_id = parent_id";
    $categories_id = $db->queryAll($query);

    foreach($categories_id as $category_id)
    {
        $c_id = $category_id['category_id'];
        $query = "select count(*) from bc_category_env where category_id=$c_id";
        $is_category = $db->queryOne($query);
        if($is_category == 0)
        {
            $query = "insert into bc_category_env (category_id, abr_method, abr_period, edit_date)
                    values($c_id, '$abr_method', '$abr_period', '$cur_time')";
            $db->exec($query);
        }
        else
        {
            $query = "update bc_category_env
                         set abr_method = '$abr_method', abr_period = '$abr_period', edit_date = '$cur_time'
                       where category_id = $c_id";
            $db->exec($query);
        }
    }

    echo "자동폐기 설정값이 성공적으로 변경 되었습니다";
?>
