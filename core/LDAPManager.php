<?php
namespace Proxima\core;

use Proxima\core\SSH2 as SSH2;

use Proxima\core\Logger;

/*
	CN (Common Name) : KilDong Hong, SaRang Lee 와 같은 일반적인 이름들
	SN (SirName) : 우리나라 성에 해당한다. Lee, Hong ..
	OU (Organiztion Unit) : 그룹에 해당. 조직 단위.
	DC (Domain Component) : 도메인에 대한 요소 ex ) tech.example.com dc 는 example.com 혹은 tech.example.com 모두 해당
	DN (Distinguished Name) : 위의 엔트리 및 기타 여러가지 엔트리들이 모여 특정한 한 사용자(혹은 물체)를 구분할 수 있는 고유의 이름.
*/
//Change this to the IP address of the LDAP server
/*
$v_param['ldap_server_ip']	= '10.160.77.210';
$v_param['ldap_domain']		= 'DC=cha, DC=ddmc';
$v_param['ldap_domain1']	= 'cha.ddmc';
$v_param['ldap_ou']			= 'OU=ChannelAGroup';

//Change this to the id, domain of the LDAP server
$v_param['ldap_rdn']		= 'CN=Administrator, CN=users, '.$v_ldap_domain;

//Change this to the pwd of the LDAP server
$v_param['ldap_pwd']		= 'chanps!Q@W#E';
*/

//Change this to the id, domain of the LDAP server
//$v_param['ldap_rdn']		= 'CN=chaadmin, CN=users, '.$v_ldap_domain;
//$v_param['ldap_rdn']		= 'CN=users, '.$v_ldap_domain;


//$v_param['ldap_rdn']		= 'CN=users';
//$v_param['ldap_rdn']		= 'CN=groups';


//Change this to the pwd of the LDAP server
//$v_param['ldap_pwd']		= '1234';

/* 작업구분	[ladp_action : 작업 구분]
 * search			: 검색 조건별 검색기능 - 내부에서만 기능 사용함. 외부에서 사용은 테스트 안됨
 * search_cnt		: 검색 조건별 데이터 카운트 조회 - 내부에서만 기능 사용함. 외부에서 사용은 테스트 안됨
 * group_add		: 그룹 추가기능[group_nm : 그룹명]
 * group_delete		: 그룹 삭제기능[group_nm : 그룹명]
 * group_modify		: 그룹 변경기능[group_nm : 그룹명, group_nm_new : 그룹 변경 시 변경할 그룹명]
 * user_add			: 사용자 추가기능[id : 사용자 ID, pwd : 비밀번호, user_nm : 사용자명, phone : 전화번호, email : 이메일, dept_nm : 부서명, use_yn : 사용여부, user_group_nm_new : 그룹명]
 * user_delete		: 사용자 삭제기능[id : 사용자 ID]
 * user_modify		: 사용자 변경기능[id : 사용자 ID, user_nm : 사용자명, phone : 전화번호, email : 이메일, dept_nm : 부서명, use_yn : 사용여부, user_group_nm_old : 기존 그룹명, user_group_nm_new : 새로운 그룹명]
 * user_modify_pwd	: 사용자 비밀번호 변경기능[id : 사용자 ID, pwd : 비밀번호]
 * 2015.03.17 g.c.Shin
 */

/* 조회구분 [search_action : 검색 시 사용되는 검색 구분]
 * USER_ALL : 사용자 전체목록 조회 : 다건 추출
 * USER_ID : 사용자 ID를 이용한 사용자 정보 조회 : 1건 추출
 * USER_GROUP : 사용자 ID를 이용한 소속그룹 목록 조회 : 다건 추출
 * USER_NM : 사용자 이름을 이용한 사용자 목록 조회 : 다건 추출
 * GROUP_ALL : 그룹 전체목록 조회 : 다건 추출
 * GROUP_NM : 그룹 이름으로 그룹 조회 : 1건 추출
 * GROUP_MEMBER : 그룹 이름을 이용한 소속사용자 목록 조회 : 다건 추출
 */


class LDAPManager
{
	private $v_param = array();

	public function __construct($v_param){

		//생성자
		//$v_param['ldap_server_ip']	= '10.11.10.23';
		//$v_param['ldap_domain']		= 'DC=ods1, DC=nps, DC=ebs, DC=co, DC=kr';
		//$v_param['ldap_type']		= 'OD';

        //		$v_param['ldap_server_ip']	= '10.11.8.21';
        //		$v_param['ldap_domain']		= 'DC=ods, DC=odstest, DC=com';
        //		$v_param['dir_user'] = 'uid=diradmin, cn=users, '.$v_param['ldap_domain'];
        //		$v_param['dir_pwd'] = '1234';

        //		$v_param['telnet_user'] = 'admin';
        //		$v_param['telnet_pwd'] = '1234';
        //		$v_param['telnet_dir_user'] = 'diradmin';
        //		$v_param['telnet_dir_pwd'] = '1234';

                //운영
        //		$v_param['ldap_server_ip']	= '10.11.99.26';
        //		$v_param['ldap_domain']		= 'DC=ods01, DC=nps, DC=net';
        //		$v_param['dir_user_name']	= 'diradmin';
        //		$v_param['dir_pwd']			= 'diradmin';
        //		$v_param['telnet_user'] = 'admin';
        //		$v_param['telnet_pwd'] = 'admin';


		$v_param['dir_user']		= 'uid='.$v_param['dir_user_name'].', cn=users, '.$v_param['ldap_domain'];
		
		$v_param['telnet_dir_user'] = $v_param['dir_user_name'];
        $v_param['telnet_dir_pwd'] = $v_param['dir_pwd'];
        
        $v_param['default_group_id'] = '20';
        $v_param['home_dir'] = '/Users';
     
		$v_param['isLogFile'] = false;

		$this->_log($v_param);
		$this->v_param = $v_param;

		if(empty($v_param['ldap_type'])){
			$this->set_params('ldap_type','OD');
		}

	}

	function set_param($key, $value){
		$this->v_param[$key] = $value;
	}

	function set_params($params){
		foreach($params as $key => $val){
			$this->v_param[$key] = $val;
		}
	}

	function get_param($key){
		return $this->v_param[$key];
	}
	function get_params(){
		return $this->v_param;
	}

