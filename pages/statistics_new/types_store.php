<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');

try {

	if($_POST[is_excel] == 1)
	{
		$search_text = json_decode($_POST[search_f], true);
		$columngroup = json_decode($_POST[search_v], true);
		$title = trim($_POST[$title]);
		$production = empty($search_text[production]) ? 'X' :trim( $search_text[production]);
		$program = empty($search_text[program]) ? 'X' : trim($search_text[program]);
		$start_date = empty($search_text[start_date]) ? 'X' : date('Ymd', strtotime($search_text[start_date])).'000000';
		$end_date = empty($search_text[end_date]) ? 'X' : date('Ymd', strtotime($search_text[end_date])).'240000';
		$category = empty($search_text[category]) ? 'X' : $search_text[category];

		$types = array('first','second','third','fourth','fifth','sixth');
		$datas = array();
		foreach($types as $type_e)
		{
			$query = returnQuery($type_e, $title, $productio, $start_date, $end_date, $category);
			$data = $db->queryAll($query);
			array_push($datas, $data);
		}

		$columns = json_decode($_POST[columns], true);
		$arrays = array();
		foreach($datas as $data)
		{
			$array = array();
			foreach($data as $d)
			{
				$rows = array();
				foreach($columns as $col)
				{
					if( strstr($col[0], 'date')  )
					{
						if(empty($d[$col[0]]))
						{
							$value = '';
						}
						else
						{
							$value = substr($d[$col[0]],0,4).'-'.substr($d[$col[0]],4,2).'-'.substr($d[$col[0]],6,2);
						}
					}
					else
					{
						$value = $d[$col[0]];
					}
					$header = trim(str_replace('<br>', '', $col[0]));
					$rows[$header] = $value;
				}
				array_push($array, $rows);
			}
			array_push($arrays, $array);
		}
		echo createExcelFiles('분류체계_통계',$arrays, $columngroup);
	}
	else
	{
		$type = empty($_POST[type]) ? 'X' : $_POST[type];
		//$title = empty($_POST[title]) ? 'X' : $_POST[title];
		$title = trim($_POST[$title]);
		$production = empty($_POST[production]) ? 'X' :trim( $_POST[production]);
		$program = empty($_POST[program]) ? 'X' : trim($_POST[program]);
		$start_date = empty($_POST[start_date]) ? 'X' : date('Ymd', strtotime($_POST[start_date])).'000000';
		$end_date = empty($_POST[end_date]) ? 'X' : date('Ymd', strtotime($_POST[end_date])).'240000';
		$category = empty($_POST[category]) ? 'm' : $_POST[category];

		$query = returnQuery($type, $title, $productio, $start_date, $end_date, $category);
		$data = $db->queryAll($query);

		echo json_encode(array(
			'success' => true,
			'total' => $total,
			'data' => $data,
			'query'=>$query
		));
	}
}
catch(Exception $e){
	$msg = $e->getMessage();
	if($e->getCode() == ERROR_QUERY){
		$msg = $msg.'( '.$db->last_query.' )';
	}

	die(json_encode(array(
		'success' => false,
		'msg' => $msg
	)));
}

function createExcelFiles($fileName, $array, $columngroup) {
    $time = date('YmdHis');
    $fileName       = iconv('utf-8', 'euc-kr', $fileName.'_'.$time);

    header( "Content-type: application/vnd.ms-excel" );
    header( "Content-Disposition: attachment; filename={$fileName}.xls" );
    header( "Content-Description: CMS Generated Data"   );
    header( "Content-charset=UTF-8" );

	$excelTables = array();
    foreach($array as $d)
	{
		array_push($excelTables, returnData($d, $columngroup));
	}

    return join("<table border=0 cellpadding=1 cellspacing=1 bgcolor='#000000'><tr height='25' align='center' bgcolor='#000000'></tr></table>",$excelTables);
}

function returnData($array, $columngroup){
	 $excelTable="
    <table border=1 cellpadding=1 cellspacing=1 bgcolor='#000000'>";
        "<tr height='25' align='center' bgcolor='#FFFFFF'>";

    $data = "";
    $tr_start = "<tr height='25' align='center' bgcolor='#FFFFFF'>";
    $tr_end = "</tr>";

    foreach($array as $key => $value)
    {
        if($key == 0)//header 생성
        {
            $data .= $tr_start;
			foreach($columngroup as $col)
			{
				if($col['header'] == '구분' )
				{
					$colspan = " rowspan=2 ";
				}
				else
				{
					$colspan = " colspan=2 ";
				}
				$data .= "<td bgcolor='#FFFFBB' ".$colspan.">".$col['header']."</td>";
			}
			$data .= $tr_end;
			$data .= $tr_start;
            foreach($value as $k => $v)
            {
				if($k == 'c_name')
				{
					continue;
				}
                $data .= "<td bgcolor='#FFFFBB'>".getColumnName($k)."</td>";
            }
            $data .= $tr_end;
        }

        $data .= $tr_start;
        foreach($value as $k => $v)//필드 생성
        {
			if($k == 'c_name')
			{
				$data .= "<td>".$v."</td>";
			}
			else
			{
				$data .= "<td align='right'>".$v."</td>";
			}

        }
        $data .= $tr_end;
    }

    $excelTable .= $data;
    $excelTable .= "</table>";

    return $excelTable;
}

function getColumnName($dataIndex){
	if( strstr($dataIndex, 'cnt')  )
	{
		$value = '등록건수';
	}
	else if( strstr($dataIndex, 'filesize')  )
	{
		$value = '스토리지 용량(MB)';
	}
	else
	{
		$value = '';
	}

	return $value;
}

