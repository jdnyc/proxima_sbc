<?php

function buildUserMeta($content_id, $args, $content, $category_path)
{
	global $db;

	$meta_fields = $db->queryAll("select * from bc_usr_meta_field f, bc_usr_meta_value v " .
									"where v.content_id=$content_id and v.usr_meta_field_id=f.usr_meta_field_id order by f.show_order");

	if ($meta_fields)
	{
		$user_meta_form = "{
			id: 'user_metadata',
			xtype: 'form',
			autoScroll: true,
			url: '/store/content_edit.php',
			title: '기본 정보',
			padding: 5,
			border: false,
			frame: true,
			defaultType: 'textfield',
			defaults: {
				anchor: '95%'
			},
			buttons: [{
				hidden: true,
				text: '수정 요청',
				scale: 'medium',
				handler: function(btn, e){
					new Ext.Window({
						title: '수정 요청 코멘트',
						width: 400,
						height: 209,
						resizable: false,
						modal: true,
						layout: 'fit',

						items: {
							xtype: 'form',
							baseCls: 'x-plain',
							border: false,
							defaults: {
								anchor: '100%',
								border: false
							},

							items: [{
								xtype: 'textarea',
								height: 140,
								hideLabel: true,
								name: 'comment'
							}]
						},

						buttonAlign: 'center',
						buttons: [{
							text: '작성 완료',
							scale: 'medium',
							handler: function(btn, e){
								Ext.Msg.show({
									title: '정보',
									msg: '전송 하시겠습니까?',
									buttons: Ext.Msg.OKCANCEL,
									fn: function(btnId, text, opts){
										if(btnId == 'cancel') return;

										var w = Ext.Msg.wait('등록 요청 중입니다.');

										Ext.Ajax.request({
											url: '/store/add_comment.php',
											params: {
												content_id: $content_id,
												comment: btn.ownerCt.ownerCt.get(0).get(0).getValue()
											},
											callback: function(opts, success, response){

												w.hide();
												if(success)
												{
													try
													{
														var r  = Ext.decode(response.responseText);
														if(!r.success)
														{
															Ext.Msg.alert('오류', r.msg);
															return;
														}

														btn.ownerCt.ownerCt.close();
													}
													catch(e)
													{
														Ext.Msg.alert(e['name'], e['message']);
													}
												}
												else
												{
													Ext.Msg.alert('오류', response.statusText);
												}
											}
										})

									}
								})
							}
						},{
							text: '작성 취소',
							scale: 'medium',
							handler: function(btn, e){
								btn.ownerCt.ownerCt.close();
							}
						}]
					}).show();
				}
			}";

		if ( $_SESSION['user']
				&& ( $_SESSION['user']['is_admin'] == 'Y'
						|| checkAllowGrant($_SESSION['user']['user_id'], $content_id, 'modify') )
		) {
			$user_meta_form .= ",{
				text: '수정',
				scale: 'medium',
				handler: function(b, e) {

					Ext.Msg.show({
						title: '확인',
						msg: '수정하신 내용을 저장하시겠습니까?',
						icon: Ext.Msg.QUESTION,
						buttons: Ext.Msg.YESNO,
						fn: function(btnId){
							if (btnId == 'yes')
							{
								var w = Ext.Msg.wait('수정된 내용을 저장 중입니다.', '요청');

								// 상품목록 업데이트
								if (Ext.getCmp('item_list'))
								{
									var items = [];
									Ext.getCmp('item_list').store.each(function(r){
										items.push({
											item_cd: r.get('item_cd'),
											item_nm: r.get('item_nm')
										});
									});

									Ext.Ajax.request({
										url: '/store/update_item.php',
										params: {
											content_id: $content_id,
											items: Ext.encode(items)
										},
										callback: function(opts, success, resp){
											if (success)
											{
												try
												{
													var r = Ext.decode(resp.responseText);
													if (!r.success)
													{
														Ext.Msg.alert('오류', r.msg);
														return;
													}
												}
												catch (e)
												{
													Ext.Msg.alert(e['name'], e['message']);
												}
											}
											else
											{
												Ext.Msg.alert( _text('MN01098'), resp.statusText);//'서버 오류'
											}
										}
									});
								}

								var p = Ext.getCmp('user_metadata').getForm().getValues();
								var tn = Ext.getCmp('category').treePanel.getSelectionModel().getSelectedNode();
								p.c_category_id = tn.attributes.id;

								// 메타데이터 업데이트
								Ext.Ajax.request({
									url: '/store/content_edit.php',
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
													Ext.Msg.alert('오류', r.msg);
												}
												else
												{
													Ext.Msg.show({
														title: '확인',
														msg: '수정하신 내용이 저장되었습니다.<br />창을 닫으시겠습니까?',
														icon: Ext.Msg.QUESTION,
														buttons: Ext.Msg.OKCANCEL,
														fn: function(btnId){
															if (btnId == 'ok')
															{
																Ext.getCmp('winDetail').close();
															}
														}
													});
												}
											}
											catch (e)
											{
												Ext.Msg.alert(e['name'], e['message']);
											}
										}
										else
										{
											Ext.Msg.alert('서버 오류', response.ststusText+'( '+response.status+' )');
										}

									}
								});
							}
						}
					});
				}
			}";
		}
		$user_meta_form .= "],
			items: [{
				xtype: 'hidden',
				name: 'k_content_id',
				value: '{$meta_fields[0]['content_id']}'
			},{
				xtype: 'hidden',
				id: 'meta_table_id',
				name: 'k_meta_table_id',
				value: '{$meta_fields[0]['meta_table_id']}'
			},{
				xtype: 'hidden',
				name: 'k_meta_field_id',
				value: '{$meta_fields[0]['meta_field_id']}'
			},{
				xtype: 'treecombo',
				id: 'category',
				fieldLabel: '카테고리',
				autoScroll: true,
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
					text: 'EBS DAS',
					expanded: true
				})
			},{
				fieldLabel: '제목',
				name: 'c_title',
				value: '{$content['title']}'
			},";

		$label_width = 0;
		$items = array();
		//print_r($meta_fields);
		foreach ($meta_fields as $v)
		{
			if ($v['is_show'] != '1') continue;

			$xtype			= $v['type'];
			$label			= addslashes($v['name']);
			$value			= addslashes($v['value']);
			$name			= $v['meta_value_id'];
			$meta_table_id	= $v['meta_table_id'];

			if ($label == '방송일시')
			{
				$broadcast_date = '';
				$broadcast_time = '';
				if( strtotime($value) )
				{
					list($broadcast_date, $broadcast_time) = explode(' ', date('Ymd His', strtotime($value)));
				}
				$item = "{
					xtype: 'compositefield',
					fieldLabel: '방송일시',
					name: '$name',

					items: [{
						xtype: 'datefield',
						altFormats: 'Ymd',
						format: 'Y-m-d',
						name: 'broadcast_date',
						value: '$broadcast_date',
						flex: 1
					},{
						xtype: 'timefield',
						altFormats: 'His',
						format: 'H:i:s',
						name: 'broadcast_time',
						value: '$broadcast_time',
						flex: 1
					},{
						xtype: 'button',
						text: '검색',
						handler: function(b, e){
							Ext.Ajax.request({
								url: '/store/component/searchProgram.js',
								callback: function(opts, success, resp){
									if (success)
									{
										Ext.decode(resp.responseText);
									}
									else
									{
										Ext.Msg.alert( _text('MN01098'), resp.statusText);//'서버 오류'
									}
								}
							});
						}
					}]
				}";
			}
			else if ($label == '제작일')
			{
				$timestamp = strtotime($value);
				if (!$timestamp)
				{
					$value = '';
				}
				else
				{
					$value = date('Y-m-d', $timestamp);
				}
				$isHidden = ($meta_table_id != PRE_PRODUCE) ? 'true' : 'false';

				$item = "{
					xtype: 'compositefield',
					fieldLabel: '제작일',
					name: '$name',

					items: [{
						xtype: 'datefield',
						altFormat: 'YmdHis|Y-m-d H:i:s|Y-m-d',
						format: 'Y-m-d',
						value: Date.parseDate('$value', 'Y-m-d'),
						name: '$name',
						flex: 1
					},{
						hidden: $isHidden,
						xtype: 'button',
						text: '검색',
						handler: function(btn, evt){
							var applyTarget = [];

							Ext.getCmp('user_metadata').items.each(function(v){
								switch (v.fieldLabel)
								{
									case '제작일':
									case '사전제작 모델':
										if (v.xtype == 'compositefield')
										{
											applyTarget.push(v.items.get(0).getId());
										}
										else
										{
											applyTarget.push(v.getId());
										}
									break;
								}
							});
							Ext.Ajax.request({
								url: '/store/component/searchCreatedDate.php',
								params: {
									target_id: Ext.encode(applyTarget)
								},
								callback: function(opts, success, resp){
									if (success)
									{
										try
										{
											Ext.decode(resp.responseText);
										}
										catch (e)
										{
											Ext.Msg.alert(e['name'], e['message']);
										}
									}
									else
									{
										Ext.Msg.alert( _text('MN01098'), resp.statusText);//'서버 오류'
									}
								}
							});
						}
					}]
				}";
			}
			else if ($label == '상품목록')
			{
				require_once($_SERVER['DOCUMENT_ROOT'].'/store/component/cItemList.php');
				$item = $cItemList;
			}
			/*
			else if ($v['name'] == '프로그램코드')
			{
				require_once($_SERVER['DOCUMENT_ROOT'].'/store/component/fieldProgramCode.php');
				$item = $fieldProgramCode;
			}
			*/
			else
			{
				if ($xtype == 'listview')
				{
					$listview_datafields = getListViewDataFields();
					$listview_columns = getListViewColumns();
					$item .= "{
								xtype: '$xtype',
								emptyText: '등록된 데이터가 없습니다.',
								store: new Ext.data.JsonStore({
									url: '/store/getListView.php',
									root: 'data',
									fields: [
										$listview_datafields
									]
								}),
								columns: [
									$listview_columns
								]
					}";
				}
				else
				{
					$item .= "{";
					$item .= "xtype: '$xtype',";

					if ($v['editable'] != 1)	$item .= "disabled: true, ";
					if ($v['is_require'] == 1)	$item .= "allowBlank: false, ";

					if ($xtype == 'combo')
					{
						$combo_data = getComboData($v['default_value']);
						$item .= 	"triggerAction: 'all'," .
									"typeAhead: true,"  .
									"editable: false," .
									"mode: 'local'," .
									"store: [".
										$combo_data['data'].
									"],";
					}
					else if ($xtype == 'datefield')
					{
						$item .= "format: 'Y-m-d', altFormats: 'Y-m-d H:i:s|Y-m-d|Ymd|YmdHis', editable: true, ";
					}
					else if ($xtype == 'textarea')
					{
						$value = str_replace("\r", '', str_replace("\n", '', $value));
					}

					$item .= "fieldLabel: '$label',";
					$item .= "name: '$name',";
					$item .= "value: '$value'";
					$item .= "}";

					if ( strlen($label) > $label_width )
					{
						$label_width = strlen($label);
					}
				}
			}

			array_push($items, $item);
			unset($item);
		}

		$created_dt = date('Y-m-d H:i:s', strtotime($content['created_time']));
		array_push($items, "{xtype: 'textfield', fieldLabel: '등록일', name: 'c_created_time', value: '$created_dt', disabled: true, altFormats: 'Y-m-d|Ymd|YmdHis', format: 'Y-m-d'}");
		//array_push($items, "{xtype: 'checkbox', fieldLabel: '공개여부', name: 'c_is_hidden', inputValue: 1, checked: {$content['is_hidden']}}");
		array_push($items, "{xtype: 'datefield', fieldLabel: '보존만료', name: 'c_expire_date', value: '{$content['expire_date']}', editable: true, altFormats: 'Y-m-d|Ymd|YmdHis', format: 'Y-m-d'}");

		$user_meta_form .= implode(', ', $items);
		$user_meta_form .= "], labelAlign: 'right', labelWidth: ".($label_width+100)."}";
	}

	return $user_meta_form;
}

function getComboData($default_value)
{
	list($default, $data) = explode('(default)', $default_value);

	$data = explode(';', $data);
	$data = "'".join("', '", $data)."'";

	return array(
		'default' => $default,
		'data' => $data
	);
}
//
//function getListViewDataFields($columns)
//{
//	$columns = explode(';', $columns);
//	foreach ($columns as $v)
//	{
//		$result[] = "'$v'";
//	}
//
//	return join(',', $result);
//}
//
//function getListViewColumns($columns)
//{
//	$columns = explode(';', $columns);
//	foreach ($columns as $v)
//	{
//		$result[] = "{header: '$v', dataIndex: '$v'}";
//	}
//
//	return join(',', $result);
//}
//function getListViewForm($columns)
//{
//	$columns = explode(';', $columns);
//	foreach ($columns as $v)
//	{
//		$result[] = "{fieldLabel: '$v', name: '$v'}";
//	}
//
//	return join(',', $result);
//}

?>