	function fn_run_ldap($v_param){
			//LDAP Binding.
		$this->fn_ldap_bind($v_param);

		$this->_log('$v_ldap_conn ::: ' . $this->v_param['ldap_conn'] );

		if ( $this->v_param['ldap_conn'] ) {
			if($this->v_param['ladp_action'] == 'search'){
				//조회
				$v_result = $this->fn_search($v_param);

				if($v_result){
					$v_return_cd = true;
					$v_return_msg = 'LDAP 조회 성공...';
					$v_return_cnt = 1;
					$v_return_data = $v_result;
				}else{
					$v_return_cd = false;
					$v_return_msg = 'LDAP 조회 실패...';
					$v_return_cnt = 0;
					$v_return_data = array();
				}
			}else if($this->v_param['ladp_action'] == 'search_cnt'){
				//조회
				$v_result = $this->fn_search_cnt($v_param);

				if($v_result){
					$v_return_cd = true;
					$v_return_msg = 'LDAP 확인 성공...';
					$v_return_cnt = $v_result;
					$v_return_data = array();
				}else{
					$v_return_cd = false;
					$v_return_msg = 'LDAP 확인 실패...';
					$v_return_cnt = 0;
					$v_return_data = array();
				}
			}else if($this->v_param['ladp_action'] == 'user_add'){
				//사용자 추가
				$this->v_param['search_action'] = 'USER_ID';

				//조회
				//$this->set_param('ldap_filter'	,	'(uid=uidtest)');
				$v_return_cnt = $this->fn_search_cnt($v_param);

				if($v_return_cnt > 0){
					$v_return_cd = false;
					$v_return_msg = 'LDAP 사용자생성 실패 : 같은 사용자 ID가 존재 합니다...';
					$v_return_cnt = 0;
					$v_return_data = array();
				}else{

					$v_result = $this->fn_ldap_add_user($v_param);

					if($v_result){
						$v_return_cd = true;
						$v_return_msg = 'LDAP 사용자생성 성공...';
						$v_return_cnt = 1;
						$v_return_data = array();
					}else{
						$v_return_cd = false;
						$v_return_msg = 'LDAP 사용자생성 실패...';
						$v_return_cnt = 0;
						$v_return_data = array();
					}
				}

				//echo $v_return_msg;
			}else if($this->v_param['ladp_action'] == 'user_modify'){
				//사용자 변경
				$this->v_param['search_action'] = 'USER_ID';

				//조회
				$v_return_cnt = $this->fn_search_cnt($v_param);

				if($v_return_cnt == 0){
					$v_return_cd = false;
					$v_return_msg = 'LDAP 사용자정보 변경 실패 : 사용자가 존재하지 않습니다...';
					$v_return_cnt = 0;
					$v_return_data = array();
				}else{
					$v_result = $this->fn_ldap_modify_user($v_param);

					if($v_result){
						$v_return_cd = true;
						$v_return_msg = 'LDAP 사용자정보 변경 성공...';
						$v_return_cnt = 1;
						$v_return_data = array();
					}else{
						$v_return_cd = false;
						$v_return_msg = 'LDAP 사용자정보 변경 실패...';
						$v_return_cnt = 0;
						$v_return_data = array();
					}
				}

				//echo $v_return_msg;
			}else if($this->v_param['ladp_action'] == 'user_delete'){
				//사용자 삭제
				$this->v_param['search_action'] = 'USER_ID';

				//조회
				$v_return_cnt = $this->fn_search_cnt($v_param);

				if($v_return_cnt == 0){
					$v_return_cd = false;
					$v_return_msg = 'LDAP 사용자삭제 실패 : 사용자가 존재하지 않습니다...';
					$v_return_cnt = 0;
					$v_return_data = array();
				}else{
					$v_result = $this->fn_ldap_delete_user($v_param);

					if($v_result){
						$v_return_cd = true;
						$v_return_msg = 'LDAP 사용자삭제 성공...';
						$v_return_cnt = 1;
						$v_return_data = array();
					}else{
						$v_return_cd = false;
						$v_return_msg = 'LDAP 사용자삭제 실패...';
						$v_return_cnt = 0;
						$v_return_data = array();
					}
				}

				//echo $v_return_msg;
			}else if($this->v_param['ladp_action'] == 'group_add'){
				//조회
				$this->v_param['search_action'] = 'GROUP_NM';
                $v_return_cnt = $this->fn_search_cnt($v_param);
                
				if($v_return_cnt > 0){
					//새로운 이름으로 그룹이 존재하는 경우 처리실패
					$v_return_cd = false;
					$v_return_msg = 'LDAP 그룹생성 실패 : 같은 그룹명이 존재 합니다...';
					$v_return_cnt = 0;
					$v_return_data = array();
				}else{
					$v_result = $this->fn_ldap_add_group($v_param);

					if($v_result){
						$v_return_cd = true;
						$v_return_msg = 'LDAP 그룹생성 성공...';
						$v_return_cnt = 1;
						$v_return_data = array();
					}else{
						$v_return_cd = false;
						$v_return_msg = 'LDAP 그룹생성 실패...';
						$v_return_cnt = 0;
						$v_return_data = array();
					}
				}

				//echo $v_return_msg;
			}else if($this->v_param['ladp_action'] == 'group_modify'){
				$this->v_param['search_action'] = 'GROUP_NM';
				$v_param['search_group_nm'] = $v_param['group_nm_new'];

				//조회
				$v_return_cnt = $this->fn_search_cnt($v_param);

				if($v_return_cnt > 0){
					//새로운 이름으로 그룹이 존재하는 경우 처리실패
					$v_return_cd = false;
					$v_return_msg = 'LDAP 그룹변경 실패 : 같은 그룹명이 존재 합니다...';
					$v_return_cnt = 0;
					$v_return_data = array();
				}else{
					$v_result = $this->fn_ldap_modify_group($v_param);

					if($v_result){
						$v_return_cd = true;
						$v_return_msg = 'LDAP 그룹변경 성공...';
						$v_return_cnt = 1;
						$v_return_data = array();
					}else{
						$v_return_cd = false;
						$v_return_msg = 'LDAP 그룹변경 실패...';
						$v_return_cnt = 0;
						$v_return_data = array();
					}
				}

				//echo $v_return_msg;
			}else if($this->v_param['ladp_action'] == 'group_modify_member'){

				//조회
				$v_return_cnt = $this->fn_search($v_param);

				if(count($v_return_cnt) > 0){

					$v_result = $this->fn_ldap_modify_group_member($v_param);

					if($v_result){
						$v_return_cd = true;
						$v_return_msg = 'LDAP 그룹변경 성공...';
						$v_return_cnt = 1;
						$v_return_data = array();
					}else{
						$v_return_cd = false;
						$v_return_msg = 'LDAP 그룹변경 실패...';
						$v_return_cnt = 0;
						$v_return_data = array();
					}

				}else{
					$v_return_cd = false;
					$v_return_msg = 'LDAP 그룹변경 실패...';
					$v_return_cnt = 0;
					$v_return_data = array();

				}
				//echo $v_return_msg;
			}else if($this->v_param['ladp_action'] == 'group_delete'){
				$this->v_param['search_action'] = 'GROUP_NM';
				$v_param['search_group_nm'] = $v_param['group_nm'];

				//조회
				$v_return_cnt = $this->fn_search_cnt($v_param);

				if($v_return_cnt == 0){
					//새로운 이름으로 그룹이 존재하는 경우 처리실패
					$v_return_cd = false;
					$v_return_msg = 'LDAP 그룹삭제 실패 : 그룹명이 존재하지 않습니다...';
					$v_return_cnt = 0;
					$v_return_data = array();
				}else{
					$v_result = $this->fn_ldap_delete_group($v_param);

					if($v_result){
						$v_return_cd = true;
						$v_return_msg = 'LDAP 그룹삭제 성공...';
						$v_return_cnt = 1;
						$v_return_data = array();
					}else{
						$v_return_cd = false;
						$v_return_msg = 'LDAP 그룹삭제 실패...';
						$v_return_cnt = 0;
						$v_return_data = array();
					}
				}

				//echo $v_return_msg;
			}

			return array(
					'success' => $v_return_cd,
					'msg' => $v_return_msg,
					'cnt' => $v_return_cnt,
					'data' => $v_return_data
			);

			ldap_close($this->v_param['ldap_conn']);
		} else {
			$v_return_cd = false;
			$v_return_msg = 'LDAP 연결 실패...';
			$v_return_cnt = 0;
			$v_return_data = array();

			return array(
					'success' => $v_return_cd,
					'msg' => $v_return_msg,
					'cnt' => $v_return_cnt,
					'data' => $v_return_data
			);
		}
	}

