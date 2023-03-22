<?php
namespace ProximaCustom\core;

use Proxima\core\Request;
use Proxima\core\Logger;

use Proxima\core\LDAPManager;
use Proxima\core\SSH2;
use Proxima\core\SNFS;

class FolderAuthManager
{
    private $v_param = [];

    public static $is_test = true;

    public static $telnet_server_ip = '10.10.51.12';
    public static $telnet_user = 'ktv';//VOL1_TELNET_USER
    public static $telnet_pwd = 'admin12345';//VOL1_TELNET_PWD
    public static $vol1_prefix_path = '/Volumes/BACKUP/gemiso/Scratch';
    //public static $vol1_prefix_path = '/Volumes';

    public static $vol1_mid_path = 'gemiso/Scratch';

    public static $pathAuth = 755;

    public static $mdcUrl = 'http://10.10.50.11:81/sws/v2/quota/snquota?';
    public static $mdcsystemUrl =  'http://10.10.50.11:81/sws/v2/system/filesystem/info?';
    public static $storageFsname = 'BACKUP';

    public static $ldap_server_ip = '10.10.50.15';
    public static $ldap_domain = 'dc=n-ods1,dc=nps,dc=ktv,dc=go,dc=kr';
    public static $dir_user_name = 'diradmin';
    public static $dir_pwd = 'diradmin12345';

    public static $ldap_telnet_user = 'admin';//VOL1_TELNET_USER
    public static $ldap_telnet_pwd = 'admin12345';//VOL1_TELNET_PWD

    public static $ldap_default_group_id = '20';//VOL1_TELNET_PWD

    //public static $ldap_home_dir = '/Users';
    //public static $ldap_home_dir = '/Network/Servers/n-nas2.nps.ktv.go.kr/OD_HOME';
    public static $ldap_home_dir = '/Network/Servers/n-nas2.nps.ktv.go.kr/Volumes/OD_HOME';

    public static $logger = null;
    
	public function __construct($config = null){
        if(is_array($config)){
            foreach($config as $key => $val){
                self::${$key} = $val;
            }
        }
    }

    public static function test($folder = 'test')
    {
        self::$storageFsname = "BACKUP";
        self::$vol1_prefix_path = '/Volumes/BACKUP/gemiso/Scratch';
        self::$vol1_mid_path = 'gemiso/Scratch';
        self::$pathAuth = 775;

        $quotaVal = 2;

        $folderCode = "gemisot14";
        $folderName= "그룹_제미소14";
        $groupName = "group_".$folderCode;

        $userId = "gemisot16";
        $userNm = "제미소16";
        $userUnum = "300016";
        $userPwd = "123qwe!@#";


        // //그룹생성
        // $return = self::createGroupFromOD($groupName,$folderName);
        // echo print_r($return, true);

        // //폴더생성 권한 부여
        // $return = self::makeFolderSetAuthor($folderCode, $groupName );
        // echo print_r($return, true);


        // //쿼터 부여
        // $return = self::createQuota([
        //     'fsname'             => self::$storageFsname,
        //     'type'               => 'dir',
        //     'directory'          => self::$vol1_mid_path.'/'.$folderCode
        // ]);
        // echo print_r($return, true);
        // $return = self::updateQuota([
        //     'gracePeriod_unit'   => 'Weeks',
        //     'softLimit_unit'     => 'GB',
        //     'hardLimit_unit'     => 'GB',
        //     'fsname'             => self::$storageFsname,
        //     'softLimit'          => $quotaVal,
        //     'hardLimit'          => $quotaVal,
        //     'gracePeriod'        => '1',
        //     'type'               => 'dir',
        //     'directory'          => self::$vol1_mid_path.'/'.$folderCode
        // ]);
        // echo print_r($return, true);
        
        //계정 생성
        $userInfo = self::createUserFromOD($userId,$userNm,$userUnum,$userPwd);
        //echo print_r($userInfo, true);

        //그룹 계정 추가

        // $return = self::groupAddUserFromOD($groupName, [$userInfo]);
        // echo print_r($return, true);

        // //폴더생성
        // $return = self::makeFolder($folderCode);
        // echo print_r($return, true);
        
        
        // $return = self::deleteGroupFromOD('group_gemiso_testfolder2');
        // echo print_r($return, true);
        

        // $return = self::deleteQuota([
        //     'fsname'    => self::$storageFsname,
        //     'type'      => 'dir',
        //     'directory' => self::$vol1_mid_path.'/'.$folder
        // ]);


        //패스워드 변경
        // $return = self::changePasswordFromOD($userId, '12345');
        // echo print_r($return, true);

        //사용자 확인
        //$userId = 'hsj6478';
        $v_param = array(
            'ldap_type'        => 'OD',
            'ldap_server_ip'    => self::$ldap_server_ip,
            'telnet_server_ip'    => self::$telnet_server_ip,
            'ldap_domain'        => self::$ldap_domain,
            'dir_user_name'        => self::$dir_user_name,
            'dir_pwd'            =>  self::$dir_pwd,
            'telnet_user'        => self::$ldap_telnet_user,
            'telnet_pwd'        => self::$ldap_telnet_pwd
        );
        $ldap = new LDAPManager($v_param);
        
        //등록한 사용자 확인
        $ldap->set_param('ldap_rdn'        ,    'cn=users');
        $ldap->set_param('ladp_action'    ,    'search' );
        $ldap->set_param('search_action',    'USER_ALL');
        $ldap->set_param('ldap_filter'    ,    '( uid='.$userId.' )');        

        $return = $ldap->fn_search($v_param);

        //echo print_r($return, true);

        return $return;
    }

