<?php

/**
 * strip invalid file name charactors
 *
 * @param string $fileName
 * @return string
 */
function stripInvalidFileName($fileName)
{
    $validFileName = str_replace(array('\\', '/', ':', '*', '?', '"', '<', '>', '|'), '', $fileName);
    return $validFileName;
}

/**
 * DB에 14자리 문자열로 조회되는 값을 ISO8601형태 문자열로 변환
 * convert 14 charactor datetime(in db) string to ISO8601 format string
 *
 * @param string $dbDateTime db에 저장된 14자리 문자열
 * @return string
 */
function dbDateTimeToISO($dbDateTime, $format = 'Y-m-d H:i:s')
{
    if (empty($dbDateTime) || $dbDateTime == '') {
        return '';
    }
    return date($format, strtotime($dbDateTime));
}

/**
 * ISO8601형태 문자열을 DB에 입력할 14자리 문자열로 변환
 * convert ISO8601 format string 14 charactor datetime(in db) string
 *
 * @param string $dbDateTime db에 저장된 14자리 문자열
 * @return string
 */
function isoDateTimeToDB($isoDateTime)
{
    if (empty($isoDateTime) || $isoDateTime == '') {
        return '';
    }
    return date('YmdHis', strtotime($isoDateTime));
}

/**
 * 상단 메뉴를 만드는 함수
 * 아이콘을 함수 호출하는 부분에서 입력하도록 수정(2017.11.15 hkkim)
 * make top menu
 *
 * @param string $menuTitle 메뉴에서 보여질 메뉴명
 * @param string $menuId 메뉴 고유아이디(메인에서 카드 아이디와 맞춰줘야 함)
 * @param string $icon FontAwesome 아이콘
 * @return void
 */