	function fn_ldap_bind_user($v_param){
		try
		{
			$this->fn_ldap_bind($v_param);
			return true;
		}
		catch(Exception $e)
		{
			return false;
		}
	}

	//그룹 추가
	private function fn_ldap_add_group($av_param){
		//$v_dn = 'CN='.$v_param['group_nm'].', '.$v_param['ldap_dpn'].', '.$v_param['ldap_domain'];
		$v_info = $av_param['group_info'];
		$this->_log('fn_ldap_add_group:');
		$this->_log('v_group: '.print_r($v_info, true) );
		$this->_log('v_param: '.print_r($this->v_param, true) );

		if($this->v_param['ldap_type'] == 'OD'){
            //$this->createTelnetGroup($v_info);
     
			$this->createSSHGroup($v_info);
		}else{
			$v_info['samAccountName'] = $av_param['group_nm'];
			$v_info['objectClass'] = 'group';	
			$v_dn = 'CN='.$v_info['cn'].','.$this->v_param['ldap_rdn'].', '.$this->v_param['ldap_domain'];			
			$v_return = @ldap_add($this->v_param['ldap_conn'], $v_dn, $v_info);			
			$this->_error($v_return, 'fn_ldap_add_group');			
		}
		$v_return_cnt = $this->fn_search_cnt($av_param);
		if($v_return_cnt > 0){
			if($this->v_param['ldap_type'] == 'OD'){			
				//ldap으로 수정
				$v_return = $this->fn_ldap_modify_group($av_param);
			}
			if($v_return){
				return true;
			}else{
				return false;
			}
			
		}else{
			return false;
		}
	}


	//그룹 삭제
	private function fn_ldap_delete_group($av_param){
        if ($this->v_param['ldap_type'] == 'OD') {
            $v_ldap_rdn = 'CN='.$av_param['group_nm'].', '.$this->v_param['ldap_rdn'].', '.$this->v_param['ldap_domain'];
        }else{
            $v_ldap_rdn = 'CN='.$av_param['group_nm'].', '.$this->v_param['ldap_ou'].', '.$this->v_param['ldap_domain'];
        }
		$v_return = @ldap_delete($this->v_param['ldap_conn'], $v_ldap_rdn);

		if($v_return){
			return true;
		}else{
			return false;
		}
	}

	//그룹명 수정
	private function fn_ldap_modify_group($av_param){
		$v_ldap_rdn = 'CN='.$av_param['group_info']['cn'].', '.$this->v_param['ldap_rdn'].', '.$this->v_param['ldap_domain'];

        $v_info = $av_param['group_info'];        

		$this->_log('fn_ldap_modify_group:');
		$this->_log('v_ldap_rdn: '.print_r($v_ldap_rdn, true) );
		$this->_log('v_info: '.print_r($v_info, true) );

		$v_return = @ldap_modify($this->v_param['ldap_conn'], $v_ldap_rdn, $v_info);
		$this->_error( $v_return ,'ldap_modify' );

		if($v_return){
			return true;
		}else{
			return false;
		}
	}

	private function fn_ldap_modify_group_member($av_param){
		if($this->v_param['ldap_type'] == 'OD'){

			$v_ldap_rdn = 'CN='.$av_param['group_nm'].', '.$this->v_param['ldap_rdn'].', '.$this->v_param['ldap_domain'];

			$v_info = $av_param['group_member_info'];

			$this->_log('fn_ldap_modify_group_meer: OD');
			$this->_log('v_ldap_rdn: '.print_r($v_ldap_rdn, true) );
			$this->_log('v_info: '.print_r($v_info, true) );

			$v_return = @ldap_modify($this->v_param['ldap_conn'], $v_ldap_rdn, $v_info);
			$this->_error( $v_return ,'ldap_modify' );
			if($v_return){
				return true;
			}else{
				return false;
			}
		}else{
			$this->_log('fn_ldap_modify_group_member: AD');

			if($this->v_param['sub_action'] == "USER_DEL"){
				$this->fn_ldap_del_user_group($av_param);
			}else{
				$this->fn_ldap_add_user_group($av_param);
			}
		}
	}


	//사용자 그룹 추가 ad
	private function fn_ldap_add_user_group($av_param){
		//user_id
		$user_id = $av_param['user_id'];
		$group_nm =	$av_param['group_nm'];

		$this->_log('v_add_group: '.print_r($av_param, true) );

		$v_ldap_rdn = 'CN='.$group_nm.', '.$this->v_param['ldap_rdn'].', '.$this->v_param['ldap_domain'];
		$v_entry['member'] = 'CN='.$user_id.', '.$this->v_param['ldap_rdn_user'].', '.$this->v_param['ldap_domain'];
		
		$v_return = ldap_mod_add($this->v_param['ldap_conn'], $v_ldap_rdn, $v_entry);
		$this->_error($v_return, 'fn_ldap_add_user_group ldap_mod_add');	

		
		return true;
	}
	
