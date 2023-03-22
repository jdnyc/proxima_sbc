<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');
try{


	$client = new SoapClient("http://192.168.10.207:8080/wsdl/IServiceAM");


	$type = $_POST['type'];

	if($type == 'create_user'){
		$param = array(array(
			'SYS_TYPE' => 100,
			'JOB_TYPE' => 100,
			'PARAM' => array(
				'USER_NAME' => 'testuser',
				'PASSWORD'  => 'test!Q@W#E',
				'COMMON_NAME'  => '이도훈',
				 'DESCRIPTION' => '테스트계정 생성',
				'DOMAINDC'  => 'dc=cha,dc=ddmc'
			)
		));

	}else if($type == 'create_group'){
		$param = array(array(
		'SYS_TYPE' => 100,
		'JOB_TYPE' => 200,
		'PARAM' => array(
			'GROUP_NAME' => 'xfile',
			'COMMON_NAME'  => 'Xfile',
			'DESCRIPTION'  => '먹거리 X파일 그룹 생성',
			'DOMAINDC'  => 'dc=cha,dc=ddmc'
		)
	),array(
		'SYS_TYPE' => 100,
		'JOB_TYPE' => 202,
		'PARAM' => array(
			'GROUP_NAME' => 'xfile',
			'ROOT_PATH'  => 'C:/TEMP'
		)
	));
	}else if($type == 'add_user'){
		$param = array(array(
		'SYS_TYPE' => 100,
		'JOB_TYPE' => 102,
		'PARAM' => array(
			'GROUP_NAME' => 'xfile',
			'COMMON_NAME'  => '이도훈',
			'DOMAINDC'  => 'dc=cha,dc=ddmc'
		)
	));
	}else if($type == 'map_group'){
		$param = array(array(
		'SYS_TYPE' => 100,
		'JOB_TYPE' => 202,
		'PARAM' => array(
			'GROUP_NAME' => 'xfile',
			'ROOT_PATH'  => 'C:/TEMP'
		)
	));
	}

	$param_text = json_encode($param);

	$result = $client->TempTest( $param_text );

	echo json_encode(array(
		'success' => true,
		'msg' => $result
	));

}
catch(Exception $e)
{
	echo json_encode(array(
		'success' => false,
		'msg' => 'error'
	));

}

?>