<?PHP
require_once($_SERVER['DOCUMENT_ROOT'] . '/lib/config.php');
session_start();
fn_checkAuthPermission($_SESSION);
try {
	$limit = !empty($_POST['limit']) ? $_POST['limit'] : 15;
	$start = !empty($_POST['start']) ? $_POST['start'] : 0;
	$key = $_POST['key'];
	$value = $_POST['value'];
	$filter = $_POST['filter'];
	$status = $_POST['status'];
	$user_id = $_SESSION['user']['user_id'];

	$stdt = $_POST['stdt'];
	$endt = $_POST['endt'];

	$condition = array();
	if (!empty($value)) {
		$values = preg_split('/[\s]+/', $value);
		foreach ($values as $v) {
			$v = strtoupper(trim($v));
			$v = str_replace("'", "''", $v);
			$v = str_replace("_", "\\_", $v);
			$v = str_replace("%", "\\%", $v);
			if (DB_TYPE == 'oracle') {
				array_push($condition, "UPPER(E.TITLE) like '%$v%' ESCAPE '\\'");
				// array_push($condition, "%$v%");
			} else {
				array_push($condition, "UPPER(D.TITLE) like '%$v%' ESCAPE '\\'");
			}
		}
	}

	if (DB_TYPE == 'oracle') {
		$query = "
			SELECT * FROM (
					SELECT
					  COUNT(*) OVER () TOTAL_ROWS,
					  MAX(A.TASK_WORKFLOW_ID) WORKFLOW_ID,
					  MIN(A.USER_TASK_NAME) WORKFLOW_NAME,
					  MIN(D.TASK_USER_ID) USER_ID,
					  FUNC_GET_USER_NAME(MIN(D.TASK_USER_ID)) USER_NAME,
					  D.SRC_CONTENT_ID CONTENT_ID,
					  D.ROOT_TASK,
					  MAX(D.TYPE) TYPE_JOB,
					  MAX(D.TASK_ID) TASK_ID,
					  MIN(E.TITLE) CONTENT_TITLE,
					  MIN(D.CREATION_DATETIME) CREATION_DATETIME,
					  (COALESCE(sum(d.progress), 0) / COUNT(*)) total_progress,
					  (SELECT COUNT(*)
						 FROM BC_TASK_WORKFLOW_RULE
						WHERE TASK_WORKFLOW_id=D.TASK_WORKFLOW_ID
					  ) TOTAL,
						CASE MAX(D.STATUS)
							WHEN 'complete' THEN '" . _text('MN00011') . "'--성공
							WHEN 'queue' THEN '" . _text('MN00039') . "'--대기
							WHEN 'processing' THEN '" . _text('MN00262') . "'--처리중
							WHEN 'error' THEN '" . _text('MN00012') . "'--실패
							ELSE MAX(D.STATUS)
						END STATUS,
					  count(decode(d.status, 'complete', 'complete')) count_complete,
					  count(decode(d.status, 'error', 'error')) count_error,
					  count(decode(d.status, 'queue', 'queue')) count_queue,
					  count(decode(d.status, 'processing', 'processing')) count_processing,
					  count(decode(d.status, 'cancel', 'cancel')) count_cancel,
					FROM
						(SELECT * FROM BC_TASK_WORKFLOW WHERE TASK_WORKFLOW_ID NOT IN (14, 510, 819, 825, 827, 828)) A,
						BC_TASK_WORKFLOW_RULE B,
						BC_TASK_RULE C,
						BC_TASK D,
						BC_CONTENT E
					WHERE A.TASK_WORKFLOW_ID = B.TASK_WORKFLOW_ID
					  AND B.TASK_RULE_ID=C.TASK_RULE_ID
					  AND B.WORKFLOW_RULE_ID=D.WORKFLOW_RULE_ID(+)
					  AND A.TASK_WORKFLOW_ID=D.TASK_WORKFLOW_ID
					  AND D.SRC_CONTENT_ID=E.CONTENT_ID(+)
					  AND D.STATUS != 'skip'
		";
	} else {
		$query = "
			SELECT	*
			FROM
						(
						SELECT	
									COUNT(*) OVER () TOTAL_ROWS,
									MAX(A.TASK_WORKFLOW_ID) WORKFLOW_ID,
									MIN(A.USER_TASK_NAME) WORKFLOW_NAME,
									MIN(D.TASK_USER_ID) USER_ID,
									FUNC_GET_USER_NAME(MIN(D.TASK_USER_ID)) USER_NAME,
									D.SRC_CONTENT_ID CONTENT_ID,
									D.ROOT_TASK,
									MAX(D.TYPE) TYPE_JOB,
									MAX(D.TASK_ID) TASK_ID,
									MIN(D.TITLE) CONTENT_TITLE,
									MIN(D.CREATION_DATETIME) CREATION_DATETIME,
									(COALESCE(SUM(d.progress), 0) / COUNT(*)) total_progress,
									(SELECT COUNT(*)
									 FROM BC_TASK_WORKFLOW_RULE
									WHERE TASK_WORKFLOW_id=D.TASK_WORKFLOW_ID
									) TOTAL,
									CASE MAX(D.STATUS)
										WHEN 'complete' THEN '" . _text('MN00011') . "'--성공
										WHEN 'queue' THEN '" . _text('MN00039') . "'--대기
										WHEN 'processing' THEN '" . _text('MN00262') . "'--처리중
										WHEN 'error' THEN '" . _text('MN00012') . "'--실패
										ELSE MAX(D.STATUS)
									END STATUS,
									SUM(CASE
											  WHEN d.status = 'complete' THEN 1
											  ELSE CAST(0 AS DOUBLE PRECISION)
											END
									)AS count_complete,
									SUM(CASE
											  WHEN d.status = 'error' THEN 1
											  ELSE CAST(0 AS DOUBLE PRECISION)
											END
									)AS count_error,
									SUM(CASE
											  WHEN d.status = 'queue' THEN 1
											  ELSE CAST(0 AS DOUBLE PRECISION)
											END
									)AS count_queue,
									SUM(CASE
											  WHEN d.status = 'processing' THEN 1
											  ELSE CAST(0 AS DOUBLE PRECISION)
											END
									)AS count_processing,
									SUM(CASE
											  WHEN d.status = 'cancel' THEN 1
											  ELSE CAST(0 AS DOUBLE PRECISION)
											END
									)AS count_cancel
						FROM		BC_TASK_WORKFLOW_RULE B
										LEFT JOIN (
											SELECT	F.TASK_ID, F.TASK_WORKFLOW_ID, F.SRC_CONTENT_ID, F.WORKFLOW_RULE_ID, F.CREATION_DATETIME, F.TASK_USER_ID, F.STATUS, F.PROGRESS, F.ROOT_TASK, F.TYPE,
														E.CONTENT_ID, E.TITLE
											FROM		BC_TASK F
															LEFT JOIN BC_CONTENT E
															ON   F.SRC_CONTENT_ID = E.CONTENT_ID
										) D
										ON B.WORKFLOW_RULE_ID = D.WORKFLOW_RULE_ID
									, BC_TASK_RULE C
									,(SELECT TASK_WORKFLOW_ID, USER_TASK_NAME FROM BC_TASK_WORKFLOW WHERE TASK_WORKFLOW_ID NOT IN (14, 510, 819, 825, 827, 828)) A
						WHERE	B.TASK_RULE_ID = C.TASK_RULE_ID
							AND  A.TASK_WORKFLOW_ID = B.TASK_WORKFLOW_ID
							AND  A.TASK_WORKFLOW_ID = D.TASK_WORKFLOW_ID
		";
	}



	if (!empty($stdt) && !empty($endt)) {
		$query .= " AND D.CREATION_DATETIME BETWEEN '" . $stdt . "000000' AND '" . $endt . "999999' ";
	} else if (!empty($stdt)) {
		$query .= " AND D.CREATION_DATETIME > '" . $stdt . "000000' ";
	} else if (!empty($endt)) {
		$query .= " AND D.CREATION_DATETIME < '" . $endt . "999999' ";
	}

	if ($key == 'content_title' && !empty($condition)) {
		$query .= " AND (" . join(' AND ', $condition) . ")";
	}
	if ($key == 'filename' && !empty($value)) {
		$query .= "AND D.TARGET LIKE '%$value%";
	}
	if ($filter == 2) {
		$query .= " AND D.TASK_USER_ID = '$user_id'";
	}

	if ($status == 2) {
		$query .= " AND D.STATUS = 'complete'";
	} else if ($status == 3) {
		$query .= " AND D.STATUS = 'processing'";
	} else if ($status == 4) {
		$query .= " AND D.STATUS = 'error'";
	}



	$query .= " GROUP BY D.ROOT_TASK, D.TASK_WORKFLOW_ID, D.SRC_CONTENT_ID) H
               ORDER BY CREATION_DATETIME DESC";
	$db->setLimit($limit, $start);

	$total = 0;
	$data = $db->queryAll($query);
	if (count($data) > 0) $total = $data[0]['total_rows'];

	$return_data = array();

	$root_tasks = array();
	foreach ($data as $row) {
		if (!empty($row['root_task'])) $root_tasks[] = $row['root_task'];
	}

	if (empty($root_tasks)) {
		echo json_encode(array(
			'success' => true,
			'status' =>  0,
			'message' => "OK",
			'total' => 0,
			'query'	=>	$query,
			'data' => $return_data
		));
		die();
	}

	$query_root_task_all_q = "
        SELECT B.JOB_NAME
        ,A.TASK_ID
        ,A.CREATION_DATETIME
        ,A.START_DATETIME
        ,A.COMPLETE_DATETIME
        ,A.PARAMETER
        ,A.PROGRESS
        ,A.TYPE
        ,CASE A.STATUS
            WHEN 'complete' THEN '" . _text('MN00011') . "'--성공
            WHEN 'queue' THEN '" . _text('MN00039') . "'--대기
            WHEN 'processing' THEN '" . _text('MN00262') . "'--처리중
			WHEN 'error' THEN '" . _text('MN00012') . "'--실패
            ELSE A.STATUS
        END AS STATUS

        ,A.SOURCE
        ,A.TARGET
    FROM  (      
        SELECT *
        FROM  BC_TASK
        WHERE ROOT_TASK IN (" . implode(',', $root_tasks) . ")
        ) A
        LEFT OUTER JOIN
        (
        SELECT A.WORKFLOW_RULE_ID
            ,C.JOB_NAME
        FROM  BC_TASK_WORKFLOW_RULE A
            LEFT OUTER JOIN 
            BC_TASK_WORKFLOW B
            ON (A.TASK_WORKFLOW_ID = B.TASK_WORKFLOW_ID)
            LEFT OUTER JOIN
            BC_TASK_RULE C
            ON (A.TASK_RULE_ID = c.TASK_RULE_ID)
        ) B
        ON (A.WORKFLOW_RULE_ID = B.WORKFLOW_RULE_ID)
    ORDER BY A.TASK_ID
    ";
	$query_root_task_all = $db->queryAll($query_root_task_all_q);
	$root_task_info = array();
	foreach ($root_tasks as $root_task) {
		$root_task_info[$root_task] = array();
	}
	foreach ($query_root_task_all as $qrta) {
		$root_task = $qrta['root_task'];
		$root_task_info[$root_task][] = $qrta;
	}

	foreach ($data as $row) {
		$root_task = $row['root_task'];

		// if( !empty($root_task) ) {
		// 	$root_task_query = "ROOT_TASK = $root_task ";
		// }
		// $query_all = "
		// 	SELECT B.JOB_NAME
		// 	      ,A.TASK_ID
		// 	      ,A.CREATION_DATETIME
		// 	      ,A.START_DATETIME
		// 	      ,A.COMPLETE_DATETIME
		// 	      ,A.PARAMETER
		// 	      ,A.PROGRESS
		// 	      ,A.TYPE
		// 		  ,CASE A.STATUS
		// 				WHEN 'complete' THEN '"._text('MN00011')."'--성공
		// 				WHEN 'queue' THEN '"._text('MN00039')."'--대기
		// 				WHEN 'processing' THEN '"._text('MN00262')."'--처리중
		// 				WHEN 'error' THEN '"._text('MN00012')."'--실패
		// 				ELSE A.STATUS
		// 		  END AS STATUS

		// 	      ,A.SOURCE
		// 	      ,A.TARGET
		// 	FROM  (      
		// 	      SELECT *
		// 	      FROM  BC_TASK
		// 	      WHERE ".$root_task_query."
		// 	      ) A
		// 	      LEFT OUTER JOIN
		// 	      (
		// 	      SELECT A.WORKFLOW_RULE_ID
		// 	            ,C.JOB_NAME
		// 	      FROM  BC_TASK_WORKFLOW_RULE A
		// 	            LEFT OUTER JOIN 
		// 	            BC_TASK_WORKFLOW B
		// 	            ON (A.TASK_WORKFLOW_ID = B.TASK_WORKFLOW_ID)
		// 	            LEFT OUTER JOIN
		// 	            BC_TASK_RULE C
		// 	            ON (A.TASK_RULE_ID = c.TASK_RULE_ID)
		// 	      ) B
		// 	      ON (A.WORKFLOW_RULE_ID = B.WORKFLOW_RULE_ID)
		// 	ORDER BY A.TASK_ID
		// 		  ";
		// $result_details = $db->queryAll($query_all);
		$result_details = $root_task_info[$root_task];
		$group_details = array();

		foreach ($result_details as $detail) {
			array_push($group_details, $detail);
		}

		$row['details'] = $group_details;
		array_push($return_data, $row);
	}
	echo json_encode(array(
		'success' => true,
		'status' =>  0,
		'message' => "OK",
		'total' => $total,
		'query'	=>	$query,
		'data' => $return_data
	));
} catch (Exception $e) {
	echo json_encode(array(
		'success' => false,
		'msg' => $e->getMessage()
	));
}