    /**
     * makeFolder function 
     * ssh 접속 mkdir 명령어로 폴더생성
     * @param [type] $folder 생성할 물리폴더명
     * @return true/false
     */
    public static function makeFolder($folder){
        
        if( empty($folder) ){
            return false;
        }
        //mkdir folder
        $ssh = new SSH2(self::$telnet_server_ip, 22); 
        //print_r($ssh);
        $ssh->authPassword( self::$telnet_user, self::$telnet_pwd );
        $ssh->openShell( 'xterm' , 1);
        
		$return = $ssh->writeShell( 'sudo su' ,2);	
        $return = $ssh->writeShell( self::$telnet_pwd ,2);   

        $defualt_command = 'mkdir '.self::$vol1_prefix_path.'/'.$folder;
        $return = $ssh->writeShell( $defualt_command ,1); 
        $ssh->isError($return , $defualt_command );
        return true;
    }

    /**
     * setAuthor function
     *
     * ssh 접속 소유권 변경
     * 권한 부여
     * @param string $user
     * @param string $group
     * @param [type] $folder
     * @return void
     */
    public static function makeFolderSetAuthor($folder, $group = 'admin' , $user = 'admin' ){
        if( empty($folder) ){
            return false;
        }
        //var_dump('makeFolderSetAuthor');
        $target_path = self::$vol1_prefix_path.'/'.$folder;
		$ssh = new SSH2(self::$telnet_server_ip, 22); 
        $ssh->authPassword( self::$telnet_user, self::$telnet_pwd );
        $ssh->openShell( 'xterm' , 1);
        //var_dump($target_path);
        //var_dump(self::$telnet_user);
       // var_dump(self::$telnet_pwd);
       // var_dump('sudo su');
        $defualt_command = 'sudo su';
        $return = $ssh->writeShell( $defualt_command , 2);	
        $ssh->isError($return , $defualt_command );
        //var_dump($return);
        $return = $ssh->writeShell( self::$telnet_pwd ,2);
        //$ssh->isError($return , $defualt_command );
       //var_dump('sudo su');
        //var_dump($return);
        
        $defualt_command = 'mkdir '.$target_path;
        $return = $ssh->writeShell( $defualt_command ,1); 
        $ssh->isError($return , $defualt_command );
        //var_dump($return);
        $defualt_command = "chmod ".self::$pathAuth." ".$target_path;
		$return = $ssh->writeShell( $defualt_command ,1);
        $ssh->isError($return , $defualt_command );        
	
        $defualt_command = 'chown '.$user.':'.$group.' '.$target_path;
		$return = $ssh->writeShell( $defualt_command ,1);
		$ssh->isError($return , $defualt_command );
		return true;
    }

