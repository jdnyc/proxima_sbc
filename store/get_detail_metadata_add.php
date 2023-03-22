<?php
/*

예전 버전 토픽 추가용. 2014-11-22 사용안함.

*/
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/lang.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/store/metadata/getCombo.php');

$ud_content_id = $_POST['ud_content_id'];
$category_id = $_POST['category_id'];

$ud_content_info = $db->queryRow("select * from bc_ud_content where ud_content_id='".$ud_content_id."'");
$bs_content_id = $ud_content_info['bs_content_id'];

$user_id = $_SESSION['user']['user_id'];

$listview_usr_meta_field_id = $db->queryOne("select usr_meta_field_id from bc_usr_meta_field
				where ud_content_id='".$ud_content_id."' and usr_meta_field_type='listview'");

$containerList = $db->queryAll("select * from bc_usr_meta_field where ud_content_id='{$ud_content_id}' and container_id is not null and depth=0 order by show_order");

$path_separator = ' > ';
if( empty($category_id) ) {
	$category_id = TOPIC_CATEGORY_ID;
}
$category_path = getCategoryFullPath($category_id);
$catPathTitle = getCategoryPathTitle($category_path, $path_separator);
$root_category_id = TOPIC_CATEGORY_ID;
$root_category_text = '토픽';

$view_container="[";
if ( count($containerList)>0 )
{
	foreach ( $containerList as $key=>$val )
	{
		if ( $val['container_id'] )
		{
			$container_id_tmp = $val['container_id'];

			$buttons = buildButtons($container_id_tmp, $content_id, $ud_content_id, $bs_content_id, 2, $user_id,$user_id);

			$containerBody .= "{
				id: 'user_metadata_{$container_id_tmp}',
				xtype: 'form',
				autoScroll: true,
				url: '/store/content_edit.php',
				title: '{$val['usr_meta_field_title']}',
				padding: 5,
				border: false,
				frame: true,
				defaultType: 'textfield',
				defaults: {
					labelSeparator: '',
					labelStyle: 'width:120px;',
					anchor: '93%'
				}
				,buttonAlign: 'left'
				,buttons: [$buttons]
				,listeners: {
					render: function (self) {
						self.getForm().on('beforeaction', function (form) {
							form.items.each(function (item)	{
								if (item.xtype == 'checkbox')
								{
									if (!item.checked) {
										item.el.dom.checked = true;
										item.el.dom.value = 'off';
									}
								}
							});
						});
					}
				}
				,items:[
					{
						xtype: 'hidden',
						name: 'k_meta_table_id',
						value: '{$val['ud_content_id']}'
					},{
						xtype: 'hidden',
						name: 'k_meta_field_id',
						value: '{$val['usr_meta_field_id']}'
					},{
						xtype: 'hidden',
						name: 'k_bs_content_id',
						value: '{$bs_content_id}'
					},{
						xtype: 'treecombo',
						fieldLabel: _text('MN00387'),//카테고리
						treeWidth: '400',
						width: '90%',
						allowBlank: false,
						autoScroll: true,
						pathSeparator: '$path_separator',
						rootVisible: true,
						name: 'c_category_id',
						value: '$category_path',
						listeners: {
							render: function(self){
								var path = '$category_path';
								if(!Ext.isEmpty(path)){
									path = path.split('/');
									var catId = path[path.length-1];
									if(path.length <= 1)
									{
										self.setValue('');
										self.setRawValue('');
									}
									else
									{
										self.setValue(catId);
										self.setRawValue('$catPathTitle');
									}
								}
							}
						},
						loader: new Ext.tree.TreeLoader({
							url: '/store/get_categories.php',
							baseParams: {
								action: 'get-folders',
								path: '$category_path',
								node: '$root_category_id'
							},
							listeners: {

							}
						}),
						root: new Ext.tree.AsyncTreeNode({
							id: '$root_category_id',
							text: '$root_category_text',
							expanded: true
						})
					},{
						xtype: 'textfield',
						fieldLabel: '제목',
						name: 'k_title',
						value: '',
						allowBlank: false
					},{
						xtype: 'datefield',
						fieldLabel: '만료일자',
						name: 'c_expired_date',
						value: new Date().add(Date.MONTH, 1),
						format: 'Y-m-d',
						altFormats: 'Y-m-d|Y-m-d H:i:s|YmdHis|Ymd',
						allowBlank: false
					},
				";



			$meta_field_list = empty_content_meta_value_list( $ud_content_id , $container_id_tmp );
			if ( !empty($meta_field_list) )
			{
				foreach ( $meta_field_list as $k=>$v )
				{
					if ( $v['is_show'] != '1' ) continue;

					$value = $v['usr_meta_value'];

					$xtype			= $v['usr_meta_field_type'];
					$label			= addslashes($v['usr_meta_field_title']);
					$value			= addslashes($value);
					//$name			= $v['meta_value_id'];
					$ud_content_id	= $v['ud_content_id'];
					$meta_field_id	= $v['usr_meta_field_id'];

					$name = $v['usr_meta_field_id'];

					/*
					 * $item -> $s_item 서브 아이템으로 변경
					*/
					if ($xtype == 'listview')
					{
/*
						%label%
						%content_id%
						%ud_content_id%
						%usr_meta_field_id%
						%meta_value_id%
						%listview_form%
						%listview_datafields%
						%listview_columns%
*/
						$item .= listview_template($label, $content_id, $v['ud_content_id'], $v['usr_meta_field_id'], $v['default_value']);
					}
					else if( in_array($v['usr_meta_field_id'], array(META_BOOK_ARTICLE,META_BOOK_KEYWORD,META_PROG_MEAN,META_PROG_CONTENT)) )
					{//KNN, textarea에 담기엔 너무 큰 자료일 시
						switch($v['usr_meta_field_id'])
						{
							case META_BOOK_ARTICLE: $sub_name = 'ARTICLE'; break;
							case META_BOOK_KEYWORD: $sub_name = 'KEYWORD'; break;
							case META_PROG_MEAN: $sub_name = 'MEAN'; break;
							case META_PROG_CONTENT: $sub_name = 'CONTENT'; break;
						}
						$val_len = '';
						//$val_len = strlen($value);
						$value = str_replace("\r", '', str_replace("\n", '\\n', $value));
						$s_item .= "{";
						$s_item .= "xtype: 'compositefield',";
						if ($v['is_editable'] == '0')	$s_item .= "readOnly: true, ";
						if ($v['is_required'] == '1')	$s_item .= "allowBlank: false, ";

						$s_item .= "
							width: '90%',
							fieldLabel: '$label',
							items: [{
								xtype: 'textarea',
								editable: true,
								maxLength: 1000,
								flex: 1,
								id: '".$name."',
								value: '".$value."'
							},{
								xtype: 'button',
								text: '".$val_len."전체보기',
								icon: '/led-icons/page_white_text.png',
								handler: function(b, e){
									var owner_win = Ext.getCmp('winDetail_add');
									var content_id_add = Ext.getCmp('content_id_add').getValue();
									if( Ext.isEmpty(content_id_add) )
									{
										Ext.Msg.alert( _text('MN00023'),'먼저 자료등록부터 해 주시기 바랍니다.');
										return;
									}
									owner_win.el.mask();
									Ext.Ajax.request({
										url: '/store/KNN_pbok_content_win.php',
										params: {
											content_id: content_id_add,
											type: '".$sub_name."',
											mode: 'select',
											val: Ext.getCmp('".$name."').getValue(),
											usr_meta_field_id: '".$v['usr_meta_field_id']."'
										},
										callback: function(opt, success, response){
							var res = Ext.decode(response.responseText);
							if(res.success)
							{
								if(Ext.isEmpty(res.val))
								{
									res.val = Ext.getCmp('".$name."').getValue();
								}
								var win = new Ext.Window({
									width: 700,
									height: 400,
									modal: true,
									layout: 'fit',
									title: '전체보기',
									buttons: [{
										text: '수정',
										scale: 'medium',
										icon: '/led-icons/application_edit.png',
										handler: function(b, e){
											win.el.mask();
											Ext.Ajax.request({
												url: '/store/KNN_pbok_content_win.php',
												params: {
													content_id: content_id_add,
													type: '".$sub_name."',
													mode: 'update',
													val: Ext.getCmp('".$name."_total_view').getValue(),
													usr_meta_field_id: '".$v['usr_meta_field_id']."'
												},
												callback: function(opt, success, response){
													var subres = Ext.decode(response.responseText);
													if(subres.success)
													{
														Ext.getCmp('".$name."').setValue(subres.mini_val);
													}
													else
													{
														Ext.Msg.alert( _text('MN00023'), subres.msg);
													}

													win.el.unmask();
													win.close();
												}
											});
										}
									},{
										text: '닫기',
										scale: 'medium',
										icon: '/led-icons/cross.png',
										handler: function(b, e){
											win.close();
										}
									}],
									items: [{
										xtype: 'textarea',
										id: '".$name."_total_view',
										value: res.val
									}]
								});
								win.show();
							}
							else
							{
								Ext.Msg.alert('오류', '다시 시도 해 주시기 바랍니다.');
							}
							owner_win.el.unmask();
										}
									});
								}
							}]
						},";
					}
					else if( in_array($v['usr_meta_field_id'], explode(',', KNN_ARRAY_META_ID)) ) //KNN, 등록번호일 시
					{
						$auto_id = '';
						if(in_array($ud_content_id, array(UD_BOOK)))
						{
							$auto_id = KNN_get_new_id($ud_content_id, 'id');
						}

						$arr_value = array(
							UD_BOOK => $auto_id,
							UD_COL => 'V1',
							UD_PROG => 'V20',
							UD_KPOP_R => 'MU1',
							UD_CLASSIC_R => 'MU5',
							UD_POP_R => 'MU3'
						);

						$s_item .= "{";
						$s_item .= "xtype: '$xtype',";
						//if ($v['is_editable'] == '0' || $ud_content_id == UD_BOOK)	$s_item .= "disabled: true, ";
						if ($v['is_required'] == '1')	$s_item .= "allowBlank: false, ";

						$s_item .= "width: '90%',";
						$s_item .= "fieldLabel: '$label',";
						//키를 입력받아서 규칙에 맞는 자리수 까지 입력되면 DB에서 최대값을 구해옴.
						//ex) 취재원본 V112 입력하면 해당 규칙이 없으므로 V1으로 다시 setValue,
						//	  V111 입력하면 해당 규칙의 최대값 V111001234 이런식으로 setValue.
						$s_item .= "enableKeyEvents: true,
									listeners: {
										afterrender: function(self){
											self.focus();
											//self.setValue('".$arr_value[$ud_content_id]."');
										},
										keyup: function(self, e){
											var val = self.getValue();
											var val_classify = val.substr(0,2);

											if(  (val.length == 4 && val_classify == 'V1')
											  || (val.length == 5 && val_classify == 'V2')
											  || (val.length == 4 && val_classify == 'MU') )
											{
												self.setReadOnly(true);
												Ext.Ajax.request({
													url: '/store/KNN_get_new_id.php',
													params: {
														ud_content_id: ".$ud_content_id.",
														type: 'id',
														id: val
													},
													callback: function(opt, success, response){
														self.setReadOnly(false);
														var res = Ext.decode(response.responseText);
														if(res.success)
														{
															self.setValue(res.msg);
															if(res.msg.length < 4)
															{
																return;
															}
															self.setReadOnly(true);
														}
														else
														{
															Ext.Msg.alert('오류', res.msg);
														}
													}
												});
											}
										}
									},";
						//$s_item .= "title: '$label',";
						//$s_item .= "id: '$name',";
						$s_item .= "name: '$name',";
						$s_item .= "value: '".$arr_value[$ud_content_id]."'";
						$s_item .= "},";
					}
					else if( in_array($v['usr_meta_field_id'], explode(',', KNN_ARRAY_META_REQ_ID)) ) //KNN, 청구번호일 시
					{
						$auto_id = '';
						if(in_array($ud_content_id, array(UD_BOOK)))
						{
							//$auto_id = KNN_get_new_id($ud_content_id, 'req_id');
						}

						$arr_value = array(
							UD_BOOK => ''
						);

						$s_item .= "{";
						$s_item .= "xtype: '$xtype',";
						if ($v['is_editable'] == '0')	$s_item .= "disabled: true, ";
						if ($v['is_required'] == '1')	$s_item .= "allowBlank: false, ";

						$s_item .= "width: '90%',";
						$s_item .= "fieldLabel: '$label',";
						//키를 입력받아서 규칙에 맞는 자리수 까지 입력되면 DB에서 최대값을 구해옴.
						$s_item .= "enableKeyEvents: true,
									listeners: {
										afterrender: function(self){
											var ud_content_id = '".$ud_content_id."';
											if(ud_content_id == '".UD_BOOK."')
											{
												self.focus();
											}

											self.setValue('".$arr_value[$ud_content_id]."');
										},
										keyup: function(self, e){
											var val = self.getValue();
											var val_classify = val.substr(0,2);
											var ud_content_id = '".$ud_content_id."';

											if(  (val.length == 2 && ud_content_id == '".UD_BOOK."')
											  || (val.length == 4 && ud_content_id == '".UD_KPOP_R."')
											  || (val.length == 4 && ud_content_id == '".UD_CLASSIC_R."')
											  || (val.length == 4 && ud_content_id == '".UD_POP_R."') )
											{
												self.setReadOnly(true);
												Ext.Ajax.request({
													url: '/store/KNN_get_new_id.php',
													params: {
														ud_content_id: ".$ud_content_id.",
														type: 'req_id',
														id: val
													},
													callback: function(opt, success, response){
														self.setReadOnly(false);
														var res = Ext.decode(response.responseText);
														if(res.success)
														{
															self.setValue(res.msg);
															if(res.msg.length < 4)
															{
																return;
															}
															self.setReadOnly(true);
														}
														else
														{
															Ext.Msg.alert('오류', res.msg);
														}
													}
												});
											}
										}
									},";
						//$s_item .= "title: '$label',";
						//$s_item .= "id: '$name',";
						$s_item .= "name: '$name',";
						$s_item .= "value: ''";
						$s_item .= "},";
					}
					else if( in_array($v['usr_meta_field_id'], explode(',', KNN_ARRAY_META_LOCATION)) ) //KNN, 보관위치일 시
					{
						$s_item .= "{";
						$s_item .= "xtype: 'compositefield',";
						if ($v['is_editable'] == '0')	$s_item .= "disabled: true, ";
						if ($v['is_required'] == '1')	$s_item .= "allowBlank: false, ";

						$arr_value = explode('|', $value);
						if(trim($arr_value[0]) == '') $arr_value[0] = '본사';
						if(trim($arr_value[1]) == '') $arr_value[1] = '자료실';

						$s_item .= "width: '90%',";
						$s_item .= "fieldLabel: '$label',";
						$s_item .= "items: [{
										xtype: 'combo',
										triggerAction: 'all',
										typeAhead: true,
										editable: true,
										flex: 1,
										mode: 'local',
										id: 'k_".$name."_pre',
										value: '".$arr_value[0]."',
										store: ['본사','경남지사','서울지사'],
										listeners: {
											change: function(self){
												var pre = Ext.getCmp('k_".$name."_pre').getValue();
												var pos = Ext.getCmp('k_".$name."_pos').getValue();
												var combine = pre+'|'+pos;
												Ext.getCmp('".$name."_hidden_field').setValue(combine);
											}
										}
									},{
										xtype: 'combo',
										triggerAction: 'all',
										typeAhead: true,
										editable: true,
										flex: 1,
										mode: 'local',
										id: 'k_".$name."_pos',
										value: '".$arr_value[1]."',
										store: ['자료실','음향실','기타부서'],
										listeners: {
											change: function(self){
												var pre = Ext.getCmp('k_".$name."_pre').getValue();
												var pos = Ext.getCmp('k_".$name."_pos').getValue();
												var combine = pre+'|'+pos;
												Ext.getCmp('".$name."_hidden_field').setValue(combine);
											}
										}
									},{
										xtype: 'textfield',
										hidden: true,
										name: '".$name."',
										id: '".$name."_hidden_field',
										listeners: {
											afterrender: function(self){
												var pre = Ext.getCmp('k_".$name."_pre').getValue();
												var pos = Ext.getCmp('k_".$name."_pos').getValue();
												var combine = pre+'|'+pos;
												Ext.getCmp('".$name."_hidden_field').setValue(combine);
											}
										}
									}]
								},";
					}
					else
					{
						$s_item .= "{";
						$s_item .= "xtype: '$xtype',";
						if ($v['is_editable'] == '0')	$s_item .= "disabled: true, ";
						if ($v['is_required'] == '1')	$s_item .= "allowBlank: false, ";

						if ($xtype == 'combo')
						{
							$combo_data = getCombo($v['default_value']);
							$s_item .= 	"triggerAction: 'all'," .
										"typeAhead: true,"  .
										"editable: true," .
										"mode: 'local'," .
										"store: [".
											$combo_data['data'].
										"],";
							$value = $combo_data['default'];
							$value = addslashes($value);
							$combo_data="";
						}
						else if ($xtype == 'datefield')
						{
							$s_item .= "format: 'Y-m-d', altFormats: 'Y-m-d H:i:s|Y-m-d|Ymd|YmdHis', editable: true, ";
						}
						else if ($xtype == 'textarea')
						{
							$value = str_replace("\r", '', str_replace("\n", '\\n', $value));
						}
						else if( $xtype == 'checkbox' )
						{
							if (!empty($value)) {
								$s_item .= "checked: true,";
							}
						}

						$s_item .= "width: '90%',";
						$s_item .= "fieldLabel: '$label',";
						//$s_item .= "title: '$label',";
						//$s_item .= "id: '$name',";
						$s_item .= "name: '$name',";
						$s_item .= "value: '$value'";
						$s_item .= "},";
					}

					$item .= $s_item;
					$s_item='';
				}

				//$item=substr($item,0,-1);
				$item = rtrim($item, ',');
				$containerBody.=$item;
				$item='';
			}

			$containerBody.="]},";

		}
	}

	$containerBody=substr($containerBody,0,-1);
}

