<?php 


require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');

$search_word = $_REQUEST['search_word'];//검색했을 시



try{
$where = "";
if($search_word){
    $where = "
    AND BCP.CODE LIKE '%".$search_word."%'  OR BCP.CODE_PATH LIKE '%".$search_word."%' OR BCP.DESCRIPTION LIKE '%".$search_word."%'
    ";
}
$query = "
        SELECT 
        BCP.ID,
        BCP.CODE,
        BCP.CODE_PATH,
        BCP.DESCRIPTION,
        BCP.PARENT_ID,
        BCP.USE,
        BCP.P_DEPTH,
            (
        SELECT 
            LISTAGG(G.MEMBER_GROUP_ID, ',') WITHIN GROUP(ORDER BY G.MEMBER_GROUP_ID)
        FROM BC_MEMBER_GROUP G, 
                BC_PERMISSION_GROUP PG
        WHERE PG.PERMISSION_ID = BCP.ID AND PG.MEMBER_GROUP_ID = G.MEMBER_GROUP_ID
            ) AS GROUPS ,
            (
        SELECT 
            LISTAGG(G.MEMBER_GROUP_NAME, ', ') WITHIN GROUP(ORDER BY G.MEMBER_GROUP_ID)
        FROM BC_MEMBER_GROUP G, 
                BC_PERMISSION_GROUP PG
        WHERE PG.PERMISSION_ID = BCP.ID AND PG.MEMBER_GROUP_ID = G.MEMBER_GROUP_ID
            ) AS GROUPS_NAME
        FROM BC_PERMISSION BCP
        WHERE BCP.DELETED_AT IS NULL
    ".$where."
    ";
    // $total = $db->queryOne("
    //             SELECT COUNT(*)
    //             FROM    (".$query.") CNT ");
    $total = $db->queryOne("
                SELECT COUNT(*)
                FROM		(".$query.") CNT
            ");
    $order = "ORDER BY BCP.ID ASC ";
    $result = $db->queryAll($query.$order);

    echo json_encode(array(
        'success'=> true,
        'total'=> $total,
        'data' => $result,
        'query'=> $query.$order
    ));
}catch(Exception $e){
	echo json_encode(array(
		'success' => false,
		'msg' => $e->getMessage()
	));

}
?>