	private function fn_ldap_del_user_group($av_param){

		$user_id = $av_param['user_id'];
		$group_nm =	$av_param['group_nm'];

		$this->_log('v_add_group: '.print_r($av_param, true) );

		$v_ldap_rdn = 'CN='.$group_nm.', '.$this->v_param['ldap_rdn'].', '.$this->v_param['ldap_domain'];
		$v_entry['member'] = 'CN='.$user_id.', '.$this->v_param['ldap_rdn_user'].', '.$this->v_param['ldap_domain'];

		$v_return = ldap_mod_del($this->v_param['ldap_conn'], $v_ldap_rdn, $v_entry);
		$this->_error($v_return, 'fn_ldap_del_user_group ldap_mod_del');					
		
		
		return true;
	}
	
	private function fn_ldap_modify_user_group($av_param){
		//user_id
		$user_id = $av_param['user_id'];

		$v_add_group = $this->fn_add_group($av_param);	//추가할 그룹
		$this->_log('v_add_group: '.print_r($v_add_group, true) );
		$v_del_group = $this->fn_del_group($av_param);	//삭제할 그룹
		$this->_log('fn_del_group: '.print_r($v_del_group, true) );
		
		//그룹추가로직
		for ($i=0; $i < count($v_add_group); $i++) {

			$this->_log('v_add_group: '.print_r($v_add_group, true) );
		
			if(strlen(trim($v_add_group[$i]))){
				$v_ldap_rdn = 'CN='.$v_add_group[$i].', '.$this->v_param['ldap_rdn'].', '.$this->v_param['ldap_domain'];
				$v_entry['member'] = 'CN='.$user_id.', '.$this->v_param['ldap_rdn_user'].', '.$this->v_param['ldap_domain'];
				
				$v_return = ldap_mod_add($this->v_param['ldap_conn'], $v_ldap_rdn, $v_entry);
				$this->_error($v_return, 'fn_ldap_modify_user_group ldap_mod_add');	
			}
		}
		
		//그룹삭제로직
		for ($i=0; $i < count($v_del_group); $i++) {

			$this->_log('v_del_group: '.print_r($v_del_group, true) );

			if(strlen(trim($v_del_group[$i]))){
				$v_ldap_rdn = 'CN='.$v_del_group[$i].', '.$this->v_param['ldap_rdn'].', '.$this->v_param['ldap_domain'];
				$v_entry['member'] = 'CN='.$user_id.', '.$this->v_param['ldap_rdn_user'].', '.$this->v_param['ldap_domain'];

				$v_return = ldap_mod_del($this->v_param['ldap_conn'], $v_ldap_rdn, $v_entry);
				$this->_error($v_return, 'fn_ldap_modify_user_group ldap_mod_del');					
			}
		}
		
		return true;
	}
	//사용자 추가
	private function fn_ldap_add_user($av_param){

		$v_info = $av_param['user_info'];
		$this->_log('fn_ldap_add_user:');
		$this->_log('v_info: '.print_r($v_info, true) );

		if($this->v_param['ldap_type'] == 'OD'){
			//OD에서 생성되지 않아 텔넷방식으로 변경
			//$v_return = $this->createTelnetUser( $v_info );
            $v_return = $this->createSSHUser( $v_info );
		}else{
			//uid : ID, cn : Name 표시이름, sn : Last Name 성, samAccountName : Logon ID 사용자 로그온 이름
			$v_ldap_rdn = 'CN='.$av_param['cn'].', '.$this->v_param['ldap_rdn'].', '.$this->v_param['ldap_domain'];
			
			$ren_ldap_domain = str_replace(" ", "",$this->v_param['ldap_domain']);
			$ren_ldap_domain = str_replace(",", ".",$ren_ldap_domain);
			$ren_ldap_domain = str_replace("DC=", "",$ren_ldap_domain);

			$v_info["pwdlastset"] = "-1";						//다음로그온시 사용자가 반드시 암호를 변경해야함			
			$v_info['displayname'] = $av_param['user_nm'].'('.$av_param['user_id'].')';		//표시 이름
			$v_info['givenname'] =  $av_param['user_nm'];		//이름
			$v_info['userprincipalname'] = $av_param['user_id'].'@'.$ren_ldap_domain ;		//사용자 로그온 이름
			$v_info['samaccountname'] = $av_param['user_id'];		//2000이전버전 로그온 이름			
			$v_info["objectClass"] = "user";
			//$v_info['UserAccountControl'] = '544';	
			
			/* 계정유형
			 * 512 - Enable Account
			 * 514 - Disable account
			 * 544 - Account Enabled - Require user to change password at first logon
			 * 66048 - Enabled, password never expires
			 * 66050 - Disabled, password never expires
			 */
			
			$v_info['UserAccountControl'] = '544';
			
			//$v_info['UserAccountControl'] = '66050';
			
			$v_info['description'] = 'NPS에서 자동생성';

			//전자메일
			if(strlen(trim($av_param['email'])) > 0){
				$v_info['mail'] = $av_param['email'];
			}else{
				$v_info['mail'] = ' ';
			}			
			//전화번호
			if(strlen(trim($av_param['phone'])) > 0){
				$v_info['telephoneNumber'] = $av_param['phone'];
			}else{
				$v_info['telephoneNumber'] = ' ';
			}			
			//조직-부서
			if(strlen(trim($av_param['dept_nm'])) > 0){
				$v_info['department'] = $av_param['dept_nm'];
			}else{
				$v_info['department'] = ' ';
			}			
			$v_return = @ldap_add($this->v_param['ldap_conn'], $v_ldap_rdn, $v_info);
			
			$this->_error($v_return, 'fn_ldap_add_user');

			$adsi = new ADSI(array(
				'ad_url' => VOL2_AD_ADSI_URL,
				'domaindc' => VOL2_LDAP_DOMAIN,
				'file_system' => VOL2_AD_FILE_SYSTEM
			));	
			$data = array(
				'user_name' => $v_info['uid'],	
				'password' => $v_info['userPassword']
			);
			$return = $adsi ->setPassword( $data );
			if( !$return_json = json_decode($return ,true) ){
				throw new Exception("연동 실패");
			}else{
				if( $return_json['status'] != 0){
					throw new Exception("연동 실패");
				}
			}
		}

		$v_return_cnt = $this->fn_search_cnt($av_param);
		if($v_return_cnt > 0){
			//ldap으로 수정
			if($this->v_param['ldap_type'] == 'OD'){
				//생성시에만 셋팅 추가
				sleep(1);
				$getMacCustomSettingInfo =	$this->getMacCustomSettingInfo();
				$av_param['user_info']['apple-mcxflags'] = $getMacCustomSettingInfo['apple-mcxflags'];
				$av_param['user_info']['apple-mcxsettings'] = $getMacCustomSettingInfo['apple-mcxsettings'];

				$v_result = $this->fn_ldap_modify_user($av_param);
				$this->_error($v_result, 'fn_ldap_modify_user');
			}
		}else{
			return false;
		}
	}

