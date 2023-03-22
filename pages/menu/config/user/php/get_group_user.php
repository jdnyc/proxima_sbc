<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
session_start();
fn_checkAuthPermission($_SESSION);
try {
    //user+group list
    $query = "
        SELECT
            coalesce(CNT.CNT,0),
            MG.MEMBER_GROUP_NAME || ' (' || coalesce(CNT.CNT,0) || ')'  GROUP_TITLE,
            M.MEMBER_ID, M.USER_ID, M.USER_NM, M.DEPT_NM, M.EMAIL, M.PHONE, M.CREATED_DATE MEMEBER_CREATED_DATE,
            MG.MEMBER_GROUP_ID, MG.MEMBER_GROUP_NAME, MG.IS_DEFAULT, MG.DESCRIPTION, MG.PARENT_GROUP_ID, MG.CREATED_DATE GROUP_CREATED_DATE, MG.IS_ADMIN
        FROM
            BC_MEMBER_GROUP MG
                LEFT OUTER JOIN
                    (
                        SELECT MG.MEMBER_GROUP_ID, COUNT(*) CNT
                        FROM BC_MEMBER_GROUP_MEMBER MG
                        GROUP BY MG.MEMBER_GROUP_ID
                    )CNT
                ON
                CNT.MEMBER_GROUP_ID = MG.MEMBER_GROUP_ID
                LEFT OUTER JOIN
                    (
                        SELECT
                            MGM.MEMBER_GROUP_ID,
                            M.MEMBER_ID, M.USER_ID, M.USER_NM, M.DEPT_NM, M.EMAIL, M.PHONE, M.CREATED_DATE
                        FROM
                            BC_MEMBER M, BC_MEMBER_GROUP_MEMBER MGM
                        WHERE
                            M.MEMBER_ID = MGM.MEMBER_ID
                        AND
                        	M.DEL_YN = 'N'
                    )M
                ON
                    MG.MEMBER_GROUP_ID = M.MEMBER_GROUP_ID
        ORDER BY
            MG.SHOW_ORDER ASC, M.USER_NM ASC
    ";

    $totalquery = "
        SELECT COUNT(*)
        FROM (".$query.")cnt
    ";

    $total = $db->queryOne($totalquery);
    $rows = $db->queryAll($query);
    $data = array();
    //date("Y-m-d H:i:s", $row['group_created_date'])

    if($total > 0){
        foreach($rows as $row){
            //$row['member_group_name']  = $row['member_group_name']+  ' : ' +$row['description']+'</br>'+'기본값 : '+$row['is_default'];
            array_push($data, array(
                $row['member_id'],
                $row['user_id'],
                $row['user_nm'],
                $row['dept_nm'],
                $row['email'],
                $row['phone'],
                $row['memeber_created_date'],
                $row['member_group_id'],
                $row['member_group_name'],
                $row['is_default'],
                $row['description'],
                $row['parent_group_id'],
                $row['group_created_date'],
                $row['is_admin'],
                $row['group_title']
            ));
        }
    }

    echo json_encode($data);
}
catch(Exception $e){
    $msg = $e->getMessage();

    switch($e->getCode()){
        case ERROR_QUERY:
            $msg = $msg.'( '.$db->last_query.' )';
        break;
    }

    die(json_encode(array(
        'success' => false,
        'msg' => $msg
    )));
}
?>