function returnQuery($type=null, $title=null, $productio=null, $start_date=null, $end_date=null, $category=null){
	if($type == 'second')
	{
		switch($category)
		{
			case 'm':
			default:
				$query = "
					SELECT	A.C_ID, A.C_NAME
							,TO_CHAR(NVL(B.CNT , 0), 'FM999,999,999,999,990') AS CNT , TO_CHAR(DECODE(C.FILESIZE , NULL, 0, C.FILESIZE ), 'FM999,999,999,999,990') AS FILESIZE
							,TO_CHAR(NVL(B.CNT1, 0), 'FM999,999,999,999,990') AS CNT1, TO_CHAR(DECODE(C.FILESIZE1, NULL, 0, C.FILESIZE1), 'FM999,999,999,999,990') AS FILESIZE1
							,TO_CHAR(NVL(B.CNT2, 0), 'FM999,999,999,999,990') AS CNT2, TO_CHAR(DECODE(C.FILESIZE2, NULL, 0, C.FILESIZE2), 'FM999,999,999,999,990') AS FILESIZE2
							,TO_CHAR(NVL(B.CNT3, 0), 'FM999,999,999,999,990') AS CNT3, TO_CHAR(DECODE(C.FILESIZE3, NULL, 0, C.FILESIZE3), 'FM999,999,999,999,990') AS FILESIZE3
							,TO_CHAR(NVL(B.CNT4, 0), 'FM999,999,999,999,990') AS CNT4, TO_CHAR(DECODE(C.FILESIZE4, NULL, 0, C.FILESIZE4), 'FM999,999,999,999,990') AS FILESIZE4
							,TO_CHAR(NVL(B.CNT5, 0), 'FM999,999,999,999,990') AS CNT5, TO_CHAR(DECODE(C.FILESIZE5, NULL, 0, C.FILESIZE5), 'FM999,999,999,999,990') AS FILESIZE5
							,TO_CHAR(NVL(B.CNT6, 0), 'FM999,999,999,999,990') AS CNT6, TO_CHAR(DECODE(C.FILESIZE6, NULL, 0, C.FILESIZE6), 'FM999,999,999,999,990') AS FILESIZE6
					FROM	(
							SELECT	C_ID, C_NAME, C_PID, C_NAMEID, SORT
							FROM	(
									SELECT	99999 AS C_ID, '진로직업동영상분류 중분류 합계' AS C_NAME, NULL AS C_PID, NULL AS C_NAMEID, 0 AS SORT
									FROM		(SELECT USER_ID FROM BC_MEMBER WHERE USER_ID = '".$_SESSION['user']['user_id']."')  DUAL_D
									UNION ALL
									SELECT	C_ID, C_NAME, C_PID, C_NAMEID, ROWNUM AS SORT
									FROM	(
											SELECT	C_ID, C_NAME, C_PID, C_NAMEID
											FROM	TB_CLASSIFICATION
											WHERE	C_PID = (SELECT C_PID FROM TB_CLASSIFICATIONNAME WHERE C_ID = 189)
											START WITH C_PID = (SELECT C_PID FROM TB_CLASSIFICATIONNAME WHERE C_ID = 189)
											CONNECT BY PRIOR C_ID = C_PID
											ORDER SIBLINGS BY C_NAME
											) A
									) A
							) A
							LEFT OUTER JOIN (
								SELECT	NVL(C.C_ID, 99999) AS C_ID
										,COUNT(C.CONTENT_ID) AS CNT
										,SUM(CASE WHEN TO_CHAR(SYSDATE, 'YYYY') = C.CREATED_YEAR THEN 1 ELSE 0 END) AS CNT1
										,SUM(CASE WHEN TO_CHAR(SYSDATE - 365 , 'YYYY') = C.CREATED_YEAR THEN 1 ELSE 0 END) AS CNT2
										,SUM(CASE WHEN TO_CHAR(SYSDATE - 730 , 'YYYY') = C.CREATED_YEAR THEN 1 ELSE 0 END) AS CNT3
										,SUM(CASE WHEN TO_CHAR(SYSDATE - 1095, 'YYYY') = C.CREATED_YEAR THEN 1 ELSE 0 END) AS CNT4
										,SUM(CASE WHEN TO_CHAR(SYSDATE - 1460, 'YYYY') = C.CREATED_YEAR THEN 1 ELSE 0 END) AS CNT5
										,SUM(CASE WHEN TO_CHAR(SYSDATE - 1460, 'YYYY') > C.CREATED_YEAR THEN 1 ELSE 0 END) AS CNT6
								FROM	(
										SELECT	A.C_ID, C.CONTENT_ID, SUBSTR(C.CREATED_DATE, 1, 4) AS CREATED_YEAR
										FROM	TB_CLASSIFICATIONMASTER A
												LEFT OUTER JOIN BC_CONTENT C ON(C.CONTENT_ID = A.CID)
										WHERE	A.C_ID IN (
														SELECT	C_ID
														FROM	TB_CLASSIFICATION
														WHERE	C_PID = (SELECT C_PID FROM TB_CLASSIFICATIONNAME WHERE C_ID = 189)
														START WITH C_PID = (SELECT C_PID FROM TB_CLASSIFICATIONNAME WHERE C_ID = 189)
														CONNECT BY PRIOR C_ID = C_PID
														)
										AND		C.CONTENT_ID IN (
																SELECT	CONTENT_ID
																FROM	BC_CONTENT
																WHERE	TITLE LIKE '%%'
																UNION ALL
																SELECT	USR_CONTENT_ID
																FROM	BC_USRMETA_WORKTV_SRC
																WHERE	('".$start_date."' != 'X' AND USR_WT_BDATE BETWEEN '".$start_date."' AND '".$end_date."')
																		OR
																		('".$production."' != 'X' AND USR_WT_BMT LIKE '%".$production."%')
																		OR
																		('".$program."' != 'X' AND USR_WT_PGMHNM LIKE '%".$program."%')
																		OR
																		('".$program."' != 'X' AND USR_WT_PGMENM LIKE '%".$program."%')
																UNION ALL
																SELECT	USR_CONTENT_ID
																FROM	BC_USRMETA_WORKTV_SRC2
																WHERE	('".$start_date."' != 'X' AND USR_WT_BDATE BETWEEN '".$start_date."' AND '".$end_date."')
																		OR
																		('".$production."' != 'X' AND USR_WT_BMT LIKE '%".$production."%')
																		OR
																		('".$program."' != 'X' AND USR_WT_PGMHNM LIKE '%".$program."%')
																		OR
																		('".$program."' != 'X' AND USR_WT_PGMENM LIKE '%".$program."%')
																)
										) C
								GROUP BY ROLLUP(C.C_ID)
							) B ON(B.C_ID = A.C_ID)
							LEFT OUTER JOIN (
								SELECT	NVL(C.C_ID, 99999) AS C_ID
										,SUM(C.FILESIZE) AS FILESIZE
										,SUM(CASE WHEN TO_CHAR(SYSDATE, 'YYYY') = C.CREATED_YEAR THEN C.FILESIZE ELSE 0 END) AS FILESIZE1
										,SUM(CASE WHEN TO_CHAR(SYSDATE - 365 , 'YYYY') = C.CREATED_YEAR THEN C.FILESIZE ELSE 0 END) AS FILESIZE2
										,SUM(CASE WHEN TO_CHAR(SYSDATE - 730 , 'YYYY') = C.CREATED_YEAR THEN C.FILESIZE ELSE 0 END) AS FILESIZE3
										,SUM(CASE WHEN TO_CHAR(SYSDATE - 1095, 'YYYY') = C.CREATED_YEAR THEN C.FILESIZE ELSE 0 END) AS FILESIZE4
										,SUM(CASE WHEN TO_CHAR(SYSDATE - 1460, 'YYYY') = C.CREATED_YEAR THEN C.FILESIZE ELSE 0 END) AS FILESIZE5
										,SUM(CASE WHEN TO_CHAR(SYSDATE - 1460, 'YYYY') > C.CREATED_YEAR THEN C.FILESIZE ELSE 0 END) AS FILESIZE6
								FROM	(
										SELECT	A.C_ID, C.CONTENT_ID, C.CREATED_YEAR, TRUNC(C.FILESIZE/1024/1024) AS FILESIZE
										FROM	TB_CLASSIFICATIONMASTER A
												LEFT OUTER JOIN (
													SELECT	C.CONTENT_ID
															,SUBSTR(C.CREATED_DATE, 1, 4) AS CREATED_YEAR
															,SUM(M.FILESIZE) AS FILESIZE
													FROM	BC_CONTENT C
															LEFT OUTER JOIN BC_MEDIA M ON(M.CONTENT_ID = C.CONTENT_ID)
													WHERE	C.CONTENT_ID IN (
																			SELECT	A.CID
																			FROM	TB_CLASSIFICATIONMASTER A
																			WHERE	C_ID IN (
																							SELECT	C_ID
																							FROM	TB_CLASSIFICATION
																							WHERE	C_PID = (SELECT C_PID FROM TB_CLASSIFICATIONNAME WHERE C_ID = 189)
																							START WITH C_PID = (SELECT C_PID FROM TB_CLASSIFICATIONNAME WHERE C_ID = 189)
																							CONNECT BY PRIOR C_ID = C_PID
																							)
																			)
													AND		C.CONTENT_ID IN (
																			SELECT	CONTENT_ID
																			FROM	BC_CONTENT
																			WHERE	TITLE LIKE '%%'
																			UNION ALL
																			SELECT	USR_CONTENT_ID
																			FROM	BC_USRMETA_WORKTV_SRC
																			WHERE	('".$start_date."' != 'X' AND USR_WT_BDATE BETWEEN '".$start_date."' AND '".$end_date."')
																					OR
																					('".$production."' != 'X' AND USR_WT_BMT LIKE '%".$production."%')
																					OR
																					('".$program."' != 'X' AND USR_WT_PGMHNM LIKE '%".$program."%')
																					OR
																					('".$program."' != 'X' AND USR_WT_PGMENM LIKE '%".$program."%')
																			UNION ALL
																			SELECT	USR_CONTENT_ID
																			FROM	BC_USRMETA_WORKTV_SRC2
																			WHERE	('".$start_date."' != 'X' AND USR_WT_BDATE BETWEEN '".$start_date."' AND '".$end_date."')
																					OR
																					('".$production."' != 'X' AND USR_WT_BMT LIKE '%".$production."%')
																					OR
																					('".$program."' != 'X' AND USR_WT_PGMHNM LIKE '%".$program."%')
																					OR
																					('".$program."' != 'X' AND USR_WT_PGMENM LIKE '%".$program."%')
																			)
													GROUP BY C.CONTENT_ID, C.CREATED_DATE
												) C ON(C.CONTENT_ID = A.CID)
										WHERE	A.C_ID IN (
														SELECT	C_ID
														FROM	TB_CLASSIFICATION
														WHERE	C_PID = (SELECT C_PID FROM TB_CLASSIFICATIONNAME WHERE C_ID = 189)
														START WITH C_PID = (SELECT C_PID FROM TB_CLASSIFICATIONNAME WHERE C_ID = 189)
														CONNECT BY PRIOR C_ID = C_PID
														)
										) C
								GROUP BY ROLLUP(C.C_ID)
							) C ON(C.C_ID = A.C_ID)
					ORDER BY A.SORT
				";
			break;
			case 's':
				$query = "
					SELECT	A.C_ID, A.C_NAME
							,TO_CHAR(NVL(B.CNT , 0), 'FM999,999,999,999,990') AS CNT , TO_CHAR(DECODE(C.FILESIZE , NULL, 0, C.FILESIZE ), 'FM999,999,999,999,990') AS FILESIZE
							,TO_CHAR(NVL(B.CNT1, 0), 'FM999,999,999,999,990') AS CNT1, TO_CHAR(DECODE(C.FILESIZE1, NULL, 0, C.FILESIZE1), 'FM999,999,999,999,990') AS FILESIZE1
							,TO_CHAR(NVL(B.CNT2, 0), 'FM999,999,999,999,990') AS CNT2, TO_CHAR(DECODE(C.FILESIZE2, NULL, 0, C.FILESIZE2), 'FM999,999,999,999,990') AS FILESIZE2
							,TO_CHAR(NVL(B.CNT3, 0), 'FM999,999,999,999,990') AS CNT3, TO_CHAR(DECODE(C.FILESIZE3, NULL, 0, C.FILESIZE3), 'FM999,999,999,999,990') AS FILESIZE3
							,TO_CHAR(NVL(B.CNT4, 0), 'FM999,999,999,999,990') AS CNT4, TO_CHAR(DECODE(C.FILESIZE4, NULL, 0, C.FILESIZE4), 'FM999,999,999,999,990') AS FILESIZE4
							,TO_CHAR(NVL(B.CNT5, 0), 'FM999,999,999,999,990') AS CNT5, TO_CHAR(DECODE(C.FILESIZE5, NULL, 0, C.FILESIZE5), 'FM999,999,999,999,990') AS FILESIZE5
							,TO_CHAR(NVL(B.CNT6, 0), 'FM999,999,999,999,990') AS CNT6, TO_CHAR(DECODE(C.FILESIZE6, NULL, 0, C.FILESIZE6), 'FM999,999,999,999,990') AS FILESIZE6
					FROM	(
							SELECT	C_ID, C_NAME, C_PID, C_NAMEID, SORT
							FROM	(
									SELECT	99999 AS C_ID, '진로직업동영상분류 소분류 합계' AS C_NAME, NULL AS C_PID, NULL AS C_NAMEID, 0 AS SORT
									FROM		(SELECT USER_ID FROM BC_MEMBER WHERE USER_ID = '".$_SESSION['user']['user_id']."')  DUAL_D
									UNION ALL
									SELECT	C_ID, C_NAME, C_PID, C_NAMEID, ROWNUM AS SORT
									FROM	(
											SELECT	C_ID, C_NAME, C_PID, C_NAMEID
											FROM	TB_CLASSIFICATION
											WHERE	C_PID != (SELECT C_PID FROM TB_CLASSIFICATIONNAME WHERE C_ID = 189)
											AND		LEVEL = 2
											START WITH C_PID = (SELECT C_PID FROM TB_CLASSIFICATIONNAME WHERE C_ID = 189)
											CONNECT BY PRIOR C_ID = C_PID
											ORDER SIBLINGS BY C_NAME
											) A
									) A
							) A
							LEFT OUTER JOIN (
								SELECT	NVL(C.C_ID, 99999) AS C_ID
										,COUNT(C.CONTENT_ID) AS CNT
										,SUM(CASE WHEN TO_CHAR(SYSDATE, 'YYYY') = C.CREATED_YEAR THEN 1 ELSE 0 END) AS CNT1
										,SUM(CASE WHEN TO_CHAR(SYSDATE - 365 , 'YYYY') = C.CREATED_YEAR THEN 1 ELSE 0 END) AS CNT2
										,SUM(CASE WHEN TO_CHAR(SYSDATE - 730 , 'YYYY') = C.CREATED_YEAR THEN 1 ELSE 0 END) AS CNT3
										,SUM(CASE WHEN TO_CHAR(SYSDATE - 1095, 'YYYY') = C.CREATED_YEAR THEN 1 ELSE 0 END) AS CNT4
										,SUM(CASE WHEN TO_CHAR(SYSDATE - 1460, 'YYYY') = C.CREATED_YEAR THEN 1 ELSE 0 END) AS CNT5
										,SUM(CASE WHEN TO_CHAR(SYSDATE - 1460, 'YYYY') > C.CREATED_YEAR THEN 1 ELSE 0 END) AS CNT6
								FROM	(
										SELECT	A.C_ID, C.CONTENT_ID, SUBSTR(C.CREATED_DATE, 1, 4) AS CREATED_YEAR
										FROM	TB_CLASSIFICATIONMASTER A
												LEFT OUTER JOIN BC_CONTENT C ON(C.CONTENT_ID = A.CID)
										WHERE	A.C_ID IN (
														SELECT	C_ID
														FROM	TB_CLASSIFICATION
														WHERE	C_PID != (SELECT C_PID FROM TB_CLASSIFICATIONNAME WHERE C_ID = 189)
														AND		LEVEL = 2
														START WITH C_PID = (SELECT C_PID FROM TB_CLASSIFICATIONNAME WHERE C_ID = 189)
														CONNECT BY PRIOR C_ID = C_PID
														)
										AND		C.CONTENT_ID IN (
																SELECT	CONTENT_ID
																FROM	BC_CONTENT
																WHERE	TITLE LIKE '%".$title."%'
																UNION ALL
																SELECT	USR_CONTENT_ID
																FROM	BC_USRMETA_WORKTV_SRC
																WHERE	('".$start_date."' != 'X' AND USR_WT_BDATE BETWEEN '".$start_date."' AND '".$end_date."')
																		OR
																		('".$production."' != 'X' AND USR_WT_BMT LIKE '%".$production."%')
																		OR
																		('".$program."' != 'X' AND USR_WT_PGMHNM LIKE '%".$program."%')
																		OR
																		('".$program."' != 'X' AND USR_WT_PGMENM LIKE '%".$program."%')
																UNION ALL
																SELECT	USR_CONTENT_ID
																FROM	BC_USRMETA_WORKTV_SRC2
																WHERE	('".$start_date."' != 'X' AND USR_WT_BDATE BETWEEN '".$start_date."' AND '".$end_date."')
																		OR
																		('".$production."' != 'X' AND USR_WT_BMT LIKE '%".$production."%')
																		OR
																		('".$program."' != 'X' AND USR_WT_PGMHNM LIKE '%".$program."%')
																		OR
																		('".$program."' != 'X' AND USR_WT_PGMENM LIKE '%".$program."%')
																)
										) C
								GROUP BY ROLLUP(C.C_ID)
							) B ON(B.C_ID = A.C_ID)
							LEFT OUTER JOIN (
								SELECT	NVL(C.C_ID, 99999) AS C_ID
										,SUM(C.FILESIZE) AS FILESIZE
										,SUM(CASE WHEN TO_CHAR(SYSDATE, 'YYYY') = C.CREATED_YEAR THEN C.FILESIZE ELSE 0 END) AS FILESIZE1
										,SUM(CASE WHEN TO_CHAR(SYSDATE - 365 , 'YYYY') = C.CREATED_YEAR THEN C.FILESIZE ELSE 0 END) AS FILESIZE2
										,SUM(CASE WHEN TO_CHAR(SYSDATE - 730 , 'YYYY') = C.CREATED_YEAR THEN C.FILESIZE ELSE 0 END) AS FILESIZE3
										,SUM(CASE WHEN TO_CHAR(SYSDATE - 1095, 'YYYY') = C.CREATED_YEAR THEN C.FILESIZE ELSE 0 END) AS FILESIZE4
										,SUM(CASE WHEN TO_CHAR(SYSDATE - 1460, 'YYYY') = C.CREATED_YEAR THEN C.FILESIZE ELSE 0 END) AS FILESIZE5
										,SUM(CASE WHEN TO_CHAR(SYSDATE - 1460, 'YYYY') > C.CREATED_YEAR THEN C.FILESIZE ELSE 0 END) AS FILESIZE6
								FROM	(
										SELECT	A.C_ID, C.CONTENT_ID, C.CREATED_YEAR, TRUNC(C.FILESIZE/1024/1024) AS FILESIZE
										FROM	TB_CLASSIFICATIONMASTER A
												LEFT OUTER JOIN (
													SELECT	C.CONTENT_ID
															,SUBSTR(C.CREATED_DATE, 1, 4) AS CREATED_YEAR
															,SUM(M.FILESIZE) AS FILESIZE
													FROM	BC_CONTENT C
															LEFT OUTER JOIN BC_MEDIA M ON(M.CONTENT_ID = C.CONTENT_ID)
													WHERE	C.CONTENT_ID IN (
																			SELECT	A.CID
																			FROM	TB_CLASSIFICATIONMASTER A
																			WHERE	C_ID IN (
																							SELECT	C_ID
																							FROM	TB_CLASSIFICATION
																							WHERE	C_PID != (SELECT C_PID FROM TB_CLASSIFICATIONNAME WHERE C_ID = 189)
																							AND		LEVEL = 2
																							START WITH C_PID = (SELECT C_PID FROM TB_CLASSIFICATIONNAME WHERE C_ID = 189)
																							CONNECT BY PRIOR C_ID = C_PID
																							)
																			)
													AND		C.CONTENT_ID IN (
																			SELECT	CONTENT_ID
																			FROM	BC_CONTENT
																			WHERE	TITLE LIKE '%".$title."%'
																			UNION ALL
																			SELECT	USR_CONTENT_ID
																			FROM	BC_USRMETA_WORKTV_SRC
																			WHERE	('".$start_date."' != 'X' AND USR_WT_BDATE BETWEEN '".$start_date."' AND '".$end_date."')
																					OR
																					('".$production."' != 'X' AND USR_WT_BMT LIKE '%".$production."%')
																					OR
																					('".$program."' != 'X' AND USR_WT_PGMHNM LIKE '%".$program."%')
																					OR
																					('".$program."' != 'X' AND USR_WT_PGMENM LIKE '%".$program."%')
																			UNION ALL
																			SELECT	USR_CONTENT_ID
																			FROM	BC_USRMETA_WORKTV_SRC2
																			WHERE	('".$start_date."' != 'X' AND USR_WT_BDATE BETWEEN '".$start_date."' AND '".$end_date."')
																					OR
																					('".$production."' != 'X' AND USR_WT_BMT LIKE '%".$production."%')
																					OR
																					('".$program."' != 'X' AND USR_WT_PGMHNM LIKE '%".$program."%')
																					OR
																					('".$program."' != 'X' AND USR_WT_PGMENM LIKE '%".$program."%')
																			)
													GROUP BY C.CONTENT_ID, C.CREATED_DATE
												) C ON(C.CONTENT_ID = A.CID)
										WHERE	A.C_ID IN (
														SELECT	C_ID
														FROM	TB_CLASSIFICATION
														WHERE	C_PID != (SELECT C_PID FROM TB_CLASSIFICATIONNAME WHERE C_ID = 189)
														AND		LEVEL = 2
														START WITH C_PID = (SELECT C_PID FROM TB_CLASSIFICATIONNAME WHERE C_ID = 189)
														CONNECT BY PRIOR C_ID = C_PID
														)
										) C
								GROUP BY ROLLUP(C.C_ID)
							) C ON(C.C_ID = A.C_ID)
					ORDER BY A.SORT
				";
			break;
			case 'n':
				$query = "
					SELECT	A.C_ID, A.C_NAME
							,TO_CHAR(NVL(B.CNT , 0), 'FM999,999,999,999,990') AS CNT , TO_CHAR(DECODE(C.FILESIZE , NULL, 0, C.FILESIZE ), 'FM999,999,999,999,990') AS FILESIZE
							,TO_CHAR(NVL(B.CNT1, 0), 'FM999,999,999,999,990') AS CNT1, TO_CHAR(DECODE(C.FILESIZE1, NULL, 0, C.FILESIZE1), 'FM999,999,999,999,990') AS FILESIZE1
							,TO_CHAR(NVL(B.CNT2, 0), 'FM999,999,999,999,990') AS CNT2, TO_CHAR(DECODE(C.FILESIZE2, NULL, 0, C.FILESIZE2), 'FM999,999,999,999,990') AS FILESIZE2
							,TO_CHAR(NVL(B.CNT3, 0), 'FM999,999,999,999,990') AS CNT3, TO_CHAR(DECODE(C.FILESIZE3, NULL, 0, C.FILESIZE3), 'FM999,999,999,999,990') AS FILESIZE3
							,TO_CHAR(NVL(B.CNT4, 0), 'FM999,999,999,999,990') AS CNT4, TO_CHAR(DECODE(C.FILESIZE4, NULL, 0, C.FILESIZE4), 'FM999,999,999,999,990') AS FILESIZE4
							,TO_CHAR(NVL(B.CNT5, 0), 'FM999,999,999,999,990') AS CNT5, TO_CHAR(DECODE(C.FILESIZE5, NULL, 0, C.FILESIZE5), 'FM999,999,999,999,990') AS FILESIZE5
							,TO_CHAR(NVL(B.CNT6, 0), 'FM999,999,999,999,990') AS CNT6, TO_CHAR(DECODE(C.FILESIZE6, NULL, 0, C.FILESIZE6), 'FM999,999,999,999,990') AS FILESIZE6
					FROM	(
							SELECT	C_ID, C_NAME, C_PID, C_NAMEID, SORT
							FROM	(
									SELECT	99999 AS C_ID, '진로직업동영상분류 세분류 합계' AS C_NAME, NULL AS C_PID, NULL AS C_NAMEID, 0 AS SORT
									FROM		(SELECT USER_ID FROM BC_MEMBER WHERE USER_ID = '".$_SESSION['user']['user_id']."')  DUAL_D
									UNION ALL
									SELECT	C_ID, C_NAME, C_PID, C_NAMEID, ROWNUM AS SORT
									FROM	(
											SELECT	C_ID, C_NAME, C_PID, C_NAMEID
											FROM	TB_CLASSIFICATION
											WHERE	C_PID != (SELECT C_PID FROM TB_CLASSIFICATIONNAME WHERE C_ID = 189)
											AND		LEVEL = 3
											START WITH C_PID = (SELECT C_PID FROM TB_CLASSIFICATIONNAME WHERE C_ID = 189)
											CONNECT BY PRIOR C_ID = C_PID
											ORDER SIBLINGS BY C_NAME
											) A
									) A
							) A
							LEFT OUTER JOIN (
								SELECT	NVL(C.C_ID, 99999) AS C_ID
										,COUNT(C.CONTENT_ID) AS CNT
										,SUM(CASE WHEN TO_CHAR(SYSDATE, 'YYYY') = C.CREATED_YEAR THEN 1 ELSE 0 END) AS CNT1
										,SUM(CASE WHEN TO_CHAR(SYSDATE - 365 , 'YYYY') = C.CREATED_YEAR THEN 1 ELSE 0 END) AS CNT2
										,SUM(CASE WHEN TO_CHAR(SYSDATE - 730 , 'YYYY') = C.CREATED_YEAR THEN 1 ELSE 0 END) AS CNT3
										,SUM(CASE WHEN TO_CHAR(SYSDATE - 1095, 'YYYY') = C.CREATED_YEAR THEN 1 ELSE 0 END) AS CNT4
										,SUM(CASE WHEN TO_CHAR(SYSDATE - 1460, 'YYYY') = C.CREATED_YEAR THEN 1 ELSE 0 END) AS CNT5
										,SUM(CASE WHEN TO_CHAR(SYSDATE - 1460, 'YYYY') > C.CREATED_YEAR THEN 1 ELSE 0 END) AS CNT6
								FROM	(
										SELECT	A.C_ID, C.CONTENT_ID, SUBSTR(C.CREATED_DATE, 1, 4) AS CREATED_YEAR
										FROM	TB_CLASSIFICATIONMASTER A
												LEFT OUTER JOIN BC_CONTENT C ON(C.CONTENT_ID = A.CID)
										WHERE	A.C_ID IN (
														SELECT	C_ID
														FROM	TB_CLASSIFICATION
														WHERE	C_PID != (SELECT C_PID FROM TB_CLASSIFICATIONNAME WHERE C_ID = 189)
														AND		LEVEL = 3
														START WITH C_PID = (SELECT C_PID FROM TB_CLASSIFICATIONNAME WHERE C_ID = 189)
														CONNECT BY PRIOR C_ID = C_PID
														)
										AND		C.CONTENT_ID IN (
																SELECT	CONTENT_ID
																FROM	BC_CONTENT
																WHERE	TITLE LIKE '%".$title."%'
																UNION ALL
																SELECT	USR_CONTENT_ID
																FROM	BC_USRMETA_WORKTV_SRC
																WHERE	('".$start_date."' != 'X' AND USR_WT_BDATE BETWEEN '".$start_date."' AND '".$end_date."')
																		OR
																		('".$production."' != 'X' AND USR_WT_BMT LIKE '%".$production."%')
																		OR
																		('".$program."' != 'X' AND USR_WT_PGMHNM LIKE '%".$program."%')
																		OR
																		('".$program."' != 'X' AND USR_WT_PGMENM LIKE '%".$program."%')
																UNION ALL
																SELECT	USR_CONTENT_ID
																FROM	BC_USRMETA_WORKTV_SRC2
																WHERE	('".$start_date."' != 'X' AND USR_WT_BDATE BETWEEN '".$start_date."' AND '".$end_date."')
																		OR
																		('".$production."' != 'X' AND USR_WT_BMT LIKE '%".$production."%')
																		OR
																		('".$program."' != 'X' AND USR_WT_PGMHNM LIKE '%".$program."%')
																		OR
																		('".$program."' != 'X' AND USR_WT_PGMENM LIKE '%".$program."%')
																)
										) C
								GROUP BY ROLLUP(C.C_ID)
							) B ON(B.C_ID = A.C_ID)
							LEFT OUTER JOIN (
								SELECT	NVL(C.C_ID, 99999) AS C_ID
										,SUM(C.FILESIZE) AS FILESIZE
										,SUM(CASE WHEN TO_CHAR(SYSDATE, 'YYYY') = C.CREATED_YEAR THEN C.FILESIZE ELSE 0 END) AS FILESIZE1
										,SUM(CASE WHEN TO_CHAR(SYSDATE - 365 , 'YYYY') = C.CREATED_YEAR THEN C.FILESIZE ELSE 0 END) AS FILESIZE2
										,SUM(CASE WHEN TO_CHAR(SYSDATE - 730 , 'YYYY') = C.CREATED_YEAR THEN C.FILESIZE ELSE 0 END) AS FILESIZE3
										,SUM(CASE WHEN TO_CHAR(SYSDATE - 1095, 'YYYY') = C.CREATED_YEAR THEN C.FILESIZE ELSE 0 END) AS FILESIZE4
										,SUM(CASE WHEN TO_CHAR(SYSDATE - 1460, 'YYYY') = C.CREATED_YEAR THEN C.FILESIZE ELSE 0 END) AS FILESIZE5
										,SUM(CASE WHEN TO_CHAR(SYSDATE - 1460, 'YYYY') > C.CREATED_YEAR THEN C.FILESIZE ELSE 0 END) AS FILESIZE6
								FROM	(
										SELECT	A.C_ID, C.CONTENT_ID, C.CREATED_YEAR, TRUNC(C.FILESIZE/1024/1024) AS FILESIZE
										FROM	TB_CLASSIFICATIONMASTER A
												LEFT OUTER JOIN (
													SELECT	C.CONTENT_ID
															,SUBSTR(C.CREATED_DATE, 1, 4) AS CREATED_YEAR
															,SUM(M.FILESIZE) AS FILESIZE
													FROM	BC_CONTENT C
															LEFT OUTER JOIN BC_MEDIA M ON(M.CONTENT_ID = C.CONTENT_ID)
													WHERE	C.CONTENT_ID IN (
																			SELECT	A.CID
																			FROM	TB_CLASSIFICATIONMASTER A
																			WHERE	C_ID IN (
																							SELECT	C_ID
																							FROM	TB_CLASSIFICATION
																							WHERE	C_PID != (SELECT C_PID FROM TB_CLASSIFICATIONNAME WHERE C_ID = 189)
																							AND		LEVEL = 3
																							START WITH C_PID = (SELECT C_PID FROM TB_CLASSIFICATIONNAME WHERE C_ID = 189)
																							CONNECT BY PRIOR C_ID = C_PID
																							)
																			)
													AND		C.CONTENT_ID IN (
																			SELECT	CONTENT_ID
																			FROM	BC_CONTENT
																			WHERE	TITLE LIKE '%".$title."%'
																			UNION ALL
																			SELECT	USR_CONTENT_ID
																			FROM	BC_USRMETA_WORKTV_SRC
																			WHERE	('".$start_date."' != 'X' AND USR_WT_BDATE BETWEEN '".$start_date."' AND '".$end_date."')
																					OR
																					('".$production."' != 'X' AND USR_WT_BMT LIKE '%".$production."%')
																					OR
																					('".$program."' != 'X' AND USR_WT_PGMHNM LIKE '%".$program."%')
																					OR
																					('".$program."' != 'X' AND USR_WT_PGMENM LIKE '%".$program."%')
																			UNION ALL
																			SELECT	USR_CONTENT_ID
																			FROM	BC_USRMETA_WORKTV_SRC2
																			WHERE	('".$start_date."' != 'X' AND USR_WT_BDATE BETWEEN '".$start_date."' AND '".$end_date."')
																					OR
																					('".$production."' != 'X' AND USR_WT_BMT LIKE '%".$production."%')
																					OR
																					('".$program."' != 'X' AND USR_WT_PGMHNM LIKE '%".$program."%')
																					OR
																					('".$program."' != 'X' AND USR_WT_PGMENM LIKE '%".$program."%')
																			)
													GROUP BY C.CONTENT_ID, C.CREATED_DATE
												) C ON(C.CONTENT_ID = A.CID)
										WHERE	A.C_ID IN (
														SELECT	C_ID
														FROM	TB_CLASSIFICATION
														WHERE	C_PID != (SELECT C_PID FROM TB_CLASSIFICATIONNAME WHERE C_ID = 189)
														AND		LEVEL = 3
														START WITH C_PID = (SELECT C_PID FROM TB_CLASSIFICATIONNAME WHERE C_ID = 189)
														CONNECT BY PRIOR C_ID = C_PID
														)
										) C
								GROUP BY ROLLUP(C.C_ID)
							) C ON(C.C_ID = A.C_ID)
					ORDER BY A.SORT
				";
			break;
		}
	}
	else
	{
		switch($type)
		{
			case 'first':
				$query = "
					SELECT	A.C_ID, A.C_NAME
							,TO_CHAR(NVL(B.CNT , 0), 'FM999,999,999,999,990') AS CNT , TO_CHAR(DECODE(C.FILESIZE , NULL, 0, C.FILESIZE ), 'FM999,999,999,999,990') AS FILESIZE
							,TO_CHAR(NVL(B.CNT1 , 0), 'FM999,999,999,999,990') AS CNT1, TO_CHAR(DECODE(C.FILESIZE1, NULL, 0, C.FILESIZE1), 'FM999,999,999,999,990') AS FILESIZE1
							,TO_CHAR(NVL(B.CNT2 , 0), 'FM999,999,999,999,990') AS CNT2, TO_CHAR(DECODE(C.FILESIZE2, NULL, 0, C.FILESIZE2), 'FM999,999,999,999,990') AS FILESIZE2
							,TO_CHAR(NVL(B.CNT3 , 0), 'FM999,999,999,999,990') AS CNT3, TO_CHAR(DECODE(C.FILESIZE3, NULL, 0, C.FILESIZE3), 'FM999,999,999,999,990') AS FILESIZE3
							,TO_CHAR(NVL(B.CNT4 , 0), 'FM999,999,999,999,990') AS CNT4, TO_CHAR(DECODE(C.FILESIZE4, NULL, 0, C.FILESIZE4), 'FM999,999,999,999,990') AS FILESIZE4
							,TO_CHAR(NVL(B.CNT5 , 0), 'FM999,999,999,999,990') AS CNT5, TO_CHAR(DECODE(C.FILESIZE5, NULL, 0, C.FILESIZE5), 'FM999,999,999,999,990') AS FILESIZE5
							,TO_CHAR(NVL(B.CNT6 , 0), 'FM999,999,999,999,990') AS CNT6, TO_CHAR(DECODE(C.FILESIZE6, NULL, 0, C.FILESIZE6), 'FM999,999,999,999,990') AS FILESIZE6
					FROM	(
							SELECT	C_ID, C_NAME, C_PID, C_NAMEID, SORT
							FROM	(
									SELECT	99999 AS C_ID, '장르분류 합계' AS C_NAME, NULL AS C_PID, NULL AS C_NAMEID, 0 AS SORT
									FROM		(SELECT USER_ID FROM BC_MEMBER WHERE USER_ID = '".$_SESSION['user']['user_id']."')  DUAL_D
									UNION ALL
									SELECT	C_ID, C_NAME, C_PID, C_NAMEID, ROWNUM AS SORT
									FROM	(
											SELECT	C_ID, C_NAME, C_PID, C_NAMEID
											FROM	TB_CLASSIFICATION
											WHERE	C_PID = (SELECT C_PID FROM TB_CLASSIFICATIONNAME WHERE C_ID = 6)
											START WITH C_PID = (SELECT C_PID FROM TB_CLASSIFICATIONNAME WHERE C_ID = 6)
											CONNECT BY PRIOR C_ID = C_PID
											ORDER SIBLINGS BY C_NAME
											) A
									) A
							) A
							LEFT OUTER JOIN (
								SELECT	NVL(C.C_ID, 99999) AS C_ID
										,COUNT(C.CONTENT_ID) AS CNT
										,SUM(CASE WHEN TO_CHAR(SYSDATE, 'YYYY') = C.CREATED_YEAR THEN 1 ELSE 0 END) AS CNT1
										,SUM(CASE WHEN TO_CHAR(SYSDATE - 365 , 'YYYY') = C.CREATED_YEAR THEN 1 ELSE 0 END) AS CNT2
										,SUM(CASE WHEN TO_CHAR(SYSDATE - 730 , 'YYYY') = C.CREATED_YEAR THEN 1 ELSE 0 END) AS CNT3
										,SUM(CASE WHEN TO_CHAR(SYSDATE - 1095, 'YYYY') = C.CREATED_YEAR THEN 1 ELSE 0 END) AS CNT4
										,SUM(CASE WHEN TO_CHAR(SYSDATE - 1460, 'YYYY') = C.CREATED_YEAR THEN 1 ELSE 0 END) AS CNT5
										,SUM(CASE WHEN TO_CHAR(SYSDATE - 1460, 'YYYY') > C.CREATED_YEAR THEN 1 ELSE 0 END) AS CNT6
								FROM	(
										SELECT	A.C_ID, C.CONTENT_ID, SUBSTR(C.CREATED_DATE, 1, 4) AS CREATED_YEAR
										FROM	TB_CLASSIFICATIONMASTER A
												LEFT OUTER JOIN BC_CONTENT C ON(C.CONTENT_ID = A.CID)
										WHERE	A.C_ID IN (
														SELECT	C_ID
														FROM	TB_CLASSIFICATION
														WHERE	C_PID = (SELECT C_PID FROM TB_CLASSIFICATIONNAME WHERE C_ID = 6)
														START WITH C_PID = (SELECT C_PID FROM TB_CLASSIFICATIONNAME WHERE C_ID = 6)
														CONNECT BY PRIOR C_ID = C_PID
														)
										AND		C.CONTENT_ID IN (
																SELECT	CONTENT_ID
																FROM	BC_CONTENT
																WHERE	TITLE LIKE '%".$title."%'
																UNION ALL
																SELECT	USR_CONTENT_ID
																FROM	BC_USRMETA_WORKTV_SRC
																WHERE	('".$start_date."' != 'X' AND USR_WT_BDATE BETWEEN '".$start_date."' AND '".$end_date."')
																		OR
																		('".$production."' != 'X' AND USR_WT_BMT LIKE '%".$production."%')
																		OR
																		('".$program."' != 'X' AND USR_WT_PGMHNM LIKE '%".$program."%')
																		OR
																		('".$program."' != 'X' AND USR_WT_PGMENM LIKE '%".$program."%')
																UNION ALL
																SELECT	USR_CONTENT_ID
																FROM	BC_USRMETA_WORKTV_SRC2
																WHERE	('".$start_date."' != 'X' AND USR_WT_BDATE BETWEEN '".$start_date."' AND '".$end_date."')
																		OR
																		('".$production."' != 'X' AND USR_WT_BMT LIKE '%".$production."%')
																		OR
																		('".$program."' != 'X' AND USR_WT_PGMHNM LIKE '%".$program."%')
																		OR
																		('".$program."' != 'X' AND USR_WT_PGMENM LIKE '%".$program."%')
																)
										) C
								GROUP BY ROLLUP(C.C_ID)
							) B ON(B.C_ID = A.C_ID)
							LEFT OUTER JOIN (
								SELECT	NVL(C.C_ID, 99999) AS C_ID
										,SUM(C.FILESIZE) AS FILESIZE
										,SUM(CASE WHEN TO_CHAR(SYSDATE, 'YYYY') = C.CREATED_YEAR THEN C.FILESIZE ELSE 0 END) AS FILESIZE1
										,SUM(CASE WHEN TO_CHAR(SYSDATE - 365 , 'YYYY') = C.CREATED_YEAR THEN C.FILESIZE ELSE 0 END) AS FILESIZE2
										,SUM(CASE WHEN TO_CHAR(SYSDATE - 730 , 'YYYY') = C.CREATED_YEAR THEN C.FILESIZE ELSE 0 END) AS FILESIZE3
										,SUM(CASE WHEN TO_CHAR(SYSDATE - 1095, 'YYYY') = C.CREATED_YEAR THEN C.FILESIZE ELSE 0 END) AS FILESIZE4
										,SUM(CASE WHEN TO_CHAR(SYSDATE - 1460, 'YYYY') = C.CREATED_YEAR THEN C.FILESIZE ELSE 0 END) AS FILESIZE5
										,SUM(CASE WHEN TO_CHAR(SYSDATE - 1460, 'YYYY') > C.CREATED_YEAR THEN C.FILESIZE ELSE 0 END) AS FILESIZE6
								FROM	(
										SELECT	A.C_ID, C.CONTENT_ID, C.CREATED_YEAR, TRUNC(C.FILESIZE/1024/1024) AS FILESIZE
										FROM	TB_CLASSIFICATIONMASTER A
												LEFT OUTER JOIN (
													SELECT	C.CONTENT_ID
															,SUBSTR(C.CREATED_DATE, 1, 4) AS CREATED_YEAR
															,SUM(M.FILESIZE) AS FILESIZE
													FROM	BC_CONTENT C
															LEFT OUTER JOIN BC_MEDIA M ON(M.CONTENT_ID = C.CONTENT_ID)
													WHERE	C.CONTENT_ID IN (
																			SELECT	A.CID
																			FROM	TB_CLASSIFICATIONMASTER A
																			WHERE	C_ID IN (
																							SELECT	C_ID
																							FROM	TB_CLASSIFICATION
																							WHERE	C_PID = (SELECT C_PID FROM TB_CLASSIFICATIONNAME WHERE C_ID = 6)
																							START WITH C_PID = (SELECT C_PID FROM TB_CLASSIFICATIONNAME WHERE C_ID = 6)
																							CONNECT BY PRIOR C_ID = C_PID
																							)
																			)
													AND		C.CONTENT_ID IN (
																			SELECT	CONTENT_ID
																			FROM	BC_CONTENT
																			WHERE	TITLE LIKE '%".$title."%'
																			UNION ALL
																			SELECT	USR_CONTENT_ID
																			FROM	BC_USRMETA_WORKTV_SRC
																			WHERE	('".$start_date."' != 'X' AND USR_WT_BDATE BETWEEN '".$start_date."' AND '".$end_date."')
																					OR
																					('".$production."' != 'X' AND USR_WT_BMT LIKE '%".$production."%')
																					OR
																					('".$program."' != 'X' AND USR_WT_PGMHNM LIKE '%".$program."%')
																					OR
																					('".$program."' != 'X' AND USR_WT_PGMENM LIKE '%".$program."%')
																			UNION ALL
																			SELECT	USR_CONTENT_ID
																			FROM	BC_USRMETA_WORKTV_SRC2
																			WHERE	('".$start_date."' != 'X' AND USR_WT_BDATE BETWEEN '".$start_date."' AND '".$end_date."')
																					OR
																					('".$production."' != 'X' AND USR_WT_BMT LIKE '%".$production."%')
																					OR
																					('".$program."' != 'X' AND USR_WT_PGMHNM LIKE '%".$program."%')
																					OR
																					('".$program."' != 'X' AND USR_WT_PGMENM LIKE '%".$program."%')
																			)
													GROUP BY C.CONTENT_ID, C.CREATED_DATE
												) C ON(C.CONTENT_ID = A.CID)
										WHERE	A.C_ID IN (
														SELECT	C_ID
														FROM	TB_CLASSIFICATION
														WHERE	C_PID = (SELECT C_PID FROM TB_CLASSIFICATIONNAME WHERE C_ID = 6)
														START WITH C_PID = (SELECT C_PID FROM TB_CLASSIFICATIONNAME WHERE C_ID = 6)
														CONNECT BY PRIOR C_ID = C_PID
														)
										) C
								GROUP BY ROLLUP(C.C_ID)
							) C ON(C.C_ID = A.C_ID)
					ORDER BY A.SORT
				";
			break;
			case 'third':
				$query = "
					SELECT	A.C_ID, A.C_NAME
							,TO_CHAR(NVL(B.CNT , 0), 'FM999,999,999,999,990') AS CNT , TO_CHAR(DECODE(C.FILESIZE , NULL, 0, C.FILESIZE ), 'FM999,999,999,999,990') AS FILESIZE
							,TO_CHAR(NVL(B.CNT1 , 0), 'FM999,999,999,999,990') AS CNT1, TO_CHAR(DECODE(C.FILESIZE1, NULL, 0, C.FILESIZE1), 'FM999,999,999,999,990') AS FILESIZE1
							,TO_CHAR(NVL(B.CNT2 , 0), 'FM999,999,999,999,990') AS CNT2, TO_CHAR(DECODE(C.FILESIZE2, NULL, 0, C.FILESIZE2), 'FM999,999,999,999,990') AS FILESIZE2
							,TO_CHAR(NVL(B.CNT3 , 0), 'FM999,999,999,999,990') AS CNT3, TO_CHAR(DECODE(C.FILESIZE3, NULL, 0, C.FILESIZE3), 'FM999,999,999,999,990') AS FILESIZE3
							,TO_CHAR(NVL(B.CNT4 , 0), 'FM999,999,999,999,990') AS CNT4, TO_CHAR(DECODE(C.FILESIZE4, NULL, 0, C.FILESIZE4), 'FM999,999,999,999,990') AS FILESIZE4
							,TO_CHAR(NVL(B.CNT5 , 0), 'FM999,999,999,999,990') AS CNT5, TO_CHAR(DECODE(C.FILESIZE5, NULL, 0, C.FILESIZE5), 'FM999,999,999,999,990') AS FILESIZE5
							,TO_CHAR(NVL(B.CNT6 , 0), 'FM999,999,999,999,990') AS CNT6, TO_CHAR(DECODE(C.FILESIZE6, NULL, 0, C.FILESIZE6), 'FM999,999,999,999,990') AS FILESIZE6
					FROM	(
							SELECT	C_ID, C_NAME, C_PID, C_NAMEID, SORT
							FROM	(
									SELECT	99999 AS C_ID, '시청자분류 합계' AS C_NAME, NULL AS C_PID, NULL AS C_NAMEID, 0 AS SORT
									FROM		(SELECT USER_ID FROM BC_MEMBER WHERE USER_ID = '".$_SESSION['user']['user_id']."')  DUAL_D
									UNION ALL
									SELECT	A.C_ID, C_NAME, C_PID, C_NAMEID, ROWNUM AS SORT
									FROM	(
											SELECT	C_ID, C_NAME, C_PID, C_NAMEID
											FROM	TB_CLASSIFICATION
											WHERE	C_PID = (SELECT C_PID FROM TB_CLASSIFICATIONNAME WHERE C_ID = 365)
											START WITH C_PID = (SELECT C_PID FROM TB_CLASSIFICATIONNAME WHERE C_ID = 365)
											CONNECT BY PRIOR C_ID = C_PID
											ORDER SIBLINGS BY C_NAME
											) A
									) A
							) A
							LEFT OUTER JOIN (
								SELECT	NVL(C.C_ID, 99999) AS C_ID
										,COUNT(C.CONTENT_ID) AS CNT
										,SUM(CASE WHEN TO_CHAR(SYSDATE, 'YYYY') = C.CREATED_YEAR THEN 1 ELSE 0 END) AS CNT1
										,SUM(CASE WHEN TO_CHAR(SYSDATE - 365 , 'YYYY') = C.CREATED_YEAR THEN 1 ELSE 0 END) AS CNT2
										,SUM(CASE WHEN TO_CHAR(SYSDATE - 730 , 'YYYY') = C.CREATED_YEAR THEN 1 ELSE 0 END) AS CNT3
										,SUM(CASE WHEN TO_CHAR(SYSDATE - 1095, 'YYYY') = C.CREATED_YEAR THEN 1 ELSE 0 END) AS CNT4
										,SUM(CASE WHEN TO_CHAR(SYSDATE - 1460, 'YYYY') = C.CREATED_YEAR THEN 1 ELSE 0 END) AS CNT5
										,SUM(CASE WHEN TO_CHAR(SYSDATE - 1460, 'YYYY') > C.CREATED_YEAR THEN 1 ELSE 0 END) AS CNT6
								FROM	(
										SELECT	A.C_ID, C.CONTENT_ID, SUBSTR(C.CREATED_DATE, 1, 4) AS CREATED_YEAR
										FROM	TB_CLASSIFICATIONMASTER A
												LEFT OUTER JOIN BC_CONTENT C ON(C.CONTENT_ID = A.CID)
										WHERE	A.C_ID IN (
														SELECT	C_ID
														FROM	TB_CLASSIFICATION
														WHERE	C_PID = (SELECT C_PID FROM TB_CLASSIFICATIONNAME WHERE C_ID = 365)
														START WITH C_PID = (SELECT C_PID FROM TB_CLASSIFICATIONNAME WHERE C_ID = 365)
														CONNECT BY PRIOR C_ID = C_PID
														)
										AND		C.CONTENT_ID IN (
																SELECT	CONTENT_ID
																FROM	BC_CONTENT
																WHERE	TITLE LIKE '%".$title."%'
																UNION ALL
																SELECT	USR_CONTENT_ID
																FROM	BC_USRMETA_WORKTV_SRC
																WHERE	('".$start_date."' != 'X' AND USR_WT_BDATE BETWEEN '".$start_date."' AND '".$end_date."')
																		OR
																		('".$production."' != 'X' AND USR_WT_BMT LIKE '%".$production."%')
																		OR
																		('".$program."' != 'X' AND USR_WT_PGMHNM LIKE '%".$program."%')
																		OR
																		('".$program."' != 'X' AND USR_WT_PGMENM LIKE '%".$program."%')
																UNION ALL
																SELECT	USR_CONTENT_ID
																FROM	BC_USRMETA_WORKTV_SRC2
																WHERE	('".$start_date."' != 'X' AND USR_WT_BDATE BETWEEN '".$start_date."' AND '".$end_date."')
																		OR
																		('".$production."' != 'X' AND USR_WT_BMT LIKE '%".$production."%')
																		OR
																		('".$program."' != 'X' AND USR_WT_PGMHNM LIKE '%".$program."%')
																		OR
																		('".$program."' != 'X' AND USR_WT_PGMENM LIKE '%".$program."%')
																)
										) C
								GROUP BY ROLLUP(C.C_ID)
							) B ON(B.C_ID = A.C_ID)
							LEFT OUTER JOIN (
								SELECT	NVL(C.C_ID, 99999) AS C_ID
										,SUM(C.FILESIZE) AS FILESIZE
										,SUM(CASE WHEN TO_CHAR(SYSDATE, 'YYYY') = C.CREATED_YEAR THEN C.FILESIZE ELSE 0 END) AS FILESIZE1
										,SUM(CASE WHEN TO_CHAR(SYSDATE - 365 , 'YYYY') = C.CREATED_YEAR THEN C.FILESIZE ELSE 0 END) AS FILESIZE2
										,SUM(CASE WHEN TO_CHAR(SYSDATE - 730 , 'YYYY') = C.CREATED_YEAR THEN C.FILESIZE ELSE 0 END) AS FILESIZE3
										,SUM(CASE WHEN TO_CHAR(SYSDATE - 1095, 'YYYY') = C.CREATED_YEAR THEN C.FILESIZE ELSE 0 END) AS FILESIZE4
										,SUM(CASE WHEN TO_CHAR(SYSDATE - 1460, 'YYYY') = C.CREATED_YEAR THEN C.FILESIZE ELSE 0 END) AS FILESIZE5
										,SUM(CASE WHEN TO_CHAR(SYSDATE - 1460, 'YYYY') > C.CREATED_YEAR THEN C.FILESIZE ELSE 0 END) AS FILESIZE6
								FROM	(
										SELECT	A.C_ID, C.CONTENT_ID, C.CREATED_YEAR, TRUNC(C.FILESIZE/1024/1024) AS FILESIZE
										FROM	TB_CLASSIFICATIONMASTER A
												LEFT OUTER JOIN (
													SELECT	C.CONTENT_ID
															,SUBSTR(C.CREATED_DATE, 1, 4) AS CREATED_YEAR
															,SUM(M.FILESIZE) AS FILESIZE
													FROM	BC_CONTENT C
															LEFT OUTER JOIN BC_MEDIA M ON(M.CONTENT_ID = C.CONTENT_ID)
													WHERE	C.CONTENT_ID IN (
																			SELECT	A.CID
																			FROM	TB_CLASSIFICATIONMASTER A
																			WHERE	C_ID IN (
																							SELECT	C_ID
																							FROM	TB_CLASSIFICATION
																							WHERE	C_PID = (SELECT C_PID FROM TB_CLASSIFICATIONNAME WHERE C_ID = 365)
																							START WITH C_PID = (SELECT C_PID FROM TB_CLASSIFICATIONNAME WHERE C_ID = 365)
																							CONNECT BY PRIOR C_ID = C_PID
																							)
																			)
													AND		C.CONTENT_ID IN (
																			SELECT	CONTENT_ID
																			FROM	BC_CONTENT
																			WHERE	TITLE LIKE '%".$title."%'
																			UNION ALL
																			SELECT	USR_CONTENT_ID
																			FROM	BC_USRMETA_WORKTV_SRC
																			WHERE	('".$start_date."' != 'X' AND USR_WT_BDATE BETWEEN '".$start_date."' AND '".$end_date."')
																					OR
																					('".$production."' != 'X' AND USR_WT_BMT LIKE '%".$production."%')
																					OR
																					('".$program."' != 'X' AND USR_WT_PGMHNM LIKE '%".$program."%')
																					OR
																					('".$program."' != 'X' AND USR_WT_PGMENM LIKE '%".$program."%')
																			UNION ALL
																			SELECT	USR_CONTENT_ID
																			FROM	BC_USRMETA_WORKTV_SRC2
																			WHERE	('".$start_date."' != 'X' AND USR_WT_BDATE BETWEEN '".$start_date."' AND '".$end_date."')
																					OR
																					('".$production."' != 'X' AND USR_WT_BMT LIKE '%".$production."%')
																					OR
																					('".$program."' != 'X' AND USR_WT_PGMHNM LIKE '%".$program."%')
																					OR
																					('".$program."' != 'X' AND USR_WT_PGMENM LIKE '%".$program."%')
																			)
													GROUP BY C.CONTENT_ID, C.CREATED_DATE
												) C ON(C.CONTENT_ID = A.CID)
										WHERE	A.C_ID IN (
														SELECT	C_ID
														FROM	TB_CLASSIFICATION
														WHERE	C_PID = (SELECT C_PID FROM TB_CLASSIFICATIONNAME WHERE C_ID = 365)
														START WITH C_PID = (SELECT C_PID FROM TB_CLASSIFICATIONNAME WHERE C_ID = 365)
														CONNECT BY PRIOR C_ID = C_PID
														)
										) C
								GROUP BY ROLLUP(C.C_ID)
							) C ON(C.C_ID = A.C_ID)
					ORDER BY A.SORT
				";
			break;
			case 'fourth':
				$query = "
					SELECT	A.C_ID, A.C_NAME
							,TO_CHAR(NVL(B.CNT , 0), 'FM999,999,999,999,990') AS CNT , TO_CHAR(DECODE(C.FILESIZE , NULL, 0, C.FILESIZE ), 'FM999,999,999,999,990') AS FILESIZE
							,TO_CHAR(NVL(B.CNT1, 0), 'FM999,999,999,999,990') AS CNT1, TO_CHAR(DECODE(C.FILESIZE1, NULL, 0, C.FILESIZE1), 'FM999,999,999,999,990') AS FILESIZE1
							,TO_CHAR(NVL(B.CNT2, 0), 'FM999,999,999,999,990') AS CNT2, TO_CHAR(DECODE(C.FILESIZE2, NULL, 0, C.FILESIZE2), 'FM999,999,999,999,990') AS FILESIZE2
							,TO_CHAR(NVL(B.CNT3, 0), 'FM999,999,999,999,990') AS CNT3, TO_CHAR(DECODE(C.FILESIZE3, NULL, 0, C.FILESIZE3), 'FM999,999,999,999,990') AS FILESIZE3
							,TO_CHAR(NVL(B.CNT4, 0), 'FM999,999,999,999,990') AS CNT4, TO_CHAR(DECODE(C.FILESIZE4, NULL, 0, C.FILESIZE4), 'FM999,999,999,999,990') AS FILESIZE4
							,TO_CHAR(NVL(B.CNT5, 0), 'FM999,999,999,999,990') AS CNT5, TO_CHAR(DECODE(C.FILESIZE5, NULL, 0, C.FILESIZE5), 'FM999,999,999,999,990') AS FILESIZE5
							,TO_CHAR(NVL(B.CNT6, 0), 'FM999,999,999,999,990') AS CNT6, TO_CHAR(DECODE(C.FILESIZE6, NULL, 0, C.FILESIZE6), 'FM999,999,999,999,990') AS FILESIZE6
					FROM	(
							SELECT	C_ID, C_NAME, C_PID, C_NAMEID, SORT
							FROM	(
									SELECT	99999 AS C_ID, '진로교육영상분류 합계' AS C_NAME, NULL AS C_PID, NULL AS C_NAMEID, 0 AS SORT
									FROM		(SELECT USER_ID FROM BC_MEMBER WHERE USER_ID = '".$_SESSION['user']['user_id']."')  DUAL_D
									UNION ALL
									SELECT	C_ID, C_NAME, C_PID, C_NAMEID, ROWNUM AS SORT
									FROM	(
											SELECT	C_ID, C_NAME, C_PID, C_NAMEID
											FROM	TB_CLASSIFICATION
											WHERE	C_PID = (SELECT C_PID FROM TB_CLASSIFICATIONNAME WHERE C_ID = 376)
											START WITH C_PID = (SELECT C_PID FROM TB_CLASSIFICATIONNAME WHERE C_ID = 376)
											CONNECT BY PRIOR C_ID = C_PID
											ORDER SIBLINGS BY C_NAME
											) A
									) A
							) A
							LEFT OUTER JOIN (
								SELECT	NVL(C.C_ID, 99999) AS C_ID
										,COUNT(C.CONTENT_ID) AS CNT
										,SUM(CASE WHEN TO_CHAR(SYSDATE, 'YYYY') = C.CREATED_YEAR THEN 1 ELSE 0 END) AS CNT1
										,SUM(CASE WHEN TO_CHAR(SYSDATE - 365 , 'YYYY') = C.CREATED_YEAR THEN 1 ELSE 0 END) AS CNT2
										,SUM(CASE WHEN TO_CHAR(SYSDATE - 730 , 'YYYY') = C.CREATED_YEAR THEN 1 ELSE 0 END) AS CNT3
										,SUM(CASE WHEN TO_CHAR(SYSDATE - 1095, 'YYYY') = C.CREATED_YEAR THEN 1 ELSE 0 END) AS CNT4
										,SUM(CASE WHEN TO_CHAR(SYSDATE - 1460, 'YYYY') = C.CREATED_YEAR THEN 1 ELSE 0 END) AS CNT5
										,SUM(CASE WHEN TO_CHAR(SYSDATE - 1460, 'YYYY') > C.CREATED_YEAR THEN 1 ELSE 0 END) AS CNT6
								FROM	(
										SELECT	A.C_ID, C.CONTENT_ID, SUBSTR(C.CREATED_DATE, 1, 4) AS CREATED_YEAR
										FROM	TB_CLASSIFICATIONMASTER A
												LEFT OUTER JOIN BC_CONTENT C ON(C.CONTENT_ID = A.CID)
										WHERE	A.C_ID IN (
														SELECT	C_ID
														FROM	TB_CLASSIFICATION
														WHERE	C_PID = (SELECT C_PID FROM TB_CLASSIFICATIONNAME WHERE C_ID = 376)
														START WITH C_PID = (SELECT C_PID FROM TB_CLASSIFICATIONNAME WHERE C_ID = 376)
														CONNECT BY PRIOR C_ID = C_PID
														)
										AND		C.CONTENT_ID IN (
																SELECT	CONTENT_ID
																FROM	BC_CONTENT
																WHERE	TITLE LIKE '%%'
																UNION ALL
																SELECT	USR_CONTENT_ID
																FROM	BC_USRMETA_WORKTV_SRC
																WHERE	('".$start_date."' != 'X' AND USR_WT_BDATE BETWEEN '".$start_date."' AND '".$end_date."')
																		OR
																		('".$production."' != 'X' AND USR_WT_BMT LIKE '%".$production."%')
																		OR
																		('".$program."' != 'X' AND USR_WT_PGMHNM LIKE '%".$program."%')
																		OR
																		('".$program."' != 'X' AND USR_WT_PGMENM LIKE '%".$program."%')
																UNION ALL
																SELECT	USR_CONTENT_ID
																FROM	BC_USRMETA_WORKTV_SRC2
																WHERE	('".$start_date."' != 'X' AND USR_WT_BDATE BETWEEN '".$start_date."' AND '".$end_date."')
																		OR
																		('".$production."' != 'X' AND USR_WT_BMT LIKE '%".$production."%')
																		OR
																		('".$program."' != 'X' AND USR_WT_PGMHNM LIKE '%".$program."%')
																		OR
																		('".$program."' != 'X' AND USR_WT_PGMENM LIKE '%".$program."%')
																)
										) C
								GROUP BY ROLLUP(C.C_ID)
							) B ON(B.C_ID = A.C_ID)
							LEFT OUTER JOIN (
								SELECT	NVL(C.C_ID, 99999) AS C_ID
										,SUM(C.FILESIZE) AS FILESIZE
										,SUM(CASE WHEN TO_CHAR(SYSDATE, 'YYYY') = C.CREATED_YEAR THEN C.FILESIZE ELSE 0 END) AS FILESIZE1
										,SUM(CASE WHEN TO_CHAR(SYSDATE - 365 , 'YYYY') = C.CREATED_YEAR THEN C.FILESIZE ELSE 0 END) AS FILESIZE2
										,SUM(CASE WHEN TO_CHAR(SYSDATE - 730 , 'YYYY') = C.CREATED_YEAR THEN C.FILESIZE ELSE 0 END) AS FILESIZE3
										,SUM(CASE WHEN TO_CHAR(SYSDATE - 1095, 'YYYY') = C.CREATED_YEAR THEN C.FILESIZE ELSE 0 END) AS FILESIZE4
										,SUM(CASE WHEN TO_CHAR(SYSDATE - 1460, 'YYYY') = C.CREATED_YEAR THEN C.FILESIZE ELSE 0 END) AS FILESIZE5
										,SUM(CASE WHEN TO_CHAR(SYSDATE - 1460, 'YYYY') > C.CREATED_YEAR THEN C.FILESIZE ELSE 0 END) AS FILESIZE6
								FROM	(
										SELECT	A.C_ID, C.CONTENT_ID, C.CREATED_YEAR, TRUNC(C.FILESIZE/1024/1024) AS FILESIZE
										FROM	TB_CLASSIFICATIONMASTER A
												LEFT OUTER JOIN (
													SELECT	C.CONTENT_ID
															,SUBSTR(C.CREATED_DATE, 1, 4) AS CREATED_YEAR
															,SUM(M.FILESIZE) AS FILESIZE
													FROM	BC_CONTENT C
															LEFT OUTER JOIN BC_MEDIA M ON(M.CONTENT_ID = C.CONTENT_ID)
													WHERE	C.CONTENT_ID IN (
																			SELECT	A.CID
																			FROM	TB_CLASSIFICATIONMASTER A
																			WHERE	C_ID IN (
																							SELECT	C_ID
																							FROM	TB_CLASSIFICATION
																							WHERE	C_PID = (SELECT C_PID FROM TB_CLASSIFICATIONNAME WHERE C_ID = 376)
																							START WITH C_PID = (SELECT C_PID FROM TB_CLASSIFICATIONNAME WHERE C_ID = 376)
																							CONNECT BY PRIOR C_ID = C_PID
																							)
																			)
													AND		C.CONTENT_ID IN (
																			SELECT	CONTENT_ID
																			FROM	BC_CONTENT
																			WHERE	TITLE LIKE '%%'
																			UNION ALL
																			SELECT	USR_CONTENT_ID
																			FROM	BC_USRMETA_WORKTV_SRC
																			WHERE	('".$start_date."' != 'X' AND USR_WT_BDATE BETWEEN '".$start_date."' AND '".$end_date."')
																					OR
																					('".$production."' != 'X' AND USR_WT_BMT LIKE '%".$production."%')
																					OR
																					('".$program."' != 'X' AND USR_WT_PGMHNM LIKE '%".$program."%')
																					OR
																					('".$program."' != 'X' AND USR_WT_PGMENM LIKE '%".$program."%')
																			UNION ALL
																			SELECT	USR_CONTENT_ID
																			FROM	BC_USRMETA_WORKTV_SRC2
																			WHERE	('".$start_date."' != 'X' AND USR_WT_BDATE BETWEEN '".$start_date."' AND '".$end_date."')
																					OR
																					('".$production."' != 'X' AND USR_WT_BMT LIKE '%".$production."%')
																					OR
																					('".$program."' != 'X' AND USR_WT_PGMHNM LIKE '%".$program."%')
																					OR
																					('".$program."' != 'X' AND USR_WT_PGMENM LIKE '%".$program."%')
																			)
													GROUP BY C.CONTENT_ID, C.CREATED_DATE
												) C ON(C.CONTENT_ID = A.CID)
										WHERE	A.C_ID IN (
														SELECT	C_ID
														FROM	TB_CLASSIFICATION
														WHERE	C_PID = (SELECT C_PID FROM TB_CLASSIFICATIONNAME WHERE C_ID = 381)
														START WITH C_PID = (SELECT C_PID FROM TB_CLASSIFICATIONNAME WHERE C_ID = 381)
														CONNECT BY PRIOR C_ID = C_PID
														)
										) C
								GROUP BY ROLLUP(C.C_ID)
							) C ON(C.C_ID = A.C_ID)
					ORDER BY A.SORT
				";
			break;
			case 'fifth':
				$query = "
					SELECT	A.C_ID, A.C_NAME
							,TO_CHAR(NVL(B.CNT , 0), 'FM999,999,999,999,990') AS CNT , TO_CHAR(DECODE(C.FILESIZE , NULL, 0, C.FILESIZE ), 'FM999,999,999,999,990') AS FILESIZE
							,TO_CHAR(NVL(B.CNT1 , 0), 'FM999,999,999,999,990') AS CNT1, TO_CHAR(DECODE(C.FILESIZE1, NULL, 0, C.FILESIZE1), 'FM999,999,999,999,990') AS FILESIZE1
							,TO_CHAR(NVL(B.CNT2 , 0), 'FM999,999,999,999,990') AS CNT2, TO_CHAR(DECODE(C.FILESIZE2, NULL, 0, C.FILESIZE2), 'FM999,999,999,999,990') AS FILESIZE2
							,TO_CHAR(NVL(B.CNT3 , 0), 'FM999,999,999,999,990') AS CNT3, TO_CHAR(DECODE(C.FILESIZE3, NULL, 0, C.FILESIZE3), 'FM999,999,999,999,990') AS FILESIZE3
							,TO_CHAR(NVL(B.CNT4 , 0), 'FM999,999,999,999,990') AS CNT4, TO_CHAR(DECODE(C.FILESIZE4, NULL, 0, C.FILESIZE4), 'FM999,999,999,999,990') AS FILESIZE4
							,TO_CHAR(NVL(B.CNT5 , 0), 'FM999,999,999,999,990') AS CNT5, TO_CHAR(DECODE(C.FILESIZE5, NULL, 0, C.FILESIZE5), 'FM999,999,999,999,990') AS FILESIZE5
							,TO_CHAR(NVL(B.CNT6 , 0), 'FM999,999,999,999,990') AS CNT6, TO_CHAR(DECODE(C.FILESIZE6, NULL, 0, C.FILESIZE6), 'FM999,999,999,999,990') AS FILESIZE6
					FROM	(
							SELECT	C_ID, C_NAME, C_PID, C_NAMEID, SORT
							FROM	(
									SELECT	99999 AS C_ID, '학과정보영상 합계' AS C_NAME, NULL AS C_PID, NULL AS C_NAMEID, 0 AS SORT
									FROM		(SELECT USER_ID FROM BC_MEMBER WHERE USER_ID = '".$_SESSION['user']['user_id']."')  DUAL_D
									UNION ALL
									SELECT	C_ID, C_NAME, C_PID, C_NAMEID, ROWNUM AS SORT
									FROM	(
											SELECT	C_ID, C_NAME, C_PID, C_NAMEID
											FROM	TB_CLASSIFICATION
											WHERE	C_PID = (SELECT C_PID FROM TB_CLASSIFICATIONNAME WHERE C_ID = 381)
											START WITH C_PID = (SELECT C_PID FROM TB_CLASSIFICATIONNAME WHERE C_ID = 381)
											CONNECT BY PRIOR C_ID = C_PID
											ORDER SIBLINGS BY C_NAME
											) A
									) A
							) A
							LEFT OUTER JOIN (
								SELECT	NVL(C.C_ID, 99999) AS C_ID
										,COUNT(C.CONTENT_ID) AS CNT
										,SUM(CASE WHEN TO_CHAR(SYSDATE, 'YYYY') = C.CREATED_YEAR THEN 1 ELSE 0 END) AS CNT1
										,SUM(CASE WHEN TO_CHAR(SYSDATE - 365 , 'YYYY') = C.CREATED_YEAR THEN 1 ELSE 0 END) AS CNT2
										,SUM(CASE WHEN TO_CHAR(SYSDATE - 730 , 'YYYY') = C.CREATED_YEAR THEN 1 ELSE 0 END) AS CNT3
										,SUM(CASE WHEN TO_CHAR(SYSDATE - 1095, 'YYYY') = C.CREATED_YEAR THEN 1 ELSE 0 END) AS CNT4
										,SUM(CASE WHEN TO_CHAR(SYSDATE - 1460, 'YYYY') = C.CREATED_YEAR THEN 1 ELSE 0 END) AS CNT5
										,SUM(CASE WHEN TO_CHAR(SYSDATE - 1460, 'YYYY') > C.CREATED_YEAR THEN 1 ELSE 0 END) AS CNT6
								FROM	(
										SELECT	A.C_ID, C.CONTENT_ID, SUBSTR(C.CREATED_DATE, 1, 4) AS CREATED_YEAR
										FROM	TB_CLASSIFICATIONMASTER A
												LEFT OUTER JOIN BC_CONTENT C ON(C.CONTENT_ID = A.CID)
										WHERE	A.C_ID IN (
														SELECT	C_ID
														FROM	TB_CLASSIFICATION
														WHERE	C_PID = (SELECT C_PID FROM TB_CLASSIFICATIONNAME WHERE C_ID = 381)
														START WITH C_PID = (SELECT C_PID FROM TB_CLASSIFICATIONNAME WHERE C_ID = 381)
														CONNECT BY PRIOR C_ID = C_PID
														)
										AND		C.CONTENT_ID IN (
																SELECT	CONTENT_ID
																FROM	BC_CONTENT
																WHERE	TITLE LIKE '%".$title."%'
																UNION ALL
																SELECT	USR_CONTENT_ID
																FROM	BC_USRMETA_WORKTV_SRC
																WHERE	('".$start_date."' != 'X' AND USR_WT_BDATE BETWEEN '".$start_date."' AND '".$end_date."')
																		OR
																		('".$production."' != 'X' AND USR_WT_BMT LIKE '%".$production."%')
																		OR
																		('".$program."' != 'X' AND USR_WT_PGMHNM LIKE '%".$program."%')
																		OR
																		('".$program."' != 'X' AND USR_WT_PGMENM LIKE '%".$program."%')
																UNION ALL
																SELECT	USR_CONTENT_ID
																FROM	BC_USRMETA_WORKTV_SRC2
																WHERE	('".$start_date."' != 'X' AND USR_WT_BDATE BETWEEN '".$start_date."' AND '".$end_date."')
																		OR
																		('".$production."' != 'X' AND USR_WT_BMT LIKE '%".$production."%')
																		OR
																		('".$program."' != 'X' AND USR_WT_PGMHNM LIKE '%".$program."%')
																		OR
																		('".$program."' != 'X' AND USR_WT_PGMENM LIKE '%".$program."%')
																)
										) C
								GROUP BY ROLLUP(C.C_ID)
							) B ON(B.C_ID = A.C_ID)
							LEFT OUTER JOIN (
								SELECT	NVL(C.C_ID, 99999) AS C_ID
										,SUM(C.FILESIZE) AS FILESIZE
										,SUM(CASE WHEN TO_CHAR(SYSDATE, 'YYYY') = C.CREATED_YEAR THEN C.FILESIZE ELSE 0 END) AS FILESIZE1
										,SUM(CASE WHEN TO_CHAR(SYSDATE - 365 , 'YYYY') = C.CREATED_YEAR THEN C.FILESIZE ELSE 0 END) AS FILESIZE2
										,SUM(CASE WHEN TO_CHAR(SYSDATE - 730 , 'YYYY') = C.CREATED_YEAR THEN C.FILESIZE ELSE 0 END) AS FILESIZE3
										,SUM(CASE WHEN TO_CHAR(SYSDATE - 1095, 'YYYY') = C.CREATED_YEAR THEN C.FILESIZE ELSE 0 END) AS FILESIZE4
										,SUM(CASE WHEN TO_CHAR(SYSDATE - 1460, 'YYYY') = C.CREATED_YEAR THEN C.FILESIZE ELSE 0 END) AS FILESIZE5
										,SUM(CASE WHEN TO_CHAR(SYSDATE - 1460, 'YYYY') > C.CREATED_YEAR THEN C.FILESIZE ELSE 0 END) AS FILESIZE6
								FROM	(
										SELECT	A.C_ID, C.CONTENT_ID, C.CREATED_YEAR, TRUNC(C.FILESIZE/1024/1024) AS FILESIZE
										FROM	TB_CLASSIFICATIONMASTER A
												LEFT OUTER JOIN (
													SELECT	C.CONTENT_ID
															,SUBSTR(C.CREATED_DATE, 1, 4) AS CREATED_YEAR
															,SUM(M.FILESIZE) AS FILESIZE
													FROM	BC_CONTENT C
															LEFT OUTER JOIN BC_MEDIA M ON(M.CONTENT_ID = C.CONTENT_ID)
													WHERE	C.CONTENT_ID IN (
																			SELECT	A.CID
																			FROM	TB_CLASSIFICATIONMASTER A
																			WHERE	C_ID IN (
																							SELECT	C_ID
																							FROM	TB_CLASSIFICATION
																							WHERE	C_PID = (SELECT C_PID FROM TB_CLASSIFICATIONNAME WHERE C_ID = 381)
																							START WITH C_PID = (SELECT C_PID FROM TB_CLASSIFICATIONNAME WHERE C_ID = 381)
																							CONNECT BY PRIOR C_ID = C_PID
																							)
																			)
													AND		C.CONTENT_ID IN (
																			SELECT	CONTENT_ID
																			FROM	BC_CONTENT
																			WHERE	TITLE LIKE '%".$title."%'
																			UNION ALL
																			SELECT	USR_CONTENT_ID
																			FROM	BC_USRMETA_WORKTV_SRC
																			WHERE	('".$start_date."' != 'X' AND USR_WT_BDATE BETWEEN '".$start_date."' AND '".$end_date."')
																					OR
																					('".$production."' != 'X' AND USR_WT_BMT LIKE '%".$production."%')
																					OR
																					('".$program."' != 'X' AND USR_WT_PGMHNM LIKE '%".$program."%')
																					OR
																					('".$program."' != 'X' AND USR_WT_PGMENM LIKE '%".$program."%')
																			UNION ALL
																			SELECT	USR_CONTENT_ID
																			FROM	BC_USRMETA_WORKTV_SRC2
																			WHERE	('".$start_date."' != 'X' AND USR_WT_BDATE BETWEEN '".$start_date."' AND '".$end_date."')
																					OR
																					('".$production."' != 'X' AND USR_WT_BMT LIKE '%".$production."%')
																					OR
																					('".$program."' != 'X' AND USR_WT_PGMHNM LIKE '%".$program."%')
																					OR
																					('".$program."' != 'X' AND USR_WT_PGMENM LIKE '%".$program."%')
																			)
													GROUP BY C.CONTENT_ID, C.CREATED_DATE
												) C ON(C.CONTENT_ID = A.CID)
										WHERE	A.C_ID IN (
														SELECT	C_ID
														FROM	TB_CLASSIFICATION
														WHERE	C_PID = (SELECT C_PID FROM TB_CLASSIFICATIONNAME WHERE C_ID = 381)
														START WITH C_PID = (SELECT C_PID FROM TB_CLASSIFICATIONNAME WHERE C_ID = 381)
														CONNECT BY PRIOR C_ID = C_PID
														)
										) C
								GROUP BY ROLLUP(C.C_ID)
							) C ON(C.C_ID = A.C_ID)
					ORDER BY A.SORT
				";
			break;
			case 'sixth':
				$query = "
					SELECT	A.C_ID, A.C_NAME
							,TO_CHAR(NVL(B.CNT , 0), 'FM999,999,999,999,990') AS CNT , TO_CHAR(DECODE(C.FILESIZE , NULL, 0, C.FILESIZE ), 'FM999,999,999,999,990') AS FILESIZE
							,TO_CHAR(NVL(B.CNT1 , 0), 'FM999,999,999,999,990') AS CNT1, TO_CHAR(DECODE(C.FILESIZE1, NULL, 0, C.FILESIZE1), 'FM999,999,999,999,990') AS FILESIZE1
							,TO_CHAR(NVL(B.CNT2 , 0), 'FM999,999,999,999,990') AS CNT2, TO_CHAR(DECODE(C.FILESIZE2, NULL, 0, C.FILESIZE2), 'FM999,999,999,999,990') AS FILESIZE2
							,TO_CHAR(NVL(B.CNT3 , 0), 'FM999,999,999,999,990') AS CNT3, TO_CHAR(DECODE(C.FILESIZE3, NULL, 0, C.FILESIZE3), 'FM999,999,999,999,990') AS FILESIZE3
							,TO_CHAR(NVL(B.CNT4 , 0), 'FM999,999,999,999,990') AS CNT4, TO_CHAR(DECODE(C.FILESIZE4, NULL, 0, C.FILESIZE4), 'FM999,999,999,999,990') AS FILESIZE4
							,TO_CHAR(NVL(B.CNT5 , 0), 'FM999,999,999,999,990') AS CNT5, TO_CHAR(DECODE(C.FILESIZE5, NULL, 0, C.FILESIZE5), 'FM999,999,999,999,990') AS FILESIZE5
							,TO_CHAR(NVL(B.CNT6 , 0), 'FM999,999,999,999,990') AS CNT6, TO_CHAR(DECODE(C.FILESIZE6, NULL, 0, C.FILESIZE6), 'FM999,999,999,999,990') AS FILESIZE6
					FROM	(
							SELECT	C_ID, C_NAME, C_PID, C_NAMEID, SORT
							FROM	(
									SELECT	99999 AS C_ID, '기타영상분류 합계' AS C_NAME, NULL AS C_PID, NULL AS C_NAMEID, 0 AS SORT
									FROM		(SELECT USER_ID FROM BC_MEMBER WHERE USER_ID = '".$_SESSION['user']['user_id']."')  DUAL_D
									UNION ALL
									SELECT	C_ID, C_NAME, C_PID, C_NAMEID, ROWNUM AS SORT
									FROM	(
											SELECT	C_ID, C_NAME, C_PID, C_NAMEID
											FROM	TB_CLASSIFICATION
											WHERE	C_PID = (SELECT C_PID FROM TB_CLASSIFICATIONNAME WHERE C_ID = 391)
											START WITH C_PID = (SELECT C_PID FROM TB_CLASSIFICATIONNAME WHERE C_ID = 391)
											CONNECT BY PRIOR C_ID = C_PID
											ORDER SIBLINGS BY C_NAME
											) A
									) A
							) A
							LEFT OUTER JOIN (
								SELECT	NVL(C.C_ID, 99999) AS C_ID
										,COUNT(C.CONTENT_ID) AS CNT
										,SUM(CASE WHEN TO_CHAR(SYSDATE, 'YYYY') = C.CREATED_YEAR THEN 1 ELSE 0 END) AS CNT1
										,SUM(CASE WHEN TO_CHAR(SYSDATE - 365 , 'YYYY') = C.CREATED_YEAR THEN 1 ELSE 0 END) AS CNT2
										,SUM(CASE WHEN TO_CHAR(SYSDATE - 730 , 'YYYY') = C.CREATED_YEAR THEN 1 ELSE 0 END) AS CNT3
										,SUM(CASE WHEN TO_CHAR(SYSDATE - 1095, 'YYYY') = C.CREATED_YEAR THEN 1 ELSE 0 END) AS CNT4
										,SUM(CASE WHEN TO_CHAR(SYSDATE - 1460, 'YYYY') = C.CREATED_YEAR THEN 1 ELSE 0 END) AS CNT5
										,SUM(CASE WHEN TO_CHAR(SYSDATE - 1460, 'YYYY') > C.CREATED_YEAR THEN 1 ELSE 0 END) AS CNT6
								FROM	(
										SELECT	A.C_ID, C.CONTENT_ID, SUBSTR(C.CREATED_DATE, 1, 4) AS CREATED_YEAR
										FROM	TB_CLASSIFICATIONMASTER A
												LEFT OUTER JOIN BC_CONTENT C ON(C.CONTENT_ID = A.CID)
										WHERE	A.C_ID IN (
														SELECT	C_ID
														FROM	TB_CLASSIFICATION
														WHERE	C_PID = (SELECT C_PID FROM TB_CLASSIFICATIONNAME WHERE C_ID = 391)
														START WITH C_PID = (SELECT C_PID FROM TB_CLASSIFICATIONNAME WHERE C_ID = 391)
														CONNECT BY PRIOR C_ID = C_PID
														)
										AND		C.CONTENT_ID IN (
																SELECT	CONTENT_ID
																FROM	BC_CONTENT
																WHERE	TITLE LIKE '%".$title."%'
																UNION ALL
																SELECT	USR_CONTENT_ID
																FROM	BC_USRMETA_WORKTV_SRC
																WHERE	('".$start_date."' != 'X' AND USR_WT_BDATE BETWEEN '".$start_date."' AND '".$end_date."')
																		OR
																		('".$production."' != 'X' AND USR_WT_BMT LIKE '%".$production."%')
																		OR
																		('".$program."' != 'X' AND USR_WT_PGMHNM LIKE '%".$program."%')
																		OR
																		('".$program."' != 'X' AND USR_WT_PGMENM LIKE '%".$program."%')
																UNION ALL
																SELECT	USR_CONTENT_ID
																FROM	BC_USRMETA_WORKTV_SRC2
																WHERE	('".$start_date."' != 'X' AND USR_WT_BDATE BETWEEN '".$start_date."' AND '".$end_date."')
																		OR
																		('".$production."' != 'X' AND USR_WT_BMT LIKE '%".$production."%')
																		OR
																		('".$program."' != 'X' AND USR_WT_PGMHNM LIKE '%".$program."%')
																		OR
																		('".$program."' != 'X' AND USR_WT_PGMENM LIKE '%".$program."%')
																)
										) C
								GROUP BY ROLLUP(C.C_ID)
							) B ON(B.C_ID = A.C_ID)
							LEFT OUTER JOIN (
								SELECT	NVL(C.C_ID, 99999) AS C_ID
										,SUM(C.FILESIZE) AS FILESIZE
										,SUM(CASE WHEN TO_CHAR(SYSDATE, 'YYYY') = C.CREATED_YEAR THEN C.FILESIZE ELSE 0 END) AS FILESIZE1
										,SUM(CASE WHEN TO_CHAR(SYSDATE - 365 , 'YYYY') = C.CREATED_YEAR THEN C.FILESIZE ELSE 0 END) AS FILESIZE2
										,SUM(CASE WHEN TO_CHAR(SYSDATE - 730 , 'YYYY') = C.CREATED_YEAR THEN C.FILESIZE ELSE 0 END) AS FILESIZE3
										,SUM(CASE WHEN TO_CHAR(SYSDATE - 1095, 'YYYY') = C.CREATED_YEAR THEN C.FILESIZE ELSE 0 END) AS FILESIZE4
										,SUM(CASE WHEN TO_CHAR(SYSDATE - 1460, 'YYYY') = C.CREATED_YEAR THEN C.FILESIZE ELSE 0 END) AS FILESIZE5
										,SUM(CASE WHEN TO_CHAR(SYSDATE - 1460, 'YYYY') > C.CREATED_YEAR THEN C.FILESIZE ELSE 0 END) AS FILESIZE6
								FROM	(
										SELECT	A.C_ID, C.CONTENT_ID, C.CREATED_YEAR, TRUNC(C.FILESIZE/1024/1024) AS FILESIZE
										FROM	TB_CLASSIFICATIONMASTER A
												LEFT OUTER JOIN (
													SELECT	C.CONTENT_ID
															,SUBSTR(C.CREATED_DATE, 1, 4) AS CREATED_YEAR
															,SUM(M.FILESIZE) AS FILESIZE
													FROM	BC_CONTENT C
															LEFT OUTER JOIN BC_MEDIA M ON(M.CONTENT_ID = C.CONTENT_ID)
													WHERE	C.CONTENT_ID IN (
																			SELECT	A.CID
																			FROM	TB_CLASSIFICATIONMASTER A
																			WHERE	C_ID IN (
																							SELECT	C_ID
																							FROM	TB_CLASSIFICATION
																							WHERE	C_PID = (SELECT C_PID FROM TB_CLASSIFICATIONNAME WHERE C_ID = 391)
																							START WITH C_PID = (SELECT C_PID FROM TB_CLASSIFICATIONNAME WHERE C_ID = 391)
																							CONNECT BY PRIOR C_ID = C_PID
																							)
																			)
													AND		C.CONTENT_ID IN (
																			SELECT	CONTENT_ID
																			FROM	BC_CONTENT
																			WHERE	TITLE LIKE '%".$title."%'
																			UNION ALL
																			SELECT	USR_CONTENT_ID
																			FROM	BC_USRMETA_WORKTV_SRC
																			WHERE	('".$start_date."' != 'X' AND USR_WT_BDATE BETWEEN '".$start_date."' AND '".$end_date."')
																					OR
																					('".$production."' != 'X' AND USR_WT_BMT LIKE '%".$production."%')
																					OR
																					('".$program."' != 'X' AND USR_WT_PGMHNM LIKE '%".$program."%')
																					OR
																					('".$program."' != 'X' AND USR_WT_PGMENM LIKE '%".$program."%')
																			UNION ALL
																			SELECT	USR_CONTENT_ID
																			FROM	BC_USRMETA_WORKTV_SRC2
																			WHERE	('".$start_date."' != 'X' AND USR_WT_BDATE BETWEEN '".$start_date."' AND '".$end_date."')
																					OR
																					('".$production."' != 'X' AND USR_WT_BMT LIKE '%".$production."%')
																					OR
																					('".$program."' != 'X' AND USR_WT_PGMHNM LIKE '%".$program."%')
																					OR
																					('".$program."' != 'X' AND USR_WT_PGMENM LIKE '%".$program."%')
																			)
													GROUP BY C.CONTENT_ID, C.CREATED_DATE
												) C ON(C.CONTENT_ID = A.CID)
										WHERE	A.C_ID IN (
														SELECT	C_ID
														FROM	TB_CLASSIFICATION
														WHERE	C_PID = (SELECT C_PID FROM TB_CLASSIFICATIONNAME WHERE C_ID = 391)
														START WITH C_PID = (SELECT C_PID FROM TB_CLASSIFICATIONNAME WHERE C_ID = 391)
														CONNECT BY PRIOR C_ID = C_PID
														)
										) C
								GROUP BY ROLLUP(C.C_ID)
							) C ON(C.C_ID = A.C_ID)
					ORDER BY A.SORT
				";
			break;
		}
	}

	return $query;
}

?>