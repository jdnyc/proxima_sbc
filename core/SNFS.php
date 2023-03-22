<?php
namespace Proxima\core;


class SNFS
{
	//const PREFIX = 'N';

    private $v_param = array();
    
    public static $gracePeriodMap = array(
        'Minutes' => 'm',
        'Weeks' => 'w',
        'Hours' => 'h',
        'Days' => 'd',
        'Years' => 'y'
    );

    public static $limitMap = array(
        'KB' => 'K',
        'MB' => 'M',
        'GB' => 'G',
        'TB' => 'T',
        'PB' => 'P'
    );

	public function __construct($v_param){

		//생성자
		//운영

		//$v_param['isLogFile'] = false;
		//$v_param['isLogEcho'] = false;
		$this->v_param = $v_param;
		//$this->_log($v_param);
		
	}

	function set_param($key, $value){
		$this->v_param[$key] = $value;
    }
    
    	
	function createFolder($directory, $softlimit,$hardlimit,$graceperiod ='1w'){

		$data = array(
			'fsname' => $this->v_param['fsname'],
			'action' => 'create',
			'directory' => $directory,
			'softlimit' => $softlimit.$this->v_param['quota_byte'],
			'hardlimit' => $hardlimit.$this->v_param['quota_byte'],
			'graceperiod' => $graceperiod,
			'format' => 'json'
		);
		$return = $this->getList($data);

		if($return['returnCode'] == 0){
			 $this->setQuotas($directory, $softlimit,$hardlimit,$graceperiod);
		}		

		return $return;
	}
    
    /**
     * createFolderQuotas function
     *
     * 폴더 쿼터 생성시 처음 create로 생성 후 set 실행해야함
     * @param [type] $info
     * @return void
     */
	function createFolderQuotas($info){

		$data = array(
            'action'		=> 'create',		
            'fsname'		=> $info['fsname'],
            'directory'     => $info['directory'],
			'format'        => 'json'
		);
		$return = $this->getList($data);

		if($return['returnCode'] == 0){
            return true;
		}else{
            return false;
        }
	}

	function setQuotas($directory, $softlimit,$hardlimit,$graceperiod ='1w'){
		$data = array(
			'fsname' => $this->v_param['fsname'],
			'action' => 'set',
			'directory' => $directory,
			'softlimit' => $softlimit.$this->v_param['quota_byte'],
			'hardlimit' => $hardlimit.$this->v_param['quota_byte'],
			'graceperiod' => $graceperiod,
			'format' => 'json'
		);
		$return = $this->getList($data);
		return $return;
	}

	function setQuotasV2($info){

		$gracePeriod_unit = self::$gracePeriodMap[$info['gracePeriod_unit']];
		if( empty($gracePeriod_unit) ){
			$gracePeriod_unit = 'w';
        }        
		$softlimit_unit = self::$limitMap[$info['softLimit_unit']];
		if( empty($softlimit_unit) ){
			$softlimit_unit = 'T';
		}
		$hardLimit_unit = self::$limitMap[$info['hardLimit_unit']];
		if( empty($hardLimit_unit) ){
			$hardLimit_unit = 'T';
		}
		$data = array(
			'action'		=> 'set',		
			'fsname'		=> $info['fsname'],					
			'softlimit'		=> $info['softLimit'].$softlimit_unit,
			'hardlimit'		=> $info['hardLimit'].$hardLimit_unit,
			'graceperiod'	=> $info['gracePeriod'].$gracePeriod_unit,			
			'format'		=> 'json'
		);

		if( $info['type'] == 'group' ){
			$groupname		= "G:".$info['id'];
			$data['groupname']	= $groupname;			
		}else if( $info['type'] == 'user'  ){
			$user			= "U:".$info['id'];
			$data['user']	= $user;
		}else{
            $data['directory']	= $info['directory'];
        }
		
		$return = $this->getList($data);
		return $return;
    }
    
    function delQuotasV2($info)	{
		$data = array(
			'fsname' => $this->v_param['fsname'],
			'action' => 'delete',
			'format' => 'json'
        );
        if( $info['type'] == 'group' ){
			$groupname		= "G:".$info['id'];
			$data['groupname']	= $groupname;			
		}else if( $info['type'] == 'user'  ){
			$user			= "U:".$info['id'];
			$data['user']	= $user;
		}else{
            $data['directory']	= $info['directory'];
        }
		$return = $this->getList($data);
		return $return;

	}

	function delQuotas($directory)	{
		$data = array(
			'fsname' => $this->v_param['fsname'],
			'action' => 'delete',
			'directory' => $directory,
			'format' => 'json'
		);
		$return = $this->getList($data);
		return $return;

	}

	function convNumber($val){
		
		if(is_numeric($val)){
			return $val;
		}

		$char = substr($val , -1);
		$num = substr($val ,0, -1);

		if($char == 'P'){

			$rtn = $num * 1024 * 1024 * 1024 * 1024 * 1024 ;

		}else if($char == 'T'){

			$rtn = $num * 1024 * 1024 * 1024 * 1024 ;

		}else if($char == 'G'){
			$rtn = $num * 1024 * 1024 * 1024  ;
		}else if($char == 'M'){
			$rtn = $num * 1024 * 1024   ;
		}else if($char == 'K'){
			$rtn = $num * 1024    ;
		}else{
			$rtn = 0;
		}

		return $rtn ;
	}