    /**
     * MDC 쿼터 업데이트
     *
     * @param [type] $data
     * @return void
     */
    public static function updateQuota($data){ 
        $param = array(
            'mdc_url' => self::$mdcUrl,
            'mdcsystem_url' => self::$mdcsystemUrl,
            'isLogFile' => false,
            'fsname' => self::$storageFsname
        );
        $snfs = new SNFS($param);
        $return = $snfs->setQuotasV2($data);

        return $return;
    }

    public static function createQuota($data){

        // 'Minutes' => 'm',
        // 'Weeks' => 'w',
        // 'Hours' => 'h',
        // 'Days' => 'd',
        // 'Years' => 'y'  
        // 'KB' => 'K',
        // 'MB' => 'M',
        // 'GB' => 'G',
        // 'TB' => 'T',
        // 'PB' => 'P'
        // $info = [];
        // $info['gracePeriod_unit']   = $data['gracePeriod_unit'];
        // $info['softLimit_unit']     = $data['softLimit_unit'];
        // $info['hardLimit_unit']     = $data['hardLimit_unit'];
        // $info['fsname']             = $data['fsname'];
        // $info['softLimit']          = $data['softLimit'];
        // $info['hardLimit']          = $data['hardLimit'];
        // $info['gracePeriod']        = $data['gracePeriod'];
        // $info['type']               = $data['type'];
        // $info['id']                 = $data['id'];
        // $info['path']               = $data['path'];
        $param = array(
            'mdc_url' => self::$mdcUrl,
            'mdcsystem_url' => self::$mdcsystemUrl,
            'isLogFile' => false,
            'fsname' => self::$storageFsname
        );
        $snfs = new SNFS($param);
        $return = $snfs->createFolderQuotas($data);
        return $return;
    }

    public static function deleteQuota($data){
        // ['directory']
        // ['id']
        // ['type']
        $param = array(
            'mdc_url' => self::$mdcUrl,
            'mdcsystem_url' => self::$mdcsystemUrl,
            'isLogFile' => false,
            'fsname' => self::$storageFsname
        );
        $snfs = new SNFS($param);
        $return = $snfs->delQuotasV2($data);

        return $return;
    }

    public static function getQuotas($qType = 'directoryQuotas'){
        $param = array(
            'mdc_url' => self::$mdcUrl,
            'mdcsystem_url' => self::$mdcsystemUrl,
            'isLogFile' => false,
            'fsname' => self::$storageFsname
        );
        $data = array(
			'fsname' => self::$storageFsname,
			'action' => 'listall',
			'format' => 'json'
		);
        $snfs = new SNFS($param);
        $list = $snfs->getList($data);

        $return = $list[$qType];
        
        return $return;
    }

    public static function convNumber($val){
		
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

	public static function getPercent($c_size, $max_size){	
		if( $c_size == 0 || $max_size == 0 ){
			$percent = 0;
		}else{		
			$percent = round( ( $c_size / $max_size) * 100 );
		}

		return $percent;
	}

	
	public static function convSizeNum($value , $meta = null ){

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
		}
		else if($unit == "P"){
				return $num * 1024 * 1024 * 1024 * 1024 * 1024;
		}else{
			return 0;
		}
	}



    /**
     * ldap 그룹 생성 function
     *
     * @param [type] $groupName
     * @param [type] $groupNameDesc
     * @return void
     */
    public static function createGroupFromOD($groupName, $groupNameDesc = null ){
  
        $v_param = array(
            'ldap_type'        => 'OD',
            'ldap_server_ip'    => self::$ldap_server_ip,
            'telnet_server_ip'    => self::$telnet_server_ip,
            'ldap_domain'        => self::$ldap_domain,
            'dir_user_name'        => self::$dir_user_name,
            'dir_pwd'            =>  self::$dir_pwd,
            'telnet_user'        => self::$ldap_telnet_user,
            'telnet_pwd'        => self::$ldap_telnet_pwd
        );
        $ldap = new LDAPManager($v_param);
        //그룹 생성
        $v_param['group_nm'] = $groupName;
        $v_param['group_info']['cn'] = $groupName;
        $v_param['group_info']['apple-group-realname'] = $groupNameDesc;
        $v_param['group_info']['description'] = $groupNameDesc;
        $ldap->set_param('ldap_rdn'        ,    'cn=groups');
        $ldap->set_param('ladp_action'    ,    'group_add' );
        $ldap->set_param('search_action',    'GROUP_ALL');
        $ldap->set_param('ldap_filter'    ,    '(  cn='.$groupName.' )' );

        $return = $ldap->fn_run_ldap($v_param);
        if( isset($return) && $return['success'] == true ){
            return true;
        }else{
            return false;
        }
    }

