<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
try
{
	$start= $_POST['start'];
	$limit = $_POST['limit'];
	$searchType = $_POST['search_type'];
    $searchValue = $_POST['search_value'];

    $sort = $_POST['sort'];
    $dir = $_POST['dir'];
   

	$startDate = $_POST['start_date'];
	$endDate = $_POST['end_date'];
	
	$search = "";
	if(($searchType != null) && ($searchType != 'All') && ($searchValue != "")){
		if(($searchType == 'title') || ($searchType == 'user_id')){
			$search = " AND A.{$searchType} LIKE '%{$searchValue}%'";
		}

		if(($searchType == 'ingest_system_ip') || ($searchType == 'channel')){
			$search = " AND A.{$searchType} = '{$searchValue}'";
		}		
	};
	if($searchType == 'create_time'){
	$search = " AND A.{$searchType} BETWEEN {$startDate} AND {$endDate}";
    }

    if( !empty($dir) && !empty($sort) ){
        if( $sort == 'ip_name' ){
            $sort = 'INGEST_SYSTEM_IP';
        }
        $order = " ORDER BY A.{$sort} {$dir}, A.CHANNEL ,A.DATE_TIME, A.START_TIME ";
    }else{
        $order = " ORDER BY A.INGEST_SYSTEM_IP, A.CHANNEL ,A.DATE_TIME, A.START_TIME ";
    }
    $db->setLimit($limit, $start);
	$result = $db->queryAll("
				SELECT	A.*, B.NAME AS IP_NAME, M.USER_NM
				FROM	INGESTMANAGER_SCHEDULE A
						LEFT OUTER JOIN BC_MEMBER M ON M.USER_ID = A.USER_ID
						,
						(
							SELECT	AA.CODE AS CODE, AA.NAME AS NAME
							FROM	BC_CODE AA, BC_CODE_TYPE BB
							WHERE	AA.CODE_TYPE_ID = BB.ID
							AND		BB.CODE = 'ingest_ip'
						) B
				WHERE	B.CODE=A.INGEST_SYSTEM_IP{$search}
				
			".$order);

	echo json_encode(array(
		'success' => true,
		'data' => $result
	));
	
} catch(Exception $e) {
	die(json_encode(array(
        'success' => false,
        'msg' => $e->getMessage().'('.$db->last_query.')'
    )));
}
?>