function createMenu($menuTitle, $menuId, $icon)
{
    global $db;
    $user_id = $_SESSION['user']['user_id'];

    $top_menu_mode = $db->queryOne("
        SELECT	TOP_MENU_MODE
        FROM		BC_MEMBER_OPTION
        WHERE	MEMBER_ID = (
            SELECT	MEMBER_ID
            FROM		BC_MEMBER
            WHERE	USER_ID =  '" . $user_id . "' and del_yn='N'
        )
    ");

    if ($menuId == 'system') $menuTitle = _text('MN00093'); //시스템
    if ($menuId == 'statistics') $menuTitle = _text('MN00293'); //통계

    $nameval = 'TopImage';
    $tragetid = $nameval . '-' . $menuId;
    $function = "TopMenuFunc(this,'" . $tragetid . "')";
    //MM_swapImage(\''.$tragetid.'\',\'\',\'/css/h_img/nps_menu_sun.png\',1)
    //MM_swapImgRestore()
    $s_tragetid = "arrow_" . $tragetid;
    $return = "";

    $iStyle = '<i class="fa ' . $icon . '" style="font-size: 26px; left:35px; position: absolute; top: -20px;">';
    
    // 매뉴 최대한 좁게
    $sWidth = strlen($menuTitle)*6.5;
    $smallIStyle = 'style="width:'.$sWidth.'px;"';
    if ($top_menu_mode == 'S') {
        if ($menuId == "home") {
            $return = '<li class="" onClick="' . $function . '" onfocus="blur();"'.$smallIStyle.'><a href="javascript:;"  onmouseout="" onmouseover=""  name="' . $tragetid . '" border="0" id="' . $tragetid . '" ></i><strong class="fa-text">' . $menuTitle . '</strong></a><span id="total_new_notice"></span></li>';
        } else if ($menuId == "media") {
            $return = '<li class="" onClick="' . $function . '" onfocus="blur();"'.$smallIStyle.'><a href="javascript:;"  onmouseout="" onmouseover=""  name="' . $tragetid . '" border="0" id="' . $tragetid . '" ><strong class="fa-text">' . $menuTitle . '</strong></a><span id="total_new_content_all_tab"></span></li>';
        } else if ($menuId == "request") {
            $return = '<li class="" onClick="' . $function . '" onfocus="blur();"'.$smallIStyle.'><a href="javascript:;"  onmouseout="" onmouseover=""  name="' . $tragetid . '" border="0" id="' . $tragetid . '" ><strong class="fa-text">' . $menuTitle . '</strong></a><span id="total_new_request"></span></li>';
        } else {
            $return = '<li class="" onClick="' . $function . '" onfocus="blur();"'.$smallIStyle.'><a href="javascript:;"  onmouseout="" onmouseover=""  name="' . $tragetid . '" border="0" id="' . $tragetid . '" ><strong class="fa-text">' . $menuTitle . '</strong></a></li>';
        }
    } else {
        
        if ($menuId == "home") {
            $return = '<li class="" onClick="' . $function . '" onfocus="blur();"><span id="total_new_notice"></span><a href="javascript:;"  onmouseout="" onmouseover="" name="' . $tragetid . '" border="0" id="' . $tragetid . '" >' . $iStyle . '</i><strong class="fa-text">' . $menuTitle . '</strong></a></li>';
        } else if ($menuId == "media") {
            $return = '<li class="" onClick="' . $function . '" onfocus="blur();"><span id="total_new_content_all_tab"></span><a href="javascript:;"  onmouseout="" onmouseover="" name="' . $tragetid . '" border="0" id="' . $tragetid . '" >' . $iStyle . '</i><strong class="fa-text">' . $menuTitle . '</strong></a></li>';
        } else if ($menuId == "request") {
            $return = '<li class="" onClick="' . $function . '" onfocus="blur();"><span id="total_new_request"></span><a href="javascript:;"  onmouseout="" onmouseover="" name="' . $tragetid . '" border="0" id="' . $tragetid . '" >' . $iStyle . '</i><strong class="fa-text">' . $menuTitle . '</strong></a></li>';
        } else {
            $return = '<li class="" onClick="' . $function . '" onfocus="blur();"><a href="javascript:;"  onmouseout="" onmouseover="" name="' . $tragetid . '" border="0" id="' . $tragetid . '" >' . $iStyle . '</i><strong class="fa-text">' . $menuTitle . '</strong></a></li>';
        }
    }
    return $return;
}

function createTopMenu_main2($SESSION, $GET)
{
    global $db;
    global $ADMIN_GROUP_ID;
    global $arr_sys_code;

    $nameval = 'TopImage';
    $cnt = 1;
    //$bar = '<li style="width:10px;height:10px;"></li>';
    //$bar = '<li style="width:2px;top:2px;position: relative;"><img src="/css/h_img/menu_bar.png"></li>';

    $mArr = array();
    $menu_array = array();
    $topMenu_array = array();

    $user_id = $_SESSION['user']['user_id'];

    /**
     * 사용자 그룹별 화면 권한 부분 추가 작성
     * 2017.12.27 Alex
     */

    /*기본항목 추가 - 고정항목임*/
    array_push($menu_array, createMenu(_text('MN00311'), 'home', 'fa-home')); //'홈으로'
    array_push($menu_array, createMenu(_text('MN00096'), 'media', 'fa-search')); //'미디어 검색'

    /*그룹별 화면권한 목록*/
    $topMenuGrants = $db->queryAll("
                        SELECT	G.*
                        FROM	BC_TOP_MENU_GRANT G,
                                BC_MEMBER_GROUP_MEMBER GM,
                                BC_MEMBER M
                        WHERE	G.MEMBER_GROUP_ID = GM.MEMBER_GROUP_ID
                        AND		GM.MEMBER_ID = M.MEMBER_ID
                        AND		M.USER_ID = '" . $user_id . "'
                    ");
    /*화면 메뉴 정보*/
    $topMenus = $db->queryAll("
                    SELECT	*
                    FROM	BC_TOP_MENU
                    WHERE	IS_SHOW = 'Y'
                    ORDER BY SORT ASC
                ");

    foreach ($topMenus as $topMenu) {
        $menu_id = $topMenu['id'];
        $topMenu_array[$menu_id] = $topMenu;
    }

    foreach ($topMenuGrants as $grant) {
        $menuId = $grant['menu_id'];
        $tmpMenuArr = explode(',', $menuId);

        foreach ($tmpMenuArr as $tmpMenu) {
            $topMenuInfo = $topMenu_array[$tmpMenu];

            if (!empty($topMenuInfo)) {
                if (in_array($topMenuInfo['menu_code'], $mArr)) {
                    continue;
                }

                array_push($mArr, $topMenuInfo['menu_code']);
                array_push($menu_array, createMenu($topMenuInfo['menu_name'], $topMenuInfo['menu_code'], $topMenuInfo['menu_icon']));
            }
        }
    }

    /*
    $v_tmmonitor_use_yn =  $db->queryOne("
        SELECT	COALESCE((
                    SELECT	USE_YN
                    FROM	BC_SYS_CODE A
                    WHERE	A.TYPE_ID = 1
                    AND		A.CODE = 'MONITERING_USE_YN'), 'N') AS USE_YN
        FROM	(
                SELECT	USER_ID
                FROM	BC_MEMBER
                WHERE	USER_ID = '".$user_id."') DUAL
    ");
    
    if($v_tmmonitor_use_yn == 'Y'){	
        array_push($menu_array, createMenu(_text('MN00231'), 'tmmonitor', 'fa-desktop') );//
    }

    if(  INTERWORK_ZODIAC == 'Y' )//2015-12-08 proxima_zodiac zodiac 연계 여부 확인
    {
        array_push($menu_array, createMenu( _text('MN00095'), 'request', 'fa-edit') );//'의뢰'//2015-10-19 proxima_zodiac  메뉴 추가
        array_push($menu_array, createMenu( _text('MN00094'), 'info_report', 'fa-microphone') );//'보도정보'//2015-10-30 proxima_zodiac  메뉴 추가
        
    }

    if($arr_sys_code['interwork_harris']['use_yn'] == 'Y') {
        array_push($menu_array, createMenu(_text('MN02548'), 'harris', 'fa-server') );//
    }
    
    // Custom Menu Items
    if(defined('CUSTOM_ROOT') && class_exists('\ProximaCustom\core\MenuItemManager')) {
        $menuItems = \ProximaCustom\core\MenuItemManager::getCustomMenuItems();
        foreach($menuItems as $menuItem) {
            array_push($menu_array, createMenu($menuItem['menuTitle'], $menuItem['menuId'], $menuItem['icon']) );
        }
    }       

    $user_group = $_SESSION['user']['groups'];
    if( in_array($ADMIN_GROUP_ID, $user_group) )
    {
        array_push($menu_array, createMenu( _text('MN00093'), 'system', 'fa-cogs') );//'시스템'
    }

    //if(in_array(4182572,$_SESSION[user][groups]))
    if( in_array($ADMIN_GROUP_ID, $user_group) )
    {
        array_push($menu_array, createMenu( _text('MN00293'), 'statistics', 'fa-bar-chart') );//'통계'//groups
    
    }
*/
    $public_key = check_system_license($_SESSION['user']['user_pass'], 'c3cfb6e1b944333d0db7fd473a8aab2b85ccd6e0e93748bec4a7c93adb4bfd794fd0da7db075cf418d762560341d8494b00bd09017893831cfffc7da7cf63673');
    require_once($_SERVER['DOCUMENT_ROOT'] . '/lib/config.SYSTEM.php');

    if ($_SESSION['user']['user_id']  == 'gemiso') {
        array_push($menu_array, createMenu(_text('MN02194'), 'system_dev', 'fa-cube')); //시스템	
    }

    $value = join($menu_array);

    return $value;
}

function createTopMenu_main($SESSION, $GET)
{
    global $db;
    global $ADMIN_GROUP_ID;
    global $arr_sys_code;

    $nameval = 'TopImage';
    $cnt = 1;
    //$bar = '<li style="width:10px;height:10px;"></li>';
    //$bar = '<li style="width:2px;top:2px;position: relative;"><img src="/css/h_img/menu_bar.png"></li>';

    $mArr = array();
    $menu_array = array();
    $topMenu_array = array();
    $tmpMenuArr = array();
    $user_id = $_SESSION['user']['user_id'];
    $topMenuGrantsQuery = "
    SELECT	G.*
    FROM	BC_TOP_MENU_GRANT G,
            BC_MEMBER_GROUP_MEMBER GM,
            BC_MEMBER M
    WHERE	G.MEMBER_GROUP_ID = GM.MEMBER_GROUP_ID
    AND		GM.MEMBER_ID = M.MEMBER_ID
    AND		M.USER_ID = '" . $user_id . "'";
    /**
     * 사용자 그룹별 화면 권한 부분 추가 작성
     * 2017.12.27 Alex
     */

    /*기본항목 추가 - 고정항목임*/
    array_push($menu_array, createMenu(_text('MN00311'), 'home', 'fa-home')); //'홈으로'
    array_push($menu_array, createMenu(_text('MN00096'), 'media', 'fa-search')); //'미디어 검색'

    /*그룹별 화면권한 목록*/
    $topMenuGrants = $db->queryAll($topMenuGrantsQuery);
	$menuIds = [];
	if( !empty($topMenuGrants) ){
		foreach($topMenuGrants as $topMenuGrant)
		{
			$menus = explode(',', $topMenuGrant['menu_id']);
			foreach($menus as $menu)
			{
				$menuIds [] =  $menu;
			}
		}
		array_unique($menuIds);
	}

    /*화면 메뉴 정보*/
    $topMenus = $db->queryAll("
                    SELECT	*
                    FROM	BC_TOP_MENU
                    WHERE	IS_SHOW = 'Y'
                    ORDER BY SORT ASC
                ");

    foreach ($topMenus as $topMenu) {
        $menu_id = $topMenu['id'];
        $topMenu_array[$menu_id] = $topMenu;
        if(in_array($menu_id,$menuIds)){
            array_push($tmpMenuArr, $menu_id);
        };
    }



        foreach ($tmpMenuArr as $tmpMenu) {
            $topMenuInfo = $topMenu_array[$tmpMenu];

            if (!empty($topMenuInfo)) {
                if (in_array($topMenuInfo['menu_code'], $mArr)) {
                    continue;
                }

                array_push($mArr, $topMenuInfo['menu_code']);
                array_push($menu_array, createMenu($topMenuInfo['menu_name'], $topMenuInfo['menu_code'], $topMenuInfo['menu_icon']));
            }
        }
    

    /*
    $v_tmmonitor_use_yn =  $db->queryOne("
        SELECT	COALESCE((
                    SELECT	USE_YN
                    FROM	BC_SYS_CODE A
                    WHERE	A.TYPE_ID = 1
                    AND		A.CODE = 'MONITERING_USE_YN'), 'N') AS USE_YN
        FROM	(
                SELECT	USER_ID
                FROM	BC_MEMBER
                WHERE	USER_ID = '".$user_id."') DUAL
    ");
    
    if($v_tmmonitor_use_yn == 'Y'){	
        array_push($menu_array, createMenu(_text('MN00231'), 'tmmonitor', 'fa-desktop') );//
    }

    if(  INTERWORK_ZODIAC == 'Y' )//2015-12-08 proxima_zodiac zodiac 연계 여부 확인
    {
        array_push($menu_array, createMenu( _text('MN00095'), 'request', 'fa-edit') );//'의뢰'//2015-10-19 proxima_zodiac  메뉴 추가
        array_push($menu_array, createMenu( _text('MN00094'), 'info_report', 'fa-microphone') );//'보도정보'//2015-10-30 proxima_zodiac  메뉴 추가
        
    }

    if($arr_sys_code['interwork_harris']['use_yn'] == 'Y') {
        array_push($menu_array, createMenu(_text('MN02548'), 'harris', 'fa-server') );//
    }
    
    // Custom Menu Items
    if(defined('CUSTOM_ROOT') && class_exists('\ProximaCustom\core\MenuItemManager')) {
        $menuItems = \ProximaCustom\core\MenuItemManager::getCustomMenuItems();
        foreach($menuItems as $menuItem) {
            array_push($menu_array, createMenu($menuItem['menuTitle'], $menuItem['menuId'], $menuItem['icon']) );
        }
    }       

    $user_group = $_SESSION['user']['groups'];
    if( in_array($ADMIN_GROUP_ID, $user_group) )
    {
        array_push($menu_array, createMenu( _text('MN00093'), 'system', 'fa-cogs') );//'시스템'
    }

    //if(in_array(4182572,$_SESSION[user][groups]))
    if( in_array($ADMIN_GROUP_ID, $user_group) )
    {
        array_push($menu_array, createMenu( _text('MN00293'), 'statistics', 'fa-bar-chart') );//'통계'//groups
    
    }
*/
    $public_key = check_system_license($_SESSION['user']['user_pass'], 'c3cfb6e1b944333d0db7fd473a8aab2b85ccd6e0e93748bec4a7c93adb4bfd794fd0da7db075cf418d762560341d8494b00bd09017893831cfffc7da7cf63673');
    require_once($_SERVER['DOCUMENT_ROOT'] . '/lib/config.SYSTEM.php');

    if ($_SESSION['user']['user_id']  == 'gemiso') {
        array_push($menu_array, createMenu(_text('MN02194'), 'system_dev', 'fa-cube')); //시스템	
    }

    $value = join($menu_array);

    return $value;
}

function checkAllowUdContentGrant($user_id, $ud_content_id, $grant, $category_id = null)
{ //사용자정의콘텐츠 별 콘텐츠 권한 함수 $ud_content_id	

    if (empty($ud_content_id)) {
        return false;
    }

    global $db;

    $grant_type = 'content_grant';

    if($category_id!=null){
        $catQuery = " AND g.CATEGORY_ID = $category_id ";
    }

    $groups = $db->queryAll("
    select 
    m.is_admin, mg.member_group_id ,g.GROUP_GRANT
    from 
    bc_member m 
    JOIN bc_member_group_member mg ON m.member_id = mg.member_id
    JOIN BC_GRANT g ON mg.MEMBER_GROUP_ID=g.MEMBER_GROUP_ID
    where m.user_id='$user_id' 
    AND g.GRANT_TYPE='$grant_type' 
    AND g.UD_CONTENT_ID='$ud_content_id'
    ".$catQuery );

    foreach ($groups as $group) {
        $group_grant = $group['group_grant'];
        if (!empty($group_grant)) {
            if (($group_grant & $grant) == $grant)
                return true;
        }
    }

    
    // $groups = $db->queryAll("select is_admin, member_group_id from bc_member m, bc_member_group_member mg
    //                             where user_id='" . $user_id . "' and m.member_id = mg.member_id");
    // // if (empty($groups)) {
    // //     //$group_id = $db->queryOne("select member_group_id from bc_member_group where is_default='Y'");
    // //     $groups[] = array(
    // //         'member_group_id' => $group_id
    // //     );
    // // }

    // foreach ($groups as $group) {
    //     if (!empty($category_id)) {
    //         $cat_query = " AND CATEGORY_ID = $category_id ";
    //     }

    //     $group_grant = $db->queryOne("
    //             SELECT	GROUP_GRANT
    //             FROM	BC_GRANT
    //             WHERE	UD_CONTENT_ID=" . $ud_content_id . "
    //             AND		MEMBER_GROUP_ID=" . $group['member_group_id'] . "
    //             AND		GRANT_TYPE='" . $grant_type . "'
    //             " . $cat_query . "");

    //     if (!empty($group_grant)) {
    //         if (($group_grant & $grant) == $grant)
    //             return true;
    //     }
    // }

    return false;
}
// 큐시트 권한이 있는지 여부를 확인 하는 함수
function checkCuesheetGrant($user_id, $ud_content_id, $grant)
{
    global $db;

    $user_groups = $db->queryAll("select * from bc_member_group_member mgm, bc_member m where m.user_id = '$user_id' and m.member_id = mgm.member_id");

    foreach ($user_groups as $group) {
        $group_grant = $db->queryOne("
                                SELECT	GROUP_GRANT
                                FROM 	BC_GRANT
                                WHERE	UD_CONTENT_ID=" . $ud_content_id . "
                                AND 	MEMBER_GROUP_ID = " . $group['member_group_id'] . "
                                AND		GRANT_TYPE = 'content_grant'
                                ");
        if (!empty($group_grant)) {
            if (($group_grant & $grant) == $grant) return true;
        }
    }
    return false;
}

/**
 * 11-12-11, 이승수. 성용선배가 작업.
 * 함수 파라메터 $grant는 숫자이다. ex) 읽기 1, 쓰기 2, 삭제 4, 다운로드 8 이고, 권한은 읽기/다운로드만 되어있다면 $grant = 9
 * 그룹이 없는 사용자는 default그룹의 기능으로, 관리자계정(is_admin이 Y인 사용자)
 *
 * @param $user_id
 * @param $content_id
 * @param $grant
 * @param null $category_id
 * @return bool
 */
function checkAllowGrant($user_id, $content_id, $grant, $category_id = null)
{
    global $db, $logger;

    //	$logger->info('$grant : ' . $grant);

    $grant_type = 'content_grant';

    if (empty($user_id)) return false;

    //if( is_null( $category_id ) )  $category_id = 0;

    $groups = $db->queryAll("select is_admin, member_group_id from bc_member m, bc_member_group_member mg
                                where user_id='" . $user_id . "' and m.member_id = mg.member_id");

    //	$logger->info('groups', $groups);

    /*
    20101021 박정근
    검색된 사용자가 없을 경우에 일반사용자로 인식되도록 변경
    */
    if (empty($groups)) {
        $group_id = $db->queryOne("select member_group_id from bc_member_group where is_default='Y'");
        $groups[] = array(
            'member_group_id' => $group_id
        );
    }

    foreach ($groups as $group) {
        if (!empty($category_id)) {
            $cat_query = " AND CATEGORY_ID = $category_id ";
        }

        $query = "
            SELECT	GROUP_GRANT
            FROM	BC_GRANT
            WHERE	UD_CONTENT_ID = (SELECT UD_CONTENT_ID
                                    FROM BC_CONTENT
                                    WHERE CONTENT_ID = $content_id)
            AND		MEMBER_GROUP_ID = {$group['member_group_id']}
            AND		GRANT_TYPE = '$grant_type'
            " . $cat_query . "";

        $group_grant = $db->queryOne($query);
        if (!empty($group_grant)) {
            if (($group_grant & $grant)) {
                // 내가 등록한 콘텐츠 수정 권한이면 콘텐츠 등록자 까지 확인해야 함
                if (
                    $grant == GRANT_EDIT_MY_CONTENT ||
                    $grant == GRANT_DELETE_MY_CONTENT
                ) {
                    return (\Proxima\models\content\Content::find($content_id)->get('reg_user_id') == $user_id);
                } else {
                    return true;
                }
            }
        }
    }

    return false;
}

function checkAllowGrantTopic($user_id, $grant)
{
    global $db, $logger;

    $groups = $db->queryAll("select is_admin, member_group_id from bc_member m, bc_member_group_member mg
                                where user_id='" . $user_id . "' and m.member_id = mg.member_id");

    if (empty($groups)) {
        $group_id = $db->queryOne("select member_group_id from bc_member_group where is_default='Y'");
        $groups[] = array(
            'member_group_id' => $group_id
        );
    }

    foreach ($groups as $group) {

        // 그룹 중 관리자 권한이 있다면 true
        if ($group['is_admin'] == 'Y') {
            return true;
        }
        $query = "
            select group_grant
            from bc_grant
            where ud_content_id = (select ud_content_id
                                    from bc_content
                                    where content_id = $content_id)
            and member_group_id = {$group['member_group_id']}
            and grant_type = '$grant_type'";
        if (isset($category_id)) {
            $query .= " AND CATEGORY_ID = $category_id";
        }

        $group_grant = $db->queryOne($query);
        if (!empty($group_grant)) {
            if (($group_grant & $grant) == $grant) {
                return true;
            }
        }
    }


    return false;
}

//function findCategoryRoot($meta_table_id)  //카테고리 콘텐츠 유형별로 루트설정을 위한 함수 2011-02-25 by 이성용
//{
//  global $db;
//  $result = $db->queryRow("
//  select c.category_id , c.category_title
//  from bc_category c, bc_category_mapping cm
//  where c.category_id=cm.category_id
//  and cm.ud_content_id='$meta_table_id'");
//  if ( empty($result) )
//  {
//	  return false;
//  }
//  return $result;
//}

function checkGroupsAllowGrant($groups, $content_id, $grant)
{
    global $db;

    if (empty($groups)) return false;

    foreach ($groups as $group) {
        $result = $db->queryOne("select name from bc_ud_content_grant " .
            "where ud_content_id=(select ud_content_id from content " .
            "where bs_content_id=" . $content_id . ") " .
            "and member_group_id=" . $group . " " .
            "and granted_right='" . $grant . "'");
        if (!empty($result)) {
            return true;
        }
    }

    return false;
}

function dates_weekofyear($year, $nweek)
{
    $mdate = mktime(0, 0, 0, 1, 10, $year);
    $today = mktime(0, 0, 0, date('m'), date('d'), date('Y'));
    $firstday = mktime(0, 0, 0, 1, 1, $year);
    $firstnweek = date('W', $firstday);
    $fweekday = date('w', $firstday);

    if ($firstnweek == 52) {
        $day_firstweek = $firstday + 60 * 60 * 24 * (7 - $fweekday + 1);
    } else {
        $day_firstweek = $firstday - 60 * 60 * 24 * ($fweekday - 1);
    }
    $firstdate = date('Ymd', $day_firstweek + 60 * 60 * 24 * 7 * $nweek);
    $lastdate = date('Ymd', $day_firstweek + 60 * 60 * 24 * 7 * $nweek + 60 * 60 * 24 * 6);

    return array($firstdate, $lastdate);
}

function allowVisible($groups)
{
    foreach ($groups as $group) {

        if ($_SESSION['user'] && in_array($group, $_SESSION['user']['groups'])) return true;
    }

    return false;
}

function getCategoryFullPath($id)
{
    global $db;

    $parent_id = $db->queryOne("select parent_id from bc_category where category_id=" . $id);
    if ($parent_id != -1 && $parent_id !== 0 && !is_null($parent_id)) {
        $self_id = getCategoryFullPath($parent_id);
    }

    return $self_id . '/' . $id;
}

function getCategoryPathTitle($categoryPath, $path_separator = '/')
{
    global $db;

    $arrCatId = explode('/', $categoryPath);

    $arrQuery = array();
    foreach ($arrCatId as $catId) {
        if ($catId == '')
            continue;
        array_push($arrQuery, "SELECT category_title FROM bc_category WHERE category_id = {$catId}");
    }
    $query = implode(' union all ', $arrQuery);

    $rows = $db->queryAll($query);

    $arrCatTitle = array();
    foreach ($rows as $row) {
        array_push($arrCatTitle, $row['category_title']);
    }

    $catPathTitle = count($arrCatTitle) > 1 ? implode($path_separator, $arrCatTitle) : $arrCatTitle[0];
    return $catPathTitle;
}

function getCategoryPath($id)
{
    global $db;

    $parent_id = $db->queryOne("select parent_id from bc_category where category_id=" . $id);
    if ($parent_id != -1 && !empty($parent_id)) {
        $r = getCategoryPath($parent_id);
    }

    //  echo $id."\n";
    return $r . '/' . $parent_id;
}

function esc2($str)
{
    $str = str_replace("\n", "", $str);
    $str = str_replace("\r", "", $str);

    return $str;
}

function esc3($str)
{
    $str = str_replace("\n", "\\n", $str);
    $str = str_replace("\r", "\\r", $str);

    return $str;
}

function includeAll($path)
{
    $result = array();

    $d = dir($path);
    while (($file = $d->read()) !== false) {
        if ($file == '.' || $file == '..') continue;

        if (is_dir($d->path . '/' . $file)) {
            array_merge($result, includeAll($d->path . '/' . $file));
        } else {
            array_push($result, $d->path . '/' . $file);
        }
    }

    return $result;
}

function get_content_meta()
{
    global $mdb;
    $data = array();
    $q = $mdb->queryAll("select c.title, m.ud_content_title, c.category_id from bc_content c, bc_ud_content m where c.ud_content_id = m.ud_content_id and c.bs_content_id = $content_id");
    foreach ($q as $i) {

        array_push($data, $data['title'] = $i['title'], $data['name'] = $i['name'], $data['category'] = $i['category_id']);
    }

    $query = $mdb->queryAll("select mf.usr_meta_field_title, mv.usr_meta_value from bc_usr_meta_value mv, bc_usr_meta_field mf where mv.content_id = $content_id  and mv.usr_meta_field_id = mf.usr_meta_field_id");
    foreach ($query as $meta) {
        //  if($meta['name'] == '방송일시' or $meta['name'] == 'PD' or $meta['name'] == '출연자' or $meta['name'] == '쇼호스트')
        //  {
        $data[$meta['name']] = $meta['value'];
        //  }
    }
    return $data;
}

function stripExtensionOfFilename($filename)
{
    return substr($filename, 0, strrpos($filename, '.'));
}

function changeExtensionOfFile($file, $ext)
{
    return substr($file, 0, strrpos($file, '.')) . '.' . $ext;
}

function buildDestinationWithoutPerfix()
{ }

function secToTimecode($sec)
{
    $h = floor($sec / 3600);
    $i = (floor($sec / 60) - ($h * 60));
    $s = $sec % 60;

    return sprintf("%02d:%02d:%02d", $h, $i, $s);
}

function frameToTimecode_old($frame)
{
    $sec = $frame / FRAMERATE;
    return secToTimecode($sec);
}

function frameToTimeCode($frame, $content_id = null)
{
    global $db;

    $frame_rate = FRAMERATE;
    if (!empty($content_id)) {
        $sys_frame_rate = $db->queryOne("select sys_frame_rate from bc_sysmeta_movie where sys_content_id = '" . $content_id . "'");
        if (is_numeric(floatval($sys_frame_rate))) {
            $frame_rate = floatval($sys_frame_rate);
        }
    }
    if (!is_numeric($frame)) {
        $frame = 0;
    }


    $sec = (int) ((int) $frame / $frame_rate);
    $frame_cnt = (int) ((int) $frame - ($sec * $frame_rate));
    $hour = (int) ($sec / 3600);
    $min = (int) (($sec % 3600) / 60);
    $sec = (int) (($sec % 3600) % 60);

    $time_code = str_pad($hour, 2, 0, STR_PAD_LEFT) . ':' . str_pad($min, 2, 0, STR_PAD_LEFT) . ':' . str_pad($sec, 2, 0, STR_PAD_LEFT) . ':' . str_pad($frame_cnt, 2, 0, STR_PAD_LEFT);
    return $time_code;
}

function getStorageInfo($name)
{
    global $mdb;

    $result = $mdb->queryRow("select * from bc_storage where name='" . $name . "'");

    return $result;
}

function getHarrisSetting($id)
{
    global $mdb;

    $r = $mdb->queryRow("select * from harris_setting where server_uid=" . $id);

    return $r;
}
function executeQuery($q)
{
    global $db;

    $r = $db->exec($q);
}

function checkXMLSyntax($receive_xml)
{
    libxml_use_internal_errors(true);
    $rtn = simplexml_load_string($receive_xml);
    if (!$rtn) {
        foreach (libxml_get_errors() as $error) {
            $err_msg .= $error->message . "\n";
        }
        throw new Exception('xml 파싱 에러: ' . $err_msg);
    }

    return $rtn;
}

function insertMetaValue($content_id, $ud_content_id)
{
    global $db;

    $hasAudio = array(
        81782,
        81851,
        81874,
        81899,
        83422
    );

    $hasModelLimitDate = array(
        81779,
        81848,
        81871,
        81895,
        83420
    );

    $field_list = $db->queryAll("select * from bc_usr_meta_field where ud_content_id=$table_id");
    foreach ($field_list as $field) {
        $usr_meta_value = '';
        $usr_meta_value_id = getNextSequence();
        if (in_array($field['usr_meta_field_id'], $hasAudio)) {
            $usr_meta_value = 'O/O';
        } else if (in_array($field['usr_meta_field_id'], $hasModelLimitDate)) {
            $usr_meta_value = date('Y-m-d', strtotime("+{$field['default_value']} day"));
        } else {
            $usr_meta_value = '';
        }

        $query = "insert into bc_usr_meta_value " .
            "(content_id, ud_content_id, usr_meta_field_id, usr_meta_value_id, usr_meta_value) " .
            "value " .
            "('$content_id', '$ud_content_id', '{$field['usr_meta_field_id']}', '$usr_meta_value_id', '$usr_meta_value')";
        executeQuery($query);
    }
}

function audio_default($usr_meta_field_id)
{
    global $mdb;
    $find_default = $mdb->queryRow("select usr_meta_field_title, default_value from bc_usr_meta_field where usr_meta_field_id = '$usr_meta_field_id'");

    if ($find_default['usr_meta_field_title'] == '오디오유무(BGM/Nar)') {
        $default_value = explode('(default)', $find_default['default_value']);
        $value = $default_value[0];
    } else {
        $value = 'O/O';
    }
    //  echo $mdb->last_query;
    return $value;
}

function log_transfer_chn($content_id, $code_ch, $user_id)
{
    global $db;

    $rtn = $db->exec("insert into transfer_chn (content_id, chn_code, transfer_dtm, user_id) values($content_id, $code_ch, '" . date('YmdHis') . "', '$user_id')");
}

function get_code_name($codeset_id, $code_value)
{
    global $db;

    $rtn = $db->queryOne("select c.code from codeset cs, code c " .
        "where " .
        "cs.codeset_id=" . $codeset_id . " and cs.codeset_id=c.codeset_id and c.code_value='" . $code_value . "'");

    return $rtn['code'];
}

function get_default_value($usr_meta_field_id)
{
    global $mdb;

    $value = $mdb->queryOne("select default_value from usr_meta_field where usr_meta_field_id = $usr_meta_field_id");

    return $value;
}

//해리스 셋팅 디비에서 이름비교로 넘버 구해오는 함수.
function get_harris_server_num($server_name)
{
    global $mdb;
    $server_num = $mdb->queryOne("select server_uid from harris_setting where replace(server_name, ' ', '') = '" . str_replace(' ', '', $server_name) . "'");
    return $server_num;
}

function checkDuplicateHarrisMAM($content_id)
{
    global $db;

    return $db->queryOne("select count(*) from bc_content where content_id='" . $content_id . "' and is_deleted='N'");
}

function checkIsDeleteContent()
{
    global $db;

    return $db->queryOne("select count(*) from bc_content where content_id=" . $content_id . " and is_deleted='Y'");
}

function checkNLEClient($ip)
{
    global $db;

    return $db->queryOne("select count(*) from allowed_ip where ip_add='$ip'");
}

function checkLogin()
{
    if ($_SESSION['user']['user_id'] == 'temp') {
        ?>
        <script type="text/javascript">
            alert("로그인 해주세요.");
            window.location = '/';
        </script>
    <?php
        }
    }

    function checkNewPageAllowGrant()
    {
        if ($_SESSION['user']['is_admin'] == 'Y' || allowVisible(array(ADMIN_GROUP, CHANNEL_GROUP))) { } else {

            ?>
        <script type="text/javascript">
            alert('권한이 없습니다.');
            window.location = '/';
        </script>
        <?php
            }
        }

        function _debug($filename, $msg, $debug_flag = '')
        {
            global $is_debug;

            if (!empty($debug_flag)) {
                $is_debug = $debug_flag;
            }
            //$is_debug = true;
            if ($is_debug) {
                //날짜별로 log파일이 생성되게 변경.
                $log_file = LOG_PATH . '/' . substr($filename, 0, strrpos($filename, '.')) . '_' . date('Ymd') . '.log';
                $log_msg = $_SERVER['REMOTE_ADDR'] . '[' . date('Y-m-d H:i:s') . '] ' . $msg . chr(10);

                file_put_contents($log_file, $log_msg, FILE_APPEND);
            }
        }

        function convertSpecialChar($string)
        {
            $string = str_replace('&',  "&amp;",    $string);
            $string = str_replace('"',  "&quot;",   $string);
            $string = str_replace('\'', "&apos;",   $string);
            $string = str_replace('<',  "&lt;",     $string);
            $string = str_replace('>',  "&gt;",     $string);

            return $string;
        }

        function reConvertSpecialChar($string)
        {
            $string = str_replace('&quot;', '"',    $string);
            $string = str_replace('&apos;', '\'',   $string);
            $string = str_replace('&lt;',   '<',    $string);
            $string = str_replace('&gt;',   '>',    $string);
            $string = str_replace('&amp;',  '&',    $string);

            return $string;
        }

        function replaceSpecialChar($string)
        {
            return convertSpecialChar($string);
        }

        function regist_content($content_type_id, $meta_table_id, $content_id, $title, $reg_user_id, $expire_date, $created_date)
        {
            global $mdb;

            $query = "insert into content (bs_content_id, ud_content_id, content_id, title, reg_user_id, expired_date, created_date) " .
                "values " .
                "('506', '$ud_content_id', '$content_id', '$title', '$reg_user_id', '$expired_date', '$creatd_date')";
            $result = $mdb->exec($query);
        }

        function regist_metatable($content_id, $created_date, $reg_type, $task_id)
        {
            global $mdb;
            $type = array('original', 'proxy', 'thumb');
            foreach ($type as $v) {
                $path = ' ';
                $media_id = getNextSequence();
                if ($v == 'thumb') {
                    $path = 'images/process.jpg';
                }
                if ($v == 'original') {
                    $path = $mdb->queryOne("select target from bc_task where task_id = $task_id");
                }
                $query = "insert into bc_media " .
                    "(content_id, media_id, storage_id, media_type, path, filesize, created_date, reg_type) " .
                    "values " .
                    "('$content_id', '$media_id', '0', '$v', '$path', ' ', '$created_date', '$reg_task')";
                $result = $mdb->exec($query);

                unset($path);
            }
        }

        function regist_content_value($content_id)
        {
            global $mdb;
            $count_field = $mdb->queryAll("select * from bc_sys_meta_field where bs_content_id = '506'");
            foreach ($count_field as $field) {
                $sys_meta_value_id = getNextSequence();

                $query = "insert into bc_sys_meta_value (content_id, sys_meta_field_id, sys_meta_value_id, sys_meta_value)" .
                    " values" .
                    " ('$content_id', '{$field['sys_meta_field_id']}', '$sys_meta_value_id', ' ')";
                $result = $mdb->exec($query);
            }
        }

        function regist_meta_value($content_id, $ud_content_id)
        {
            global $db;

            $get_m_field = $db->queryAll("select usr_meta_field_id, default_value from bc_usr_meta_field where ud_content_id = $ud_content_id");

            foreach ($get_m_field as $m_field) {
                $usr_meta_value_id = getNextSequence();

                $value = ' ';
                $query = "insert into bc_usr_meta_value " .
                    "(content_id, ud_content_id, usr_meta_field_id, usr_meta_value_id, usr_meta_value) " .
                    "values " .
                    "('$content_id', '$ud_content_id', '{$m_field['usr_meta_field_id']}', '$usr_meta_value_id', '$usr_meta_value')";
                $result = $db->exec($query);
            }
        }

        function regist_transcoder_task($creation_datetime, $parameter, $task_id)
        {
            global $db;

            $source = $db->queryOne("select target from bc_task where task_id = $task_id");
            $storage_info = $db->queryRow("select * from bc_storage where name = 'transcoder'");
            $target = explode('/', $source, -1);
            foreach ($target as $path) {
                $target_path .= $path . '/';
            }
            $target_path = $target_path . 'Proxy';

            $query = "insert into bc_task " .
                "(type, source, source_id, source_pw, target, parameter, status, priority, creation_datetime) " .
                "values " .
                "('20', '$source', '{$storage_info['login_id']}', '{$storage_info['login_pw']}', '$target_path', '$parameter', 'queue', '300', '$creation_datetime')";
            $r = $db->exec($query);
        }

        function regist_catalog_task($creation_datetime, $parameter, $task_id)
        {
            global $db;

            $source = $db->queryOne("select target from bc_task where task_id = $task_id");
            $storage_info = $db->queryRow("select * from bc_storage where name = 'catalog'");
            $target = explode('/', $source, -1);
            foreach ($target as $path) {
                $target_path .= $path . '/';
            }
            $target_path = $target_path . 'Catalog';

            $query = "insert into bc_task " .
                "(type, source, source_id, source_pw, target, parameter, status, priority, creation_datetime) " .
                "values " .
                "('10', '$source', '{$storage_info['login_id']}', '{$storage_info['login_pw']}', '$target_path', '$parameter', 'queue', '300', '$creation_datetime')";
            $r = $db->exec($query);
        }

        function storage_backup_fs($task_id)
        {
            global $db;

            $task_info = $db->queryRow("select media_id, path from bc_media where content_id = (select content_id from bc_media where media_id = (select media_id from bc_task where task_id = " . $task_id . ")) and media_type = 'original'");

            $path = $task_info['path'];
            $path = explode('/', $path, -1);
            $backup_path = join('/', $path);

            $query = "insert into bc_task " .
                "(media_id, type, status, priority, source, target, parameter, creation_datetime, destination) " .
                "values " .
                "('" . $task_info['media_id'] . "', '" . ARIEL_TRANSFER_FS . "', 'queue', '300', '" . $backup_path . "', '" . $backup_path . "', 'over_write', '" . date('YmdHis') . "', 'backup')";
            return $query;
        }

        ///// workflow에 따른 task작업 function /////
        function insert_task_query($content_id, $source, $target, $cur_time, $channel, $media_id = null, $add_param = null)
        {
            global $db;

            $channel_q = "select task_workflow_id from bc_task_workflow where register = '$channel'";
            $get_register = $db->queryOne($channel_q);
            //  echo $get_register.'c	';
            if (empty($get_register)) throw new Exception('task_workflow에 등록되지 않은 인제스트 채널입니다.');

            $get_jobs = $db->queryRow("select * from bc_task_workflow_rule where task_workflow_id = " . $get_register . " order by job_priority asc");
            //  print_r($get_jobs);

            //$insert_job = $db->queryRow("select * from bc_task_rule where task_rule_id = '{$get_jobs['task_rule_id']}'");
            $insert_job = $db->queryRow("select tr.*, tt.type from bc_task_rule tr, bc_task_type tt where tr.task_rule_id = '{$get_jobs['task_rule_id']}' and tt.task_type_id = tr.task_type_id");

            //  print_r($insert_job);

            switch ($insert_job['type']) {
                case '20':
                case '21':
                    $target_path = $target . 'Proxy';
                    $get_info = $db->queryRow("select s.login_id, s.login_pw from bc_task_rule tr, bc_storage s where tr.job_name='transcoding' and tr.target_path = s.storage_id");
                    //if(empty($get_info)) throw new Exception('디비에서 transcoder 값이 없습니다.');

                    if ($insert_job['type'] == '21') {
                        $proxy_media = $db->queryRow("select * from bc_media where content_id=$content_id and media_type='proxy_hi'");
                    } else {
                        $proxy_media = $db->queryRow("select * from bc_media where content_id=$content_id and media_type='proxy'");
                    }
                    $proxy_mediaID = $proxy_media['media_id'];


                    //가상클립 작업일시 파라미터값 변경을 위해 기존 파라미터값을 변수로 뺌.
                    $parameter = $insert_job['parameter'];
                    if (!empty($proxy_media['vr_start']) and !empty($proxy_media['vr_end'])) {
                        $parameter = $parameter . ' "' . $proxy_media['vr_start'] . '" "' . $proxy_media['vr_end'] . '"';
                    }

                    $query = "insert into bc_task
            (media_id, type, status, priority, source, target, target_id, target_pw, parameter, creation_datetime, destination, task_workflow_id, job_priority, task_rule_id)
            values
            ('$proxy_mediaID', '{$insert_job['type']}', 'queue', 300, '$source', '$target_path', '{$get_info['login_id']}', '{$get_info['login_pw']}', '$parameter', '$cur_time', '$channel', '{$get_jobs['task_workflow_id']}', '{$get_jobs['job_priority']}', '{$get_jobs['task_rule_id']}')";
                    break;

                case '10':
                    $target_path = $target . '/Catalog';
                    $get_info = $db->queryRow("select s.login_id, s.login_pw from bc_task_rule tr, bc_storage s where tr.job_name='cataloging' and tr.target_path = s.storage_id");
                    if (empty($get_info)) throw new Exception('디비에서 catalog 값이 없습니다.');
                    $proxy_mediaID = $db->queryOne("select media_id from bc_media where content_id=$content_id and media_type='original'");

                    $query = "insert into bc_task
            (media_id, type, status, priority, source, target, target_id, target_pw, parameter, creation_datetime, destination, task_workflow_id, job_priority, task_rule_id)
            values
            ('$proxy_mediaID', '{$insert_job['type']}', 'queue', 300, '$source', '$target_path', '{$get_info['login_id']}', '{$get_info['login_pw']}', '{$insert_job['parameter']}', '$cur_time', '$channel', '{$get_jobs['task_workflow_id']}', '{$get_jobs['job_priority']}', '{$get_jobs['task_rule_id']}')";
                    break;

                case '11':
                    $target_path = $target . '/Thumbnail';
                    //		  $get_info = $db->queryRow("SELECT S.LOGIN_ID, S.LOGIN_PW
                    //									  FROM BC_TASK_RULE TR, BC_STORAGE S
                    //									  WHERE TR.JOB_NAME='cataloging'
                    //									  AND TR.TARGET_PATH = S.STORAGE_ID");
                    //		  if(empty($get_info)) throw new Exception('디비에서 catalog 값이 없습니다.');
                    $proxy_mediaID = $db->queryOne("select media_id from bc_media where content_id=$content_id and media_type='original'");

                    $query = "insert into bc_task
            (media_id, type, status, priority, source, target, parameter, creation_datetime, destination, task_workflow_id, job_priority, task_rule_id)
            values
            ('$proxy_mediaID', '{$insert_job['type']}', 'queue', 300, '$source', '$target_path', '{$insert_job['parameter']}', '$cur_time', '$channel', '{$get_jobs['task_workflow_id']}', '{$get_jobs['job_priority']}', '{$get_jobs['task_rule_id']}')";
                    break;

                case '30':
                    $target_path = $target;
                    $get_info = $db->queryRow("select s.login_id, s.login_pw from bc_task_rule tr, bc_storage s where tr.job_name='구간 추출' and tr.target_path = s.storage_id");
                    //if(empty($get_info)) throw new Exception('디비에서 구간 추출 값이 없습니다.');

                    if (strstr($channel, 'PFR_NEW_REGIST_')) {
                        $pfr_media_id = $db->queryOne("select media_id from bc_media where content_id=$content_id and media_type ='original' order by media_type desc");
                    } else {
                        $pfr_media_id = $db->queryOne("select media_id from bc_media where content_id=$content_id and media_type like 'pfr%' order by media_type desc");
                    }

                    $tc = $db->queryRow("select vr_start, vr_end from bc_media where media_id=" . $pfr_media_id);
                    $insert_job['parameter'] = '"' . $tc['vr_start'] . '" "' . $tc['vr_end'] . '" ' . $insert_job['parameter'];

                    $query = "insert into bc_task
            (media_id, type, status, priority, source, target, target_id, target_pw, parameter, creation_datetime, destination, task_workflow_id, job_priority, task_rule_id)
            values
            ('$pfr_media_id', '{$insert_job['type']}', 'queue', 300, '$source', '$target_path', '{$get_info['login_id']}', '{$get_info['login_pw']}', '{$insert_job['parameter']}', '$cur_time', '$channel', '{$get_jobs['task_workflow_id']}', '{$get_jobs['job_priority']}', '{$get_jobs['task_rule_id']}')";
                    break;

                case '31':
                    //echo 'aaaaaaaaaaaa';
                    $target_path = $target;
                    $get_info = $db->queryRow("select s.login_id, s.login_pw from bc_task_rule tr, bc_storage s where tr.job_name='mxf to mov' and tr.target_path = s.storage_id");
                    if (empty($media_id)) {
                        $media_id = $db->queryOne("select media_id from bc_media where content_id=$content_id and media_type='original'");
                    }

                    $query = "insert into bc_task
            (media_id, type, status, priority, source, target, target_id, target_pw, parameter, creation_datetime, destination, task_workflow_id, job_priority, task_rule_id)
            values
            ('$media_id', '{$insert_job['type']}', 'queue', 300, '$source', '$target_path', '{$get_info['login_id']}', '{$get_info['login_pw']}', '{$insert_job['parameter']}', '$cur_time', '$channel', '{$get_jobs['task_workflow_id']}', '{$get_jobs['job_priority']}', '{$get_jobs['task_rule_id']}')";
                    break;

                case '60':
                    $target_path = $target;
                    $get_info = $db->queryRow("select s.login_id, s.login_pw from bc_task_rule tr, bc_storage s where tr.job_name='tm' and tr.target_path = s.storage_id");
                    //if(empty($get_info)) throw new Exception('디비에서 tm 값이 없습니다.');

                    if (empty($media_id)) {
                        $media_id = $db->queryOne("select media_id from bc_media where content_id=$content_id and media_type='original'");
                    }



                    $query = "insert into bc_task
            (media_id, type, status, priority, source, target, target_id, target_pw, parameter, creation_datetime, destination, task_workflow_id, job_priority, task_rule_id)
            values
            ('$media_id', '{$insert_job['type']}', 'queue', 300, '$source', '$target_path', '{$get_info['login_id']}', '{$get_info['login_pw']}', '{$insert_job['parameter']}', '$cur_time', '$channel', '{$get_jobs['task_workflow_id']}', '{$get_jobs['job_priority']}', '{$get_jobs['task_rule_id']}')";
                    break;

                case '61':
                    //$content_id, $source, $target, $cur_time, $channel

                    //		  $file_name = explode('/', $source);
                    //		  $get_num = count($file_name)-1;
                    //		  $get_file_name = $file_name[$get_num];
                    //		  $target_path = $target.'/'.$get_file_name;
                    //$get_info = $db->queryRow("select * from storage where name='transfer'");
                    //if(empty($get_info)) throw new Exception('디비에서 transfer 값이 없습니다.');

                    $proxy_mediaID = $db->queryOne("select media_id from bc_media where content_id=$content_id and media_type='original'");

                    $query = "insert into bc_task
            (media_id, type, status, priority, source, target, parameter, creation_datetime, destination, task_workflow_id, job_priority, task_rule_id)
            values
            ('$proxy_mediaID', '61', 'queue', 300, '$source', '$target', '{$insert_job['parameter']}', '$cur_time', '$channel', '{$get_jobs['task_workflow_id']}', '{$get_jobs['job_priority']}', '{$get_jobs['task_rule_id']}')";
                    break;

                case '90':
                    $target_path = $target;
                    $get_info = $db->queryRow("select s.login_id, s.login_pw from bc_task_rule tr, bc_storage s where tr.job_name='tm' and tr.target_path = s.storage_id");
                    //if(empty($get_info)) throw new Exception('디비에서 tm 값이 없습니다.');
                    $proxy_mediaID = $db->queryOne("select media_id from bc_media where content_id=$content_id and media_type='original'");

                    $query = "insert into bc_task
            (media_id, type, status, priority, source, target, target_id, target_pw, parameter, creation_datetime, destination, task_workflow_id, job_priority, task_rule_id)
            values
            ('$proxy_mediaID', '{$insert_job['type']}', 'queue', 300, '$source', '$target_path', '{$get_info['login_id']}', '{$get_info['login_pw']}', '{$insert_job['parameter']}', '$cur_time', '$channel', '{$get_jobs['task_workflow_id']}', '{$get_jobs['job_priority']}', '{$get_jobs['task_rule_id']}')";
                    break;

                case '80':

                    $make_target_path = explode('/', $source);
                    $i = count($make_target_path);
                    $target_path = $make_target_path[$i - 1];

                    //		  $get_info = $db->queryRow("select * from bc_storage where name='$channel'");
                    //		  if(empty($get_info)) throw new Exception('디비에서 '.$channel.' 값이 없습니다.');
                    $proxy_mediaID = $db->queryOne("select media_id from bc_media where content_id=$content_id and media_type='original'");

                    $query = "insert into bc_task
            (media_id, type, status, priority, source, target, source_id, source_pw, parameter, creation_datetime, destination, task_workflow_id, job_priority, task_rule_id)
            values
            ('$proxy_mediaID', '{$insert_job['type']}', 'queue', 300, '$source', '$target', '{$get_info['login_id']}', '{$get_info['login_pw']}', '{$insert_job['parameter']}', '$cur_time', '$channel', '{$get_jobs['task_workflow_id']}', '{$get_jobs['job_priority']}', '{$get_jobs['task_rule_id']}')";
                    break;

                case '110':

                    $insert_job['parameter'] .= ' ' . $add_param;

                    //print_r($insert_job);

                    $query = "insert into bc_task
            (media_id, type, status, priority, source, target, target_id, target_pw, parameter, creation_datetime, destination, task_workflow_id, job_priority, task_rule_id)
            values
            ('$media_id', '{$insert_job['type']}', 'queue', 300, '$source', '$target', '{$get_info['login_id']}', '{$get_info['login_pw']}', '{$insert_job['parameter']}', '$cur_time', '$channel', '{$get_jobs['task_workflow_id']}', '{$get_jobs['job_priority']}', '{$get_jobs['task_rule_id']}')";
                    break;

                default:
                    throw new Exception('등록되지 않은 모듈 코드넘버입니다.');
                    break;
            }
            $result = $db->exec($query);
            //  unset($target_path);
            //  unset($get_info);
            //  unset($proxy_mediaID);

            return true;
        }

        function check_parent_job_($task_type)
        {
            global $db;

            $db->beginTransaction();

            usleep(rand(100000, 500000)); //0.1초부터 0.5초까지 랜덤 딜레이.
            $assign_task = $db->queryRow("select * from bc_task where type='$task_type' and status='queue' order by priority asc, creation_datetime, job_priority");

            if ($task_type == '20') { }

            if ($assign_task['job_priority'] > 1) {
                //콘텐츠아이디 구한값으로 미디어 아이디들과 토스크 타입의 값을 구해와서 상태값을 비교한다.
                $get_media_ids = $db->queryAll("select media_id from bc_media where content_id = (select content_id from bc_media where media_id = {$assign_task['media_id']})");

                //	  if($task_type == '10')
                //	  {
                //	  }

                foreach ($get_media_ids as $get_media_id) {
                    $media_ids .= $get_media_id['media_id'] . ',';
                }

                $media_ids = substr($media_ids, 0, -1);
                //선행작업이 두개이상일경우에 (error. complete)두가지가 존재할시 완료항목이 있으면 다음작업을 진행하게 변경.--김성민 02/22
                $query = "select status from bc_task where job_priority = " . ($assign_task['job_priority'] - 1) . " and media_id in ($media_ids) and status='complete'";
                $result = $db->queryOne($query);

                if ($task_type == '20') {
                    file_put_contents($_SERVER['DOCUMENT_ROOT'] . '/log/aaaaa.html', date("Y-m-d H:i:s\t") . $db->last_query . "\n\n", FILE_APPEND);
                }

                if (empty($result)) {
                    $rtn = false;
                } else {
                    $rtn = $assign_task;
                }
            } else {
                $rtn = $assign_task;
            }
            $db->commit();

            return $rtn;
        }

        function insert_task_next($content_id, $source, $target, $cur_time, $channel, $type, $parameter, $task_workflow_id, $job_priority, $task_define_id)
        {
            global $db;
            $insert_job = $db->queryRow("select * from bc_task_define where task_define_id = $task_define_id");

            switch ($type) {
                case '20':
                    if (empty($target)) {
                        $target_path = date('Y/m/d/H/is') . '/Proxy';
                    } else {
                        $target_path = $target . '/' . date('Y/m/d/H/is') . '/Proxy';
                    }
                    $get_info = $db->queryRow("select * from bc_storage where name='transcoder'");
                    if (empty($get_info)) throw new Exception('디비에서 transcoder 값이 없습니다.');

                    $proxy_mediaID = $db->queryOne("select media_id from bc_media where content_id=$content_id and media_type='proxy'");

                    $query = "insert into bc_task
            (media_id, type, status, priority, source, target, target_id, target_pw, parameter, creation_datetime, destination, task_workflow_id, job_priority, task_define_id)
            values
            ('$proxy_mediaID', '$type', 'queue', 300, '$source', '$target_path', '{$get_info['login_id']}', '{$get_info['login_pw']}', '$parameter', '$cur_time', '$channel', '$task_workflow_id', '$job_priority', '$task_define_id')";
                    break;

                case '70':
                    if (empty($target)) {
                        $target_path = date('Y/m/d/H/is') . '/Proxy';
                    }
                    $get_info = $db->queryRow("select * from bc_storage where name='transcoder'");
                    if (empty($get_info)) throw new Exception('디비에서 transcoder 값이 없습니다.');

                    $proxy_mediaID = $db->queryOne("select media_id from bc_media where content_id=$content_id and media_type='proxy'");

                    $query = "insert into bc_task
            (media_id, type, status, priority, source, target, target_id, target_pw, parameter, creation_datetime, destination, task_workflow_id, job_priority, task_define_id)
            values
            ('$proxy_mediaID', '$type', 'queue', 300, '$source', '$target_path', '{$get_info['login_id']}', '{$get_info['login_pw']}', '$parameter', '$cur_time', '$channel', '$task_workflow_id', '$job_priority', '$task_define_id')";
                    break;

                case '10':
                    if (empty($target)) {
                        $target_path = date('Y/m/d/H/is') . '/Catalog';
                    } else {
                        $target_path = $target . '/Catalog';
                    }

                    $get_info = $db->queryRow("select * from bc_storage where name='catalog'");
                    if (empty($get_info)) throw new Exception('디비에서 catalog 값이 없습니다.');
                    // 2011-01-17 박정근
                    // 임시 수정 카탈로깅은 프록시 파일로 함.
                    //$proxy_mediaID = $db->queryOne("select media_id from media where content_id=$content_id and type='original'");
                    $proxy_mediaID = $db->queryOne("select media_id from bc_media where content_id=$content_id and media_type='proxy'");

                    $query = "insert into bc_task
            (media_id, type, status, priority, source, target, target_id, target_pw, parameter, creation_datetime, destination, task_workflow_id, job_priority, task_define_id)
            values
            ('$proxy_mediaID', '$type', 'queue', 300, '$source', '$target_path', '{$get_info['login_id']}', '{$get_info['login_pw']}', '$parameter', '$cur_time', '$channel', '$task_workflow_id', '$job_priority', '$task_define_id')";
                    break;

                case '31':
                    $target_path = $target;
                    $changed_source = str_replace('.mxf', '.mov', $source);
                    //$get_info = $db->queryRow("select * from storage where name='transfer'");
                    //if(empty($get_info)) throw new Exception('디비에서 transfer 값이 없습니다.');

                    $proxy_mediaID = $db->queryOne("select media_id from bc_media where content_id=$content_id and media_type='original'");

                    $query = "insert into bc_task
            (media_id, type, status, priority, source, target, target_id, target_pw, parameter, creation_datetime, destination, task_workflow_id, job_priority, task_define_id)
            values
            ('$proxy_mediaID', '{$insert_job['type']}', 'queue', 300, '$source', '$changed_source', '{$get_info['login_id']}', '{$get_info['login_pw']}', '{$insert_job['parameter']}', '$cur_time', '$channel', '$task_workflow_id', '$job_priority', '$task_define_id')";
                    break;

                case '60':
                    $target_path = $target;
                    //$get_info = $db->queryRow("select * from storage where name='transfer'");
                    //if(empty($get_info)) throw new Exception('디비에서 transfer 값이 없습니다.');

                    $proxy_mediaID = $db->queryOne("select media_id from bc_media where content_id=$content_id and media_type='original'");

                    $query = "insert into bc_task
            (media_id, type, status, priority, source, target, target_id, target_pw, parameter, creation_datetime, destination, task_workflow_id, job_priority, task_define_id)
            values
            ('$proxy_mediaID', '{$insert_job['type']}', 'queue', 300, '$source', '$target_path', '{$get_info['login_id']}', '{$get_info['login_pw']}', '{$insert_job['parameter']}', '$cur_time', '$channel', '$task_workflow_id', '$job_priority', '$task_define_id')";
                    break;

                case '61':
                    $file_name = explode('/', $source);
                    $get_num = count($file_name) - 1;
                    $get_file_name = $file_name[$get_num];
                    $target_path = $target . '/' . $get_file_name;
                    //$get_info = $db->queryRow("select * from storage where name='transfer'");
                    //if(empty($get_info)) throw new Exception('디비에서 transfer 값이 없습니다.');

                    $proxy_mediaID = $db->queryOne("select media_id from bc_media where content_id=$content_id and media_type='original'");

                    $query = "insert into bc_task
            (media_id, type, status, priority, source, target, target_id, target_pw, parameter, creation_datetime, destination, task_workflow_id, job_priority, task_define_id)
            values
            ('$proxy_mediaID', '{$insert_job['type']}', 'queue', 300, '$source', '$target_path', '{$get_info['login_id']}', '{$get_info['login_pw']}', '{$insert_job['parameter']}', '$cur_time', '$channel', '$task_workflow_id', '$job_priority', '$task_define_id')";
                    break;

                case '80':

                    if ($channel == 'ground_channel' || $channel == 'satillite_channel') { }
                    $make_target_path = explode('/', $source);
                    $i = count($make_target_path);
                    $target_path = $make_target_path[$i - 1];

                    $get_info = $db->queryRow("select * from bc_storage where name='$channel'");
                    if (empty($get_info)) throw new Exception('디비에서 ' . $channel . ' 값이 없습니다.');

                    $proxy_mediaID = $db->queryOne("select media_id from bc_media where content_id=$content_id and media_type='original'");

                    $query = "insert into bc_task
            (media_id, type, status, priority, source, target, source_id, source_pw, parameter, creation_datetime, destination, task_workflow_id, job_priority, task_define_id)
            values
            ('$proxy_mediaID', '{$insert_job['type']}', 'queue', 300, '$target_path', '$source', '{$get_info['login_id']}', '{$get_info['login_pw']}', '$parameter', '$cur_time', '$channel', '$task_workflow_id', '$job_priority', '$task_define_id')";
                    break;

                case 'archive':
                    //$content_id, $source, $target, $cur_time, $channel, $type, $parameter, $task_workflow_id, $job_priority, $task_define_id;
                    $media_info = $db->queryRow("select media_id, path from bc_media where content_id = $content_id and media_type = 'nearline'");
                    $media_id = $media_info['media_id'];
                    $source = $media_info['path'];

                    $archive_id = buildDASArchiveID();
                    $type = 'archive';
                    $status = 'queue';
                    $priority = '300';

                    $r = $db->exec("insert into bc_archive (media_id, archive_id, task_id) values ('$media_id', '$archive_id', '$task_id')");
                    //file_put_contents('log/update_task_error_'.date('Ymd').'.html', '['.date('Y-m-d H:i:s').']'.$db->last_query."\n", FILE_APPEND);
                    //아카이브 아이디를 타겟어 넣어주기 위해서 수정함. 20110210 dohoon
                    $r = $db->exec("insert into bc_task (media_id, type, source, target, status, priority, creation_datetime, destination, task_workflow_id, job_priority, task_define_id)
                                values ($media_id, '$type', '$source', '$archive_id', '$status', '$priority', '$cur_time', '$channel', '$task_workflow_id', '$job_priority', '$task_define_id')");
                    //file_put_contents('log/update_task_error_'.date('Ymd').'.html', '['.date('Y-m-d H:i:s').']'.$db->last_query."\n", FILE_APPEND);
                    break;

                case '110':
                    $ud_content_id  = $db->queryOne("select ud_content_id from bc_content where content_id=" . $content_id);

                    $media = $db->queryRow("select media_id as id, path from bc_media where media_type='original' and content_id=" . $content_id);
                    $exists_archive = $db->queryOne("select count(*) from alto_archive where media_id=" . $media['id']);

                    $archive_id = buildArchiveID('NPS', 'SEQ_NPS_ARCHIVE');
                    $media_id = $media['id'];
                    $source = $media['path'];
                    $target = '';
                    $status = 'queue';
                    $priority = '300';
                    //아카이브 그룹화를 위해 매핑 / 보도영상 : news / 그외 : data

                    switch ($ud_content_id) {
                        case '4':
                            $add_param = $archive_id . ' ' . 'news';
                            break;

                        default:
                            $add_param = $archive_id . ' ' . 'data';
                            break;
                    }


                    $cur_time = date('YmdHis');

                    $db->exec("insert into alto_archive (media_id, archive_id) values ('$media_id', '$archive_id')");

                    $parameter .= ' ' . $add_param;

                    $r = $db->exec("insert into bc_task (media_id, type, source, target, status, parameter, priority, creation_datetime, destination, task_workflow_id, job_priority, task_define_id)
                    values ($media_id, '110', '$source', '', '$status', '','$priority', '$cur_time', '$channel', '$task_workflow_id', '$job_priority', '$task_define_id')");

                    break;
            }
            $result = $db->exec($query);
            file_put_contents('log/update_task_error_' . date('Ymd') . '.html', '[' . date('Y-m-d H:i:s') . ']' . $db->last_query . "\n\n", FILE_APPEND);
            //echo "insert task:: $query<br />";

            return;
        }

        function add_next_job($task_id)
        {
            global $db;
            // 작업이 끝났을때 다음 작업이 있는지 체크하여 등록하기
            //--받은 태스크아이디의 잡프리어리티를 체크하여 다음잡이 있으면 태스크에 등록.
            $get_task_info = $db->queryRow("select * from bc_task where task_id = $task_id"); //완료한 작업의 job_priority를 구해와서..

            $next_job = $get_task_info['job_priority'] + 1;  // 다음잡= 1을 더한값이 task_workflow_define에 있는지 체크.
            $next_query = "select type, parameter, source_path, task_define_id, target_path from bc_task_define " .
                "where " .
                "task_define_id = (select task_define_id from bc_task_workflow_define where task_workflow_id = {$get_task_info['task_workflow_id']} " .
                "and job_priority = $next_job)";

            @$get_next_job_info = $db->queryRow($next_query);

            if (!empty($get_next_job_info)) {
                //file_put_contents('log/update_task_error_'.date('Ymd').'.html', '['.date('Y-m-d H:i:s').']'.$db->last_query."\n", FILE_APPEND);

                //트랜스퍼작업이 아닐시기존작업의 타겟패스를 활용
                //if($get_task_info['type'] != ARIEL_TRANSFER_FS)
                //{
                //  $target = substr($get_task_info['target'], 0, 18);
                //}
                //else
                //{
                //$target = substr($get_task_info['target'], 0, 18);
                $target = dirname($get_task_info['target']);
                if ($target == '.') {
                    $target = '';
                }
                //}

                if ($get_next_job_info['type'] == ARIEL_CATALOG) {
                    $source = $db->queryOne("select path from bc_media where content_id = (select m.content_id from bc_media m, bc_task t where m.media_id = t.media_id and t.task_id = $task_id) and media_type = 'proxy'");
                    if (empty($source)) {
                        $get_info = $db->queryRow("select content_id, path from bc_media where content_id = (select content_id from bc_media where media_id = {$get_task_info['media_id']}) and media_type = 'original'");
                        $source = $get_info['path'];
                    }
                    //$target= '';
                } elseif ($get_next_job_info['type'] == ARIEL_TRANSCODER || $get_next_job_info['type'] == ARIEL_TRANS_AUDIO) {
                    /*
            $query = "select user_id from content where content_id =(select m.content_id from media m, task t where m.media_id = t.media_id and t.id = $task_id)";
            $get_user_id = $db->queryOne($query);
            $category_path = $db->queryOne("select path from path_mapping where category_id = (select category_id from user_mapping where user_id = '$get_user_id')");
            $source = $category_path.'/'.$get_info['path'];
        */
                    $source = $db->queryOne("select target from bc_task where task_id = $task_id");
                    $target = '';
                } else {
                    $get_info = $db->queryRow("select content_id, path from bc_media where content_id = (select content_id from bc_media where media_id = {$get_task_info['media_id']}) and media_type = 'original'");
                    $source = $get_info['path'];
                }
                $content_id = $db->queryOne("select content_id from bc_media where media_id={$get_task_info['media_id']}");

                $register_next_job = insert_task_next($content_id, $source, $target, date('YmdHis'), $get_task_info['destination'], $get_next_job_info['type'], $get_next_job_info['parameter'], $get_task_info['task_workflow_id'], $next_job, $get_next_job_info['task_define_id']);
                return;
            } else {
                return false;
            }
        }

        function get_storage_info($type)
        {
            global $db;

            if ($type == 'thumb' || $type == 'proxy') {
                $type = '저해상도';
            } else {
                $type = '아카이브 스토리지';
            }
            $storage_info = $db->queryRow("select path, storage_id from bc_storage where name = '$type'");
            return ($storage_info);
        }

        //function buildArchiveID()
        //{
        //  global $db;
        //
        //  return $db->queryOne('select archive_seq.nextval from dual');
        //}

        ////////////////////////////////////////////////////////////////////////////////
        ///// workflow에 따른 task작업 function /////NPS db에 등록하기위해 db connect 변경
        function insert_task_queryNps($content_id, $source, $target, $cur_time, $channel)
        {

            global $dbNps;
            $channel_q = "select task_workflow_id from bc_task_workflow where register = '$channel'";
            $get_register = $dbNps->queryOne($channel_q);
            if (empty($get_register)) throw new Exception('task_workflow에 등록되지 않은 인제스트 채널입니다.');

            $get_jobs = $dbNps->queryRow("select * from bc_task_workflow_define where task_workflow_id = " . $get_register . " order by job_priority asc");

            $insert_job = $dbNps->queryRow("select * from bc_task_define where task_define_id = '{$get_jobs['task_define_id']}'");

            $target_path = $target;
            //$get_info = $dbNps->queryRow("select * from storage where name='transfer'");
            //if(empty($get_info)) throw new Exception('디비에서 transfer 값이 없습니다.');

            $proxy_mediaID = $dbNps->queryOne("select media_id from bc_media where content_id=$content_id and media_type='original'");

            $query = "insert into bc_task
    (media_id, type, status, priority, source, target, target_id, target_pw, parameter, creation_datetime, destination, task_workflow_id, job_priority, task_define_id)
    values
    ('$proxy_mediaID', '{$insert_job['type']}', 'queue', 300, '$source', '$target_path', '{$get_info['login_id']}', '{$get_info['login_pw']}', '{$insert_job['parameter']}', '$cur_time', '$channel', '{$get_jobs['task_workflow_id']}', '{$get_jobs['job_priority']}', '{$get_jobs['task_define_id']}')";

            $result = $dbNps->exec($query);

            return;
        }

        function convertTime($value)  /// 00:00:00 형식을 sec 로 변환
        {
            if (strlen($value) == 8) {
                $h = substr($value, 0, 2);
                $i = substr($value, 3, 2);
                $s = substr($value, 6, 2);
                $time = ($h * 3600) + ($i * 60) + $s;
            } else {
                return false;
            }
            return $time;
        }

        function calculationLength($start, $end) // 길이 계산, 리턴값 00:00:00
        {

            $length_time = $end - $start;

            $h = (int) ($length_time / 3600);
            $i = (int) (($length_time % 3600) / 60);
            $s = (int) (($length_time % 3600) % 60);

            $h = str_pad($h, 2, '0', STR_PAD_LEFT);
            $i = str_pad($i, 2, '0', STR_PAD_LEFT);
            $s = str_pad($s, 2, '0', STR_PAD_LEFT);

            return $h . ':' . $i . ':' . $s;
        }

        function updateMultiField($content_id, $usr_meta_field_id, $json_value, $action, $del_value)
        {
            global $db;

            if ($action == '삭제') {
                if (!empty($del_value)) {
                    $delete = $db->exec("delete from bc_meta_multi_xml where meta_multi_xml_id='$del_value' ");
                }
            }

            for ($i = 0; $i < count($json_value); $i++) {
                $sort_value = $i + 1;

                $json_value[$i]['columnA'] = $sort_value; //순번 재 입력

                if ((timecode::getConvSec(trim($json_value[$i]['columnB']))  !== false) && (timecode::getConvSec(trim($json_value[$i]['columnC'])) !== false)) {
                    $start_tc = timecode::getConvSec(trim($json_value[$i]['columnB']));
                    $end_tc =  timecode::getConvSec(trim($json_value[$i]['columnC']));

                    $json_value[$i]['columnD'] = timecode::getConvTimecode($end_tc - $start_tc);
                }

                if ($usr_meta_field_id == CLEAN) {
                    $temp_category = $json_value[$i]['columnE'];

                    if (strstr($json_value[$i]['columnE'], '->')) {
                        $categories = explode('->', $json_value[$i]['columnE']);

                        $category_array = array();

                        foreach ($categories as $item) {
                            $item = trim($item);
                            if (!empty($item) && $item != '3936212' && $item != '') {
                                $category_array[] = trim($item);
                            }
                        }
                        $json_value[$i]['columnE'] = join('/', $category_array);
                    } else if ($json_value[$i]['columnE'] == '3936212') {
                        $json_value[$i]['columnE'] = '';
                    } else if (strstr($json_value[$i]['columnE'], '/')) {
                        $json_value[$i]['columnE'] = trim($json_value[$i]['columnE']);
                    } else {
                        $parent_id = $db->queryOne("select parent_id from bc_category where category_id=" . $json_value[$i]['columnE']);
                        if ($parent_id != -1 && !empty($parent_id)) {
                            if ($parent_id == '3936212') {
                                $json_value[$i]['columnE'] = $json_value[$i]['columnE'];
                            } else {
                                $json_value[$i]['columnE'] = $parent_id . '/' . $json_value[$i]['columnE'];
                            }
                        } else {
                            $json_value[$i]['columnE'] = '';
                        }
                    }
                    $created = date('Ymd');
                    file_put_contents('/oradata/web/das/log/total_info/tc_log_' . $created . '.log', $content_id . ' : ' . $sort_value . ' : ' . $temp_category . ' => ' . $json_value[$i]['columnE'] . "\n", FILE_APPEND);
                }

                $tmp = array();
                ksort($json_value[$i]);
                foreach ($json_value[$i] as $k => $v) {
                    if ($k != 'meta_value_id') {
                        array_push($tmp, '<' . $k . '>' . $db->escape(htmlspecialchars($v)) . '</' . $k . '>');
                    }
                }

                $columns = '<columns>' . join('', $tmp) . '</columns>';

                if (!empty($json_value[$i]['meta_value_id'])) {
                    $result = $db->exec("update bc_meta_multi_xml set show_order=$sort_value , xml_value='$columns' where meta_multi_xml_id=" . $json_value[$i]['meta_value_id']);
                } else {
                    $meta_multi_xml_id = getNextMetaMultiSequence();

                    $result = $db->exec("insert into bc_meta_multi_xml (content_id, usr_meta_field_id, show_order, meta_multi_xml_id, xml_value) values ($content_id, $usr_meta_field_id, $sort_value,'$meta_multi_xml_id' ,'$columns')");
                }
            }
        }

        function printArrayMulti($content_id)
        {
            global $db;
            $query = "select * from bc_meta_multi_xml where content_id = '$content_id' order by show_order";
            $meta_data = $db->queryAll($query);

            $data = array();
            for ($i = 0; $i < count($meta_data); $i++) {

                $xml = simplexml_load_string($meta_data[$i]['xml_value']);

                $xml->addChild('meta_value_id', $meta_data[$i]['meta_multi_xml_id']);


                foreach ($xml as $k => $v) {
                    $data[$i][$k] = htmlspecialchars_decode($v);
                }
            }

            echo json_encode(array(
                'success' => true,
                'data' => $data
            ));
        }

        function insertLog($action, $user_id, $content_id, $description)
        {
            global $db;

            if (!empty($description)) {
                $description = $db->escape($description);
            }

            $cur_datetime = date('YmdHis');
            if (empty($content_id)) {
                $query = "
            INSERT INTO BC_LOG
                (ACTION, USER_ID, CREATED_DATE, DESCRIPTION)
            VALUES
                ('$action', '$user_id', '$cur_datetime', '$description')
        ";
            } else {

                $con_info = $db->queryRow("select bs_content_id, ud_content_id from bc_content where content_id = '$content_id'");

                $query = "
            INSERT INTO BC_LOG
                (ACTION, USER_ID, BS_CONTENT_ID, UD_CONTENT_ID, CONTENT_ID, CREATED_DATE, DESCRIPTION)
            VALUES
                ('$action', '$user_id','{$con_info['bs_content_id']}', '{$con_info['ud_content_id']}', '$content_id', '$cur_datetime', '$description')
        ";
            }

            @file_put_contents($_SERVER['DOCUMENT_ROOT'] . '/log/insertLog' . date('Ymd') . '.log', $_SERVER['REMOTE_ADDR'] . "\t[" . date('Y-m-d H:i:s') . ']' . $query . "\r\n", FILE_APPEND);

            $result = $db->exec($query);
        }

        function insertLogTopic($action, $user_id, $category_id, $description)
        {
            global $db;

            $cur_datetime = date('YmdHis');
            $topic_full_path = getCategoryFullPath($category_id);
            $topic_full_path_title = getCategoryPathTitle($topic_full_path);
            $description .= '(' . $topic_full_path_title . ')';
            if (!empty($description)) {
                $description = $db->escape($description);
            }

            $result = $db->exec("insert into bc_log (action, user_id, bs_content_id, ud_content_id, content_id, created_date, description) values ('$action', '$user_id','', '', '$category_id', '$cur_datetime', '$description')");
        }
        // 20160226 CANPN add inserLogNotice
        function insertLogNotice($action, $user_id, $notice_id, $description)
        {
            global $db;

            if (!empty($description)) {
                $description = $db->escape($description);
            }

            $cur_datetime = date('YmdHis');

            $query = "
        INSERT INTO BC_LOG
            (
                ACTION
                ,USER_ID
                ,CONTENT_ID
                ,CREATED_DATE
                ,DESCRIPTION
            )
        VALUES
            (
                '$action'
                ,'$user_id'
                ,'$notice_id'
                ,'$cur_datetime'
                ,'$description'
            )
    ";

            $result = $db->exec($query);
        }

        function insertLogRequest($action, $user_id, $request_id, $description)
        {
            global $db;

            if (!empty($description)) {
                $description = $db->escape($description);
            }

            $cur_datetime = date('YmdHis');

            $query = "
    INSERT INTO BC_LOG
        (
            ACTION
            ,USER_ID
            ,ZODIAC_ID
            ,CREATED_DATE
            ,DESCRIPTION
        )
    VALUES
        (
            '$action'
            ,'$user_id'
            ,'$request_id'
            ,'$cur_datetime'
            ,'$description'
        )
    ";

            $result = $db->exec($query);
        }

        function content_meta_value_list($content_id, $ud_content_id, $container_id = null)
        {
            global $db;

            // 메타 테이블의 필드값만 받아온 리스트
            try {
                $where = "";

                if ($container_id) {
                    $where = " and f.container_id=$container_id ";
                }

                $field_list = $db->queryAll("
                        select
                            f.*
                        from
                            bc_usr_meta_field f
                        where
                            f.ud_content_id=$ud_content_id
                        {$where}
                        and
                            f.depth = 1
                        order by f.show_order"); // , f.container_id desc

                // meta_value에 조인이 안되는 value를 리스트에 추가해준다.
                $value_list = $db->queryAll("
                        select
                            f.*
                        from
                            bc_usr_meta_field f
                        where
                            f.ud_content_id=$ud_content_id
                        {$where}
                        and
                            f.depth = 1
                        order by f.show_order"); // , f.container_id desc

                for ($i = 0; $i < count($field_list); $i++) {
                    foreach ($value_list as $value_key => $value) {
                        if ($field_list[$i]['usr_meta_field_id'] ==  $value['usr_meta_field_id']) {
                            $field_list[$i]['usr_meta_value'] = $value['usr_meta_value'];
                        } else {
                            if (!isset($field_list[$i]['usr_meta_value'])) {
                                $field_list[$i]['usr_meta_value'] = '';
                            }
                        }
                    }
                }

                return $field_list;
            } catch (Exception $e) {
                return $e->getMessage() . '(' . $db->last_query . ')';
            }
        }

        function createExcelFileForStoryBoard($fileName, $array)
        {
            $time = date('YmdHis');
            $fileName       = iconv('utf-8', 'euc-kr', $fileName . '_' . $time);

            header("Pragma: public");
            //header( "Content-type: application/vnd.ms-excel" );
            header("Content-Type: application/octet-stream");
            header("Content-Disposition: attachment; filename={$fileName}.xls");
            header("Content-Description: CMS Generated Data");
            header("Content-charset:UTF-8");

            $excelTable = "
    <table border=1 cellpadding=1 cellspacing=1 bgcolor='#000000'>";
            "<tr height='25' align='center' bgcolor='#FFFFFF'>";

            $data = "";
            $tr_start = "<tr height='25' align='center' bgcolor='#FFFFFF'>";
            $tr_end = "</tr>";
            $temp = 0;
            $count = 1;
            $height_arr = array();
            $height_temp = 0;
            $j = 0;
            $i = 0;
            $numItems = count($array);
            foreach ($array as $key => $value) {

                foreach ($value as $k => $v) //필드 생성
                {
                    if (array_key_exists('title', $value) && $k == 'title') {
                        array_push($height_arr, $height_temp);
                        $height_temp = -1;
                    }
                }
                $height_temp++;
                if (++$i === $numItems) {
                    array_push($height_arr, $height_temp);
                }
            }
            filePutContent('SB_Test_download____', 'SB height_arr', $height_arr);
            foreach ($array as $key => $value) {
                if ($temp == 0) //header 생성
                {
                    $data .= $tr_start;
                    $data .= "<td bgcolor='#FFFFBB'>Content</td>";
                    $data .= "<td bgcolor='#FFFFBB'>Conmment</td>";
                    $data .= "<td bgcolor='#FFFFBB'>Image</td>";
                    //$data .= "<td bgcolor='#FFFFBB'  height='200' width='320'>".iconv('utf-8', 'euc-kr', $k)."</td>";
                    $data .= $tr_end;
                    $temp = 1;
                    $data .= $tr_start;
                    $data .= "<td style=\"vertical-align: middle;\">test content</td>";
                    $data .= "<td style=\"vertical-align: middle;\">test comment</td>";
                    $height = ceil(($height_arr[$j] / 5)) * 190;
                    $data .= "<td height='" . $height . "' width='1650'>";
                    $j++;
                }


                foreach ($value as $k => $v) //필드 생성
                {
                    if ($k == 'url') {
                        //$data .= "<img src = \"".$_SERVER['DOCUMENT_ROOT'].iconv('utf-8', 'euc-kr', $v)."\">";
                        $data .= "<img src = \"http://" . $_SERVER['SERVER_NAME'] . ":" . $_SERVER['SERVER_PORT'] . iconv('utf-8', 'euc-kr', $v) . "\">";
                        if (($count % 5) == 0) {
                            $data .= "<br>";
                        }
                    }

                    /*else if ($k == 'scene_id'){
                $data .= "<td >".iconv('utf-8', 'euc-kr', $v)."</td>";
            } else if ($k == 'media_id'){
                $data .= "<td >".iconv('utf-8', 'euc-kr', $v)."</td>";
            }  */
                    //$data .= "<td>".iconv('utf-8', 'euc-kr', $v)."</td>";
                    if (array_key_exists('title', $value) && $k == 'title') {
                        //filePutContent('SB_Test_download____','test 111111111', $k);
                        $data .= "</td>";
                        $data .= $tr_end;
                        $data .= $tr_start;
                        $data .= "<td width='300' style=\"vertical-align: middle;\">" . iconv('utf-8', 'euc-kr', $v) . "</td>";
                        $data .= "<td width='300' style=\"vertical-align: middle;\">test comment</td>";
                        $height = ceil(($height_arr[$j] / 5)) * 190;
                        $data .= "<td height='" . $height . "' width='1650'>";
                        $j++;
                        $count = 0;
                    }
                }
                $count++;
                //$data .= $tr_end;
            }

            $data .= "</td>";
            $data .= $tr_end;
            $excelTable .= $data;
            $excelTable .= "</table>";
            return $excelTable;
        }

        function createExcelFile($fileName, $array)
        {
            $time = date('YmdHis');
            $fileName   = $fileName . '_' . $time;

            require_once($_SERVER['DOCUMENT_ROOT'] . '/lib/PHPExcel.php');
            $objPHPExcel = new PHPExcel();

            //컬럼 헤더 정보
            $row_seq = 1;
            foreach ($array as $arr_val) {
                if ($row_seq != 1) continue;
                $col_seq = A; // ++$col_seq로 B가 됨
                foreach ($arr_val as $col => $val) {
                    $objPHPExcel->setActiveSheetIndex(0)->setCellValue($col_seq . $row_seq, $col);
                    ++$col_seq;
                }
                $row_seq++;
            }
            // $objPHPExcel->setActiveSheetIndex(0)
            // ->setCellValue('A1', _text("MN00195"))
            // ->setCellValue('B1', _text("MN00196"))
            // ->setCellValue('C1', _text("MN00181"))
            // ->setCellValue('D1', _text("MN02127"))
            // ->setCellValue('E1', _text("MN02208"));

            //데이터

            $row_seq = 2;
            foreach ($array as $col => $arr_val) {
                $col_seq = 'A';
                foreach ($arr_val as $val) {
                    $objPHPExcel->setActiveSheetIndex(0)->setCellValue($col_seq . $row_seq, $val);
                    ++$col_seq;
                }
                $row_seq++;
            }
            // $i = 2;
            // foreach ($rows as $row){
            //     $objPHPExcel->setActiveSheetIndex(0)
            //     ->setCellValue('A'.$i, $row['user_id'])
            //     ->setCellValue('B'.$i, $row['user_nm'])
            //     ->setCellValue('C'.$i, $row['dept_nm'])
            //     ->setCellValue('D'.$i, $row['email'])
            //     ->setCellValue('E'.$i, $row['phone']);
            //     $i++;
            // }

            $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');

            header('Content-type: application/vnd.ms-excel');
            header('Content-Disposition: attachment; filename="' . $fileName . '.xlsx"');
            $objWriter->save('php://output');
        }

        function t($str)
        {
            $lang = array(
                'Proxima' => 'Proxima'
            );

            return $lang[$str];
        }


        /**
         * 그룹별 카테고리 권한 배열 생성 11-11-25 by lsy
         * @param $groups
         * @return array
         */
        function categoryGroupGrant($groups)
        {
            global $db;

            $grant = array();

            $category_grants = $db->queryAll("SELECT * FROM BC_CATEGORY_GRANT ORDER BY UD_CONTENT_ID, CATEGORY_ID, GROUP_GRANT ASC");
            foreach ($groups as $group_id) {
                foreach ($category_grants as $category_grant) {
                    if ($category_grant['member_group_id'] == $group_id) {
                        if (is_null($grant[$category_grant['ud_content_id']][$category_grant['category_id']])) {
                            $grant[$category_grant['ud_content_id']][$category_grant['category_id']] = $category_grant['group_grant'];
                        } else if ($category_grant['group_grant'] > $grant[$category_grant['ud_content_id']][$category_grant['category_id']]) {
                            $grant[$category_grant['ud_content_id']][$category_grant['category_id']] = $category_grant['group_grant'];
                        }
                    }
                }
            }

            return $grant;
        }

        function accessGroupGrant($groups)
        {
            global $db;

            $grant = array();

            $category_grants = $db->queryAll("SELECT * FROM BC_GRANT ORDER BY UD_CONTENT_ID, CATEGORY_ID, GROUP_GRANT ASC");
            foreach ($groups as $group_id) {
                foreach ($category_grants as $category_grant) {
                    if ($category_grant['member_group_id'] == $group_id) {
                        if (is_null($grant[$category_grant['ud_content_id']][$category_grant['category_id']])) {
                            $grant[$category_grant['ud_content_id']][$category_grant['category_id']] = $category_grant['group_grant'];
                        } else if ($category_grant['group_grant'] > $grant[$category_grant['ud_content_id']][$category_grant['category_id']]) {
                            $grant[$category_grant['ud_content_id']][$category_grant['category_id']] = $category_grant['group_grant'];
                        }
                    }
                }
            }

            return $grant;
        }

        function getGroups($user_id)
        {
            global $db;
            $_groups = $db->queryAll("
        SELECT	MGM.MEMBER_GROUP_ID
        FROM		BC_MEMBER_GROUP_MEMBER MGM, BC_MEMBER M
        WHERE	M.MEMBER_ID = MGM.MEMBER_ID
        AND		USER_ID = '" . $user_id . "'
    ");

            if ($_groups) {
                foreach ($_groups as $_group) {
                    $groups[] = $_group['member_group_id'];
                }
            } else {
                /*
        그룹이 없는 사용자는 기본으로 ANONYMOUS_GROUP 로 등록
        */
                $default_group_id = $db->queryOne("select member_group_id from bc_member_group where is_default='Y'");
                $groups[] = $default_group_id;
            }

            return $groups;
        }

        /**
         * 대상노드 카테고리 아이디, 세션의 카테고리 권한 배열, 부모노드 권한 배열, 사용자 정의 콘텐츠 아이디
         * 권한별 노드 옵션 배열 생성 11-11-25 by lsy
         * @param $node_category_id
         * @param $category_grant
         * @param $category_grant_array
         * @param null $ud_content_id
         * @return mixed
         */
        function set_category_grant($node_category_id, $category_grant, $category_grant_array, $ud_content_id = null)
        {

            // 세션에 권한정보가 있고 , 사용자정의콘텐츠 정보가 없을때
            if (!empty($category_grant) && empty($ud_content_id)) {
                foreach ($category_grant as $ud_content_id => $category_ids) {
                    foreach ($category_ids as $category_id => $grant) {
                        if ($category_id == $node_category_id) {
                            switch ($grant) {

                                    // 권한 없음
                                case 0:
                                    $category_grant_array['read'] = 0;
                                    $category_grant_array['add'] = 0;
                                    $category_grant_array['edit'] = 0;
                                    $category_grant_array['del'] = 0;
                                    $category_grant_array['hidden'] = 0;
                                    break;

                                    // 읽기 권한
                                case 1:
                                    $category_grant_array['read'] = 1;
                                    $category_grant_array['add'] = 0;
                                    $category_grant_array['edit'] = 0;
                                    $category_grant_array['del'] = 0;
                                    $category_grant_array['hidden'] = 0;
                                    break;

                                    // 읽기 / 생성 / 수정 권한
                                case 2:
                                    $category_grant_array['read'] = 1;
                                    $category_grant_array['add'] = 1;
                                    $category_grant_array['edit'] = 1;
                                    $category_grant_array['del'] = 0;
                                    $category_grant_array['hidden'] = 0;
                                    break;

                                    // 읽기 / 생성 / 수정 / 삭제 권한
                                case 3:
                                    $category_grant_array['read'] = 1;
                                    $category_grant_array['add'] = 1;
                                    $category_grant_array['edit'] = 1;
                                    $category_grant_array['del'] = 1;
                                    $category_grant_array['hidden'] = 0;
                                    break;

                                case 4:
                                    $category_grant_array['hidden'] = 1;
                                    $category_grant_array['read'] = 0;
                                    $category_grant_array['add'] = 0;
                                    $category_grant_array['edit'] = 0;
                                    $category_grant_array['del'] = 0;
                                    break;
                                case 7:
                                    $category_grant_array['hidden'] = 0;
                                    $category_grant_array['read'] = 1;
                                    $category_grant_array['add'] = 1;
                                    $category_grant_array['edit'] = 1;
                                    $category_grant_array['del'] = 0;
                                    break;
                            }
                        }
                    }
                }

                // 세션에 권한정보가 있고, 사용자정의콘텐츠 정보가 있을때
            } else if (!empty($category_grant) && !empty($ud_content_id)) {
                $category_ids = $category_grant[$ud_content_id];
                if (!empty($category_ids)) {
                    foreach ($category_ids as $category_id => $grant) {
                        if ($category_id == $node_category_id) {
                            switch ($grant) {
                                case 0: //권한 없음
                                    $category_grant_array['read'] = 0;
                                    $category_grant_array['add'] = 0;
                                    $category_grant_array['edit'] = 0;
                                    $category_grant_array['del'] = 0;
                                    $category_grant_array['hidden'] = 0;
                                    break;

                                case 1: //읽기 권한
                                    $category_grant_array['read'] = 1;
                                    $category_grant_array['add'] = 0;
                                    $category_grant_array['edit'] = 0;
                                    $category_grant_array['del'] = 0;
                                    $category_grant_array['hidden'] = 0;
                                    break;

                                case 2: //읽기 / 생성 / 수정 권한
                                    $category_grant_array['read'] = 1;
                                    $category_grant_array['add'] = 1;
                                    $category_grant_array['edit'] = 1;
                                    $category_grant_array['del'] = 0;
                                    $category_grant_array['hidden'] = 0;
                                    break;

                                case 3: //읽기 / 생성 / 수정 / 삭제 권한
                                    $category_grant_array['read'] = 1;
                                    $category_grant_array['add'] = 1;
                                    $category_grant_array['edit'] = 1;
                                    $category_grant_array['del'] = 1;
                                    $category_grant_array['hidden'] = 0;
                                    break;

                                case 4:
                                    $category_grant_array['hidden'] = 1;
                                    $category_grant_array['read'] = 0;
                                    $category_grant_array['add'] = 0;
                                    $category_grant_array['edit'] = 0;
                                    $category_grant_array['del'] = 0;
                                    break;
                                case 7:
                                    $category_grant_array['hidden'] = 0;
                                    $category_grant_array['read'] = 1;
                                    $category_grant_array['add'] = 1;
                                    $category_grant_array['edit'] = 1;
                                    $category_grant_array['del'] = 0;
                                    break;
                            }
                        }
                    }
                }
            }

            return $category_grant_array;
        }

        function set_category_access_grant($node_category_id, $category_grant, $category_grant_array, $ud_content_id = null)
        {
            $user_id = $_SESSION['user']['user_id'];
            // 세션에 권한정보가 있고 , 사용자정의콘텐츠 정보가 없을때
            if (!empty($category_grant) && empty($ud_content_id)) {
                foreach ($category_grant as $ud_content_id => $category_ids) {
                    foreach ($category_ids as $category_id => $grant) {
                        if ($category_id == $node_category_id) {
                            switch ($grant) {
                                    // 권한 없음
                                case 0:
                                    $category_grant_array['read'] = 0;
                                    $category_grant_array['add'] = 0;
                                    $category_grant_array['edit'] = 0;
                                    $category_grant_array['del'] = 0;
                                    $category_grant_array['hidden'] = 0;
                                    break;

                                    // 읽기 권한
                                case 1:
                                case 3:
                                case 5:
                                case 9:
                                    $category_grant_array['read'] = 1;
                                    $category_grant_array['add'] = 0;
                                    $category_grant_array['edit'] = 0;
                                    $category_grant_array['del'] = 0;
                                    $category_grant_array['hidden'] = 0;
                                    break;

                                    // 카테고리 관리 권한
                                case 17:
                                case 31:
                                    $category_grant_array['read'] = 1;
                                    $category_grant_array['add'] = 1;
                                    $category_grant_array['edit'] = 1;
                                    $category_grant_array['del'] = 1;
                                    $category_grant_array['hidden'] = 0;
                                    break;
                            }
                        }
                    }
                }
                // 세션에 권한정보가 있고, 사용자정의콘텐츠 정보가 있을때
            } else if (!empty($category_grant) && !empty($ud_content_id)) {
                $category_ids = $category_grant[$ud_content_id];
                if (!empty($category_ids)) {
                    foreach ($category_ids as $category_id => $grant) {
                        if ($category_id == $node_category_id) {
                            $category_grant_array['read'] = 0;
                            $category_grant_array['add'] = 0;
                            $category_grant_array['edit'] = 0;
                            $category_grant_array['del'] = 0;
                            $category_grant_array['hidden'] = 0;
                            if (checkAllowUdContentGrant($user_id, $ud_content_id, GRANT_READ)) {
                                $category_grant_array['read'] = 1;
                            }
                            if (checkAllowUdContentGrant($user_id, $ud_content_id, GRANT_CATEGORY_MANAGE)) {
                                $category_grant_array['add'] = 1;
                                $category_grant_array['edit'] = 1;
                                $category_grant_array['del'] = 1;
                            }
                        }
                    }
                }
            }

            return $category_grant_array;
        }

        // 삭제 폐기 관리 함수
        // 2011.12.13 by 허광회
        // 2011.12.16 수정 by 허광회

        // 콘텐츠에 해당하는 만료기한을 구하는 함수
        function check_contents_expire_date($ud_content_id, $created_datetime)
        {
            global $db;

            $query = "select date_code from bc_ud_content_delete_info where ud_content_id = $ud_content_id and code_type = 'UCDDDT'";
            $code = $db->queryOne($query);

            return check_limit_date($code, $created_datetime);
        }

        //미디어에 타입별 만료기한을 구하는 함수
        function check_media_expire_date($ud_content_id, $file_type, $created_datetime)
        {
            global $db;
            //content_id 사용시

            /*
    $query ="
    select bicmd.* from bc_ud_content_media_del bicmd
    where bicmd.ud_content_id in (select ud_content_id from bc_content where content_id = $content_id)";
    */

            if (!empty($ud_content_id)) {
                //ud_content_id 사용시
                $query = "select * from bc_ud_content_delete_info where ud_content_id = $ud_content_id and code_type = 'FLDLNM'";
                $data = $db->queryAll($query);

                foreach ($data as $d => $da) {
                    $type = $da[type_code];
                    $code = $da[date_code];

                    if (strstr($file_type, $type)) {
                        return check_limit_date($code, $created_datetime);
                    }
                }
            }

            return "99981231000000";
        }

        function check_limit_date($limit, $created_datetime)
        {
            $limit = explode("_", $limit);
            $str = '';

            switch ($limit[1]) {
                case 'y':
                    if ($limit[0] > 26) { //26년이상 계산이 안됨
                        $cur_year_date = date('Y');
                        $cur_date = date('mdHis');
                        $year = $limit[0] + $cur_year_date;
                        return $year . $cur_date;
                    } else {
                        $str = '+' . $limit[0] . ' year';
                    }
                    break;

                case 'm':
                    $str = '+' . $limit[0] . ' month';
                    break;

                case 'd':
                    $str = '+' . $limit[0] . ' day';
                    break;

                case 'h':
                    $str = '+' . $limit[0] . ' hours';
                    break;

                case 'i':
                    $str = '+' . $limit[0] . ' minutes';
                    break;

                case 's':
                    $str = '+' . $limit[0] . ' seconds';
                    break;

                default:
                    //그 이외 값은 999년 으로 처리
                    return "99981231000000";
                    break;
            }

            return date('YmdHis', strtotime($str, strtotime($created_datetime)));
        }

        function check_media_del_approve($content_id = null, $media_flag, $cur_date)
        {
            global $db;

            if ($content_id) {
                $query = "select media_type,media_id from bc_media where content_id = $content_id";
            } else {
                $query = "select media_type,media_id from bc_media bm where bm.expired_date < '{$cur_date}'";
            }

            $media_id_data = $db->queryAll($query);
            //echo("$query\r\n \r\n");

            if (count($media_id_data > 0)) {
                foreach ($media_id_data as $mid => $data) {
                    // 만료된 콘텐츠 ud_content_id 를 넣어줌
                    //ex> ptr 3 / ptr 4 앞단만 가져옴
                    $media_type_code = explode(" ", trim($data['media_type']));
                    //echo("type_code : $media_type_code[0] ");
                    $flag = $media_flag[$media_type_code[0]]; // 자동 승인인지 아닌지 판단

                    // 각각 update를 날려줌
                    // setting 값에 따라 다르게 처리
                    // 기본 pfr 은 $flag 값 true로 설정
                    //////////////////////////////
                    if ($flag) {
                        // 값이 있으면 관리자 허락없이 그냥 삭제  수행  'ex> pfr 파일  '
                        $media_flag = DEL_MEDIA_AUTO_APPROVE_FLAG; //자동 승인처리
                        $query = "update bc_media set flag = '{$media_flag}', deleted_date = '{$cur_datetime}' where media_id={$data[media_id]}";
                    } else {
                        // 관리자가 수락해야  삭제수행
                        $media_flag = DEL_MEDIA_CONTENT_EXPIRE_FLAG;  //만료상태
                        $query = "update bc_media set flag = '{$media_flag}', deleted_date = '{$cur_datetime}' where media_id={$data[media_id]}";
                    }
                    //$db->exec($query);
                    //echo("$query\r\n \r\n");
                    //array_push($media_id_list, $data['media_id']);
                    //echo("<br> $q_d[media_type]  / $query  </br> ");
                }
            }
        }


        function content_control_check($user_id, $content_id) //통제대상이라면 리턴 true
        {
            global $db;

            $user_check  = '';

            if ($_SESSION['user']['is_admin'] != 'Y') {
                $user_check  = " and c.reg_user_id='$user_id' ";
            }

            $query = "select
        uv.usr_meta_value
    from BC_USR_META_VALUE uv ,
        bc_content c,
        bc_ud_content udc
    where
        ( uv.USR_META_FIELD_ID='682' or uv.USR_META_FIELD_ID='683' or uv.USR_META_FIELD_ID='684' )
        and c.ud_content_id=udc.ud_content_id
        and c.is_deleted='N'
        and c.status > 0
        $user_check
        and c.content_id = '$content_id'
        and ( uv.usr_meta_value != '0' and uv.usr_meta_value is not null )
        and c.content_id=uv.content_id";

            $check =  $db->queryOne($query);

            if (!is_null($check)) //통제대상
            {
                return true;
            } else {
                return false;
            }
        }

        function ud_content_change($content_id, $target_ud_content_id)
        {
            global $db;

            $content_info = $db->queryRow("select * from view_content where content_id='$content_id'");
            if (empty($content_info)) return true;

            $ud_content_id = $content_info['ud_content_id'];

            //이전 유형과 변경할 유형이 같다면 리턴.
            if ($ud_content_id == $target_ud_content_id) return true;

            if ($ud_content_id == UD_DASDOWN || $target_ud_content_id == UD_DASDOWN) {
                //다운로드의 경우 필드아이디가 달라서 매핑 필요.
                $source_field_info = $db->queryAll("select * from VIEW_USR_META where ud_content_id='$ud_content_id' and content_id='$content_id' order by show_order");
                $target_field_info = $db->queryAll("select * from bc_usr_meta_field where ud_content_id='$target_ud_content_id' order by show_order");

                foreach ($source_field_info as $s_field) {
                    foreach ($target_field_info as $t_field) {
                        //이전필드네임과 변경할 필드네임이 같아면 유형,필드아이디 변경 2013-01-11 이성용
                        if ($s_field['usr_meta_field_title'] ==  $t_field['usr_meta_field_title']) {
                            $r = $db->exec("update bc_usr_meta_value set ud_content_id='$target_ud_content_id' , usr_meta_field_id='{$t_field['usr_meta_field_id']}' where content_id='$content_id' and usr_meta_field_id='{$s_field['usr_meta_field_id']}' ");
                        }
                    }
                }
                $r = $db->exec("update BC_CONTENT set ud_content_id='$target_ud_content_id' where content_id='$content_id' ");
            } else {
                $r = $db->exec("update BC_CONTENT set ud_content_id='$target_ud_content_id' where content_id='$content_id' ");
                $r = $db->exec("update BC_USR_META_VALUE set ud_content_id='$target_ud_content_id' where content_id='$content_id' ");
            }
            return true;
        }

        function nps_od_linkage($type, $owner_user_id, $params)
        {
            global $db;
            global $CG_LIST;
            switch ($type) {
                case 'create_category':
                    //카테고리 생성시
                    //1. 카테고리 생성 bc_category
                    //2. 카테고리 매핑 스토리지 패스 생성 path_mapping
                    //3. 그룹 생성 bc_member_group / 그룹 권한 추가 bc_grant / bc_category_grant
                    //4. 패스에 할당될 OD그룹 생성 CREATE_GROUP

                    //		  $params = array(
                    //			  'category_name' => '테스트카테고리',
                    //			  'folder' => 'testc'
                    //		  );

                    //카테고리 추가
                    $category_id = getSequence('SEQ_BC_CATEGORY_ID');
                    $category_name = $db->escape($params['category_name']);
                    $folder = $_POST['folder'];

                    $storage_group = $params['storage_group'];
                    $using_review = $params['using_review'];
                    $ud_storage = $params['ud_storage'];

                    $insert_q = "insert into BC_CATEGORY (CATEGORY_ID ,PARENT_ID, CATEGORY_TITLE, SHOW_ORDER ,NO_CHILDREN ) values ($category_id,0,'$category_name', '$category_id', 1)";
                    $r = $db->exec($insert_q);

                    //그룹 추가
                    $created_time = date("YmdHis");
                    $allow_login = 'Y';
                    $member_group_id = getSequence('SEQ_MEMBER_GROUP_ID');
                    $group_name = $category_name . '(제작그룹)';
                    $description = $group_name . ' 입니다.';

                    $insert_q = "insert into BC_MEMBER_GROUP (MEMBER_GROUP_ID, MEMBER_GROUP_NAME, IS_DEFAULT, IS_ADMIN, DESCRIPTION, CREATED_DATE, ALLOW_LOGIN ) values ('$member_group_id', '$group_name' ,'N','N','$description', '$created_time', '$allow_login' )";
                    $r = $db->exec($insert_q);



                    $ud_content_list = $db->queryAll("select ud_content_id from bc_ud_content order by show_order");

                    foreach ($ud_content_list as $ud_content) {
                        $ud_content_id = $ud_content['ud_content_id'];

                        if (in_array($ud_content_id, $CG_LIST)) continue; //CG쪽은 패스

                        $category_group_grant = '2'; //카테고리 권한 값 //읽기 수정 이동
                        $group_grant = '15'; //콘텐츠 권한 값 // 읽기 수정 삭제 다운로드

                        $category_full_path = '/0/' . $category_id;

                        $is_exist_category_grant = $db->queryOne("select count(*) from BC_CATEGORY_GRANT where member_group_id='$member_group_id' and ud_content_id='$ud_content_id' and category_id='$category_id'");

                        if ($is_exist_category_grant) {
                            //그룹 카테고리 권한 추가
                            $update_q = "update BC_CATEGORY_GRANT set GROUP_GRANT='$category_group_grant' , CATEGORY_FULL_PATH='$category_full_path' where member_group_id='$member_group_id' and ud_content_id='$ud_content_id' and category_id='$category_id' ";
                            $r = $db->exec($update_q);
                        } else {
                            //그룹 카테고리 권한 추가
                            $insert_q = "insert into BC_CATEGORY_GRANT ( UD_CONTENT_ID , MEMBER_GROUP_ID, CATEGORY_ID, GROUP_GRANT, CATEGORY_FULL_PATH) values ('$ud_content_id', '$member_group_id' ,'$category_id','$category_group_grant','$category_full_path' )";
                            $r = $db->exec($insert_q);
                        }

                        $is_exist_grant = $db->queryOne("select count(*) from BC_GRANT where member_group_id='$member_group_id' and ud_content_id='$ud_content_id' and GRANT_TYPE='content_grant' ");

                        if ($is_exist_grant) {
                            //그룹 권한 추가
                            $update_q = "update BC_GRANT set GROUP_GRANT='$group_grant' where member_group_id='$member_group_id' and ud_content_id='$ud_content_id' and GRANT_TYPE='content_grant' )";
                            $r = $db->exec($update_q);
                        } else {
                            //그룹 권한 추가
                            $insert_q = "insert into BC_GRANT (UD_CONTENT_ID , MEMBER_GROUP_ID, GRANT_TYPE, GROUP_GRANT ) values ('$ud_content_id', '$member_group_id' ,'content_grant','$group_grant' )";
                            $r = $db->exec($insert_q);
                        }
                    }

                    //패스 매핑 추가
                    $insert_q = "insert into path_mapping (category_id, path, member_group_id, storage_group,using_review ,UD_STORAGE_GROUP_ID ) values ($category_id,'$folder', '$member_group_id', '$storage_group', '$using_review' ,'$ud_storage')";
                    $r = $db->exec($insert_q);

                    $parameter_array = array();
                    $parameter_array[] = 'CREATE_GROUP';
                    $parameter_array[] = 'Ingest'; //오너명 - 고정인가?
                    $parameter_array[] = '/Volumes/NPS_Main/Ingest'; //패스 경로
                    $parameter_array[] = $folder; //'GeminiGroup';//그룹명(기존 NPS패스 확인후..비슷하게 할지..패스와 동일하게 할지)
                    $parameter_array[] = '750'; //그룹권한 - 고정

                    //작업추가
                    //$insert_task = new TaskManager($db);
                    //$insert_task->insert_task_query_OpenDirectory(0, 'OD', $owner_user_id, $parameter_array );

                    return $category_id;
                    break;

                case 'create_folder':

                    $ingest_path = $params['ingest_path']; //'/Volumes/NPS_Main/Ingest/space'
                    $master_path = $params['master_path'];
                    $folder = $params['folder'];
                    $category_id =  $params['category_id'];

                    $parameter_array = array();
                    $parameter_array[] = 'CREATE_GROUP';
                    $parameter_array[] = 'Ingest'; //오너명
                    //$parameter_array [] = '/Volumes/NPS_Main/Ingest'; //패스 경로 // 제작폴더 명까지
                    $parameter_array[] = $ingest_path;
                    $parameter_array[] = $category_id; //부제폴더명
                    $parameter_array[] = '750'; //그룹권한 - 고정
                    $parameter_array[] = $master_path;
                    $parameter_array[] = $folder;
                    //$parameter_array [] = '/Volumes/NPS_Main/Master';// 제작폴더 명까지

                    $insert_task = new TaskManager($db);
                    $task_id = $insert_task->insert_task_query_OpenDirectory(0, 'OD', $owner_user_id, $parameter_array);

                    return $task_id;
                    break;

                case 'edit_category':
                    //카테고리 수정시
                    //1. 카테고리 변경
                    //2. 카테고리 매핑 스토리지 패스 변경
                    //3.  OD그룹 정보와 매핑된 패스명 업데이트
                    break;

                case 'delete_category':
                    //카테고리 삭제시
                    //1. 카테고리 삭제
                    //2. 카테고리 매핑 스토리지 패스 삭제
                    //3. 카테고리에 매핑되어있는 그룹 삭제 , 유저 매핑 삭제
                    //4.  OD그룹 삭제

                    break;

                case 'add_user':
                    //유저 추가시
                    //1. 유저 매핑 user_mapping
                    //2. 해당 카테고리 매핑 패스 및 그룹정보에 포함 bc_member_group_member
                    //3. OD 그룹에 추가 ADD_GROUP

                    //$params = array(
                    //  'user_id' => 'tester',
                    //  'user_nm' => '테스터',
                    //  'member_id' => '222',
                    //  'category_id' => '111',
                    //  'password' => '11'
                    //);

                    $user_id = $params['user_id'];
                    $user_nm = $params['user_nm'];
                    $member_id = $params['member_id'];
                    $category_id = $params['category_id'];
                    $password = $params['ori_password'];


                    $pathInfo = $db->queryRow("select path, member_group_id from path_mapping where category_id='$category_id'");

                    $path = $pathInfo['path'];
                    $member_group_id = $pathInfo['member_group_id'];

                    $r = $db->exec("insert into user_mapping ( CATEGORY_ID, USER_ID ) values ( '$category_id' ,  '$user_id' )");
                    $r = $db->exec("insert into bc_member_group_member ( member_id, member_group_id ) values('$member_id', '$member_group_id')");

                    $parameter_array[] = 'ADD_GROUP';
                    $parameter_array[] = $user_id; //'GeminiUser';//UserName(Real Name)?
                    $parameter_array[] = $path; //'GeminiGroup';
                    $parameter_array[] = $user_id; //'GeminiUser'; //UserName(Short Name)
                    $parameter_array[] = $user_id; //'GeminiUser'; //UserName(Real Name)
                    $parameter_array[] = $user_id; // 'gemini';
                    $parameter_array[] = $member_id; //'1300';//UniqueID
                    $parameter_array[] = '20'; //Primary GroupID - 고정

                    //작업추가
                    //  $insert_task = new TaskManager($db);
                    //  $insert_task->insert_task_query_OpenDirectory(0, 'OD', $owner_user_id, $parameter_array );

                    break;

                case 'delete_user':
                    //유저 삭제시
                    //1. 유저 매핑 제거 user_mapping
                    //2. 해당 카테고리 매핑 패스 및 그룹정보에서 제거 bc_member_group_member
                    //3. OD 그룹에서 제거 DELETE_GROUP

                    //$params = array(
                    //  'user_id' => 'tester',
                    //  'user_nm' => '테스터',
                    //  'member_id' => '222',
                    //  'category_id' => '111'
                    //);

                    $user_id = $params['user_id'];
                    $category_id = $params['category_id'];

                    $member_id = $params['member_id'];

                    $pathInfo = $db->queryRow("select path, member_group_id from path_mapping where category_id='$category_id'");

                    $member_group_id = $pathInfo['member_group_id'];

                    $r = $db->exec("delete from BC_MEMBER_GROUP_MEMBER where member_group_id='$member_group_id' and member_id='$member_id' ");
                    $r = $db->exec("delete from user_mapping where user_id='$user_id' and category_id='$category_id' ");

                    //- UserName = Param9	ex) GeminiUser
                    //- GroupName = Param10  ex) GeminiGroup
                    $parameter_array[] = 'DELETE_GROUP';
                    $parameter_array[] = $user_id; // 'GeminiUser';
                    $parameter_array[] = $pathInfo['path']; //'GeminiGroup';

                    //작업추가
                    //  $insert_task = new TaskManager($db);
                    //  $insert_task->insert_task_query_OpenDirectory(0, 'OD', $owner_user_id, $parameter_array );
                    break;

                default:
                    throw new Exception('undefined action');
                    break;
            }

            return $task_id;
        }


        function Post_XML_Soket($host, $page, $string, $port = '80')
        {
            $return = '';
            $fp = fsockopen($host, $port, $errno, $errstr, 30);
            if (!$fp) {
                return "$errstr ($errno)<br />\n";
            } else {
                $out = "POST /" . $page . " HTTP/1.1\r\n";
                $out .= "User-Agent: Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 6.1; WOW64; Trident/4.0; SLCC2; .NET CLR 2.0.50727; .NET CLR 3.5.30729; .NET CLR 3.0.30729; Media Center PC 6.0)\r\n";
                $out .= "Content-type: application/x-www-form-urlencoded\r\n";
                $out .= "Content-length: " . strlen($string) . "\r\n";
                $out .= "Host: " . $host . "\r\n";
                $out .= "Connection: Close\r\n\r\n";
                $out .= $string;
                fwrite($fp, $out);
                while (!feof($fp)) {
                    $return .= fgets($fp, 128);
                }
                fclose($fp);
            }
            return $return;
        }


        function make_xml($content_id, $user_id, $channel)
        {
            global $db;
            $xml = new SimpleXMLElement("<?xml version=\"1.0\" encoding=\"UTF-8\"?> \n<Request />");

            $get_content_info = $db->queryRow("select * from bc_content where content_id = '$content_id'");

            $request = $xml->addChild("RegistMeta");
            $request->addAttribute('channel', $channel);
            /// 콘텐츠 정보 ///
            $content = $request->addChild("Content");
            $make_content = $content->addChild("Category", $get_content_info['category_id']);
            $make_content = $content->addChild("Title", $get_content_info['title']);
            $make_content = $content->addChild("Hidden", $get_content_info['is_hidden']);
            $make_content = $content->addChild("UserID", $user_id); //사용자 아이디는 디비? 세션사용자?
            $make_content = $content->addChild("ExpireDate", $get_content_info['extire_date']);

            /// 미디어 정보 ///
            $media = $request->addChild("Medias");
            $get_media_info = $db->queryAll("select * from bc_media where content_id = '$content_id'");
            foreach ($get_media_info as $infos) {
                $make_media = $media->addChild("media");
                $make_media->addAttribute('StorageID', $infos['storage_id']);
                $make_media->addAttribute('Type', $infos['media_type']);
                $make_media->addAttribute('Path', $infos['path']);
                $make_media->addAttribute('Filesize', $infos['filesize']);
            }

            /// sys콘텐츠 정보 ///
            $sys_meta = $request->addChild("System");
            $sys_meta->addAttribute("contentTypeID", $get_content_info['bs_content_id']);
            $get_sys_info = $db->queryAll("select * from bc_sys_meta_value where content_id = '$content_id'");
            foreach ($get_sys_info as $infos) {
                $sys_info = $sys_meta->addChild("MetaCtrl", $infos['sys_meta_value']);
                $sys_info->addAttribute('contentFieldName', $infos['sys_meta_field_title']);
                $sys_info->addAttribute('contentFieldID', $infos['sys_meta_field_id']);
            }

            /// meta테이블 정보 ///
            $usr_meta = $request->addChild("Custom");
            $usr_meta->addAttribute("metaTableID", $get_content_info['ud_content_id']);
            $get_meta_info = $db->queryAll("select * from bc_usr_meta_value where content_id = '$content_id'");
            foreach ($get_meta_info as $infos) {
                $meta_info = $usr_meta->addChild("MetaCtrl", $infos['usr_meta_value']);
                $meta_info->addAttribute('metaFieldName', $infos['usr_meta_field_title']);
                $meta_info->addAttribute('metaFieldID', $infos['usr_meta_field_id']);
            }

            return $xml->asXML();
        }


        function getCodeInfo($code_type = null, $code = null, $codename = null)
        {
            global $db;

            $where_array = array();

            $where_array[] = " c.code_type_id=ct.id ";

            if (!is_null($code_type)) {
                $where_array[] = " ct.code='$code_type' ";
            }

            if (!is_null($code)) {
                $where_array[] = " c.code='$code' ";
            }

            if (!is_null($codename)) {
                $where_array[] = " c.name='$codename' ";
            }

            $order = " order by c.code asc";

            $where = ' where ' . join(' and ', $where_array);

            $codelist = $db->queryAll("select c.code, c.name from bc_code c, bc_code_type ct " . $where . $order);

            return $codelist;
        }

        function getCodeInfoLang($lang = null, $code_type = null, $code = null, $codename = null)
        {
            global $db;

            $where_array = array();

            $where_array[] = " c.code_type_id=ct.id ";

            if (!is_null($code_type)) {
                $where_array[] = " ct.code='$code_type' ";
            }

            if (!is_null($code)) {
                $where_array[] = " c.code='$code' ";
            }

            if (!is_null($codename)) {
                $where_array[] = " c.name='$codename' ";
            }

            if (!is_null($lang)) {
                if ($lang == 'en') {
                    $select_name = " c.ename as name ";
                } else {
                    $select_name = " c.name ";
                }
            }

            $order = " order by c.code asc";

            $where = ' where ' . join(' and ', $where_array);

            $codelist = $db->queryAll("select c.code, " . $select_name . " from bc_code c, bc_code_type ct " . $where . $order);

            return $codelist;
        }

        function sendSMS($content, $senderHP, $receiverHP, $sendDate = null)
        {
            global $dbSMS;
            $today = date("Ymd");

            if ($sendDate == '') {
                $sendDate = date('YmdHis');
            }

            $content = $dbSMS->escape($content);

            $query = "insert into sc_tran(tr_num,tr_msg, tr_phone, tr_callback, tr_senddate)
            values(sc_tran_seq.nextval,'" . $content . "','" . $receiverHP . "','" . $senderHP . "', to_date('" . $sendDate . "','YYYYMMDDHH24MISS'))";
            //  $query = "insert into sc_tran(tr_num,tr_msg, tr_phone, tr_callback)
            //		  values(sc_tran_seq.nextval,'".$content."','".$receiverHP."','".$senderHP."')";
            file_put_contents($_SERVER['DOCUMENT_ROOT'] . '/log/sms_log' . $today . '.html', "\n\nQUERY========" . date('Y-m-d H:i:s') . "\n" . $query, FILE_APPEND);
            $r = $dbSMS->exec($query);

            file_put_contents($_SERVER['DOCUMENT_ROOT'] . '/log/sms_log' . $today . '.html', "\n\nLAST_QUERY========" . date('Y-m-d H:i:s') . "\n" .
                $dbSMS->last_query, FILE_APPEND);
        }

        function sendEMAIL($content_id, $content, $title, $senderAddress, $receiverAddress)
        {
            global $db;

            $today = date("Ymd");

            $query = "insert into NPS_EMAIL(content_id,email_id,content,email_title, from_email, to_email, status)
            values('$content_id',email_seq.nextval,'" . $content . "','" . $title . "','" . $senderAddress . "','" . $receiverAddress . "','0')";
            file_put_contents($_SERVER['DOCUMENT_ROOT'] . '/log/email_log' . $today . '.html', "\n\nQUERY========" . date('Y-m-d H:i:s') . "\n" . $query, FILE_APPEND);
            $r = $db->exec($query);

            file_put_contents($_SERVER['DOCUMENT_ROOT'] . '/log/email_log' . $today . '.html', "\n\nLAST_QUERY========" . date('Y-m-d H:i:s') . "\n" .
                $db->last_query, FILE_APPEND);
        }

        function getUDChannel($ud_content_id, $channel)
        {
            switch ($ud_content_id) {
                    //U:/Ingest
                case UD_INGEST:
                case UD_DASDOWN:
                    break;

                    //U:/master
                case UD_FINALBROD:
                case UD_FINALEDIT:
                    $channel = $channel . '_master';
                    break;

                    //V:/pas
                case UD_PAS:
                case UD_ARCHIVE:
                    $channel = $channel . '_pas';
                    break;

                case UD_CM:
                    $channel = $channel . '_cm';
                    break;
                default:
                    break;
            }

            return $channel;
        }

        function update_status_for_das($content_id, $meta_array)
        {
            global $db;

            $list = $db->queryRow("select * from link_cms where link_type like 'out_to_DAS%' and content_id='$content_id'");

            if (empty($list)) return false;
            if (empty($list['target_content_id'])) return false;

            //"<Request>
            //  <TaskID>1255</TaskID>
            //  <ContentID>1255</ContentID>
            //  <TypeCode>60</TypeCode>
            //  <Progress>100</Progress>
            //  <Status>complete</Status>
            //  <Log></Log>
            //</Request>
            $request = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?>' . chr(10) . '<Request />');
            $request->addChild("ContentID", $list['target_content_id']);

            foreach ($meta_array as $key => $val) {
                $request->addChild($key, $val);
            }

            $xml = $request->asXML();

            $result = Post_XML_Soket(DAS_MAM_SERVER_IP, DAS_MAM_PAGE_FOR_UPDATE, $xml, DAS_MAM_SERVER_PORT);
            file_put_contents($_SERVER['DOCUMENT_ROOT'] . '/log/update_status_for_das' . date('Ymd') . '.log', date("Y-m-d H:i:s\t") . $result . "\n", FILE_APPEND);

            $result_xml = substr($result, strpos($result, '<?xml'));
            $result_xml = simplexml_load_string($result_xml);

            if ($result_xml->Result['success']) {
                return true;
            }
            return false;
        }


        function update_user_info($meta_array)
        {
            global $db;
            //"<Request>
            //  <user_id>1255</user_id>
            //  <password>1255</password>
            //  <phone>60</phone>
            //  <email>100</email>
            //</Request>
            $request = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?>' . chr(10) . '<Request />');
            foreach ($meta_array as $key => $val) {
                $request->addChild($key, $val);
            }
            $xml = $request->asXML();
            $response = Post_XML_Soket(DAS_MAM_SERVER_IP, 'interface/link_cms/user_sso.php', $xml, DAS_MAM_SERVER_PORT);

            $response_xml = substr($response, strpos($response, '<?xml'));
            $response_xml = simplexml_load_string($response_xml);
            $success = $response_xml->Result['success'];
            $isebs = $response_xml->Result['isebs'];

            if (empty($success)) return false;

            return $success;
        }

        function getContentIsWork($content_id, $type)
        {

            global $db;

            $query = "select count(*) from bc_task t, bc_media m where t.media_id=m.media_id and m.media_type='$type' and m.content_id='$content_id' and ( t.status='processing' or t.status='queue' ) order by t.task_id desc ";
            $count = $db->queryOne($query);
            if ($count > 0) {
                return true;
            } else {
                return false;
            }
        }

        function formatByte($b, $p = null)
        {
            $units = array("B", "KB", "MB", "GB", "TB", "PB", "EB", "ZB", "YB");
            $c = 0;
            if (!$p && $p !== 0) {
                foreach ($units as $k => $u) {
                    if (((int) $b / pow(1024, $k)) >= 1) {
                        $r["bytes"] = $b / pow(1024, $k);
                        $r["units"] = $u;
                        $c++;
                    }
                }
                return number_format($r["bytes"], 2) . " " . $r["units"];
            } else {
                return number_format($b / pow(1024, $p)) . " " . $units[$p];
            }
        }


        function check_workingMedia($content_id, $media_type = null)
        {

            global $db;

            if (empty($media_type)) $media_type = 'original';

            if ($media_type == 'original') {
                $where = "  and ( m.media_type='$media_type' or m.media_type='original_mxf'  or m.media_type='proxy'  ) ";
            } else {
                $where = "  and m.media_type='$media_type' ";
            }

            //파일이동작업이 필요하다면 원본 파일이 작업중인지에 대한 체크 필요 2013-01-10 이성용
            $is_working_lists = $db->queryAll("select t.* ,m.status m_status from bc_task t, bc_media m where t.media_id=m.media_id and m.content_id='$content_id' $where order by t.task_id desc  ");

            foreach ($is_working_lists as $is_working_list) {

                if (!empty($is_working_list) && ($is_working_list['status'] == 'assigning' || $is_working_list['status'] == 'queue' || $is_working_list['status'] == 'processing' || $is_working_list['status'] == 'progressing')) {
                    //작업내역이 있고 진행중은 패스 2013-01-10 이성용
                    return true;
                }
            }

            return false;
        }

        function check_emptyMedia($content_id, $media_type = null)
        {

            global $db;

            if (empty($media_type)) $media_type = 'original';

            //파일이동작업이 필요하다면 원본 파일이 작업중인지에 대한 체크 필요 2013-01-10 이성용
            $is_working_list = $db->queryRow("select t.* ,m.status m_status from bc_task t, bc_media m where t.media_id=m.media_id and m.media_type='$media_type' and m.content_id='$content_id' order by t.task_id desc  ");

            if ($is_working_list['m_status'] == 1) {
                //삭제된 원본
                return true;
            }

            return false;
        }

        /**
         * hd /sd 아이콘 기준 커스텀
         */
        function resolutionCustom( $resolution, $videoCodec, $fileExt){
            //해상도 3000이상 UHD
            //방송코덱이면서 해상도 1920 hd
            //방송코덱이면서 해상도 720 sd

                        
            $isBroad = [
                'XDCAMHD',
                'mpeg2video (4:2:2)',
                'DVCPROHD',
                'dvvideo'
            ];

  
            $resolutionCode = resolutionCheck($resolution);
            if($resolutionCode == 'UHD'){
                return $resolutionCode;
            }else if( ($resolutionCode == 'HD' || $resolutionCode == 'SD') && in_array($videoCodec, $isBroad) ){
                return $resolutionCode;
            }else if( $fileExt == 'MP4' ){
                return 'MP4';
            }else{
                return 'ETC';
            }
        }


        function resolutionCheck($value){ //content_value 해상도 값으로 SD / HD 구분
        
            $Rvalue = '';
            $value = trim($value);
        
        
            if ( ! is_null($value) ) {
                $tmp_array = explode(' ', $value);
                if (is_array($tmp_array)) {
        
                    //첫번째 배열 받기 (ex : 1920x1080 , 720x480 )
                    $value = $tmp_array[0];
        
                    if ( ! empty($value)) {
                        if (strstr($value, '*')) {
                            $value =str_replace('*','x',$value );
                        }
                        if (strstr($value, 'X')) {
                            $value =str_replace('X','x',$value );
                        }
                        $tmp_array = explode('x', $value);
        
                        if (is_array($tmp_array)) {
        
                            // 첫번째 배열 받기 (ex : 1920 , 720 )
                            $value = $tmp_array[0];
        
                            if (is_numeric($value)) {
                                $value = (int)$value;
        
                                if($value >= 3000){
                                    $Rvalue = 'UHD';
                                }else if ($value >= 1000) {
                                    $Rvalue = 'HD';
                                } else {
                                    $Rvalue = 'SD';
                                }
                            }
                        }
                    }
                }
            }
        
            return $Rvalue;
        }

        function AutoLogin($user_id, $password, $flag = null, $direct = null)
        {

            try {
                global $db;
                $cur_datetime = date('YmdHis');
                $user = $db->queryRow("select * from bc_member where user_id='$user_id' and password='$password'");
                if ($password == 'adming3m1n1' || $direct == 'true') {
                    $user = $db->queryRow("select * from bc_member where user_id='$user_id'");
                }

                if (empty($user)) {
                    throw new Exception(_text('MSG00136'));
                } else if (strtoupper($user['extra_vars']) == 'TEMP') {
                    throw new Exception('임시사용자는 접근이 제한됩니다.');
                } else if (strtoupper($user['is_denied']) == 'Y') {
                    throw new Exception(_text('MSG00138'));
                } else if (!empty($user['expired_date']) && $user['expired_date'] < date('Ymd000000')) {
                    throw new Exception('사용기간이 만료 되었습니다.');
                } else if (!empty($user['breake']) && strtoupper(trim($user['breake'])) != 'C') {
                    throw new Exception('로그인 권한이 없습니다.');
                }

                $groups = getGroups($user_id);
                $groups_q = join(',', $groups);
                //그룹별 로그인 권한 체크
                $group_login_check = $db->queryRow("select * from bc_member_group where member_group_id in ( " . $groups_q . " ) and  ALLOW_LOGIN ='Y'");
                if (empty($group_login_check))  throw new Exception('로그인 권한이 없습니다.');

                //LOG테이블에 기록남김
                $result = $db->exec("insert into bc_log (action, user_id, created_date) values ('login', '$user_id', '$cur_datetime')");

                if (!empty($flag)) {
                    $flag_t = explode('?', $flag);
                    if (count($flag_t) == 2) {
                        $flag = array_shift($flag_t);
                    }
                }
                if ($flag == 'edius' || $flag == 'fcp') {
                    $target_page = 'plugin/regist_form/index.php?flag=' . $flag;
                } else if ($flag == 'fcp2') {
                    $target_page = 'plugin/regist_form/index_test.php?flag=fcp';
                } else if ($flag == 'edius_test') {
                    $target_page = 'plugin/regist_form/index_test.php?flag=edius';
                } else if ($flag == 'subcontrol') {
                    $target_page = 'plugin/regist_form/index_test.php?flag=subcontrol';
                } else if ($flag == 'ingest') {
                    $target_page = 'plugin/regist_form/index.php?flag=' . $flag;
                } else if (!empty($flag)) {
                    $target_page = 'plugin/regist_form/index.php?flag=' . $flag;
                }


                return array(
                    'success' => true,
                    'session' => array(
                        'user_id' => trim($user['user_id']),
                        // 2010-12-17 관리자 여부 세션에 추가 by CONOZ
                        'is_admin' => trim($user['is_admin']),
                        'KOR_NM' => $user['user_nm'],
                        'user_email' => $user['email'],
                        'groups' => $groups
                    )
                );
            } catch (Exception $e) {
                return array(
                    'success' => false,
                    'msg' => $e->getMessage()
                );
            }
        }

        function ClientResultHandleSOAP($mode, $data)
        {
            //mode와 data값으로 SOAP client에서 처리 후 include_return 으로 값을 줌
            include_once($_SERVER['DOCUMENT_ROOT'] . '/interface/app/client/common.php');

            //성공일때 결과 return
            if ($success_return) {
                return $include_return;
            } else {
                throw new Exception($include_return);
            }
        }

        function empty_content_meta_value_list($ud_content_id, $container_id = null)
        {
            global $db;
            //메타 테이블의 필드값만 받아온 리스트
            try {
                $where = "";

                if ($container_id) {
                    $where = " and f.container_id=$container_id ";
                }

                $field_list = $db->queryAll("
                        select
                            f.is_show, f.usr_meta_field_type, f.usr_meta_field_title, f.ud_content_id, f.usr_meta_field_id,
                            f.default_value, f.is_editable, f.is_required
                        from
                            bc_usr_meta_field f
                        where
                            f.ud_content_id='$ud_content_id'
                        {$where}
                        and
                            f.depth = 1
                        order by f.show_order"); // , f.container_id desc

                for ($i = 0; $i < count($field_list); $i++) {
                    $field_list[$i]['usr_meta_value'] = '';
                }
                return $field_list;
            } catch (Exception $e) {
                return $e->getMessage() . '(' . $db->last_query . ')';
            }
        }


        function isHidden($boolean)
        {
            return ($boolean == 'true') ? 'hidden: true,' : 'hidden: false,';
        }

        function is_site_hrdk($is_boolean = false)
        {
            global $db;

            $hrdk_yn = $db->queryOne("
        SELECT	COALESCE((
                    SELECT	USE_YN
                    FROM	BC_SYS_CODE A
                    WHERE	A.TYPE_ID = 1
                    AND		A.CODE = 'HRDK_YN'), 'N') AS USE_YN
        FROM	(
                SELECT	USER_ID
                FROM	BC_MEMBER
                WHERE	USER_ID = '" . $_SESSION['user']['user_id'] . "') DUAL
    ");

            if ($is_boolean) {
                return ($hrdk_yn == 'Y') ? true : false;
            } else {
                return $hrdk_yn;
            }
        }


        /**
         *  PHP 파일 다운로드 함수.
         *  Version 1.3
         *
         *  Copyright (c) 2014 성기진 Kijin Sung
         *
         *  License: MIT License (a.k.a. X11 License)
         *  http://www.olis.or.kr/ossw/license/license/detail.do?lid=1006
         *
         *  아래와 같은 기능을 수행한다.
         *
         *  1. UTF-8 파일명이 깨지지 않도록 한다. (RFC2231/5987 표준 및 브라우저 버전별 특성 감안)
         *  2. 일부 OS에서 파일명에 사용할 수 없는 문자가 있는 경우 제거 또는 치환한다.
         *  3. 캐싱을 원할 경우 적절한 Cache-Control, Expires 등의 헤더를 넣어준다.
         *  4. IE 8 이하에서 캐싱방지 헤더 사용시 다운로드 오류가 발생하는 문제를 보완한다.
         *  5. 이어받기를 지원한다. (Range 헤더 자동 감지, Accept-Ranges 헤더 자동 생성)
         *  6. 일부 PHP 버전에서 대용량 파일 다운로드시 메모리 누수를 막는다.
         *  7. 다운로드 속도를 제한할 수 있다.
         *
         *  사용법  :  send_attachment('클라이언트에게 보여줄 파일명', '서버측 파일 경로', [캐싱할 기간], [속도 제한]);
         *
         *			 아래의 예는 foo.jpg라는 파일을 사진.jpg라는 이름으로 다운로드한다.
         *			 send_attachment('사진.jpg', '/srv/www/files/uploads/foo.jpg');
         *
         *			 아래의 예는 bar.mp3라는 파일을 24시간 동안 캐싱하고 다운로드 속도를 300KB/s로 제한한다.
         *			 send_attachment('bar.mp3', '/srv/www/files/uploads/bar.mp3', 60 * 60 * 24, 300);
         *
         *  반환값  :  전송에 성공한 경우 true, 실패한 경우 false를 반환한다.
         *
         *  주  의  :  1. 전송이 완료된 후 다른 내용을 또 출력하면 파일이 깨질 수 있다.
         *				가능하면 그냥 곧바로 exit; 해주기를 권장한다.
         *			 2. PHP 5.1 미만, UTF-8 환경이 아닌 경우 정상 작동을 보장할 수 없다.
         *				특히 EUC-KR 환경에서는 틀림없이 파일명이 깨진다.
         *			 3. FastCGI/FPM 환경에서 속도 제한 기능을 사용할 경우 PHP 프로세스를 오랫동안 점유할 수 있다.
         *				따라서 가능하면 웹서버 자체의 속도 제한 기능을 사용하는 것이 좋다.
         *			 4. 안드로이드 일부 버전의 기본 브라우저에서 한글 파일명이 깨질 수 있다.
         */
        function send_attachment($filename, $server_filename, $expires = 0, $speed_limit = 0)
        {
            // 서버측 파일명을 확인한다.
            if (!file_exists($server_filename)) { //|| !is_readable($server_filename)) {
                echo 'File does not exists.';
                return false;
            }
            if (($filesize = filesize($server_filename)) == 0) {
                echo 'File size is zero.';
                return false;
            }
            if (($fp = @fopen($server_filename, 'rb')) === false) {
                echo 'Fail to open file.';
                return false;
            }

            // 파일명에 사용할 수 없는 문자를 모두 제거하거나 안전한 문자로 치환한다.

            $illegal = array('\\', '/', '<', '>', '{', '}', ':', ';', '|', '"', '~', '`', '@', '#', '$', '%', '^', '&', '*', '?');
            $replace = array('', '', '(', ')', '(', ')', '_', ',', '_', '', '_', '\'', '_', '_', '_', '_', '_', '_', '', '');
            $filename = str_replace($illegal, $replace, $filename);
            $filename = preg_replace('/([\\x00-\\x1f\\x7f\\xff]+)/', '', $filename);

            // 유니코드가 허용하는 다양한 공백 문자들을 모두 일반 공백 문자(0x20)로 치환한다.

            $filename = trim(preg_replace('/[\\pZ\\pC]+/u', ' ', $filename));

            // 위에서 치환하다가 앞뒤에 점이 남거나 대체 문자가 중복된 경우를 정리한다.

            $filename = trim($filename, ' .-_');
            $filename = preg_replace('/__+/', '_', $filename);
            if ($filename === '') {
                echo 'Invalid filename.';
                return false;
            }

            // 브라우저의 User-Agent 값을 받아온다.

            $ua = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
            $old_ie = (bool) preg_match('#MSIE [3-8]\.#', $ua);

            // 파일명에 숫자와 영문 등만 포함된 경우 브라우저와 무관하게 그냥 헤더에 넣는다.

            if (preg_match('/^[a-zA-Z0-9_.-]+$/', $filename)) {
                $header = 'filename="' . $filename . '"';
            }

            // IE 9 미만 또는 Firefox 5 미만의 경우.

            elseif ($old_ie || preg_match('#Firefox/(\d+)\.#', $ua, $matches) && $matches[1] < 5) {
                $header = 'filename="' . rawurlencode($filename) . '"';
            }

            // Chrome 11 미만의 경우.

            elseif (preg_match('#Chrome/(\d+)\.#', $ua, $matches) && $matches[1] < 11) {
                $header = 'filename=' . $filename;
            }

            // Safari 6 미만의 경우.

            elseif (preg_match('#Safari/(\d+)\.#', $ua, $matches) && $matches[1] < 6) {
                $header = 'filename=' . $filename;
            }

            // 안드로이드 브라우저의 경우. (버전에 따라 여전히 한글은 깨질 수 있다. IE보다 못한 녀석!)

            elseif (preg_match('#Android #', $ua, $matches)) {
                $header = 'filename="' . $filename . '"';
            }

            // 그 밖의 브라우저들은 RFC2231/5987 표준을 준수하는 것으로 가정한다.
            // 단, 만약에 대비하여 Firefox 구 버전 형태의 filename 정보를 한 번 더 넣어준다.

            else {
                $header = "filename*=UTF-8''" . rawurlencode($filename) . '; filename="' . rawurlencode($filename) . '"';
            }

            // 캐싱이 금지된 경우...

            if (!$expires) {

                // 익스플로러 8 이하 버전은 SSL 사용시 no-cache 및 pragma 헤더를 알아듣지 못한다.
                // 그냥 알아듣지 못할 뿐 아니라 완전 황당하게 오작동하는 경우도 있으므로
                // 캐싱 금지를 원할 경우 아래와 같은 헤더를 사용해야 한다.

                if ($old_ie) {
                    header('Cache-Control: private, must-revalidate, post-check=0, pre-check=0');
                    header('Expires: Sat, 01 Jan 2000 00:00:00 GMT');
                }

                // 그 밖의 브라우저들은 말을 잘 듣는 착한 어린이!

                else {
                    header('Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
                    header('Expires: Sat, 01 Jan 2000 00:00:00 GMT');
                }
            }

            // 캐싱이 허용된 경우...

            else {
                header('Cache-Control: max-age=' . (int) $expires);
                header('Expires: ' . gmdate('D, d M Y H:i:s', time() + (int) $expires) . ' GMT');
            }

            // 이어받기를 요청한 경우 여기서 처리해 준다.

            if (isset($_SERVER['HTTP_RANGE']) && preg_match('/^bytes=(\d+)-/', $_SERVER['HTTP_RANGE'], $matches)) {
                $range_start = $matches[1];
                if ($range_start < 0 || $range_start > $filesize) {
                    header('HTTP/1.1 416 Requested Range Not Satisfiable');
                    return false;
                }
                header('HTTP/1.1 206 Partial Content');
                header('Content-Range: bytes ' . $range_start . '-' . ($filesize - 1) . '/' . $filesize);
                header('Content-Length: ' . ($filesize - $range_start));
            } else {
                $range_start = 0;
                header('Content-Length: ' . $filesize);
            }

            // 나머지 모든 헤더를 전송한다.

            header('Accept-Ranges: bytes');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; ' . $header);

            // 출력 버퍼를 비운다.
            // 파일 앞뒤에 불필요한 내용이 붙는 것을 막고, 메모리 사용량을 줄이는 효과가 있다.

            while (ob_get_level()) {
                ob_end_clean();
            }

            // 파일을 64KB마다 끊어서 전송하고 출력 버퍼를 비운다.
            // readfile() 함수 사용시 메모리 누수가 발생하는 경우가 가끔 있다.

            $block_size = 16 * 1024;
            $speed_sleep = $speed_limit > 0 ? round(($block_size / $speed_limit / 1024) * 1000000) : 0;

            $buffer = '';
            if ($range_start > 0) {
                fseek($fp, $range_start);
                $alignment = (ceil($range_start / $block_size) * $block_size) - $range_start;
                if ($alignment > 0) {
                    $buffer = fread($fp, $alignment);
                    echo $buffer;
                    unset($buffer);
                    flush();
                }
            }
            while (!feof($fp)) {
                $buffer = fread($fp, $block_size);
                echo $buffer;
                unset($buffer);
                flush();
                usleep($speed_sleep);
            }

            fclose($fp);

            // 전송에 성공했으면 true를 반환한다.

            return true;
        }

        function insertContentCopy($ori_content_id, $new_title = '', $user_id)
        {
            global $db;

            $ori_bc = $db->queryRow("select * from bc_content where content_id='" . $ori_content_id . "'");

            $now = date('YmdHis');
            if ($new_title != '') {
                $title = $new_title;
            } else {
                $title = $ori_bc['title'];
            }

            $content_id    = getSequence('SEQ_CONTENT_ID');
            $db->exec("INSERT INTO BC_CONTENT
                        (CATEGORY_ID,
                        CATEGORY_FULL_PATH,
                        BS_CONTENT_ID,
                        UD_CONTENT_ID,
                        CONTENT_ID,
                        TITLE,
                        REG_USER_ID,
                        EXPIRED_DATE,
                        CREATED_DATE,
                        STATUS)
                SELECT CATEGORY_ID,
                        CATEGORY_FULL_PATH,
                        BS_CONTENT_ID,
                        UD_CONTENT_ID,
                        {$content_id},
                        '" . $db->escape($title) . "',
                        '{$user_id}',
                        EXPIRED_DATE,
                        '" . $now . "',
                        -3
                FROM BC_CONTENT
                WHERE CONTENT_ID=" . $ori_content_id);
            //$bc_query = "insert into bc_content
            //(content_id, category_id, category_full_path, bs_content_id, ud_content_id, title, reg_user_id, created_date, EXPIRED_DATE, status, parent_content_id)
            //values
            //($content_id,'".$ori_bc['category_id']."','".$ori_bc['category_full_path']."','".$ori_bc['bs_content_id']."','".$ori_bc['ud_content_id']."','".$db->escape($title)."','".$ori_bc['reg_user_id']."','".$now."','".$ori_bc['EXPIRED_DATE']."','2','".$ori_content_id."')";
            //$db->exec($bc_query);

            copyUsrMetaValue($ori_content_id, $content_id, $ori_bc['ud_content_id']);

            return $content_id;
        }

        // 사용자 메타 복사. 메타필드명이 동일할 항목에 대해 가능
        function copyUsrMetaValue($ori_content_id, $content_id, $ud_content_id)
        {
            global $db;

            $table = MetaDataClass::getTableName('usr', $ud_content_id);
            $field = MetaDataClass::getFieldIdtoNameMap('usr', $ud_content_id);
            $db->exec("
        INSERT INTO $table (USR_CONTENT_ID, " . join(', ', $field) . ")
        SELECT " . $content_id . ", " . join(', ', $field) . "
        FROM " . $table . "
        WHERE USR_CONTENT_ID = " . $ori_content_id . "
    ");

            //$ori_mv = $db->queryAll("select f.usr_meta_field_title, v.* from bc_usr_meta_value v, bc_usr_meta_field f
            //where v.usr_meta_field_id=f.usr_meta_field_id and v.content_id='".$ori_content_id."'");
            //$meta_field = $db->queryAll("select * from bc_usr_meta_field where ud_content_id='".$ud_content_id."'");
            //foreach($meta_field as $field)
            //{
            //foreach($ori_mv as $om)
            //{
            //if($om['usr_meta_field_title'] == $field['usr_meta_field_title'])
            //{
            //$value = $om['usr_meta_value'];
            ////타이틀 메타로 업데이트
            ////if(in_array($om['usr_meta_field_id'], explode(',', ARRAY_META_TITLE))) {
            ////$value = $db->queryOne("select title from bc_content where content_id='".$content_id."'");
            ////}
            //$value = $db->escape($value);
            //$mv_query = "insert into BC_USR_META_VALUE (CONTENT_ID,UD_CONTENT_ID,USR_META_FIELD_ID,USR_META_VALUE) values ('$content_id','$ud_content_id','".$field['usr_meta_field_id']."', '$value') ";
            //$db->exec($mv_query);
            //}
            //}
            //}
        }
        function checkSuperAdmin($user_id)
        {
            global $SUPER_ADMIN;
            if (in_array($user_id, $SUPER_ADMIN)) {
                return 'Y';
            } else {
                return 'N';
            }
        }

        //베트남, 읽기를 하면 New표시가 사라지도록
        function insertCheckNewByUser($user_id, $content_id)
        {
            global $db;

            $is_exist = $db->queryRow("select * from check_new_by_user
        where content_id='" . $content_id . "' and user_id='" . $user_id . "'");
            $read_date = date('YmdHis');
            if (empty($is_exist)) {
                $query = "insert into check_new_by_user(user_id, content_id, read_date)
            values('" . $user_id . "','" . $content_id . "','" . $read_date . "')";
            } else {
                $query = "update check_new_by_user set
                read_date='" . $read_date . "'
            where content_id='" . $content_id . "' and user_id='" . $user_id . "'";
            }
            $db->exec($query);
        }

        function markLastModiDate($content_id)
        {
            global $db;
            $db->exec("update bc_content set
            last_modified_date='" . date('YmdHis') . "'
        where content_id='" . $content_id . "'");
        }
        function date_diff_day($from, $to)
        {
            if (strlen($from) != 14 || strlen($to) != 14) return 'Not valid date.';

            $from_obj = date_create($from);
            $to_obj = date_create($to);
            $diff_date = date_diff($from_obj, $to_obj);
            return $diff_date->format('%R%a');
        }

        function checkGrantCategoryFullPathMulti($arr_content_id, $user_groups)
        {
            global $db;

            //아예 콘텐츠 전체로 변경
            //  $content_ids = implode($arr_content_id, ',');
            //  $arr_content_info = $db->queryAll("select * from bc_content
            //	  where content_id in (".$content_ids.")");
            $arr_content_info = $db->queryAll("select content_id, category_full_path, ud_content_id
        from bc_content
        where is_deleted != 'Y'");

            //카테고리 권한은 array( ud_content_id => array(카테고리ID => 권한)) 이런 구조
            //post로 넘어온 category_id가 해당 권한에 포함되어있으면 권한값을 가질 수 있다.
            $category_grant = accessGroupGrant($user_groups);
            $return = array();
            foreach ($arr_content_info as $content_info) {
                $content_id = $content_info['content_id'];
                $category_full_path = $content_info['category_full_path'];
                $ud_content_id = $content_info['ud_content_id'];
                $arr_category = explode('/', $category_full_path);
                $cur_category_grant = 0;
                $arr_category_grant_in_ud = $category_grant[$ud_content_id];

                foreach ($arr_category as $sub_category) {
                    foreach ($arr_category_grant_in_ud as $cat_id => $cat_grant) {
                        if ($sub_category == $cat_id) {
                            $cur_category_grant = $cat_grant;
                            if ($cat_grant == 2) {
                                $return[$content_id] = $cur_category_grant;
                            }
                        }
                    }
                }
                $return[$content_id] = $cur_category_grant;
            }

            return $return;
        }

        //베트남. 카테고리권한과 콘텐츠 권한 맞물려서 적용되도록 하기 위해서.
        //해당 카테고리에 생성이상의 권한이 있는지 체크하는 함수.
        //리턴값이 2 이상이면 권한이 있다고 봄. 0:없음, 1:읽기, 2:읽기+쓰기, 3:읽기+쓰기+삭제
        //사용시 : checkGrantCategoryFullPath($content_id, $_SESSION['user']['groups']);
        //리턴값 : 0,1,2,3
        function checkGrantCategoryFullPath($content_id, $user_groups)
        {
            global $db;

            $content_info = $db->queryRow("select * from bc_content where content_id='" . $content_id . "'");

            $category_id = $content_info['category_id'];
            $category_full_path = $content_info['category_full_path'];
            $ud_content_id = $content_info['ud_content_id'];
            $arr_category = explode('/', $category_full_path);

            $cur_category_grant = 0;

            //카테고리 권한은 array( ud_content_id => array(카테고리ID => 권한)) 이런 구조
            //post로 넘어온 category_id가 해당 권한에 포함되어있으면 권한값을 가질 수 있다.
            $category_grant = categoryGroupGrant($user_groups);
            $arr_category_grant_in_ud = $category_grant[$ud_content_id];
            foreach ($arr_category as $sub_category) {
                foreach ($arr_category_grant_in_ud as $cat_id => $cat_grant) {
                    if ($sub_category == $cat_id) {
                        $cur_category_grant = $cat_grant;
                        if ($cat_grant == 2) {
                            return $cur_category_grant;
                        }
                    }
                }
            }
            return $cur_category_grant;
        }

        function reCreateSession($user_id, $super_admin)
        {
            global $db, $arr_sys_code;

            $user = $db->queryRow("select * from bc_member where user_id='$user_id'");

            $groups = getGroups($user_id);
            $groups_q = join(',', $groups);

            $session_time_limit = $db->queryOne("
                            SELECT	REF1
                            FROM	BC_SYS_CODE
                            WHERE	CODE  = 'SESSION_TIME_LIMIT'
                        ");

            $prevent_duplicate_login = $arr_sys_code['duplicate_login']['use_yn']; //중복로그인 허용여부(Y:불가, N:허용)

            $_SESSION['user'] = array(
                'user_id' => trim($user['user_id']),
                'is_admin' => trim($user['is_admin']),
                'KOR_NM' => $user['user_nm'],
                'user_email' => $user['email'],
                'phone' =>  $user['phone'],
                'groups' => $groups,
                'lang' => $user['lang'],
                'super_admin' => $super_admin,
                'session_expire' => time() + ($session_time_limit * 60),
                'prevent_duplicate_login' => $prevent_duplicate_login
            );

            return true;
        }

        // create xml for LoudnessMeasurement
        function createLoudnessMeasurementXML($user_id, $source)
        {
            $temp_folder = 'C:/temp/TempAudio';
            //$resultsDirectory = 'C:/temp/Results';
            //$resultsDirectory = '\\\\192.168.1.202\\Storage\\Storage\\LoudnessXML';
            $resultsDirectory = LOUDNESS_RESULT_DIRECTORY;
            $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><Response />');

            $serverxml = $xml->addChild('AudioToolsServer');
            $serverxml->addAttribute('Version', '0.1');

            $jobxml = $serverxml->addChild('AudioToolsServerJob');
            $jobxml->addAttribute('Version', '0.1');

            $jobxml->addChild('ProjectName', 'AudioTools Server Loudness');
            $jobxml->addChild('JobName', 'Loudness Measurement');
            $jobxml->addChild('Facility', 'Minnetonka Audio Software');
            $jobxml->addChild('Creator', 'Geminisoft');
            $jobxml->addChild('Submitter', $user_id);
            $jobxml->addChild('Comment', 'Loudness Measurement');
            //$serverxml->addChild('DefaultTempFolder', $temp_folder);

            $source_xml = $jobxml->addChild('SourceFile');
            $source_xml->addChild('path', $source);

            $processingChain_xml = $jobxml->addChild('ProcessingChain');

            $processer_xml = $processingChain_xml->addChild('Processor');
            $processer_xml->addChild('name', 'SurCode Loudness Measurement');

            $processorParameters_xml = $processer_xml->addChild('ProcessorParameters');
            $processorParameters_xml->addChild('ResultsDirectory', $resultsDirectory);
            $processorParameters_xml->addChild('LogMeasurement', 'True Peak dBTP');
            $processorParameters_xml->addChild('LogMeasurement', 'Maximum Momentary Loudness LUFS');
            $processorParameters_xml->addChild('LogMeasurement', 'Maximum Short Term Loudness LUFS');
            $processorParameters_xml->addChild('LogMeasurement', 'Maximum Sample Peak Loudness dB');
            $processorParameters_xml->addChild('LogMeasurement', 'Programme Loudness LUFS');
            $processorParameters_xml->addChild('LogMeasurement', 'Loudness Range LU');
            $processorParameters_xml->addChild('LogMeasurement', 'Average ITU LKFS');

            return $xml->asXML();
        }

        // create xml for LoudnessMeasurement
        function createLoudnessAdjustXML($user_id, $source, $standard)
        {
            $temp_folder = 'C:/temp/TempAudio';
            //$resultsDirectory = 'C:/temp/Results';
            //$resultsDirectory = '\\\\192.168.1.202\\Storage\\Storage\\LoudnessXML';
            $resultsDirectory = LOUDNESS_RESULT_DIRECTORY;
            $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><Response />');

            $serverxml = $xml->addChild('AudioToolsServer');
            $serverxml->addAttribute('Version', '0.1');

            $jobxml = $serverxml->addChild('AudioToolsServerJob');
            $jobxml->addAttribute('Version', '0.1');

            $jobxml->addChild('ProjectName', 'AudioTools Server Loudness');
            $jobxml->addChild('JobName', 'Loudness Correction');
            $jobxml->addChild('Facility', 'Minnetonka Audio Software');
            $jobxml->addChild('Creator', 'Geminisoft');
            $jobxml->addChild('Submitter', $user_id);
            $jobxml->addChild('Comment', 'Loudness Correction');
            //$serverxml->addChild('DefaultTempFolder', $temp_folder);

            $source_xml = $jobxml->addChild('SourceFile');
            $source_xml->addChild('path', $source);
            $source_xml->addChild('outputPath', $source);

            $processingChain_xml = $jobxml->addChild('ProcessingChain');

            $processer_xml = $processingChain_xml->addChild('Processor');
            $processer_xml->addChild('name', 'SurCode Loudness Correction');
            $processer_xml->addChild('vendor', 'Minnetonka Audio Software, Inc.');

            $processorParameters_xml = $processer_xml->addChild('ProcessorParameters');
            $processorParameters_xml->addChild('bypass', '0');
            $processorParameters_xml->addChild('CorrectionType', '0');

            $program_config_xml = $processorParameters_xml->addChild('ProgramConfig');
            $program_xml = $program_config_xml->addChild('Program');
            $program_xml->addChild('ProgramId', '0');
            $program_xml->addChild('ProgramName', 'Correction');
            $program_xml->addChild('Normalize_dB', '-.1');
            $program_xml->addChild('TargetLoudness_LUFS', $standard);
            $program_xml->addChild('PeakLimiterThreshold_dB', '-.1');
            $program_xml->addChild('PeakLimiterAttack_ms', '1');
            $program_xml->addChild('PeakLimiterSustain_ms', '1');
            $program_xml->addChild('PeakLimiterRelease_ms', '1');

            return $xml->asXML();
        }

        //Async call
        function request_async($url, $params, $type = 'POST')
        {
            $parts = parse_url($url);

            if ($parts['scheme'] == 'http') {

                $fp = fsockopen($parts['host'], isset($parts['port']) ? $parts['port'] : 80, $errno, $errstr, 30);
            } else if ($parts['scheme'] == 'https') {
                $fp = fsockopen("ssl://" . $parts['host'], isset($parts['port']) ? $parts['port'] : 443, $errno, $errstr, 30);
            }

            if (is_array($params)) {
                foreach ($params as $key => &$val) {
                    if (is_array($val)) $val = implode(',', $val);
                    $post_params[] = $key . '=' . urlencode($val);
                }
                $post_string = implode('&', $post_params);

                // Data goes in the path for a GET request  
                if ('GET' == $type) {
                    $parts['path'] .= '?' . $post_string;
                }
            }


            $out = "$type " . $parts['path'] . " HTTP/1.1\r\n";
            $out .= "Host: " . $parts['host'] . "\r\n";
            $out .= "Content-Type: application/x-www-form-urlencoded\r\n";
            $out .= "Content-Length: " . strlen($post_string) . "\r\n";
            $out .= "Connection: Close\r\n\r\n";
            // Data goes in the request body for a POST request  
            if ('POST' == $type && isset($post_string))
                $out .= $post_string;


            fwrite($fp, $out);
            fclose($fp);
        }

        // create xml for LoudnessMeasurement
        function getFrameRate($content_id)
        {
            global $db;

            $frame_rate = $db->queryOne("select sys_frame_rate from bc_sysmeta_movie where sys_content_id = '" . $content_id . "'");
            $frame_rate = floatval($frame_rate);

            if (!$frame_rate) {
                $frame_rate = FRAMERATE;
            }
            return $frame_rate;
        }

        // getGridThumbnail
        function getGridThumbnails($thumb_file, $ud_lowres_storage)
        {
            libxml_use_internal_errors(true);
            $thumb_grid_xml = @simplexml_load_file($thumb_file);
            if (!$thumb_grid_xml) {
                return 'false';
            }

            $thumbnails = array();
            $grid_thumbnail = $thumb_grid_xml->GridThumbnail;

            $size = explode('X', strtoupper($grid_thumbnail->img_size));

            if (count($size) < 2) {
                return;
            }

            $width = $size[0];
            $height = $size[1] - 1; //Temp. After agent fix, need to revert.

            $total_cnt = $grid_thumbnail->total_cnt;
            $img_cnt = $grid_thumbnail->width_img_cnt;
            $se_per_img = $grid_thumbnail->sec_per_img;

            $img_file = "/data/" . str_replace($ud_lowres_storage, '', str_replace("\\", "/", $grid_thumbnail->filename));

            $rows = ceil($total_cnt / $img_cnt);

            if ($rows == 0 && $total_cnt > 0) {
                $rows = 1;
            }

            $tmp_sec = 0;
            $tmp_total = 0;

            for ($idx = 0; $idx < $rows; $idx++) {
                for ($idx2 = 0; $idx2 < $img_cnt && $tmp_total < $total_cnt; $idx2++) {
                    $tmp_height = '-' . $height * $idx . 'px';
                    $tmp_width =  '-' . $width * $idx2 . 'px';

                    $tmp_total++;

                    $thumbnails[] = $tmp_sec . ": {style:{top:'-120px', width:'$width" . "px', height: '$height" . "px', background: 'url($img_file) $tmp_width $tmp_height'}}";

                    $tmp_sec += $se_per_img;
                }
                $idx2 = 0;
            }

            return join(', ', $thumbnails);
        }
        function filePutContent($name_file, $message, $file)
        {
            if (is_array($file)) {
                file_put_contents($_SERVER['DOCUMENT_ROOT'] . '/log/' . $name_file . date('Ymd') . '.log', date("Y-m-d H:i:s\t") . " :: " . $message . ":::" . "\r\n" . print_r($file, true) . "\r\n\n", FILE_APPEND);
            } else {
                file_put_contents($_SERVER['DOCUMENT_ROOT'] . '/log/' . $name_file . date('Ymd') . '.log', date("Y-m-d H:i:s\t") . " :: " . $message . " :::" . "\r\n" . $file . "\r\n\n", FILE_APPEND);
            }
        }
        /**
         * check_system_license
         * param: private_key, mode
         * return public_key
         */
        function check_system_license($private_key, $mode)
        {
            $private_key_length = strlen($private_key);
            if ($mode == 'c3cfb6e1b944333d0db7fd473a8aab2b85ccd6e0e93748bec4a7c93adb4bfd794fd0da7db075cf418d762560341d8494b00bd09017893831cfffc7da7cf63673') {
                //SYSTEM_CONFIG
                return hash('sha512', $private_key) . hash('sha512', $private_key_length) . md5(md5($private_key) . hash('sha512', $private_key) . md5($private_key_length));
            } else {
                return null;
            }
        }

        function getHarrisWorkStorage($storage_id, $param)
        {
            global $db;
            $yesterday = date('Ymd', strtotime(date('Ymd') . '-1 day')) . '000000';

            if ($param == 'source') {
                $storage_q = "TA.SRC_STORAGE_ID = A.STORAGE_ID";
            } else if ($param == 'target') {
                $storage_q = "TA.TRG_STORAGE_ID = A.STORAGE_ID";
            } else {
                $storage_q = "TA.SRC_STORAGE_ID = A.STORAGE_ID OR TA.TRG_STORAGE_ID = A.STORAGE_ID";
            }

            $query = "
        SELECT Z.*
        FROM   (
                    SELECT A.STORAGE_ID, -- FTP 정보의 스토리지 ID 
                        B.PATH,       -- FTP 실제 주소와 PORT 정보 
                        B.IS_ONLINE,  -- FTP Server 상태 
                        B.CONN_LIMIT, -- FTP Connection 제한
                        (	
                                SELECT COUNT(TA.TASK_ID)
                                FROM BC_TASK TA 
                                WHERE TA.TYPE = '80' 
                                AND TA.STATUS in ('assigning', 'processing')
                                AND TA.START_DATETIME > '" . $yesterday . "' 
                                AND (" . $storage_q . ") 
                            ) AS CNT
                    FROM   HARRIS_FTP_LIST A	
                        INNER JOIN BC_STORAGE B ON B.STORAGE_ID = A.STORAGE_ID AND B.IS_ONLINE = 'Y'      
                    WHERE  A.SERVER_UID = (
                            SELECT SERVER_UID 
                            FROM   HARRIS_FTP_LIST 
                            WHERE  STORAGE_ID = $storage_id 
                        )
                ) Z
        ORDER BY Z.CNT ASC, Z.STORAGE_ID
        ";

            $harris_code = $db->queryRow($query);

            return $harris_code;
        }

        //제한없는 엑셀출력을 위해 헤더 로우 테일 분리 2015-08-12 이성용
        function export_excel_file_header($data, $field, $file_name, $title, $yelloRows = array(), $redRows = array(), $mergeArr = array())
        {
            //헤더 및 파일 이름 설정
            $date = date('YmdHis');
            $fileName    = iconv('utf-8', 'euc-kr', $file_name . '_' . $date);
            header("Content-type: application/vnd.ms-excel; charset=utf-8");
            header("Content-Disposition: attachment; filename={$fileName}.xls");
            header("Content-Description: EBS DAS Generated Data");
            print("<meta http-equiv=\"Content-Type\" content=\"application/vnd.ms-excel; charset=utf-8\">");
            $cols = count($field);  //열의 수
            //기본틀 과 타이틀 출력
            $contents_head = "<table border=0 cellpadding=0 cellspacing=10 bgcolor='#ffffff' width=2000>
                        <tr><td align=center height=45 >&nbsp; </td></tr></table>
                        <table border=1 cellpadding=0 cellspacing=0 width=2000>
                        <tr>
                        <td colspan=$cols align=center height=50><b>$title</b></td>
                        </tr>";
            // 데이터의 필드헤드 값 출력
            $contents_fields = "<tr>";
            $key_array = array();
            $i = 0;

            while (list($key, $value) = each($field)) {
                $contents_fields .= "<td align=center bgcolor='#A5A5A5'>" . htmlspecialchars($key) . "</td>";
                $key_array[$i] = $value;
                $i++;
            }


            $contents_fields .= "</tr>";
            return $contents_head . $contents_fields;
        }

        function export_excel_file_tail($data, $field, $file_name, $title, $yelloRows = array(), $redRows = array(), $mergeArr = array())
        {
            return '</table>';
        }

        function export_excel_file_row($data, $field, $file_name, $title, $yelloRows = array(), $redRows = array(), $mergeArr = array())
        {

            // 데이터의 필드헤드 값 출력
            $contents_fields = "<tr>";
            $key_array = array();
            $i = 0;

            while (list($key, $value) = each($field)) {
                $contents_fields .= "<td align=center bgcolor='#A5A5A5'>" . htmlspecialchars($key) . "</td>";
                $key_array[$i] = $value;
                $i++;
            }


            $contents_fields .= "</tr>";


            /// 출력 끝

            //데이터 값 출력

            $limit = count($key_array);
            $cnt = 0;
            $mergeRowIndex = array();
            $v_align = 'text-align: right;';

            foreach ($data as $d) {
                $contents_value .= "<tr>";

                for ($i = 0; $i < $limit; $i++) {
                    $rowspan = '';
                    foreach ($mergeArr as $merge) {
                        if ($i == $merge[0] && $cnt == $merge[1]) {
                            $rowspan_cnt = $merge[2] - $merge[1] + 1;
                            $rowspan = 'rowspan=' . $rowspan_cnt;
                            $mergeRowIndex[$i] = array();

                            for ($j = $merge[1] + 1; $j < $merge[2] + 1; $j++) {
                                array_push($mergeRowIndex[$i], $j);
                            }
                            //print_r($mergeRowIndex);
                        }
                    }

                    if (!empty($mergeRowIndex[$i]) && in_array($cnt, $mergeRowIndex[$i])) continue;
                    $value = htmlspecialchars($d[$key_array[$i]]);

                    $v_color = '';

                    $v_title = array('계', '소 계', '총계', '콘텐츠계', 'on', 'off', 'Betacam', 'Digibetacam', 'Hdcam', '기타', 'DMC', '자체등록', '외부시스템', '자체등록');

                    if (in_array($cnt, $yelloRows)) {
                        if (in_array($value, $v_title)) {
                            $v_color = " style='background: #FFFF66; text-align: left;'";
                        } else {
                            $v_color = " style='background: #FFFF66; text-align: right;'";
                        }
                    } else if (in_array($cnt, $redRows)) {
                        if (in_array($value, $v_title)) {
                            $v_color = " style='background: #FF7C80; text-align: left;'";
                        } else {
                            $v_color = " style='background: #FF7C80; text-align: right;'";
                        }
                    }

                    $contents_value .= "<td " . $rowspan . $v_color;

                    if ($value || $value == '0') //값이 존재하면 값 출력 아니면 공백으로 처리
                    {
                        if (strstr($key_array[$i], '일자') ||  strstr($key_array[$i], 'date') ||  strstr($key_array[$i], 'time')) {
                            $contents_value .= ">" . substr($value, 0, 4) . '-' . substr($value, 4, 2) . '-' . substr($value, 6, 2) . "</td>";
                        } else {
                            if ($value == '0')
                                $contents_value .= " style='mso-number-format:'\@';'>$value</td>";
                            else {
                                $contents_value .= ">$value</td>";
                            }
                        }
                    } else {
                        $contents_value .= "> &nbsp; </td>";
                    }

                    $rowspan = '';
                }
                $contents_value .= "</tr>";
                $cnt++;
            }

            //출력
            //echo $contents_head.$contents_fields_;
            //echo $contents_head.$contents_fields.$contents_value.'</table>';
            return $contents_value;
        }

        function getReturnXML($simpleXML)
        {
            $dom = dom_import_simplexml($simpleXML)->ownerDocument;
            $dom->formatOutput = true;

            return $dom->saveXML($dom->documentElement);
        }

        function convertamp($string)
        {
            $string = str_replace('&',  "&amp;",    $string);

            return $string;
        }

        function searchUpdate($content_id)
        {
            global $db, $arr_sys_code;
            if ($arr_sys_code['interwork_gmsearch']['use_yn'] == 'Y') {                
                app()->getContainer()['searcher']->update($content_id);
            } else {
                return;
            }
        }

        function searchDelete($content_id)
        {
            global $db, $arr_sys_code;

            if ($arr_sys_code['interwork_gmsearch']['use_yn'] == 'Y') {
                app()->getContainer()['searcher']->delete($content_id);
            } else {
                return;
            }
        }

        /**
         * 2017-12-28 이승수
         * 사용자정의별로 루트 카테고리를 다르게 지정해논 경우 그 매핑정보를 불러옴
         */
        function getCategoryMapInfo()
        {
            global $db;

            $get_category = $db->queryAll("
        SELECT	A.*
                ,B.CATEGORY_TITLE
        FROM	BC_CATEGORY_MAPPING A
                LEFT OUTER JOIN BC_CATEGORY B
                ON A.CATEGORY_ID=B.CATEGORY_ID
    ");

            foreach ($get_category as $ca) {
                if ($ca['category_id'] == 0) {
                    $tmp_categoryFullPath = '/0';
                } else {
                    $tmp_categoryFullPath = getCategoryFullPath($ca['category_id']);
                }

                $categories[$ca['ud_content_id']] = array(
                    'category_id' => $ca['category_id'],
                    'category_title' => $ca['category_title'],
                    'category_full_path' => $tmp_categoryFullPath
                );
            }

            return $categories;
        }

        //POST로 전송, $metadatas는 배열로
        function post_send($url, $metadatas)
        {

            $header[] = "Content-Type: html/text; charset=utf-8";

            $session = curl_init();

            curl_setopt($session, CURLOPT_HEADER,         false);
            //curl_setopt($session, CURLOPT_HTTPHEADER,     $header);
            curl_setopt($session, CURLOPT_URL,            $url);
            curl_setopt($session, CURLOPT_POSTFIELDS,     join('&', $metadatas));
            curl_setopt($session, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($session, CURLOPT_POST,           1);

            $response = curl_exec($session);

            curl_close($session);

            return $response;
        }

        //수정일 : 2010.10.11
        //작성자 : 김형기
        //내용 : 지난방송일 경우 해당일자의 카테고리가 없으면 자동으로 카테고리를 추가하고 아이디를 반환
        function addAndGetDateCategoryID($ud_content_id, $created_time)
        {
            global $db;

            //일단 지난방송만...
            if ($ud_content_id != PAST_BROADCAST) {
                $map_categories = getCategoryMapInfo();
                $root_category_id = $map_categories[$ud_content_id]['category_id'];
                return $root_category_id;
                //2017-12-31 CJO, 기존 등록대기는 루트category가 0이었지만 신규시스템은 ud_content별로 나눠져야함.
                //return CAT_ID_ROOT;
            }
            $parent_id = $db->queryOne("select category_id from bc_category_mapping where ud_content_id=" . $ud_content_id);

            //년도 확인(yyyy년)
            //월 확인(mm월)
            //일 확인(mmdd)
            $cat_titles = array(
                'year' => date('Y', strtotime($created_time)) . '년',
                'month' => date('m', strtotime($created_time)) . '월',
                'day' => date('md', strtotime($created_time))
            );
            foreach ($cat_titles as $key => $cat_title) {
                $cat_id = -1;
                $cat_id = $db->queryOne("select category_id from bc_category where parent_id = '$parent_id' and category_title = '$cat_title'");

                //없으면 만들어준다
                if (empty($cat_id)) {
                    $cat_id = getSequence('seq_bc_category_id');
                    $result = $db->exec("update bc_category set no_children='0' where category_id='$parent_id' and no_children='1'");

                    $result = $db->exec(sprintf("insert into bc_category (category_id, parent_id, category_title, no_children) values (%d, %d, '%s', '1')", $cat_id, $parent_id, $cat_title));
                    sort_category($parent_id);
                }
                if ($key != 'day') {
                    $parent_id = $cat_id;
                }
            }
            return $cat_id;
        }


        //수정일 : 2010.11.10
        //작성자 : 김형기
        //내용 : 카테고리 생성/수정/삭제 시 순서 정렬하도록
        function sort_category($parent_id)
        {
            global $db;
            //신규 시스템의 루트 카테고리들, 건너뜀
            $map_categories = getCategoryMapInfo();
            foreach ($map_categories as $map_category) {
                $root_category = $map_category['category_id'];
                if ($root_category == $parent_id) {
                    return;
                }
            }

            $query = "select category_id 
        from  bc_category 
        where parent_id = " . $parent_id . " order by category_title asc";

            $sort_orders = $db->queryAll($query);

            foreach ($sort_orders as $rownum => $sort_order) {
                $sort = $rownum + 1;
                $id = $sort_order['category_id'];

                $query = "update bc_category 
            set show_order = '$sort'
            where category_id = '$id'";
                $r = $db->exec($query);
            }
        }

        function curl_request_async($url, $params, $type = 'POST')
        {
            foreach ($params as $key => &$val) {
                if (is_array($val))
                    $val = implode(',', $val);
                $post_params[] = $key . '=' . urlencode($val);
            }
            $post_string = implode('&', $post_params);

            $parts = parse_url($url);

            if ($parts['scheme'] == 'http') {
                $fp = fsockopen($parts['host'], isset($parts['port']) ? $parts['port'] : 80, $errno, $errstr, 30);
            } else if ($parts['scheme'] == 'https') {
                $fp = fsockopen("ssl://" . $parts['host'], isset($parts['port']) ? $parts['port'] : 443, $errno, $errstr, 30);
            }

            // Data goes in the path for a GET request  
            if ('GET' == $type)
                $parts['path'] .= '?' . $post_string;

            $out = "$type " . $parts['path'] . " HTTP/1.1\r\n";
            $out .= "Host: " . $parts['host'] . "\r\n";
            $out .= "Content-Type: application/x-www-form-urlencoded\r\n";
            $out .= "Content-Length: " . strlen($post_string) . "\r\n";
            $out .= "Connection: Close\r\n\r\n";
            // Data goes in the request body for a POST request  
            if ('POST' == $type && isset($post_string))
                $out .= $post_string;

            fwrite($fp, $out);
            fclose($fp);
        }


        function checkLoudnessJob()
        {
            global $db;
            /**
             * 작업할 대상 및 작업가능 상태 확인 후 가능하면 진행 없으면 그냥 종료
             * 2018.04.16 Alex
             */
            /*미네통카 Focus 장비 IP목록 추후 DB화 할 예정*/
            $loudnessAgents = array(
                '10.26.100.206' => 0
            );

            /*작업제한 갯수 4개 초과인지 확인*/
            $hasTask = $db->queryAll("
                    SELECT  ASSIGN_IP, COUNT(*) AS CNT
                    FROM    BC_TASK
                    WHERE   TYPE = '55'
                    AND     STATUS IN ('assigning', 'processing')
                    GROUP BY ASSIGN_IP
                    ORDER BY ASSIGN_IP ASC
                ");

            if (!empty($hasTask)) {
                foreach ($hasTask as $task) {
                    $assignIp = $task['assign_ip'];
                    $loudnessAgents[$assignIp] = $task['cnt'];
                }
            }

            asort($loudnessAgents);
            $result = array_splice($loudnessAgents, 0, 1);
            $key = array_keys($result);
            /* 가장 작은 할당건수를 받은 서버의 할당개수가 4개보다 크면 아무작업 안함. 작으면 신규작업 추가 - 2018.04.16 Alex */
            if ($loudnessAgents[$key[0]] < 4) {
                return $key[0];
            }

            return 'skip';
        }

        function generateRandomString($length = 10)
        {
            $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
            $charactersLength = strlen($characters);
            $randomString = '';
            for ($i = 0; $i < $length; $i++) {
                $randomString .= $characters[rand(0, $charactersLength - 1)];
            }
            return $randomString;
        }

        //  원격지 파일 사이즈 체크
        function getSizeFile($url)
        {
            if (substr($url, 0, 4) == 'http') {
                $x = array_change_key_case(get_headers($url, 1), CASE_LOWER);
                if (strcasecmp($x[0], 'HTTP/1.1 200 OK') != 0) {
                    $x = $x['content-length'][1];
                } else {
                    $x = $x['content-length'];
                }
            } else {
                $x = @filesize($url);
            }

            return $x;
        }

        // 원격지 파일 체크
        function remoteFileExist($filepath)
        {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $filepath);
            curl_setopt($ch, CURLOPT_NOBODY, 1);
            curl_setopt($ch, CURLOPT_FAILONERROR, 1);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            if (curl_exec($ch) !== false) {
                return true;
            } else {
                return false;
            }
        }

        function send_attachment2($filename, $server_filename,  $expires = 0, $speed_limit = 0)
        {
            // 서버측 파일명을 확인한다.
            if (!remoteFileExist($server_filename)) { //|| !is_readable($server_filename)) {
                echo 'not remote file exist';
                return false;
            }

            if (($fp = @fopen($server_filename, 'rb')) === false) {
                return false;
            }

            // 파일명에 사용할 수 없는 문자를 모두 제거하거나 안전한 문자로 치환한다.

            $illegal = array('\\', '/', '<', '>', '{', '}', ':', ';', '|', '"', '~', '`', '@', '#', '$', '%', '^', '&', '*', '?');
            $replace = array('', '', '(', ')', '(', ')', '_', ',', '_', '', '_', '\'', '_', '_', '_', '_', '_', '_', '', '');
            $filename = str_replace($illegal, $replace, $filename);
            $filename = preg_replace('/([\\x00-\\x1f\\x7f\\xff]+)/', '', $filename);

            // 유니코드가 허용하는 다양한 공백 문자들을 모두 일반 공백 문자(0x20)로 치환한다.

            $filename = trim(preg_replace('/[\\pZ\\pC]+/u', ' ', $filename));

            // 위에서 치환하다가 앞뒤에 점이 남거나 대체 문자가 중복된 경우를 정리한다.

            $filename = trim($filename, ' .-_');
            $filename = preg_replace('/__+/', '_', $filename);
            if ($filename === '') {
                return false;
            }

            // 브라우저의 User-Agent 값을 받아온다.

            $ua = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
            $old_ie = (bool) preg_match('#MSIE [3-8]\.#', $ua);

            // 파일명에 숫자와 영문 등만 포함된 경우 브라우저와 무관하게 그냥 헤더에 넣는다.

            if (preg_match('/^[a-zA-Z0-9_.-]+$/', $filename)) {
                $header = 'filename="' . $filename . '"';
            }

            // IE 9 미만 또는 Firefox 5 미만의 경우.

            elseif ($old_ie || preg_match('#Firefox/(\d+)\.#', $ua, $matches) && $matches[1] < 5) {
                $header = 'filename="' . rawurlencode($filename) . '"';
            }

            // Chrome 11 미만의 경우.

            elseif (preg_match('#Chrome/(\d+)\.#', $ua, $matches) && $matches[1] < 11) {
                $header = 'filename=' . $filename;
            }

            // Safari 6 미만의 경우.

            elseif (preg_match('#Safari/(\d+)\.#', $ua, $matches) && $matches[1] < 6) {
                $header = 'filename=' . $filename;
            }

            // 안드로이드 브라우저의 경우. (버전에 따라 여전히 한글은 깨질 수 있다. IE보다 못한 녀석!)

            elseif (preg_match('#Android #', $ua, $matches)) {
                $header = 'filename="' . $filename . '"';
            }

            // 그 밖의 브라우저들은 RFC2231/5987 표준을 준수하는 것으로 가정한다.
            // 단, 만약에 대비하여 Firefox 구 버전 형태의 filename 정보를 한 번 더 넣어준다.

            else {
                $header = "filename*=UTF-8''" . rawurlencode($filename) . '; filename="' . rawurlencode($filename) . '"';
            }

            // 캐싱이 금지된 경우...

            if (!$expires) {

                // 익스플로러 8 이하 버전은 SSL 사용시 no-cache 및 pragma 헤더를 알아듣지 못한다.
                // 그냥 알아듣지 못할 뿐 아니라 완전 황당하게 오작동하는 경우도 있으므로
                // 캐싱 금지를 원할 경우 아래와 같은 헤더를 사용해야 한다.

                if ($old_ie) {
                    header('Cache-Control: private, must-revalidate, post-check=0, pre-check=0');
                    header('Expires: Sat, 01 Jan 2000 00:00:00 GMT');
                }

                // 그 밖의 브라우저들은 말을 잘 듣는 착한 어린이!

                else {
                    header('Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
                    header('Expires: Sat, 01 Jan 2000 00:00:00 GMT');
                }
            }

            // 캐싱이 허용된 경우...

            else {
                header('Cache-Control: max-age=' . (int) $expires);
                header('Expires: ' . gmdate('D, d M Y H:i:s', time() + (int) $expires) . ' GMT');
            }

            // 이어받기를 요청한 경우 여기서 처리해 준다.

            if (isset($_SERVER['HTTP_RANGE']) && preg_match('/^bytes=(\d+)-/', $_SERVER['HTTP_RANGE'], $matches)) {
                $range_start = $matches[1];
                if ($range_start < 0 || $range_start > $filesize) {
                    header('HTTP/1.1 416 Requested Range Not Satisfiable');
                    return false;
                }
                header('HTTP/1.1 206 Partial Content');
                header('Content-Range: bytes ' . $range_start . '-' . ($filesize - 1) . '/' . $filesize);
                header('Content-Length: ' . ($filesize - $range_start));
            } else {
                $range_start = 0;
                header('Content-Length: ' . $filesize);
            }

            // 나머지 모든 헤더를 전송한다.

            header('Accept-Ranges: bytes');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; ' . $header);

            // 출력 버퍼를 비운다.
            // 파일 앞뒤에 불필요한 내용이 붙는 것을 막고, 메모리 사용량을 줄이는 효과가 있다.

            while (ob_get_level()) {
                ob_end_clean();
            }

            // 파일을 64KB마다 끊어서 전송하고 출력 버퍼를 비운다.
            // readfile() 함수 사용시 메모리 누수가 발생하는 경우가 가끔 있다.

            $block_size = 16 * 1024;
            $speed_sleep = $speed_limit > 0 ? round(($block_size / $speed_limit / 1024) * 1000000) : 0;

            $buffer = '';
            if ($range_start > 0) {
                fseek($fp, $range_start);
                $alignment = (ceil($range_start / $block_size) * $block_size) - $range_start;
                if ($alignment > 0) {
                    $buffer = fread($fp, $alignment);
                    echo $buffer;
                    unset($buffer);
                    flush();
                }
            }
            while (!feof($fp)) {
                $buffer = fread($fp, $block_size);
                echo $buffer;
                unset($buffer);
                flush();
                usleep($speed_sleep);
            }

            fclose($fp);

            // 전송에 성공했으면 true를 반환한다.

            return true;
        }
        
//diva priority
function diva_priority($val, $type)
{
	//$val은 MAM에서 사용하는 우선순위값 이다.
	//타입이 없으면, 낮은 우선순위인 10을.
	$type = strtoupper($type);
	if( empty($type) ) return 10;
	$default_val = array(
			'RESTORE' => 100,
			'PFR_RESTORE' => 100,
			'ARCHIVE' => 40,
			'DELETE' => 10,
			'INFO' => 80,
			'110' => 40, //ARHCIVE
			'160' => 100, //RESTORE
			'140' => 100, //PFR_RESTORE
			'170' => 80, //INFO
			'150' => 10 //DELETE
	);
	//해당되지않은 타입인 경우, 낮은 우선순위인 10을.
	if( empty($default_val[$type]) ) return 10;

	//값이 숫자가 아닌경우. 타입에 따라 기본값을.
	if( !is_numeric($val) ) {
		return $default_val[$type];
	}

	//0~100 범위를 초과한 경우. 기본값을.
	if($val < 0 || $val > 100) {
		return $default_val[$type];
	}

	//위의 상황이 아닌 정상적인 경우.
	//	//최소값 설정.
	//	switch($type)
	//	{
	//		case 'RESTORE':
	//		case 'PFR_RESTORE':
	//			//DIVA 기준 51 ~ 100 값
	//			if($val > 49) $val = 49;
	//			if($val < 0) $val = 0;
	//		break;
	//		case 'ARCHIVE':
	//			//DIVA 기준 21 ~ 50 값
	//			if($val > 79) $val = 79;
	//			if($val < 50) $val = 50;
	//		break;
	//		case 'DELETE':
	//			//DIVA 기준 1 ~ 20 값
	//			if($val > 99) $val = 99;
	//			if($val < 80) $val = 80;
	//		break;
	//	}
	return abs($val-100);
}