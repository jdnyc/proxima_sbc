<?php
/**
 * NLE, FileIngest등의 등록페이지에서 메타데이터 폼을 생성
 */
use Proxima\core\Session;
use \Api\Models\UsrMetaSet;
require_once($_SERVER['DOCUMENT_ROOT']."/lib/config.php");
require_once($_SERVER['DOCUMENT_ROOT']."/lib/functions.php");
require_once($_SERVER['DOCUMENT_ROOT']."/store/metadata/function.php");
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/lang.php');

Session::init();
try {
	$ud_content_id = $_REQUEST['ud_content_id'];
	$content_id = $_REQUEST['content_id'];
	$mode = $_REQUEST['mode'];
	$ud_content_tab = $_REQUEST['ud_content_tab'];
	$reg_type = $_REQUEST['reg_type'];
	$content['content_id'] = 0;
	$content['ud_content_id'] = $ud_content_id;
    $user_id = $_REQUEST['user_id'];

    $flag = $_REQUEST['flag'];

    if( empty($user_id) ){
        $sessionUserId = Session::getUser('user_id');
        if($sessionUserId){
            $user_id = $sessionUserId;
        }
    }
    
    if (empty($user_id)){
        echo "<script type=\"text/javascript\">window.location=\"/interface/app/plugin/regist_form/\"</script>";
        exit;
    }

    $udContentTitle = $db->queryOne("select ud_content_title from bc_ud_content where ud_content_id='$ud_content_id'");

	if(empty($content['ud_content_id'])) {
		$content['ud_content_id'] = 'null';
	}

	//컨테이너 목록
	$containerList = $db->queryAll("
		SELECT	*
		FROM	BC_USR_META_FIELD
		WHERE 	UD_CONTENT_ID={$content['ud_content_id']}
		AND 	USR_META_FIELD_TYPE='container'
		AND		IS_SHOW = '1'
		ORDER BY SHOW_ORDER
	");

	//컨테이너 배열생성
	$container_array = array();

	$meta_tab_order = 0;
	foreach ($containerList as $container) {
		$meta_tab_order++;
		$container_id_tmp = $container['container_id'];//컨테이너 아이디
		$container_title = '메타데이터';//컨테이너 명
        $viewCode = $ud_content_tab;
        $formItem = [[
            'xtype' => 'hidden',
            'name' => 'k_user_id',
            'value' => $user_id
        ],[
            'xtype' => 'hidden',
            'name' => 'k_ud_content_id',
            'value' => $ud_content_id
        ]];
        $rsFields = content_meta_value_list($content_id, $content['ud_content_id']);
        $viewFields =  UsrMetaSet::where('view_code',$viewCode)->orderBy('sort_ordr')->get();
        $formInfos = array();
        $categoryEditable = 1;

        $customDefaultValues = [];

        //커스텀
        if ( $ud_content_tab == 'product') {
            $rootId = 200;
            $rootText = '제작';
            //$customDefaultValues['BRDCST_STLE_SE'] = 'P';
            $customDefaultValues['VIDO_TY_SE'] = 'B';
            $customDefaultValues['MATR_KND'] = 'ZP';
            $customDefaultValues['SHOOTING_ORGINL_ATRB'] = 'general';
            $customDefaultValues['PROD_DE'] = date("Y-m-d");
        }else if( $ud_content_tab == 'news' ){
            $rootId = 201;
            $rootText = '뉴스';
          
            $valueMap = [
                1=>2016,
                2=>2017,
                3=>2018,
                4=>2019,
                5=>2020,
                6=>2021,
                0=>2022
            ];
            $categoryEditable = 0;   
            //요일별
            $customDefaultValues['CTGRY_ID'] = $valueMap[date('w')];
            $customDefaultValues['BRDCST_STLE_SE'] = 'N';
            $customDefaultValues['VIDO_TY_SE'] = 'B';
            $customDefaultValues['MATR_KND'] = 'ZP';
            $customDefaultValues['PROD_DE'] = date("Y-m-d");
        }else if($ud_content_tab == 'telecine'){
            // $rootId = '205';
            // $rootText = '디지털 변환 복원';
            $customDefaultValues['VIDO_TY_SE'] = 'B';
            $customDefaultValues['MATR_KND'] = 'ZP';
            $customDefaultValues['SHOOTING_ORGINL_ATRB'] = 'general';
            $categoryEditable = 0;   
            $customDefaultValues['CTGRY_ID'] = '205';
            $customDefaultValues['PROD_DE'] = date("Y-m-d");
        }else{
            $rootId = 100;
            $rootText = '영상';
            $customDefaultValues['BRDCST_STLE_SE'] = 'E';
            $customDefaultValues['VIDO_TY_SE'] = 'B';            
        }

        $baseContentFields = [
            [
                'usr_meta_field_title' => '콘텐츠ID',
                'usr_meta_field_code' => 'K_CONTENT_ID',
                'usr_meta_field_type' => 'textfield',
                'usr_meta_field_id' => -1,
                'is_editable' => 0,
                'is_hidden' => 1
            ], [
                'usr_meta_field_title' => '<span style="font-weight:bold">제목</span><span style="color:red">&nbsp;*&nbsp;</span></span>',
                'usr_meta_field_code' => 'K_TITLE',
                'usr_meta_field_type' => 'textfield',
                'usr_meta_field_id' => -1,
                'is_editable' => 1,
                'allowBlank' => 0,
                'is_hidden' => 0
            ]
            ,[
                'usr_meta_field_title' => '<span style="font-weight:bold">물리폴더</span><span style="color:red">*</span></span>',
                'usr_meta_field_name' => 'C_CATEGORY_ID',
                'usr_meta_field_code' => 'CTGRY_ID',
                'usr_meta_field_type' => 'c-tree-combo',
                'usr_meta_field_id' => -1,
                'allowBlank' => 0,
                'is_editable' => $categoryEditable,
                'is_hidden' => 0,
                'rootId' => $rootId,
                'rootText' => $rootText
            ]
        ];
        foreach($viewFields as $viewField){
            $isUsrField = false;
            foreach($rsFields as $field){
      
                if( $field['usr_meta_field_code'] == $viewField['field_code'] ){
                    $isUsrField = true;
                    $field['field_set'] = $viewField;
                    $formInfos [ $viewField['field_set_code'] ] [] = $field;                   
                }
                unset($field);
            }
            if( !$isUsrField ){
          
                foreach ($baseContentFields as $field) {
                    if ($field['usr_meta_field_code'] == $viewField['field_code']) {  
                                  
                        $field['field_set'] = $viewField;
                        $formInfos [ $viewField['field_set_code'] ] [] = $field;
                    }
                }
                unset($field);
            }
        }     

        
        
        foreach($formInfos as $fieldSetCode => $fieldSet){    
            $items = [];
            $fieldSetItem = [
                'xtype' => 'fieldset',
                //'layout' => 'fit',
                //'columnWidth' => 0.5,
                'collapsible' => true,
                'collapsed' => false,
                'autoHeight' => true,
                'defaults' => [
                    //'anchor' => '-20',
                    'labelSeparator' => '',
					'anchor'=>  '95%'
                ]
            ];
            
            $fieldSetTitle = $fieldSetCode;
    
            foreach ($fieldSet as $field) {
                $xtype =                $field['usr_meta_field_type'];
                $usr_meta_field_id =    $field['usr_meta_field_id'];
                $fieldSetTitle =        $field['field_set']['field_set_nm'];
                $fieldItem = [
                    'xtype' => 'textfield'
                ];
                  
                $fieldItem['hidden'] = empty($field['field_set']['is_hidden']) ? false : true;
                    
                $fieldItem['xtype'] = $field['usr_meta_field_type'];
                $fieldItem['name'] = empty($field['usr_meta_field_name']) ? strtolower($field['usr_meta_field_code']) : strtolower($field['usr_meta_field_name']);
                $fieldItem['readOnly'] = empty($field['is_editable']) ? true : false;
                if($field['usr_meta_field_code'] == 'USE_PRHIBT_AT'){
                    $fieldItem['readOnly'] = false;                    
                }
                if($field['usr_meta_field_code'] == 'USE_PRHIBT_CN'){
                    $fieldItem['readOnly'] = false;
                    $fieldItem['allowBlank'] = false;
                }
                if($field['usr_meta_field_code'] == 'EMBG_RESN'){      
                    $fieldItem['allowBlank'] = false;
                }
                if($field['usr_meta_field_code'] == 'CPYRHT_CN'){      
                    $fieldItem['allowBlank'] = false;
                }
                
                // if( $flag ==  'vs2ingest' ){
                //     //인제스트는 입력가능
                //     $field['is_required'] = 0;
                // }

                $fieldItem['fieldLabel'] = empty($field['is_required']) || $field['is_required'] !== "1" ? $field['usr_meta_field_title'] : '<span style="font-weight:bold">'.$field['usr_meta_field_title'].'<span style="color:red">&nbsp;*&nbsp;</span></span>';
                
                // 벨리데이션 주석처리
                if ($fieldItem['hidden'] == false) {
                    if (isset($field['allowBlank'])) {
                        $fieldItem['allowBlank'] = empty($field['allowBlank']) ? false : true;
                    }else{
                        //$fieldItem['allowBlank'] = $field['is_required'] === "1" ? false : true;
                    }
                }
                
                $defaultInfo = getFieldDefaultValueArray($usr_meta_field_id);
                $defaultValue = $defaultInfo['default'];

                if ($xtype == 'checkbox') {
                    if ( !empty($defaultValue) ) {                      
                        $fieldItem['checked'] = true;
                    }
                }
                else if ($xtype == 'datefield') {
                    $fieldItem['altFormats'] = 'Y-m-d|Y-m-d H:i:s|YmdHis|Ymd';
                    $fieldItem['format'] = 'Y-m-d';                 
                    $fieldItem['value'] = empty($field['value'])? '' : $field['value'];

                } else if ($xtype == 'combo' || $xtype == 'c-tag-combo' || $xtype == 'g-combo') {
                    $store = getFieldCodeValue($usr_meta_field_id, $field['usr_meta_field_code']);

                    if( empty($store) ){                        
                        $store = $defaultInfo['store'];
                    }
                    if(  !empty($field['value']) ){
                        $fieldItem['value'] = $field['value'];
                    }else if( $defaultValue  ){
                        $fieldItem['value'] = $defaultValue;
                    }

                    $fieldItem['readOnly'] = empty($field['is_editable']) ? true : false;
                    $fieldItem['editable'] = false;
                    $fieldItem['triggerAction'] = 'all';
                    $fieldItem['typeAhead'] = true;
                    $fieldItem['mode'] = 'local';
                    $fieldItem['valueField'] = 'key';
                    $fieldItem['displayField'] = 'val';
                    $fieldItem['store'] = [
                        'xtype' => 'jsonstore',
                        'fields' => [['name' => 'key'],['name'=> 'val'],['name'=> 'use_yn']],
                        'data' => $store
                    ];
                    // combo 기본값 
                    switch($field['usr_meta_field_code']){
                        case 'KOGL_TY':
                            $fieldItem['value'] = 'open04';
                        break;
                        case 'INSTT':             
                        break;
                    }

                }else if($xtype == 'textfield') {
                    $fieldItem['value'] = empty($field['value'])? $defaultValue : $field['value'];
                }else if( $field['usr_meta_field_code']  == 'CTGRY_ID' ){                    
                    $fieldItem['url'] = '/store/get_categories.php';
                    //$fieldItem['url'] = '/api/v1/categories';
                    $fieldItem['params'] = ['action'=> 'get-folders','user_id' => $user_id ];
                    $fieldItem['rootId'] = empty($field['rootId'])? '0' : $field['rootId'];
                    $fieldItem['rootText'] = empty($field['rootText'])? 'Root' : $field['rootText'];
                    $fieldItem['value'] = empty($field['value'])? '' : $field['value'];
                }else if($xtype == 'embargo'){
                    $fieldItem['fieldSet'] = empty($field['field_set'])? '' : $field['field_set'];
                }

                //커스텀 기본값 셋팅
                if( empty($fieldItem['value']) && !empty($customDefaultValues[$field['usr_meta_field_code']]) ){
                    $fieldItem['value'] = $customDefaultValues[$field['usr_meta_field_code']];
                }

            
                $items [] =  $fieldItem;
            }
            if($fieldSetCode == 'default'){
                foreach($items as $item){
                    $formItem [] =  $item;
                }
            }else{
                if($fieldSetCode === 'basic'){
                    $fieldSetItem['collapsed'] = false;
                };

                $fieldSetItem['items'] = $items;
                $fieldSetItem['title'] = $fieldSetTitle;
                $formItem [] =  $fieldSetItem;
            }            
        }
        
        // dd($formItem);

        $formText = json_encode($formItem, JSON_UNESCAPED_UNICODE);


		$container_text = "	{
				id: 'user_metadata_{$container_id_tmp}',
				xtype: 'form',
				//cls: 'ingest_schedule_user_tab',
				autoScroll: true,
				url: '/store/content_edit.php',
				title: '{$container_title}',
				padding: 5,
				border: false,
				defaultType: 'textfield',
				defaults: {
					labelSeparator: '',
					anchor: '95%'
				}
				,buttonAlign: 'left'
				,buttons: [$buttons]
				,listeners: {
					render: function (self) {
						self.getForm().on('beforeaction', function (form) {
							form.items.each(function (item)	{
								if (item.xtype == 'checkbox') {
									if (!item.checked) {
										item.el.dom.checked = true;
										item.el.dom.value = 'off';
									}
								}
							});
						});
					}
				},
				items: ".$formText." }";

        $container_array[] = $container_text;
       
        break;
	}
    
	$containerBody = '['.join(',', $container_array).']';

	echo $containerBody;
} catch (Exception $e) {
	die(json_encode(array(
		'success' => false,
		'msg' => $e->getMessage().$db->last_query
	)));
}
?>