$view_container .= $containerBody.']';

echo $view_container;

function getListViewDataFields($columns)
{
	$asciiA = 65;
	$columns = explode(';', $columns);
	foreach ($columns as $v)
	{
		$result[] = "{name: 'column".chr($asciiA++)."'}";
	}
	$result[] = "{name: 'meta_value_id'}";

	return join(",\n", $result);
}

function getListViewColumns($columns, $usr_meta_field_id) //소재영상쪽 컬럼 히든처리를 위해 필드아이디 포함
{
	$asciiA = 65;
	$columns = explode(';', $columns);
	foreach ($columns as $v)
	{
		if($v == '상황설명' )
		{
			$result[] = "{width: .65, header: '$v', dataIndex: 'column".chr($asciiA++)."'}";
		}
		else
		{
			$result[] = "{header: '$v', dataIndex: 'column".chr($asciiA++)."'}";
		}
	}

//	$result[] = "{header: 'meta_value_id', dataIndex: 'meta_value_id', hidden: true}";

	return join(",\n", $result);
}

function getListViewForm($columns)
{
	$asciiA = 65;
	$columns = explode(';', $columns);
	$columnCount = count($columns);

	foreach ($columns as $v)
	{
		if($v=='내용')
		{
			$result[] = "{xtype:'textarea', fieldLabel: '$v',width:400 , name: 'column".chr($asciiA++)."'}";
		}
		else
		{
			$result[] = "{fieldLabel: '$v',width:400 , name: 'column".chr($asciiA++)."'}";
		}
	}

	return array(
		'columnHeight' => ($columnCount * 45 + 20),
		'columns' => join(",\n", $result)
	);
}