	//사용자 수정
	private function fn_ldap_modify_user($av_param){
		//uid : ID, cn : Name 표시이름, sn : Last Name 성, samAccountName : Logon ID 사용자 로그온 이름
		$v_ldap_rdn = 'UID='.$av_param['user_info']['uid'].', '.$this->v_param['ldap_rdn'].', '.$this->v_param['ldap_domain'];

		$v_info = $av_param['user_info'];

		$this->_log('fn_ldap_modify_user:');
		$this->_log('v_ldap_rdn: '.print_r($v_ldap_rdn, true) );
		$this->_log('v_info: '.print_r($v_info, true) );

		$v_return = @ldap_modify($this->v_param['ldap_conn'], $v_ldap_rdn, $v_info);

		if($v_return){
			return true;
		}else{
			return false;
		}
	}


	//추가할 그룹 추출
	private function fn_add_group($av_param){
		$v_return = array();

		$v_group_nm_new = explode('!@#', $av_param['user_group_nm_new']);
		$v_group_nm_old = explode('!@#', $av_param['user_group_nm_old']);

		//추가할 그룹명 찾기
		for ($v_f1=0; $v_f1 < count($v_group_nm_new); $v_f1++) {
			$v_chk = true;

			for ($v_f2=0; $v_f2 < count($v_group_nm_old); $v_f2++) {
				if($v_group_nm_new[$v_f1] == $v_group_nm_old[$v_f2]){
					$v_chk = false;
				}
			}

			//기존 들어있던 그룹이 아닌경우 추가그룹에 포함
			if($v_chk){
				array_push($v_return, $v_group_nm_new[$v_f1]);
			}
		}

		return $v_return;
	}

	//삭제할 그룹 추출
	private function fn_del_group($av_param){
		$v_return = array();

		$v_group_nm_new = explode('!@#', $av_param['user_group_nm_new']);
		$v_group_nm_old = explode('!@#', $av_param['user_group_nm_old']);

		//삭제할 그룹명 찾기
		for ($v_f1=0; $v_f1 < count($v_group_nm_old); $v_f1++) {
			$v_chk = true;

			for ($v_f2=0; $v_f2 < count($v_group_nm_new); $v_f2++) {
				if($v_group_nm_old[$v_f1] == $v_group_nm_new[$v_f2]){
					$v_chk = false;
				}
			}

			//기존 들어있던 그룹이 새로운 그룹에 없는경우 삭제그룹에 포함
			if($v_chk){
				array_push($v_return, $v_group_nm_old[$v_f1]);
			}
		}

		return $v_return;
	}

	//사용자 삭제
	private function fn_ldap_delete_user($av_param){
		//uid : ID, cn : Name 표시이름, sn : Last Name 성, samAccountName : Logon ID 사용자 로그온 이름
		$v_ldap_rdn = 'UID='.$av_param['user_info']['uid'].', '.$this->v_param['ldap_rdn'].', '.$this->v_param['ldap_domain'];

		$v_return = @ldap_delete($this->v_param['ldap_conn'], $v_ldap_rdn);

		if($v_return){
			//echo 'ldap_delete 성공<br>';
			return true;
		}else{
			//echo 'ldap_delete 실패<br>';
			return false;
		}
	}

	//검색결과의 Count
	function fn_search_cnt($av_param){

		$v_return = $this->fn_search($av_param);

		if($v_return){
			return count($v_return);
		}else{
			return 0;
		}
	}

