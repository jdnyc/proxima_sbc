<?php
    require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
    require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');

    global $db;

 //   $id = $_POST['id'];
    $id = '327';
    $start = $_POST['start'];
    $limit = $_POST['limit'];

    if(empty($limit))
    {
        $limit = 20;
    }
    if(empty($start))
    {
        $start = 0;
    }

    try
    {
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
       $db->setLimit($limit, $start);
  //     $total = $db->queryOne("select count(*) from (".$query.") cnt");
       $total = 1;
       $categories = $db->queryAll($query);

   //    print_r($categories);
       $get_data = array();
       foreach ($categories as $category) {
           $category_id = $category['category_id'];
   //        echo ("category id===>").$category_id;
           $query = "select bc.category_id, bc.category_title, ce.arc_method, ce.arc_period, ce.edit_date, ce.del_method, ce.del_period
                       from bc_category bc, bc_category_env ce
                      where bc.category_id = '$category_id'
                        and bc.category_id = ce.category_id";
           $data = $db->queryRow($query);
           array_push($get_data, $data);

       }
       echo json_encode(array(
		'success' => true,
		'data' => $get_data,
		'total' => $total
	));
    }
    catch(Exception $e)
    {
        echo json_encode(array(
		'success' => false,
		'msg' => $e->getMessage() . '(' . $db->last_query . ')'
	));
	exit;
    }
?>