function listview_template($label, $content_id, $ud_content_id, $usr_meta_field_id, $default_value)
{
	$listview_form			= getListViewForm($default_value);
	$listview_datafields	= getListViewDataFields($default_value);
	$listview_columns		= getListViewColumns($default_value, $usr_meta_field_id);

	$user_id = $_SESSION['user']['user_id'];
	if(true)
	{
		$is_edit_hidden = 'false';
	}
	else
	{
		$is_edit_hidden = 'true';
	}

	$listview_template = "{
		xtype: 'panel',
		hideLabel: true,
		fieldLabel: '$label',
		labelStyle: 'width: 120px',
		layout: 'fit',
		height: 300,
		frame: true,
		parent_content_id: '',
		submit: function(parent, from, action){
			return;
			var list = parent.get(0);
			var tmp = new Array();
			var del_value ='';
			var content_id = parent.parent_content_id;
			if( action == '삭제' )
			{
				del_value = from[0].data.meta_value_id;
			}

			list.getStore().each(function(i){
				tmp.push(i.data);
			});

			Ext.Ajax.request({
				url: '/store/modifyListViewData.php',
				params: {
					content_id: content_id,
					ud_content_id: '$ud_content_id',
					usr_meta_field_id: '$usr_meta_field_id',
					action : action,
					del_value : del_value,
					json_value: Ext.encode(tmp)
				},
				callback: function(opts, success, response){
					if (success)
					{
						try
						{
							var r = Ext.decode(response.responseText);
							if (r.success)
							{

							}
							else
							{
								Ext.Msg.alert('"._text('MN00254')."', r.msg);
							}

						}
						catch(e)
						{
							Ext.Msg.alert(e['title'], e['message']);
						}
					}
					else
					{
						Ext.Msg.alert('서버통신오류', response.statusText);
					}
				}
			});
		},
		form: function(outer){
			var w = new Ext.Window({
				width: 600,
				height: {$listview_form['columnHeight']},
				modal: true,
				layout: 'fit',

				items: {
					xtype: 'form',
					padding: 5,
					frame: true,
					autoScroll: true,
					labelSeparator: '',
					defaultType: 'textfield',

					items: [
						{$listview_form['columns']}
					],

					listeners: {
						afterrender: function(self){
							//self.get(0).focus(false, 250);
						}
					}
				},

				buttons: [{
					text: '전송',
					hidden: $is_edit_hidden,
					handler: function(b, e){

						var parent = b.ownerCt.ownerCt;

						Ext.Msg.show({
							title: '확인',
							msg: parent.title+' 하시겠습니까?',
							icon: Ext.Msg.QUESTION,
							buttons: Ext.Msg.OKCANCEL,
							fn: function(btnId){
								if ( btnId == 'ok')
								{
									var form = parent.get(0).getForm();
									var values = form.getFieldValues();
									var list = outer.get(0);
									var list_store = list.store;
									var new_record = new list_store.recordType( values );

									if ( parent.title == '추가' )
									{
										list_store.add( new_record );
									}
									else
									{
										var old_record = list.getSelectedRecords()[0];
										var idx = list_store.indexOf( old_record );

										list_store.remove( old_record );
										list_store.insert( idx, new_record );
									}

									outer.submit(outer, parent);
								}
							}
						});
					}
				},{
					text: '취소',
					handler: function(b, e){
						b.ownerCt.ownerCt.close();
					}
				}]
			});

			return w;
		},

		tbar: [{
			icon: '/led-icons/application_add.png',
			hidden: $is_edit_hidden,
			text: _text('MN00033'),//추가
			handler: function(b, e){
				var form_store = Ext.getCmp('list{$usr_meta_field_id}').getStore();

				//tc_panel이라고 document.php, media.php등에 존재하는 패널.
				var list_win = Ext.getCmp('tc_panel_win');
				Ext.getCmp('tc_panel').get(0).getForm().reset();

				list_win.show();
				list_win.setVisible(true);

				Ext.getCmp('list{$usr_meta_field_id}').clearSelections();

				list_win.setTitle(_text('MN00033'));//추가
				Ext.getCmp('tc_panel').buttons[1].setIcon( '/led-icons/application_add.png' );
				Ext.getCmp('tc_panel').buttons[1].setText(_text('MN00033'));
			}
		},{
			xtype: 'tbseparator',
			width: 20
        },{
			icon: '/led-icons/application_edit.png',
			hidden: $is_edit_hidden,
			text: _text('MN00043'),//!!수정
			disableGroup: true,
			//disabled: true,
			handler: function(b, e){
				var parent = b.ownerCt.ownerCt;
				if (parent.get(0).getSelectionCount() == 0)
				{
					Ext.Msg.alert(_text('MN00003'), _text('MSG00084'));
					return;
				}
				var list = parent.get(0);
				var records = list.getSelectedRecords()[0];

				//tc_panel이라고 document.php, media.php등에 존재하는 패널.
				var list_win = Ext.getCmp('tc_panel_win');
				list_win.show();
				list_win.setVisible(true);

				list_win.setTitle(_text('MN00043'));
				Ext.getCmp('tc_panel').buttons[1].setIcon( '/led-icons/application_edit.png' );
				Ext.getCmp('tc_panel').buttons[1].setText(_text('MN00043'));
				Ext.getCmp('tc_panel').get(0).getForm().loadRecord( records );
			}
		},{
			xtype: 'tbseparator',
			width: 20
        },{
			icon: '/led-icons/application_delete.png',
			hidden: $is_edit_hidden,
			text: _text('MN00034'),//!!삭제
			disableGroup: true,
			//disabled: true,
			handler: function(b, e){
				var parent = b.ownerCt.ownerCt;
				if (parent.get(0).getSelectionCount() == 0)
				{
					Ext.Msg.alert(_text('MN00003'), '삭제하실 항목을 선택하세요.');
					return;
				}
				Ext.Msg.show({
					title: _text('MN00003'),
					msg: _text('MSG00140'),
					icon: Ext.Msg.QUESTION,
					buttons: Ext.Msg.OKCANCEL,
					fn: function(btnId){
						if ( btnId == 'ok')
						{
							var action = _text('MN00034');
							var parent = b.ownerCt.ownerCt;
							var list = parent.get(0);

							var del_list= list.getSelectedRecords();

							list.getStore().remove( list.getSelectedRecords() );

							//parent.submit(parent, del_list, action);
						}
					}
				});
			}
		}],

		items: [{
			xtype: 'listview',
			id: 'list{$usr_meta_field_id}',
			columnSort: false,
			emptyText: _text('MSG00148'),//!!emptyText: '등록된 데이터가 없습니다.',
			singleSelect: true,
			store: new Ext.data.JsonStore({
				autoLoad: true,
				url: '/store/getListView.php',
				baseParams: {
					content_id: '$content_id',
					usr_meta_field_id: '$usr_meta_field_id'
				},
				root: 'data',
				fields: [
					$listview_datafields
				],
				listeners: {
					load: function(self){

					}
				}

			}),
			columns: [
				$listview_columns
			],
			listeners: {
				selectionchange: function(self, selections){

				},
				click: function(self, selections){


				}
			}
		}]

	},";

	return $listview_template;
}

