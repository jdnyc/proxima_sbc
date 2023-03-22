<?php

class ActiveDirectory
{
	const AD_URL = 'http://192.168.10.207:8080/wsdl/IServiceAM';
	const DOMAINDC = 'dc=cha,dc=ddmc';
	const FILE_SYSTEM = 'Test_Vol';
	/*
	SYS_TYPE =
		100 => 'AD'
		200 => 'OD'
		300 => 'MDC'
	JOB_TYPE =
		100 => AD, OD 시스템에 신규 사용자를 생성한다.
		101 => AD, OD 시스템에 사용자를 삭제 한다.
		102 => AD, OD 시스템에 사용자를 그룹에 포함 시킨다.
		103 => AD, OD 시스템에 사용자를 그룹에서 제외 시킨다.
		200 => AD, OD 시스템에 그룹을 추가한다.
		201 => AD, OD 시스템에 그룹을 삭제한다.
		202 => Directory에 AD, OD그룹 권한을 부여한다.
		203 => Directory에 AD, OD그룹 권한을 제외한다.
		300 => MDC에 쿼터를 생성한다.
		301 => MDC에 쿼터를 수정한다.
		302 => MDC에 쿼터를 삭제한다.
	PARAM =
		USER_NAME => user01
		PASSWORD => Ex)Password
		COMMON_NAME => Ex)홍길동
		DESCRIPTION => Ex)설명입니다.
		DOMAINDC => Ex)dc=cha,dc=ddmc
		GROUP_NAME => Ex)Group01
		WIND_ROOT_PATH => Ex)M:\Storage
		UNIX_ROOT_PATH => Ex)Test_Vol/Storage
		DIRECTORY => Ex)testdir
		MAX_SIZE => Ex)1024
		FILE_SYSTEM => Ex)Test_Vol
	*/


	function __construct()
	{

	}

	function _log($log){
		@file_put_contents(LOG_PATH.'/AD_'.date('Ymd').'.log', '['.date('Y-m-d H:i:s').'] '.print_r($log,true)."\n", FILE_APPEND);
	}

	function SoapFunc($param){
		$param_text = json_encode($param);
		$client = new SoapClient( self::AD_URL );
		$return = $client->SetAgentJob( $param_text );

		$this->_log($return);

		return $return;
	}


	function CreateGroup($data ){

		//그룹 생성 => 패스 권한 부여 => 쿼터 생성
		$param = array(
			array(
				'SYS_TYPE' => 300,
				'JOB_TYPE' => 300,
				'PARAM' => array(
					'MAX_SIZE' => $data['max_size'],
					'UNIX_ROOT_PATH' => $data['unix_root_path'],
					'DIRECTORY' => $data['directory'],
					'FILE_SYSTEM' => self::FILE_SYSTEM
				)
			),
			array(
				'SYS_TYPE' => 100,
				'JOB_TYPE' => 200,
				'PARAM' => array(
					'GROUP_NAME' => $data['group_name'],
					'COMMON_NAME'  => $data['group_name'],
					'DESCRIPTION'  => $data['category_name'].' 그룹 생성',
					'DOMAINDC'  => self::DOMAINDC
				)
			),
			array(
				'SYS_TYPE' => 100,
				'JOB_TYPE' => 202,
				'PARAM' => array(
					'GROUP_NAME' => $data['group_name'],
					'WIN_ROOT_PATH' => $data['win_root_path'],
					'DIRECTORY' => $data['directory']
				)
			)
		);
		return $this->SoapFunc($param);
	}