	//검색
	function fn_search($av_param){
		/* 조회구분
		 * USER_ALL : 사용자 전체목록 조회 : 다건 추출
		 * USER_ID : 사용자 ID를 이용한 사용자 정보 조회 : 1건 추출
		 * USER_GROUP : 사용자 ID를 이용한 소속그룹 목록 조회 : 다건 추출
		 * USER_NM : 사용자 이름을 이용한 사용자 목록 조회 : 다건 추출
		 * GROUP_ALL : 그룹 전체목록 조회 : 다건 추출
		 * GROUP_NM : 그룹 이름으로 그룹 조회 : 1건 추출
		 * GROUP_MEMBER : 그룹 이름을 이용한 소속사용자 목록 조회 : 다건 추출
		 */
		//$this->v_param['search_action'] = 'GROUP_NM';
		$v_return = array();
		if($this->v_param['ldap_type'] == 'OD'){
			$v_ldap_rdn = $this->v_param['ldap_rdn'].', '.$this->v_param['ldap_domain'];
		}else{
			$v_ldap_rdn = $this->v_param['ldap_rdn'].', '.$this->v_param['ldap_domain'];
		}
		$v_filter = $this->v_param['ldap_filter'];

		if( empty($this->v_param['ldap_conn']) ){
			$this->fn_ldap_bind($this->v_param);
		}

		$v_result = $this->fn_ldap_search($this->v_param['ldap_conn'], $v_ldap_rdn, $v_filter);

		$entries_result = $this->fn_ldap_get_entries($this->v_param['ldap_conn'], $v_result);
      
		$this->_log(' entries_result : '. print_r($entries_result, true) );

		if($this->v_param['search_action'] == 'USER_ALL' || $this->v_param['search_action'] == 'USER_ID' || $this->v_param['search_action'] == 'USER_GROUP'){
			//사용자 전체목록 조회 : 다건 추출 - 확인완료
			
			for ( $i=0; $i<$entries_result['count'] ; $i++) {

				$group_list = array();

				if( $this->v_param['ldap_type'] == 'OD' ){

					if( !empty($entries_result[$i]['gidnumber']) && $entries_result[$i]['gidnumber']['count'] > 0 ){
						for ( $j=0; $j<$entries_result[$i]['gidnumber']['count']; $j++) {
							array_push($group_list, $entries_result[$i]['gidnumber'][$j]);
						}
					}
				}else{
					$v_group = $entries_result[$i]['memberof'];
					if( $v_group['count'] > 0 ){
						for ($i=0; $i < $v_group['count']; $i++){
							$v_r = explode(",", $v_group[$i], 2);
							$v_g = explode("=",$v_r[0]);
						
							array_push($group_list, $v_g[1]);
						}
					}							
				}

				$result_list = array(
					'cn' => $entries_result[$i]['cn'][0],
					'sn' => $entries_result[$i]['sn'][0],
					'uid' => $entries_result[$i]['uid'][0],
					'uidnumber' => $entries_result[$i]['uidnumber'][0],
					'group_list' => $group_list,
					'password' => $entries_result[$i]['userpassword'][0],
					'apple-generateduid' => $entries_result[$i]['apple-generateduid'][0]
				);
				array_push($v_return, $result_list );
			}			
		

		}else if($this->v_param['search_action'] == 'GROUP_ALL' || $this->v_param['search_action'] == 'GROUP_NM' || $this->v_param['search_action'] == 'GROUP_MEMBER' ){
			//그룹 전체목록 조회 : 다건 추출 - 확인완료
			if( $this->v_param['ldap_type'] == 'OD'){
				for ( $i=0; $i<$entries_result['count']; $i++) {

					$member_list = array();
					$apple_member_list = array();

					if( !empty($entries_result[$i]['memberuid']) && $entries_result[$i]['memberuid']['count'] > 0 ){
						for ( $j=0; $j<$entries_result[$i]['memberuid']['count']; $j++) {
							array_push($member_list, $entries_result[$i]['memberuid'][$j]);
						}
					}

					if( !empty($entries_result[$i]['apple-group-memberguid']) && $entries_result[$i]['apple-group-memberguid']['count'] > 0 ){
						for ( $j=0; $j<$entries_result[$i]['apple-group-memberguid']['count']; $j++) {
							array_push($apple_member_list, $entries_result[$i]['apple-group-memberguid'][$j]);
						}
					}

					$result_list = array(
						'group_name' => $entries_result[$i]['cn'][0],
						'group_id' => $entries_result[$i]['gidnumber'][0],
						'member_list' => $member_list,
						'apple_member_list' => $apple_member_list
					);

					array_push($v_return, $result_list );
				}
			}else{
				$this->_log(' entries_result : '. print_r($entries_result, true) );
				for ( $i=0; $i<$entries_result['count']; $i++) {

					$member_list = array();
					
					if( !empty($entries_result[$i]['member']) && $entries_result[$i]['member']['count'] > 0 ){
						for ( $j=0; $j<$entries_result[$i]['member']['count']; $j++) {
							array_push($member_list, $entries_result[$i]['member'][$j]);
						}
					}

					if(empty( $entries_result[$i]['gidnumber'][0]) ){

						$gidnumber = $entries_result[$i]['cn'][0];
					}else{
						$gidnumber = $entries_result[$i]['gidnumber'][0];
					}

					$result_list = array(
						'group_name' => $entries_result[$i]['cn'][0],
						'group_id' => $gidnumber,
						'member_list' => $member_list
					);

					array_push($v_return, $result_list );
				}
			}
		}

		$this->_log(' v_return : '. print_r($v_return, true) );

		return $v_return;
	}

	//LDAP Binding.
	private function fn_ldap_bind($av_param){
		$v_ldap_conn = $this->fn_ldap_connect($av_param);
		$this->_log('$v_ldap_conn ::: ' . $v_ldap_conn );
		$this->_log('$av_param ::: ' . print_r($av_param, true) );
		if($v_ldap_conn){
			ldap_set_option($v_ldap_conn, LDAP_OPT_PROTOCOL_VERSION, 3);
			ldap_set_option($v_ldap_conn, LDAP_OPT_REFERRALS, 0);
			//ldap_set_option($v_ldap_conn, LDAP_OPT_DEBUG_LEVEL, 7);
			if($this->v_param['ldap_type'] == 'AD'){
				$bind_rdn = 'CN='.$this->v_param['dir_user_name'].', cn=users, '.$this->v_param['ldap_domain'];				
			}else{
				$bind_rdn = 'uid='.$this->v_param['dir_user_name'].', cn=users, '.$this->v_param['ldap_domain'];
			}			
            //$v_return = ldap_bind($v_ldap_conn);

            $v_return = @ldap_bind($v_ldap_conn,$bind_rdn, $this->v_param['dir_pwd']);

			//var_dump($v_return);
			$this->_error($v_return, 'ldap_bind');
			if($v_return){
				$this->_log('LDAP bind 성공...');
				return $v_ldap_conn;
			}else{
				$this->_log('LDAP bind 실패...');
				@ldap_close($v_ldap_conn);
				return null;
			}
		}
	}

	//LDAP Connecting.
	private function fn_ldap_connect($av_param){
		$v_return = ldap_connect($this->v_param['ldap_server_ip']) or die('Server 연결 실패 !!!');
		$this->_error($v_return, 'fn_ldap_connect');
		if($v_return){
			$this->v_param['ldap_conn'] = $v_return;
			$this->_log('LDAP 연결 성공...');
			return $v_return;
		}else{
			$this->_log('LDAP 연결 실패...');
			@ldap_close($v_return);

			return null;
		}
	}

	private function fn_ldap_search($ldap_conn, $v_ldap_rdn, $v_filter ){

		$this->_log('fn_ldap_search:');
		$this->_log('v_ldap_rdn: '.print_r($v_ldap_rdn, true) );
		$this->_log('v_filter: '.print_r($v_filter, true) );

		$v_result = @ldap_search($ldap_conn, $v_ldap_rdn, $v_filter);

		$this->_error($v_result, 'ldap_search');

		return $v_result;
	}

	private function fn_ldap_get_entries($ldap_conn, $v_result ){
		$this->_log('fn_ldap_get_entries:');
		$this->_log('v_result: '.print_r($v_result, true) );

		$v_result = @ldap_get_entries($ldap_conn, $v_result);

		return $v_result;
	}

	function getUserIdRen($user_id){
		$user_id = strtolower($user_id);
		return $user_id;
	}

	function HextoNum( $hexString ) {
		$code = array(
			"0", "1", "2", "3", "4", "5", "6", "7", "8", "9",
			"A", "B", "C", "D", "E", "F", "G", "H", "I", "J",
			"K", "L", "M", "N", "O", "P", "Q", "R", "S", "T",
			"U", "V", "W", "X", "Y", "Z"
		);
		foreach($code as $index => $str)
		{
			if($str == strtoupper($hexString) ){
				if($str == "F"){
					return 4;
				}
				return $index;
			}
		}
		return '';
	}

	function getUserNumRen($user_id){
		$new_return = '';
		$user_id = strtolower($user_id);
		for ( $i= 0; $i < strlen($user_id) ; $i++){
			$new_return .= $this->HextoNum($user_id[$i]);
		}
		return $new_return;
	}