    public static function deleteGroupFromOD($groupName){

        $v_param = array(
            'ldap_type'        => 'OD',
            'ldap_server_ip'    => self::$ldap_server_ip,
            'telnet_server_ip'    => self::$telnet_server_ip,
            'ldap_domain'        => self::$ldap_domain,
            'dir_user_name'        => self::$dir_user_name,
            'dir_pwd'            =>  self::$dir_pwd,
            'telnet_user'        => self::$ldap_telnet_user,
            'telnet_pwd'        => self::$ldap_telnet_pwd
        );
        $ldap = new LDAPManager($v_param);
        //그룹 생성
        $v_param['group_nm'] = $groupName;
        $ldap->set_param('ldap_rdn'        ,    'cn=groups');
        $ldap->set_param('ladp_action'    ,    'group_delete' );
        $ldap->set_param('ldap_filter'    ,    '(  cn='.$groupName.' )' );

        $return = $ldap->fn_run_ldap($v_param);
        if( isset($return) && $return['success'] == true ){
            return true;
        }else{
            return false;
        }
    }

    public static function createUserFromOD($user_id, $user_nm, $uidNumber, $password ){
        $v_param = array(
            'ldap_type'        => 'OD',
            'ldap_server_ip'    => self::$ldap_server_ip,
            'telnet_server_ip'    => self::$telnet_server_ip,
            'ldap_domain'        => self::$ldap_domain,
            'dir_user_name'        => self::$dir_user_name,
            'dir_pwd'            =>  self::$dir_pwd,
            'telnet_user'        => self::$ldap_telnet_user,
            'telnet_pwd'        => self::$ldap_telnet_pwd,
            'default_group_id' => self::$ldap_default_group_id,
            'home_dir' => self::$ldap_home_dir
        );
        $ldap = new LDAPManager($v_param);

        //$user_id = $ldap->getUserIdRen($user_id);

        $uidFix = 3;

        $v_param['user_nm']                        = $user_nm;
        $v_param['user_id']                        = $user_id;
        $v_param['user_pwd']                    = $password;
        $v_param['uidNumber']                    = $uidFix.str_pad($uidNumber, 3, "0", STR_PAD_LEFT);
        $v_param['cn']                            = $user_id;
        $v_param['user_info']['cn']                = $v_param['cn'];
        $v_param['user_info']['uid']            = $v_param['user_id'];
        $v_param['user_info']['sn']                = $v_param['cn'];
        $v_param['user_info']['uidNumber']        = $v_param['uidNumber'];
        $v_param['user_info']['userPassword']    = $v_param['user_pwd'];
        $v_param['user_info']['homedirectory']    = self::$ldap_home_dir.'/'.$user_id;
        //$v_param['user_info']['homeDirectory']    = '/Volumes/Home/'.$user_id;

        $ldap->set_param('ldap_rdn'        ,    'cn=users');
        $ldap->set_param('ladp_action'    ,    'user_add' );
        $ldap->set_param('search_action',    'USER_ALL');
        $ldap->set_param('ldap_filter'    ,    '( uid='.$user_id.' )');
        $return = $ldap->fn_run_ldap($v_param);

        //등록한 사용자 확인
        $ldap->set_param('ldap_rdn'        ,    'cn=users');
        $ldap->set_param('ladp_action'    ,    'search' );
        $ldap->set_param('search_action',    'USER_ALL');
        $ldap->set_param('ldap_filter'    ,    '( uid='.$user_id.' )');        

        $userInfo = $ldap->fn_search($v_param);

        if (!empty($userInfo)) {
            return $userInfo;
        }else{
            return false;
        }
    }

