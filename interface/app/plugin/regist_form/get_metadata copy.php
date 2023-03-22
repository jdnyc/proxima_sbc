<?php
/**
 * NLE, FileIngest등의 등록페이지에서 메타데이터 폼을 생성
 */
use Proxima\core\Session;
require_once($_SERVER['DOCUMENT_ROOT']."/lib/config.php");
require_once($_SERVER['DOCUMENT_ROOT']."/lib/functions.php");
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
	if(isset($_REQUEST['current_category_id'])){
		$category_path = getCategoryFullPath($_REQUEST['current_category_id']);
	}

    $map_categories = getCategoryMapInfo();
    $root_category_id = $map_categories[$ud_content_id]['category_id'];
    $root_category_text = $map_categories[$ud_content_id]['category_title'];

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
		$container_title = addslashes($container['usr_meta_field_title']);//컨테이너 명

		$items = array();
        array_push($items, "{xtype: 'hidden', name: 'k_user_id', value: '".$user_id."'}\n");
		array_push($items, "{xtype: 'hidden', name: 'k_content_id', value: '".$content_id."'}\n");
		array_push($items, "{xtype: 'hidden', name: 'k_ud_content_id', value: '".$content['ud_content_id']."'}\n");

		if($meta_tab_order == 1){ //because, first show_order can start from 2 or more.
			//MN00249 title
			//자동으로 타이틀을 넣어줄 경우
			$content['title'] = $_REQUEST['title'] ? $_REQUEST['title'] : $content['title'];
            array_push($items, "{xtype: 'textfield',fieldLabel: '<font color=red>*&nbsp;</font>"._text('MN00249')."', name: 'k_title', allowBlank: false, value: '".addslashes($content['title'])."'}\n");
            array_push($items, getCategoryTree($category_path, $root_category_id, $root_category_text, $ud_content_tab, $ud_content_id));
		}
		
		$rsFields = content_meta_value_list($content_id, $content['ud_content_id'], $container_id_tmp);
		foreach ($rsFields as $f) {
			$item = array();
			$xtype	= $f['usr_meta_field_type'];
			$is_required = $f['is_required'];
			if($is_required == 1)
			{
				$check_star = "<font color=red>*&nbsp;</font>";
			}
			else
			{
				$check_star = "&nbsp;&nbsp;&nbsp;";
			}
			$label	= $check_star.addslashes($f['usr_meta_field_title']);
			$name	= strtolower($f['usr_meta_field_code']);
			$value	= autoConvertByType($xtype, $f['usr_meta_value']);
			$meta_field_id = $f['usr_meta_field_id'];

			if ($xtype == 'listview') continue;
			if ($f['is_show'] !== '1') continue;

            // 커스터마이징 된 메타데이터에 대한 처리
            // The logic for customized metadata
            if(defined('CUSTOM_ROOT') && class_exists('\ProximaCustom\core\MetadataFieldManager')) {
                $control = \ProximaCustom\core\MetadataFieldManager::getFieldControl($f, 
                                $value, $content_id, \ProximaCustom\core\MetadataMode::Single, $rsFields);
                if(!empty($control)) {
                    $items[] = $control;	
                    continue;			
                }					
            }

            array_push($item, "xtype: '".$xtype."'");
            array_push($item, "name: '".$name."'");
            array_push($item, "id: '".$name."'");
            if ($f['is_editable'] == 0) {
                array_push($item, "readOnly: true");
            }
            if ($f['is_required'] == 1) {
                array_push($item, "allowBlank: false");
            }
            if ($xtype == 'checkbox') {
                if ( ! empty($value) && ($value == 'on' || $value == '1')) {
                    array_push($item, "checked: '".'true'."'");
                }

                array_push($item, "value: '".esc2($value)."'");

            } else {
                array_push($item, "value: '".esc2($value)."'");
            }

            if ($xtype == 'datefield') {
                array_push($item, "altFormats: 'Y-m-d|Y-m-d H:i:s|YmdHis', format: 'Y-m-d'");
                $now = date('Y-m-d');
                array_push($item, "value: '$now' ");
            } else if ($xtype == 'combo') {
                $store = getFieldCodeValue($meta_field_id, $f['usr_meta_field_code']);
                if ( empty($store) || $store =='[]') {
                    $store = "[".getFieldDefaultValue($meta_field_id)."]";
                    $value = getFieldDefaultValue2($meta_field_id);
                    array_push($item, "editable: true, triggerAction: 'all', typeAhead: true, mode: 'local', store: $store, value: '$value' ");
                } else {
                    array_push($item, "editable: false, triggerAction: 'all', typeAhead: true, mode: 'local', valueField: 'key',displayField: 'val',store: new Ext.data.JsonStore({ fields: [{name:'key'},{name:'val'}],data: $store }) ");
                }
            }

            if($xtype == 'textfield') {
                $value = getFieldDefaultValue2($meta_field_id);
                array_push($item, "value: '$value' ");
            }

            array_push($item, "fieldLabel: '".$label."'");
            array_push($items, "{".join(', ', $item)."}\n" );
			
		}

		$logger->addInfo('fields', $items);
		$items_text = join(', ', $items)."\n";

		$container_text = "	{
				id: 'user_metadata_{$container_id_tmp}',
				xtype: 'form',
				cls: 'change_background_panel_detail_content ingest_schedule_user_tab',
				autoScroll: true,
				url: '/store/content_edit.php',
				title: '{$container_title}',
				padding: 5,
				border: false,
				//frame: true,
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
				items: [ $items_text ] }";

		$container_array[] = $container_text;
	}

	$containerBody = '['.join(',', $container_array).']';

	echo $containerBody;
} catch (Exception $e) {
	die(json_encode(array(
		'success' => false,
		'msg' => $e->getMessage().$db->last_query
	)));
}

