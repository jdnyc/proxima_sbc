<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/store/metadata/buildMediaListTab.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/store/metadata/buildSystemMeta.php');
include_once $_SERVER['DOCUMENT_ROOT'].'/store/metadata/getCombo.php';


//if ($_POST['content_id'])
//{
//	$content = $db->queryRow("select * from ingestmanager_schedule where schedule_id='{$_POST['content_id']}'");
//
//	$title				= $content['title'];
//	$content_type_id	= $content['bs_content_id'];
//	$ud_content_id		= $content['ud_content_id'];
//	$content_id			= $content['schedule_id'];
//}
//else
if ($_POST['content_id']) {
	$content_id = $_POST['content_id'];
	$ud_content_id = $_POST['ud_content_id'];

	$content = $db->get("select * from ingestmanager_schedule where schedule_id='{$_POST['content_id']}'");

	$valueList = $db->queryAll("
		select
			*
		from
			ingestmanager_schedule isc,
			im_schedule_metadata ism
		where
			isc.schedule_id=ism.schedule_id
		and	isc.ud_content_id='$ud_content_id'
		and isc.schedule_id='$content_id'");

	$values = array();
	foreach ($valueList as $list) {
		$usr_meta_value =  $list['usr_meta_value'];
		$bc_usr_meta_field_id = $list['bc_usr_meta_field_id'];

		$values[$bc_usr_meta_field_id] = $usr_meta_value;
	}

	$category_id = $content['category_id'];
	$category_path = ltrim(substr(getCategoryPath($category_id).'/'.$category_id, 2), '/');
	$root_category_id ='0';
} elseif ($_POST['ud_content_id']) {
	$ud_content_id = $_POST['ud_content_id'];
	$category_id = $db->queryOne("select category_id from BC_CATEGORY_MAPPING where ud_content_id='$ud_content_id'");
	$category_path = ltrim(substr(getCategoryPath($category_id).'/'.$category_id, 2), '/');
	$root_category_id = '0';
} else {
	echo '메타데이터 생성을 위한 파라메터가 부족합니다.';
	exit;
}

$containerList=$db->queryAll("select * from bc_usr_meta_field where ud_content_id='{$ud_content_id}' and container_id is not null and depth=0 order by show_order");
$defaultF = true;
$view_container="[";
if (count($containerList) > 0) {
	foreach ($containerList as $key=>$val) {
		if ($val['container_id']) {
			if ($key == 0) {
				$container_id_tmp = $val['container_id'];
				$containerBody .= "{
					id: 'user_metadata',
					autoScroll: true,
					padding: 5,
					border: false,
					frame: true,
					defaultType: 'textfield',
					defaults: {
						anchor: '95%'
					},
				";
				$containerBody .= "
				items:[{
						xtype: 'hidden',
						id: 'ud_content_id',
						name: 'k_ud_content_id',
						value: '$ud_content_id'
					},
				";
			}

			$container_id_tmp = $val['container_id'];

			$meta_field_list=$db->queryAll("
			select
				f.is_show,
				f.usr_meta_field_type,
				f.usr_meta_field_title,
				f.usr_meta_field_id,
				f.default_value,
				f.is_editable,
				f.is_required
			from
				bc_usr_meta_field f
			where
				f.ud_content_id='{$ud_content_id}'
			and f.container_id={$container_id_tmp}
			and f.depth = 1 order by f.show_order"); // , f.container_id desc

			$listCheck = 0;

			foreach ($meta_field_list as $list) {
				if($list['usr_meta_field_type'] == 'listview') $listCheck++;
			}

			if ((count($meta_field_list) - $listCheck) > 0) {
				$con_name	= addslashes($val['usr_meta_field_title']);

				$item = "{";

				$item.= "	xtype: 'fieldset',";
				$item.=	"	title: '{$con_name}',";
				$item.=	"	defaults : {
								anchor: '95%',
								labelWidth: '200',
								labelSeparator: ''
						},";
				$item.= "	items: [";

				if ($defaultF) {
					$item .= "{
						xtype: 'treecombo',
						id: 'category',
						fieldLabel: _text('MN00387'),
						autoScroll: true,
						pathSeparator: ' > ',
						rootVisible: false,
						name: 'c_category_id',
						value: '$category_path',
						loader: new Ext.tree.TreeLoader({
							url: '/store/get_categories.php',
							baseParams: {
								action: 'get-folders',
								path: '$category_path'
							},
							listeners: {
								load: function(self, node, response){

									var path = self.baseParams.path;
									if(!Ext.isEmpty(path) && path != '0'){
										path = path.split('/');

										var id = path.shift();
										self.baseParams.path = path.join('/');

										var n = node.findChild('id', id);
										if(n && n.isExpandable()){
											n.expand();
										}else{
											n.select();
											Ext.getCmp('category').setValue(n.id);
										}
									}else{
										node.select();
										Ext.getCmp('category').setValue(node.id);
									}
								}
							}
						}),
						root: new Ext.tree.AsyncTreeNode({
							id: 0,
							expanded: true
						})
					},";

					$defaultF = false;
				}

				foreach ($meta_field_list as $k=>$v) {
					if ($v['usr_meta_field_id']) {
						// meta_field 정보
						// 항목 타입이 container 일 경우 or 그외
						if ($v['is_show'] != '1') continue;

						$xtype = $v['usr_meta_field_type'];
						$label = addslashes($v['usr_meta_field_title']);

						$name = $v['usr_meta_field_id'];

						if ($xtype == 'container') {
							// nothing
						} else {
                            if ($xtype == 'listview') {

    							//$s_item = listview_template($label, $content_id, $v['ud_content_id'],$v['usr_meta_field_id'], $v['default_value']);
								continue;
    						} else {
    							/*
    							 * $item -> $s_item 서브 아이템으로 변경
    							*/

    							$s_item = "{";
    							$s_item .= "xtype: '$xtype',";
    							//if ($v['editable'] != 1)	$s_item .= "readOnly: true, ";
    							if ($v['is_require'] == 1)	$s_item .= "allowBlank: false, ";

    							if ($xtype == 'combo') {
    								$combo_data = getCombo($v['default_value']);
    								$s_item .= 	"triggerAction: 'all'," .
    											"typeAhead: true,"  .
    											"editable: false," .
    											"mode: 'local'," .
    											"store: [".
    												$combo_data['data'].
    											"],";
    								$combo_data="";
    							} elseif ($xtype == 'datefield') {
    								$s_item .= "format: 'Y-m-d',
    											altFormats: 'Y-m-d H:i:s|Y-m-d|Ymd|YmdHis',
    											editable: false,
    											";
    							} else if ($xtype == 'textarea') {
    								$value = str_replace("\r", '', str_replace("\n", '', $value));
    							} else {
    							}

								$s_item .= "fieldLabel: '$label',";
    							$s_item .= "title: '$label',";
    							$s_item .= "name: '$name'";

								if($_POST['content_id'])
								{
									$value = $values[$v['usr_meta_field_id']];
									$s_item .= ",value: '$value'";
								}

    							$s_item .= "},";
                            }
						}
					}
					$item.=$s_item;

				}
				$item = substr($item,0,-1);

				$item.= "]},";
				$containerBody .= $item;
			}

		}
	}
	$containerBody = substr($containerBody,0,-1);
	$containerBody .= "]}";
}

if ($containerBody) {
	$view_container .= $containerBody.']';
} else {
	$view_container .= ']';
}

echo $view_container;

function getListViewDataFields($columns) {
	$asciiA = 65;
	$columns = explode(';', $columns);
	foreach ($columns as $v)
	{
		$result[] = "{name: 'column".chr($asciiA++)."'}";
	}
	$result[] = "{name: 'meta_multi_id'}";

	return join(",\n", $result);
}

//소재영상쪽 컬럼 히든처리를 위해 필드아이디 포함
function getListViewColumns($columns, $meta_field_id) {
	$asciiA = 65;
	$columns = explode(';', $columns);
	foreach ($columns as $v) {
		if($meta_field_id == '11879136' ) {
			if( $v == '순번' ) {
				$result[] = "{width: .1, header: '$v', dataIndex: 'column".chr($asciiA++)."'}";
			} else if( $v =='Start TC' || $v == 'End TC' ) {
				$result[] = "{width: .2, header: '$v', dataIndex: 'column".chr($asciiA++)."'}";
			} else if( $v == '내용') {
				$result[] = "{header: '$v', dataIndex: 'column".chr($asciiA++)."'}";
			} else {
				$asciiA++;
			}
		} else {
			$result[] = "{header: '$v', dataIndex: 'column".chr($asciiA++)."'}";
		}
	}

//	$result[] = "{header: 'meta_value_id', dataIndex: 'meta_value_id', hidden: true}";

	return join(",\n", $result);
}

function getListViewForm($columns) {
	$asciiA = 65;
	$columns = explode(';', $columns);
	$columnCount = count($columns);

	foreach ($columns as $v) {
		if($v=='내용') {
			$result[] = "{xtype:'textarea', fieldLabel: '$v',width:50 , name: 'column".chr($asciiA++)."'}";
		} else {
			$result[] = "{fieldLabel: '$v',width:50 , name: 'column".chr($asciiA++)."'}";
		}
	}

	return array(
		'columnHeight' => ($columnCount * 45 + 20),
		'columns' => join(",\n", $result)
	);
}


function listview_template($label, $content_id, $meta_table_id, $meta_field_id, $default_value) {
	$listview_form			= getListViewForm($default_value);
	$listview_datafields	= getListViewDataFields($default_value);
	$listview_columns		= getListViewColumns($default_value, $meta_field_id);

	$listview_template = "{
		xtype: 'panel',
		fieldLabel: '$label',
		labelSeparator: '',
		layout: 'fit',
		height: 150,
		frame: true,
		anchor: '100%',
		items: [{
			xtype: 'listview',
			columnSort: false,
			emptyText: '등록된 데이터가 없습니다.',
			singleSelect: true,
			store: new Ext.data.JsonStore({
				autoLoad: true,
				url: '/store/getListView.php',
				baseParams: {
					content_id: '$content_id',
					meta_field_id: '$meta_field_id'
				},
				root: 'data',
				fields: [
					$listview_datafields
				],
				listeners: {
					load: function(self){
						//console.log(self);
					}
				}

			}),
			columns: [
				$listview_columns
			],
			listeners: {
				click: function(self, selections){

					var list = self;
					var records = list.getSelectedRecords()[0];

					Ext.getCmp('tc_panel').setVisible(true);
					Ext.getCmp('tc_panel').get(0).getForm().loadRecord( records );
				}
			}
		},{
            id:'$meta_field_id',
            xtype: 'hidden',
            name: '$meta_field_id',
            value:''
        }]

	},";

	return $listview_template;
}
?>