    public static function findUserFromOD($user_id){
        $v_param = array(
            'ldap_type'        => 'OD',
            'ldap_server_ip'    => self::$ldap_server_ip,
            'telnet_server_ip'    => self::$telnet_server_ip,
            'ldap_domain'        => self::$ldap_domain,
            'dir_user_name'        => self::$dir_user_name,
            'dir_pwd'            =>  self::$dir_pwd,
            'telnet_user'        => self::$ldap_telnet_user,
            'telnet_pwd'        => self::$ldap_telnet_pwd,
            'default_group_id' => self::$ldap_default_group_id,
            'home_dir' => self::$ldap_home_dir
        );
        $ldap = new LDAPManager($v_param);
        //등록한 사용자 확인
        $ldap->set_param('ldap_rdn'        ,    'cn=users');
        $ldap->set_param('ladp_action'    ,    'search' );
        $ldap->set_param('search_action',    'USER_ALL');
        $ldap->set_param('ldap_filter'    ,    '( uid='.$user_id.' )');        

        $userInfo = $ldap->fn_search($v_param);

        if (!empty($userInfo)) {
            return $userInfo;
        }else{
            return false;
        }
    }

    public function getUsersFromOD(){
        $v_param = array(
            'ldap_type'        => 'OD',
            'ldap_server_ip'    => self::$ldap_server_ip,
            'telnet_server_ip'    => self::$telnet_server_ip,
            'ldap_domain'        => self::$ldap_domain,
            'dir_user_name'        => self::$dir_user_name,
            'dir_pwd'            =>  self::$dir_pwd,
            'telnet_user'        => self::$ldap_telnet_user,
            'telnet_pwd'        => self::$ldap_telnet_pwd,
            'default_group_id' => self::$ldap_default_group_id,
            'home_dir' => self::$ldap_home_dir
        );
        //$user_id = 'diradmin';
        $ldap = new LDAPManager($v_param);
        //등록한 사용자 확인
        $ldap->set_param('ldap_rdn'        ,    'cn=users');
        $ldap->set_param('ladp_action'    ,    'search' );
        $ldap->set_param('search_action',    'USER_ALL');
        $ldap->set_param('ldap_filter'	,	'( cn=* )');
     
        $userInfo = $ldap->fn_search($v_param);
     
        if (!empty($userInfo)) {
            return $userInfo;
        }else{
            return false;
        }
    }
    
    public function getGroupsFromOD(){
        $v_param = array(
            'ldap_type'        => 'OD',
            'ldap_server_ip'    => self::$ldap_server_ip,
            'telnet_server_ip'    => self::$telnet_server_ip,
            'ldap_domain'        => self::$ldap_domain,
            'dir_user_name'        => self::$dir_user_name,
            'dir_pwd'            =>  self::$dir_pwd,
            'telnet_user'        => self::$ldap_telnet_user,
            'telnet_pwd'        => self::$ldap_telnet_pwd,
            'default_group_id' => self::$ldap_default_group_id,
            'home_dir' => self::$ldap_home_dir
        );
        $ldap = new LDAPManager($v_param);
        //등록한 사용자 확인
        $ldap->set_param('ldap_rdn'        ,    'cn=groups');
        $ldap->set_param('ladp_action'    ,    'search' );
        $ldap->set_param('search_action',    'GROUP_ALL');
        $ldap->set_param('ldap_filter'	,	'( cn=* )');

        $results = $ldap->fn_search($v_param);

        if (!empty($results)) {
            return $results;
        }else{
            return false;
        }
    }

    public static function deleteUserFromOD($user_id){

        $v_param = array(
            'ldap_type'        => 'OD',
            'ldap_server_ip'    => self::$ldap_server_ip,
            'telnet_server_ip'    => self::$telnet_server_ip,
            'ldap_domain'        => self::$ldap_domain,
            'dir_user_name'        => self::$dir_user_name,
            'dir_pwd'            =>  self::$dir_pwd,
            'telnet_user'        => self::$ldap_telnet_user,
            'telnet_pwd'        => self::$ldap_telnet_pwd
        );
        $ldap = new LDAPManager($v_param);
   
        $v_param['user_id'] = $user_id;
        $v_param['uid'] = $user_id;
        $v_param['user_info']['uid'] = $user_id;
        $ldap->set_param('ldap_rdn'        ,    'cn=users');
        $ldap->set_param('ladp_action'    ,    'user_delete' );
        $ldap->set_param('ldap_filter'    ,    '( uid='.$user_id.' )' );

        $return = $ldap->fn_run_ldap($v_param);
        if( isset($return) && $return['success'] == true ){
            return true;
        }else{
            return false;
        }
    }