function getCategoryTree($category_path, $root_category_id, $root_category_text, $ud_content_tab='program', $ud_content_id = 0) {

	global $logger;

	$logger->addInfo($ud_content_id . ', ' . $ud_content_tab);
	$fieldLabel = _text('MN00387');

	return "{
		xtype: 'treecombo',
		flex: 1,
		id: 'category',
		fieldLabel: '".$fieldLabel."',
		name: 'c_category_id',
		value: '".$category_path."',
		pathSeparator: ' > ',
		rootVisible: true,
		treeWidth: 500,
		loader: new Ext.tree.TreeLoader({
			url: '/store/get_categories.php',
			baseParams: {
				action: 'get-folders',
				path: '".$category_path."',
				ud_content_tab: '".$ud_content_tab."'
			},
			listeners: {
				load: function(self, node, response){
					var path = self.baseParams.path;
					
					if(!Ext.isEmpty(path) && path != '0'){
						path = path.split('/');
						self.baseParams.path = path.join('/');

						var caregory_id, id, n, i;
						caregory_id = path[path.length-1];
						
						//Find id to select. If path is long, many time run this part.
						for(i=1; i<path.length; i++) {
							id = path[path.length -i];
							n = node.findChild('id', id);
							if(!Ext.isEmpty(n)) {
								break;
							}
						}

						if(Ext.isEmpty(n) || node.id === caregory_id) {
							//For root category or find id
							node.select();
							Ext.getCmp('category').setValue(caregory_id);
						} else {
							//Expand and search again or select
							if(n && n.isExpandable()){
								n.expand(); //if not find id in this load, then expand(reload)
							}else{
								n.select();
								Ext.getCmp('category').setValue(n.id);
							}
						}
					}else{
						node.select();
						Ext.getCmp('category').setValue(node.id);
					}
				}
			}
		}),
		root: new Ext.tree.AsyncTreeNode({
			id: '".$root_category_id."',
			text: '$root_category_text',
			expanded: true
		}),
		listeners: {
			select: function(self, node) {
				var _this = this;
				Ext.getCmp('category').setValue(node.id);
			}
		}
	}";
}

function getFieldDefaultValue($field_id)
{
	global $db;

	$data = $db->queryOne("select default_value from bc_usr_meta_field where usr_meta_field_id=".$field_id);

	list($default, $value_list) = explode('(default)', $data);
	$value_list = explode(';', $value_list);

	foreach ($value_list as $value)
	{
		$result[] = "'$value'";
	}

	return join(',', $result);
}

function getFieldDefaultValue2($field_id)
{
	global $db;

	$data = $db->queryOne("select default_value from bc_usr_meta_field where usr_meta_field_id=".$field_id);

	$data_arr = explode(';', $data);
	foreach($data_arr as $info) {
		if(strpos($info, '(default)') !== false) {
			list($search, $value) = explode('(default)', $info);
		}
	}

	return $value;
}

function getFieldCodeValue($field_id, $user_meta_field_code){
	global $db;
	$code = strtoupper($user_meta_field_code);
    //$datas = $db->queryAll("select usr_code_key as key, usr_code_value as val from BC_USR_META_CODE where USR_META_FIELD_CODE='$code' order by show_order");
    $datas = $db->queryAll("SELECT ci.CODE_ITM_CODE AS key,
    ci.CODE_ITM_NM AS val
    FROM DD_CODE_SET CS 
    JOIN DD_CODE_ITEM CI 
    ON (cs.ID=ci.CODE_SET_ID) 
    WHERE cs.DELETE_DT IS NULL 
    AND ci.DELETE_DT IS NULL 
    AND cs.CODE_SET_CODE='$user_meta_field_code'");
	if(empty($datas)){
		$datas = array();
	}
	return json_encode($datas);
	$result = array();
	foreach($datas as $data)
	{
		$result[] = "{key:'".$data[key]."',val:'". $data[val]."'}";
	}

	return join(',', $result);
}


function autoConvertByType($xtype, $value) {
	if ($xtype == 'datefield') {
		$timestamp = strtotime($value);
		if ( ! $timestamp) {
			$timestamp = '';
		} else {
			$timestamp = date('YmdHis', $timestamp);
		}

		return $timestamp;
	} else {

		return addslashes($value);
	}
}

?>
