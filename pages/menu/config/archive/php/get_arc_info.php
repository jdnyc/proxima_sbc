<?php
    require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
    require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');

    global $db;

    $id = $_POST['id'];
    if(empty($id))
    {
        $id = 0;
    }

    $limit = $_POST['rows'];
    if(empty($limit))
    {
        $limit = 20;
    }
    $start = $_POST['page'];
    if(empty($start))
    {
        $start = 1;
    }
    $offset = ($start-1)*$limit;

	if( DB_TYPE == 'oracle' ){
		$query = "select category_id from bc_category start with category_id = $id connect by prior category_id = parent_id";
	}else{
		$query = "
			WITH RECURSIVE q AS (
				SELECT	ARRAY[po.CATEGORY_ID] AS HIERARCHY
						,po.CATEGORY_ID
						,po.CATEGORY_TITLE
						,po.PARENT_ID
						,1 AS LEVEL
				FROM	BC_CATEGORY po
				WHERE	po.CATEGORY_ID = $id
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

    //$query = "select category_id from bc_category start with category_id = $id connect by prior category_id = parent_id";
    $db->setLimit($limit, $offset);
    $categories = $db->queryAll($query);

    $query = "select count(*) from bc_category start with category_id = $id connect by prior category_id = parent_id";
    $total_count = $db->queryOne($query);

    $items = array();

    foreach($categories as $category)
    {
        $category_id = $category['category_id'];

        $query = "select bc.category_id, ce.arc_method, ce.arc_period, ce.edit_date, ce.del_method, ce.del_period
                    from bc_category bc, bc_category_env ce
                   where bc.category_id = '$category_id'
                     and bc.category_id = ce.category_id";
        $category = $db->queryRow($query);

        $query = "select category_title from bc_category where category_id = $category_id";
        $cat_title = $db->queryRow($query);

        if($category['arc_method'] == 'A')
        {
            $arc_method = "자동";
        }
        else if($category['arc_method'] == 'M')
        {
            $arc_method = "수동";
        }
        else {
            $arc_method = "Archive 안함";
        }

        if(empty($category['arc_period']) || $category['arc_period']=='-')
        {
            $arc_period = $category['arc_period'];
        }
        else
        {
            $arc_period = $category['arc_period'].'일';
        }


        $item = array(
            'category_id' => $category['category_id'],
            'category_title' => $cat_title['category_title'],
            'arc_method' => $arc_method,
            'arc_period' => $arc_period,
            'edit_date' => $category['edit_date'],
            'del_method' => $category['del_method'],
            'del_period' => $category['del_period']
        );
        array_push($items, $item);
    }

    $result["total"] = $total_count;
    $result["rows"] = $items;


    echo json_encode($result);
?>