    public static function changePasswordFromOD($user_id, $user_pwd){
        $v_param = array(
            'ldap_type'        => 'OD',
            'ldap_server_ip'    => self::$ldap_server_ip,
            'telnet_server_ip'    => self::$telnet_server_ip,
            'ldap_domain'        => self::$ldap_domain,
            'dir_user_name'        => self::$dir_user_name,
            'dir_pwd'            =>  self::$dir_pwd,
            'telnet_user'        => self::$ldap_telnet_user,
            'telnet_pwd'        => self::$ldap_telnet_pwd
        );
        $ldap = new LDAPManager($v_param);
        $return  = $ldap->changeSSHPassword($user_id, $user_pwd);
        return $return;
    }

    public static function groupAddUserFromOD($groupName, $user_infos ){
        self::_log()->debug('groupAddUserFromOD');    
        self::_log()->debug(print_r($user_infos,true));
        $user_ids = [];
        $user_apple_ids = [];
        foreach($user_infos as $user_info){
            $user_ids [] = $user_info[0]['uid'];
            $user_apple_ids [] = $user_info[0]['apple-generateduid'];
        }

        $v_param = array(
            'ldap_type'        => 'OD',
            'ldap_server_ip'    => self::$ldap_server_ip,
            'telnet_server_ip'    => self::$telnet_server_ip,
            'ldap_domain'        => self::$ldap_domain,
            'dir_user_name'        => self::$dir_user_name,
            'dir_pwd'            =>  self::$dir_pwd,
            'telnet_user'        => self::$ldap_telnet_user,
            'telnet_pwd'        => self::$ldap_telnet_pwd
        );
        $ldap = new LDAPManager($v_param);

        $v_param['group_nm'] = $groupName;
        $v_param['group_info']['cn'] = $groupName;

        $ldap->set_param('ldap_rdn'        ,    'cn=groups');
        $ldap->set_param('ladp_action'    ,    'group_modify_member' );
        $ldap->set_param('search_action',    'GROUP_ALL');
        $ldap->set_param('ldap_filter'    ,    '(  cn='.$groupName.' )' );
        $v_param['group_member_info']['memberuid'] = $user_ids;
        $v_param['group_member_info']['apple-group-memberguid'] = $user_apple_ids;

        
        self::_log()->debug('bf list');    
        self::_log()->debug(print_r($v_param['group_member_info'],true));
        
        //대상그룹의 이전 사용자 조회
        $group_info = $ldap->fn_search($v_param);

        if( empty($group_info) ) return false;

        $bf_member_list         = $group_info[0]['member_list'];
        $bf_apple_member_list   = $group_info[0]['apple_member_list'];

        if( !empty( $bf_member_list ) ){          
            foreach ($bf_member_list as $before_member_id) {
                array_push($v_param['group_member_info']['memberuid'], $before_member_id);
            }
        }
        if( !empty( $bf_apple_member_list ) ){         
            foreach($bf_apple_member_list as $before_member_id){                
                array_push($v_param['group_member_info']['apple-group-memberguid'], $before_member_id );
            }
        }

        
        $appleGroupMemberguids = array_unique($v_param['group_member_info']['apple-group-memberguid']);
		$uniqueAppleGroupMemberguids = [];
		foreach($appleGroupMemberguids as $appleGroupMemberguids)
		{
			$uniqueAppleGroupMemberguids [] = $appleGroupMemberguids;
		}

		$memberuids = array_unique($v_param['group_member_info']['memberuid']);
		$uniqueMemberuids = [];
		foreach($memberuids as $memberuid)
		{
			$uniqueMemberuids [] = $memberuid;
		}
        $v_param['group_member_info']['apple-group-memberguid'] = $uniqueAppleGroupMemberguids;
        $v_param['group_member_info']['memberuid'] = $uniqueMemberuids;

        self::_log()->debug('after list');
        self::_log()->debug(print_r($v_param['group_member_info'],true));
        $return = $ldap->fn_run_ldap($v_param);
        if( isset($return) && $return['success'] == true ){
            return true;
        }else{
            return false;
        }
    }

    
    public static function groupDelUserFromOD($groupName, $del_user_infos ){

        self::_log()->debug('groupDelUserFromOD');    
        self::_log()->debug(print_r($del_user_infos,true));
        $user_ids = [];
        $user_apple_ids = [];
        foreach($del_user_infos as $user_info){
            $user_ids [] = $user_info[0]['uid'];
            $user_apple_ids [] = $user_info[0]['apple-generateduid'];
        }

        $v_param = array(
            'ldap_type'        => 'OD',
            'ldap_server_ip'    => self::$ldap_server_ip,
            'telnet_server_ip'    => self::$telnet_server_ip,
            'ldap_domain'        => self::$ldap_domain,
            'dir_user_name'        => self::$dir_user_name,
            'dir_pwd'            =>  self::$dir_pwd,
            'telnet_user'        => self::$ldap_telnet_user,
            'telnet_pwd'        => self::$ldap_telnet_pwd
        );
        $ldap = new LDAPManager($v_param);

        $v_param['group_nm'] = $groupName;
        $v_param['group_info']['cn'] = $groupName;

        $ldap->set_param('ldap_rdn'        ,    'cn=groups');
        $ldap->set_param('ladp_action'    ,    'group_modify_member' );
        $ldap->set_param('search_action',    'GROUP_ALL');
        $ldap->set_param('ldap_filter'    ,    '(  cn='.$groupName.' )' );
        $v_param['group_member_info']['memberuid'] = $user_ids;
        $v_param['group_member_info']['apple-group-memberguid'] = $user_apple_ids;
        
        //대상그룹의 이전 사용자 조회
        $group_info = $ldap->fn_search($v_param);

        if( empty($group_info) ) return false;

        $bf_member_list         = $group_info[0]['member_list'];
        $bf_apple_member_list   = $group_info[0]['apple_member_list'];

        self::_log()->debug('before list');
        self::_log()->debug(print_r($bf_member_list,true));
        self::_log()->debug(print_r($bf_apple_member_list,true));
  
        if( !empty( $bf_member_list ) ){          
            $af_member_list = array_diff($bf_member_list, $user_ids ); 
            $v_param['group_member_info']['memberuid'] = array_values($af_member_list);
        }
        if( !empty( $bf_apple_member_list ) ){
            $af_apple_member_list = array_diff($bf_apple_member_list, $user_apple_ids );
            $v_param['group_member_info']['apple-group-memberguid'] = array_values($af_apple_member_list);            
        }

        $appleGroupMemberguids = array_unique($v_param['group_member_info']['apple-group-memberguid']);
		$uniqueAppleGroupMemberguids = [];
		foreach($appleGroupMemberguids as $appleGroupMemberguids)
		{
			$uniqueAppleGroupMemberguids [] = $appleGroupMemberguids;
		}

		$memberuids = array_unique($v_param['group_member_info']['memberuid']);
		$uniqueMemberuids = [];
		foreach($memberuids as $memberuid)
		{
			$uniqueMemberuids [] = $memberuid;
		}
        $v_param['group_member_info']['apple-group-memberguid'] = $uniqueAppleGroupMemberguids;
        $v_param['group_member_info']['memberuid'] = $uniqueMemberuids;
        
        self::_log()->debug('after list');
        self::_log()->debug(print_r($v_param['group_member_info']['apple-group-memberguid'],true));
        self::_log()->debug(print_r($v_param['group_member_info']['memberuid'],true));
        $return = $ldap->fn_run_ldap($v_param);
        if( isset($return) && $return['success'] == true ){
            return true;
        }else{
            return false;
        }
    }

