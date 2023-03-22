<?php
	require_once($_SERVER['DOCUMENT_ROOT'] . '/lib/config.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/lib/functions.php');
	require_once($_SERVER['DOCUMENT_ROOT'].'/lib/PHPExcel.php');
	
try{
	$filename = $_POST['excel_path'];

	$listUser = $db->queryAll("SELECT USER_ID FROM BC_MEMBER");
	$listUserId = array();

	foreach ($listUser as $user) {
		array_push($listUserId, $user['user_id']);
	}

	

	$inputFileType = PHPExcel_IOFactory::identify($filename);
	$objReader = PHPExcel_IOFactory::createReader($inputFileType);
	 
	$objReader->setReadDataOnly(true);
	 
	/**  Load $inputFileName to a PHPExcel Object  **/
	$objPHPExcel = $objReader->load("$filename");
	 
	$total_sheets=$objPHPExcel->getSheetCount();
	 
	$allSheetName=$objPHPExcel->getSheetNames();
	$objWorksheet  = $objPHPExcel->setActiveSheetIndex(0);
	$highestRow    = $objWorksheet->getHighestRow();
	$highestColumn = $objWorksheet->getHighestColumn();
	$highestColumnIndex = PHPExcel_Cell::columnIndexFromString($highestColumn);
	$arraydata = array();
	for ($row = 2; $row <= $highestRow;++$row)
	{
	    for ($col = 0; $col <$highestColumnIndex;++$col)
	    {
	        $value=$objWorksheet->getCellByColumnAndRow($col, $row)->getValue();
	        $arraydata[$row-2][$col]=$value;
	    }
	}

	$user_id_marked = array();

	$cur_date = date('YmdHis');
	foreach ($arraydata as $userInfo) {
		$user_id = $userInfo['0'];
		
		if(!in_array($user_id, $user_id_marked)){
			if (in_array($user_id, $listUserId)) {
			    // update user information
				$update_data = array(
					'user_nm' => $userInfo['1'],
					'dept_nm' => $userInfo['2'],
					'email'	=> $userInfo['3'],
					'phone'	=> $userInfo['4']
				);

				$db->update('BC_MEMBER', $update_data, "user_id = '$user_id'");
				array_push($user_id_marked, $user_id);
			}else{

				// insert user infor to BC_MEMBER
				$member_id = ($db->queryOne("select max(member_id) as max_member_id from bc_member"))+1;
				$pwd = hash('sha512',$user_id);
				$insert_data = array(
					'member_id' => $member_id,
					'user_id'	=> $user_id,
					'password'	=> $pwd,
					'user_nm' => $userInfo['1'],
					'dept_nm' => $userInfo['2'],
					'email'	=> $userInfo['3'],
					'phone'	=> $userInfo['4'],
					'created_date' =>$cur_date
				);
				$db->insert('BC_MEMBER', $insert_data);

				// insert user infor to BC_MEMBER_OPTION
				$member_option_id = ($db->queryOne("SELECT max(member_option_id) AS max_member_opition_id FROM bc_member_option"))+1;
				$data = array(
					'member_option_id' => $member_option_id,
					'member_id'	 =>	$member_id
				);
				$db->insert('BC_MEMBER_OPTION', $data);

				// insert user infor to BC_MEMBER_OPTION
				$default_member_group_id = $db->queryAll("SELECT member_group_id FROM BC_MEMBER_GROUP WHERE IS_DEFAULT = 'Y'");
				foreach ($default_member_group_id as $member_group) {
					$data = array(
						'member_id'	 =>	$member_id,
						'member_group_id' => $member_group['member_group_id']
					);
					$db->insert('BC_MEMBER_GROUP_MEMBER', $data);
				}
				array_push($user_id_marked, $user_id);
			}
		}
	}
	unlink($filename);
	echo json_encode( array(
		'success' => true,
		'result' => 'importing success'
	));

}catch(Exception $e){
	
	unlink($_POST['excel_path']);

	die(json_encode(array(
		'success' => false,
		'result' => 'Some errors!!!',
		'msg' => $e->getMessage()
	)));
}
?>