	function getListAll( ){
		$data = array(
			'fsname' => $this->v_param['fsname'],
			'action' => 'listall',
			'format' => 'json'
		);
		$lists = $this->getList($data);
		
        $return = array();
        
        if( !empty($lists['directoryQuotas']) ){
            foreach($lists['directoryQuotas'] as $row ){

                if($row['type'] == 'dir'){
                    array_push($return, $row);
                }
            }
        }

		return $return;
	}

	function getListAt($directory){
		$data = array(
			'fsname' => $this->v_param['fsname'],
			'action' => 'list',
			'directory' => $directory,
			'format' => 'json'
		);
		$lists = $this->getList($data, false);
		
		$return = array();

		if($lists['returnCode']==0){
            if( !empty($lists['directoryQuotas']) ){
                foreach($lists['directoryQuotas'] as $row ){

                    if($row['type'] == 'dir'){
                        array_push($return, $row);
                    }
                }
            }
		}

		return $return;
	}


	function getList($data , $iserror = true){		
		
		$options = array(
			'verify' => false
		);
		
		$query = http_build_query($data);
		$request_url  = $this->v_param['mdc_url'].$query;
		$this->_log($request_url);

		$response = @\Requests::get($request_url , array('Accept' => 'application/json'), $options );
		
		$this->_log($response);

		$response_body = json_decode($response->body ,true);

		if(!$response_body){
			throw new \Exception('SNFS IF Error');
		}

		if($response_body['returnCode'] != 0){

            if( strstr($response_body['errorText'] ,"Quotas are disabled on file system")){
                //스킵
                return array();
            }else{
                if($iserror){
                    throw new \Exception('SNFS IF Error');
                }
            }
		}
		
		return $response_body;
	}

	function getFilesystemInfo($filesystem){
		$data = array(
			'filesystem' => $filesystem,			
			'format' => 'json'
		);
		$options = array(
			'verify' => false
		);
		
		$query = http_build_query($data);
		
		$request_url  = $this->v_param['mdcsystem_url'].$query;
		
		$this->_log($request_url);

		$response = @\Requests::get($request_url , array('Accept' => 'application/json'), $options );
		
		$this->_log($response);

		$response_body = json_decode($response->body ,true);
		if(!$response_body ){			
			throw new \Exception('SNFS IF Error');
		}
		return $response_body;
	}

	
	function getNameId($value){
		$name_array = explode(':',$value);
		if( empty($name_array) ){
			return $value;
		}

		if( empty($name_array[1]) ){
			return $value;
		}else{            
		    $uid = $name_array[1];
        }	

		return $uid;
	}

	function getPercent($c_size, $max_size){	
		if( $c_size == 0 || $max_size == 0 ){
			$percent = 0;
		}else{		
			$percent = round( ( $c_size / $max_size) * 100 );
		}

		return $percent;
	}

	
	function convSizeNum($value , $meta = null ){

		if( !empty($meta) ){
			if($meta == "K"){		
				return round($value / 1024 , 2)." KB";
			}
			else if( $meta == "M" ){
				return round($value / 1024 / 1024 , 2)." MB";
			}
			else if($meta == "G"){
					return round($value / 1024 / 1024 / 1024, 2)." GB";
			}
			else if($meta == "T"){
					return round($value / 1024 / 1024 / 1024 / 1024, 2)." TB";
			}
			else if($meta == "P"){
					return round($value / 1024 / 1024 / 1024 / 1024/ 1024, 2)." PB";
			}else{
				return "0 KB";
			}
		}

		if($value == 0){
			return 0;
		}
		$num = substr($value , 0, -1);
		
		$unit = substr($value, -1);
		
		if( $num == 0 ){
			return 0;
		}

		if($unit == "K"){
			return $num * 1024 ;
		}
		else if($unit == "M"){
			return $num * 1024 * 1024 ;
		}
		else if($unit == "G"){
				return $num * 1024 * 1024 * 1024;
		}
		else if($unit == "T"){
				return $num * 1024 * 1024 * 1024 * 1024;
		}else{
			return 0;
		}
	}

		
	function setLimit($start = 0, $limit = 50, $list){
		
		$return = array();

		$total = count($list);
		$max = $limit + $start;

		foreach( $list as $key => $row)
		{
			if( $key >= $start && $key <= $max ){
				array_push($return , $row);
			}
		}

		return $return;
	}
	
	function _log($log){
		// if( $this->v_param['isLogFile'] == true ){
		// 	file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/'.basename(__FILE__).date('Ymd').'.log', print_r($log, true)."\n", FILE_APPEND);
		// }
		// if( isset($this->v_param['isLogEcho'])  ){
		// 	echo print_r($log, true);
		// 	echo '<br/>';
		// }
	}
}
?>