    public static function groupMapUserFromOD( $groupName, $user_infos , $del_user_infos ){
        self::_log()->debug('groupMapUserFromOD');    
        self::_log()->debug(print_r($user_infos,true));
        self::_log()->debug(print_r($del_user_infos,true));
        
        $user_ids = [];
        $user_apple_ids = [];
        
        $del_user_ids = [];
        $del_user_apple_ids = [];
        
        $new_member_list = [];
        $new_apple_member_list = [];   

        if( !empty($user_infos) ){
            foreach ($user_infos as $user_info) {
                $user_ids [] = $user_info[0]['uid'];
                $user_apple_ids [] = $user_info[0]['apple-generateduid'];
            }
        }

        if (!empty($del_user_infos)) {
            foreach ($del_user_infos as $user_info) {
                $del_user_ids [] = $user_info[0]['uid'];
                $del_user_apple_ids [] = $user_info[0]['apple-generateduid'];
            }
        }

        $v_param = array(
            'ldap_type'        => 'OD',
            'ldap_server_ip'    => self::$ldap_server_ip,
            'telnet_server_ip'    => self::$telnet_server_ip,
            'ldap_domain'        => self::$ldap_domain,
            'dir_user_name'        => self::$dir_user_name,
            'dir_pwd'            =>  self::$dir_pwd,
            'telnet_user'        => self::$ldap_telnet_user,
            'telnet_pwd'        => self::$ldap_telnet_pwd
        );
        $ldap = new LDAPManager($v_param);

        $v_param['group_nm'] = $groupName;
        $v_param['group_info']['cn'] = $groupName;

        $ldap->set_param('ldap_rdn'        ,    'cn=groups');
        $ldap->set_param('ladp_action'    ,    'group_modify_member' );
        $ldap->set_param('search_action',    'GROUP_ALL');
        $ldap->set_param('ldap_filter'    ,    '(  cn='.$groupName.' )' );

        //대상그룹의 이전 사용자 조회
        $group_info = $ldap->fn_search($v_param);

        if( empty($group_info) ) return false;

        $bf_member_list         = $group_info[0]['member_list'];
        $bf_apple_member_list   = $group_info[0]['apple_member_list'];

        
        self::_log()->debug('bf list');
        self::_log()->debug(print_r($v_param['bf_member_list'],true));
        self::_log()->debug(print_r($v_param['bf_apple_member_list'],true));   

        //이전 목록
        if( !empty( $bf_member_list ) ){          
            foreach ($bf_member_list as $before_member_id) {
                $isExcept = false;               
                if( !empty($del_user_ids) ){
                    foreach ($del_user_ids as $del_user_id) {
                        if($before_member_id == $del_user_id){
                            $isExcept = true;
                        }
                    }
                }
                //삭제대상은 제외
                if(!$isExcept){
                    array_push($new_member_list, $before_member_id);
                }
            }
        }
        if( !empty( $bf_apple_member_list ) ){         
            foreach($bf_apple_member_list as $before_member_id){
                $isExcept = false;               
                if( !empty($del_user_apple_ids) ){
                    foreach ($del_user_apple_ids as $del_user_apple_id) {
                        if($before_member_id == $del_user_apple_id){
                            $isExcept = true;
                        }
                    }
                }                
                //삭제대상은 제외
                if(!$isExcept){
                    array_push($new_apple_member_list, $before_member_id);
                }
            }
        }

        //추가분
        if( !empty($user_ids) ){
            foreach($user_ids as $user_id){                
                array_push($new_member_list, $user_id );
            }
        }
        if (!empty($user_apple_ids)) {
            foreach ($user_apple_ids as $user_apple_id) {
                array_push($new_apple_member_list, $user_apple_id);
            }
        }

        
        $appleGroupMemberguids = array_unique($new_apple_member_list);
		$uniqueAppleGroupMemberguids = [];
		foreach($appleGroupMemberguids as $appleGroupMemberguids)
		{
			$uniqueAppleGroupMemberguids [] = $appleGroupMemberguids;
		}

		$memberuids = array_unique($new_member_list);
		$uniqueMemberuids = [];
		foreach($memberuids as $memberuid)
		{
			$uniqueMemberuids [] = $memberuid;
		}
        $v_param['group_member_info']['apple-group-memberguid'] = $uniqueAppleGroupMemberguids;
        $v_param['group_member_info']['memberuid'] = $uniqueMemberuids;

        self::_log()->debug('after list');
        self::_log()->debug(print_r($v_param['group_member_info'],true));
        $return = $ldap->fn_run_ldap($v_param);
        if( isset($return) && $return['success'] == true ){
            return true;
        }else{
            return false;
        }
    }

    public static function _log(){
        if( self::$logger == null ){
            self::$logger = new Logger(basename(__FILE__,'.php'));
        }
        return self::$logger;
    }
}