	//로깅 함수 파일로그 작성 클래스생성시 로그여부 체크
	function _log($log){     
        $logger = new Logger(basename(__FILE__,'.php'));
        $logger->debug(print_r($log, true));
	}

	function _error($result, $function){
		if(!$result){
			$error_msg = ldap_error($this->v_param['ldap_conn']);
			
			if(strstr( $error_msg , 'Success') || strstr( $error_msg , 'Already exists') || strstr( $error_msg , 'Type or value exists') ){
			}else{
                $this->_log( $function." Failed: ".$error_msg );
				throw new \Exception( $function." Failed: ".$error_msg );
			}
		}
	}

	function getGUID(){
		if (function_exists('com_create_guid')){
			return com_create_guid();
		}else{
			mt_srand((double)microtime()*10000);//optional for php 4.2.0 and up.
			$charid = strtoupper(md5(uniqid(rand(), true)));
			$hyphen = chr(45);// "-"
			$uuid = substr($charid, 0, 8).$hyphen
					.substr($charid, 8, 4).$hyphen
					.substr($charid,12, 4).$hyphen
					.substr($charid,16, 4).$hyphen
					.substr($charid,20,12);
					//.chr(125);// "}"
					//chr(123)// "{"
			return $uuid;
		}
	}

	function createSSHUser($user_info){
		$ssh = new SSH2($this->v_param['ldap_server_ip'],22);		
		$ssh->authPassword( $this->v_param['telnet_user'], $this->v_param['telnet_pwd'] );

		$ssh->openShell( 'xterm' ,1);		
		
		//$user_nm			= $user_info['user_nm'];// "test0731";
		$user_id			= $user_info['uid'];//"test0731";
		$user_number		= $user_info['uidNumber'];// "10731";
        $homeDirectory		= "/Users/".$user_id;
        $customHomeDir      = $this->v_param['home_dir'].'/'.$user_id;
		$user_pwd			=  $user_info['userPassword'];//"1234";
        $default_group_id	=  $this->v_param['default_group_id'];//stanby group //"20";//default
		$UserShell			= "/bin/bash";
		$user_cn =  $user_info['cn'];//$user_nm."(".$user_id.")";

		$default_command = "dscl -u ".$this->v_param['telnet_dir_user']." -P ".$this->v_param['telnet_dir_pwd']."  /LDAPv3/127.0.0.1";
		$create_command  = " -create ".$homeDirectory;
		$pwd_command  = " -passwd ".$homeDirectory." ".$user_pwd;
		//계정생성
		$r = $ssh->cmdExec($default_command.$create_command ,//계정생성
			$default_command.$create_command." "."UserShell"." ".$UserShell	,//UserShell 입력
			$default_command.$create_command." "."RealName"." ".$user_id,//cn 입력
			$default_command.$create_command." "."UniqueID"." ".$user_number,//uidNumber 입력
			$default_command.$create_command." "."PrimaryGroupID"." ".$default_group_id,//gidNumber 입력
			$default_command.$create_command." "."NFSHomeDirectory"." ".$homeDirectory,//HomeDirectory 입력
			$default_command.$pwd_command //userpasswd 입력
		);

		return true;

    }
    
        
    function changeSSHPassword($user_id, $user_pwd){
        $ssh = new SSH2($this->v_param['ldap_server_ip'],22);		
		$ssh->authPassword( $this->v_param['telnet_user'], $this->v_param['telnet_pwd'] );

		$ssh->openShell( 'xterm' ,1);		

		$homeDirectory		= "/Users/".$user_id;

		$defualt_command = "dscl -u ".$this->v_param['telnet_dir_user']." -P ".$this->v_param['telnet_dir_pwd']." /LDAPv3/127.0.0.1";	
        $pwd_command  = " -passwd ".$homeDirectory." ".$user_pwd;

		$r = $ssh->cmdExec($defualt_command.$pwd_command);

		return true;
    }

	function createSSHGroup($group_info){		
		//dseditgroup -n /LDAPv3/127.0.0.1 -o create -u diradmin -P 1234 dscltest
        //그룹 생성
        $ssh = new SSH2($this->v_param['ldap_server_ip'],22);	
    
		$ssh->authPassword( $this->v_param['telnet_user'], $this->v_param['telnet_pwd'] );
   
		$ssh->openShell( 'xterm' ,1);
  
		$group_nm			= $group_info['cn'];
		$defualt_command = "dseditgroup -n /LDAPv3/127.0.0.1 -o create -u ".$this->v_param['telnet_dir_user']." -P ".$this->v_param['telnet_dir_pwd']." ".$group_nm;

        
        $r = $ssh->cmdExec($defualt_command);
        
		return true;
	}

	function createTelnetUser( $user_info ){

		//유저 생성은 telnet으로
		$telnet = new PHPTelnet();
		$result = $telnet->Connect($this->v_param['ldap_server_ip'],'','' );
		if($result != 0) throw new Exception("Connect failed");

		$telnet->_log('DoCommand Start');
		$telnet->DoCommand($this->v_param['telnet_user'], $result);
		$telnet->Sleep();
		$telnet->DoCommand($this->v_param['telnet_pwd'], $result);
		$telnet->Sleep();
		$telnet->DoCommand('sudo su', $result);
		$telnet->Sleep();
		$telnet->DoCommand($this->v_param['telnet_pwd'], $result);
		$telnet->_log($result);

		if( $result == 'Login incorrect' ) throw new Exception("Login incorrect");

		//$user_nm			= $user_info['user_nm'];// "test0731";
		$user_id			= $user_info['uid'];//"test0731";
		$user_number		= $user_info['uidNumber'];// "10731";
		$homeDirectory		= "/Users/".$user_id;
		$user_pwd			=  $user_info['userPassword'];//"1234";
		$default_group_id	=  "1085";//stanby group //"20";//default
		$UserShell			= "/bin/bash";
		$user_cn =  $user_info['cn'];//$user_nm."(".$user_id.")";

		$defualt_command = "dscl -u ".$this->v_param['telnet_dir_user']." -P ".$this->v_param['telnet_dir_pwd']."  /LDAPv3/127.0.0.1";
		$create_command  = " -create ".$homeDirectory;
		$pwd_command  = " -passwd ".$homeDirectory." ".$user_pwd;
		//계정생성
		$telnet->DoCommand($defualt_command.$create_command, $result);
		$this->_log($result);
		//UserShell 입력
		$telnet->DoCommand($defualt_command.$create_command." "."UserShell"." ".$UserShell , $result);
		$this->_log($result);
		//cn 입력
		$telnet->DoCommand($defualt_command.$create_command." "."RealName"." ".$user_id, $result);
		$this->_log($result);
		//uidNumber 입력
		$telnet->DoCommand($defualt_command.$create_command." "."UniqueID"." ".$user_number, $result);
		$this->_log($result);
		//gidNumber 입력
		$telnet->DoCommand($defualt_command.$create_command." "."PrimaryGroupID"." ".$default_group_id, $result);
		$this->_log($result);
		//HomeDirectory 입력
		$telnet->DoCommand($defualt_command.$create_command." "."NFSHomeDirectory"." ".$homeDirectory, $result);
		$this->_log($result);
		//userpasswd 입력
		$telnet->DoCommand($defualt_command.$pwd_command, $result);
		$this->_log($result);

		return true;
	}