function buildButtons($container_id_tmp, $content_id, $ud_content_id,$bs_content_id, $status, $user_id,$content_user)
{
	return '';
	$buttons[] = "{xtype: 'tbfill'}";

	$buttons[] = buttonEdit($content_id, $container_id_tmp);

	if ( isset($buttons) )
	{
		$buttons = join(',', $buttons);
	}

	return $buttons;
}


function buttonEdit($content_id, $container_id_tmp)
{
	global $listview_usr_meta_field_id;
	$result = "{
		xtype: 'textfield',
		hidden: true,
		id: 'content_id_add'
	},{
		icon:'/led-icons/arrow_refresh.png',
		text: '초기화',
		scale: 'medium',
		handler: function(b, e){
			var tab = Ext.getCmp('detail_panel');
			tab.items.each(function(i){
				i.getForm().reset();
			});
		}
	},{
		icon:'/led-icons/add.png',
		text: '자료등록',
		scale: 'medium',
		submit: function(callback){
			var w = Ext.Msg.wait('알림', '등록 중 입니다...');

			var form = Ext.getCmp('user_metadata_$container_id_tmp').getForm();

			form.items.each(function(i){

				if (i.xtype == 'checkbox' && !i.checked) {
					i.el.dom.checked = true;
					i.el.dom.value = '';
				}
			});

			var p = form.getValues();

			Ext.each(form.items.items, function (v) {
				if (!v.isValid()) {

				}
			});
			if( !form.isValid() )
			{
				Ext.Msg.alert(_text('MN00023'), '필수 입력 항목을 채워주세요');
				return;
			}

			//2010-11-11
			if (Ext.getCmp('category'))
			{
				var tn = Ext.getCmp('category').treePanel.getSelectionModel().getSelectedNode();
				p.c_category_id = tn.attributes.id;
			}

			// 메타데이터 업데이트
			Ext.Ajax.request({
				url: '/store/content_add.php',
				params: p,
				callback: function(opts, success, response){
					w.hide();
					if (success)
					{
						try
						{
							var r = Ext.decode(response.responseText);
							if (!r.success)
							{
								Ext.Msg.alert(_text('MN00022'), r.msg);
							}
							else
							{
								form.items.each(function(i){
									if ( i.originalValue != undefined )
									{
										i.originalValue = i.getValue();
									}
								});
								Ext.getCmp('content_id_add').setValue(r.content_id);
								if ( Ext.isFunction(callback) )
								{
									callback(r.content_id);
								}
							}
						}
						catch (e)
						{
							Ext.Msg.alert(e['name'], e['message']);
						}
					}
					else
					{
						Ext.Msg.alert(_text('MN00022'), response.ststusText+'( '+response.status+' )');
					}

				}
			});
		},
		listeners: {
			click: function(self, pass_conform){

				if ( pass_conform == true )
				{
					self.submit(function(){
						Ext.Msg.alert(_text('MN00003'), _text('MSG00087'));
					});
				}
				else
				{
					Ext.Msg.show({
						title: '자료등록',
						msg: '해당 정보로 자료를 등록합니다.',
						icon: Ext.Msg.QUESTION,
						buttons: Ext.Msg.YESNO,
						fn: function(btnId){
							if (btnId == 'yes')
							{
								//return_content_id는.. KNN에서 음반-음악간의 부모관계에 필요한
								//bc_content테이블의 parent_content_id부분에 들어갈 ID
								self.submit(function(return_content_id){
									Ext.Msg.show({
										title: '등록완료',
										msg: '자료가 등록되었습니다.'+'<br />'+'창을 닫으시겠습니까?',
										icon: Ext.Msg.QUESTION,
										buttons: Ext.Msg.OKCANCEL,
										fn: function(btnId){
											//panel_for_song이 있는 부분에 부모 content_id를 넣어주게 된다.
											var panel_for_song = Ext.getCmp('detail_sub_panel');
											if(!Ext.isEmpty(panel_for_song))
											{
												panel_for_song.setDisabled(false);
												panel_for_song.parent_content_id = return_content_id;
											}

											var list = Ext.getCmp('list".$listview_usr_meta_field_id."');

											if(!Ext.isEmpty(list))
											{
												var outer = list.ownerCt;
												outer.parent_content_id = return_content_id;

												outer.submit(outer, '', '추가');
											}


											if (btnId == 'ok')
											{
												Ext.getCmp('winDetail_add').close();
											}
										}
									});
								});
							}
						}
					});
				}
			}
		},
		handler: function(b, e) {
			// 2011-01-20 박정근
			// listeners 에 click 으로 변경
		}
	}";

	return $result;
}
?>