	function DeleteGroup($data ){
		//그룹 추가 후 쿼터 생성
		$param = array(
			array(
					'SYS_TYPE' => 100,
					'JOB_TYPE' => 203,
					'PARAM' => array(
						'GROUP_NAME' => $data['group_name'],
						'WIN_ROOT_PATH' => $data['win_root_path'],
						'DIRECTORY' => $data['directory']
					)
				),
			array(
				'SYS_TYPE' => 100,
				'JOB_TYPE' => 201,
				'PARAM' => array(
					'GROUP_NAME' => $data['group_name'],
					'COMMON_NAME'  => $data['common_name'],
					'DESCRIPTION'  => $data['category_name'].' 그룹 삭제',
					'DOMAINDC'  => self::DOMAINDC
				)
			),
			array(
				'SYS_TYPE' => 300,
				'JOB_TYPE' => 302,
				'PARAM' => array(
					'MAX_SIZE' => $data['max_size'],
					'UNIX_ROOT_PATH' => $data['unix_root_path'],
					'DIRECTORY' => $data['directory'],
					'FILE_SYSTEM' => self::FILE_SYSTEM
				)
			)
		);
		return $this->SoapFunc($param);
	}

	function CreateUser($datas){

		$param = array();

		foreach($datas as $data)
		{
			$data['password'] = 'chanps!Q@W#E';
			array_push($param,array(
				'SYS_TYPE' => 100,
				'JOB_TYPE' => 100,
				'PARAM' => array(
					'USER_NAME' => $data['user_name'],
					'PASSWORD' => $data['password'],
					'COMMON_NAME'  => $data['common_name'],
					'DESCRIPTION'  => $data['user_name'].' 사용자 생성',
					'DOMAINDC'  => self::DOMAINDC
				)
			));

			array_push($param,array(
				'SYS_TYPE' => 100,
				'JOB_TYPE' => 102,
				'PARAM' => array(
					'GROUP_NAME' => $data['group_name'],
					'COMMON_NAME'  => $data['common_name'],
					'DOMAINDC'  => self::DOMAINDC
				)
			));
		}

		return $this->SoapFunc($param);
	}

	function DeleteUser($datas)
	{
		$param = array();
		foreach($datas as $data)
		{
			array_push($param,array(
				'SYS_TYPE' => 100,
				'JOB_TYPE' => 103,
				'PARAM' => array(
					'GROUP_NAME' => $data['group_name'],
					'COMMON_NAME'  => $data['common_name'],
					'DOMAINDC'  => self::DOMAINDC
				)
			));
		}

		return $this->SoapFunc($param);
	}

	function EditQUOTA($data)
	{
		$param = array(
			array(
				'SYS_TYPE' => 300,
				'JOB_TYPE' => 301,
				'PARAM' => array(
					'MAX_SIZE' => $data['max_size'],
					'UNIX_ROOT_PATH'  => $data['unix_root_path'],
					'DIRECTORY'  => $data['directory'],
					'FILE_SYSTEM' => self::FILE_SYSTEM
				)
			)
		);
		return $this->SoapFunc($param);
	}

	function getList( $category_id = null, $path = null ){
		global $db;
		$codeList = getCodeInfo( 'STORAGE_ROOT'  );
		if( empty($codeList) ) throw new Exception("루트 스토리지 정보가 없습니다.",106);
		$codeList = array_shift($codeList);
		$root_storage_id =  $codeList['code'];
		$root_storage = $db->queryRow("select * from bc_storage where storage_id='$root_storage_id'");
		if( empty($root_storage) ) throw new Exception("루트 스토리지 정보가 없습니다.",106);

		$root_path = $root_storage[path];

		$where_array = array();

		if( !empty($category_id) ){
			array_push($where_array," p.category_id='$category_id' ");
		}

		if( !empty($path) ){
			array_push($where_array," p.path='$path' ");
		}

		if( !empty($where_array) ){
			$where = " where ".join(' and ', $where_array);
		}

		$lists = $db->queryAll(" select  concat( '$root_path', '/' ||  p.path  ) FULL_PATH , p.path,p.category_id from PATH_MAPPING p $where order by p.path ");

		return $lists;

	}

	function update($category_id =null, $path=null, $quota_usage = null){
		global $db;

		$where_array = array();
		if( !empty($category_id) ){
			array_push($where_array," category_id='$category_id' ");
		}
		if( !empty($path) ){
			array_push($where_array," path='$path' ");
		}
		if( !empty($where_array) ){
			$where = " where ".join(' and ', $where_array);
		}

		$query = "update PATH_MAPPING set usage='$quota_usage' ".$where;
		$r = $db->exec($query);

		return true;
	}
}