	function createTelnetGroup($group_info){

		//dseditgroup -n /LDAPv3/127.0.0.1 -o create -u diradmin -P 1234 dscltest
		//그룹 생성
		$telnet = new PHPTelnet();
		$result = $telnet->Connect($this->v_param['ldap_server_ip'],'','' );
		if($result != 0) throw new Exception("Connect failed");

		$telnet->_log('DoCommand Start');
		$telnet->DoCommand($this->v_param['telnet_user'], $result);
		$telnet->DoCommand($this->v_param['telnet_pwd'], $result);
		$telnet->DoCommand('sudo su', $result);
		$telnet->DoCommand($this->v_param['telnet_pwd'], $result);
		$telnet->_log($result);

		$group_nm			= $group_info['cn'];
		$defualt_command = "dseditgroup -n /LDAPv3/127.0.0.1 -o create -u ".$this->v_param['telnet_dir_user']." -P ".$this->v_param['telnet_dir_pwd']."  ".$group_nm;
		//계정생성
		$telnet->DoCommand($defualt_command, $result);
		$this->_log($result);

		return true;
	}

	function execSSHAuth($path, $user = 'admin', $group , $pathauth = 750, $subpath ='Scratch' , $scratchauth = 770 ){

		$ssh = new SSH2($this->v_param['telnet_server_ip'],22);		
		$ssh->authPassword( $this->v_param['telnet_user'], $this->v_param['telnet_pwd'] );

		$ssh->openShell( 'xterm' ,1);

		$return = $ssh->writeShell( 'sudo su' ,2);		
		$return = $ssh->writeShell( VOL1_TELNET_PWD ,2);
		$ssh->isError($return);		
		
		$defualt_command = 'chown -R '.$user.':'.$group.' '.$path;
		$return = $ssh->writeShell( $defualt_command ,1);
		$ssh->isError($return , $defualt_command );
		
		$defualt_command = "chmod -R ".$pathauth." ".$path;
		$return = $ssh->writeShell( $defualt_command ,1);
		$ssh->isError($return , $defualt_command );
		
		$defualt_command = "chmod -R ".$scratchauth." ".$path."/".$subpath;
		$return = $ssh->writeShell( $defualt_command ,1);
		$ssh->isError($return , $defualt_command );
		return true;
	}

	function execAuth($path, $user = 'admin', $group , $pathauth = 750, $subpath ='Scratch' , $scratchauth = 770 ){

		$telnet = new PHPTelnet();
		$result = $telnet->Connect($this->v_param['ldap_server_ip'],'','' );
		if($result != 0) throw new Exception("Connect failed");
		$telnet->_log('DoCommand Start');
		$telnet->DoCommand($this->v_param['telnet_user'], $result);
		$telnet->DoCommand($this->v_param['telnet_pwd'], $result);
		$telnet->DoCommand('sudo su', $result);
		$telnet->DoCommand($this->v_param['telnet_pwd'], $result);
		$telnet->_log($result);		
		$defualt_command = 'chown -R '.$user.':'.$group.' '.$path;
		$telnet->DoCommand($defualt_command, $result);
		$telnet->_log($result);
		$telnet->DoCommand("chmod -R ".$pathauth." ".$path);
		$telnet->_log($result);
		$telnet->DoCommand("chmod -R ".$scratchauth." ".$path."/".$subpath);
		$telnet->_log($result);

		return true;
	}

	function getMacCustomSettingInfo(){
		$user_info = array();
		$user_info['apple-mcxflags'] = '<?xml version="1.0" encoding="UTF-8"?><!DOCTYPE plist PUBLIC "-//Apple//DTD PLIST 1.0//EN" "http://www.apple.com/DTDs/PropertyList-1.0.dtd"><plist version="1.0"><dict><key>has_mcx_settings</key><true/></dict></plist>';
        
        $user_info['apple-mcxsettings'] = array(			
            '<?xml version="1.0" encoding="UTF-8"?>
            <!DOCTYPE plist PUBLIC "-//Apple//DTD PLIST 1.0//EN" "http://www.apple.com/DTDs/PropertyList-1.0.dtd">
            <plist version="1.0">
            <dict>
            <key>mcx_application_data</key>
            <dict>
            <key>com.apple.homeSync</key>
            <dict>
            <key>Forced</key>
            <array>
            <dict>
            <key>mcx_preference_settings</key>
            <dict>
            <key>replaceUserPrefSyncList</key>
            <true/>
            <key>syncPreferencesAtLogin</key>
            <string>dontSync</string>
            <key>syncPreferencesAtLogout</key>
            <string>dontSync</string>
            <key>syncPreferencesAtSyncNow</key>
            <string>dontSync</string>
            <key>syncPreferencesInBackground</key>
            <string>dontSync</string>
            </dict>
            </dict>
            </array>
            </dict>
            </dict>
            </dict>
            </plist>',

            '<?xml version="1.0" encoding="UTF-8"?>
            <!DOCTYPE plist PUBLIC "-//Apple//DTD PLIST 1.0//EN" "http://www.apple.com/DTDs/PropertyList-1.0.dtd">
            <plist version="1.0">
            <dict>
            <key>mcx_application_data</key>
            <dict>
            <key>com.apple.MCX</key>
            <dict>
            <key>Forced</key>
            <array>
            <dict>
            <key>mcx_preference_settings</key>
            <dict>
            <key>cachedaccounts.WarnOnCreate.allowNever</key>
            <false/>
            <key>com.apple.cachedaccounts.CreateAtLogin</key>
            <true/>
            <key>com.apple.cachedaccounts.CreatePHDAtLogin</key>
            <true/>
            <key>com.apple.cachedaccounts.WarnOnCreate</key>
            <false/>
            </dict>
            </dict>
            </array>
            </dict>
            </dict>
            </dict>
            </plist>'
		);

		return $user_info;
	}
}
?>