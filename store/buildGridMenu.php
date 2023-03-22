<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/lang.php');
\Proxima\core\Session::init();
fn_checkAuthPermission($_SESSION);


$user_id		= $_SESSION['user']['user_id'];
$is_admin		= $_SESSION['user']['is_admin'];
$ud_content_id	= $_POST['ud_content_id'];
$bs_content_id	= $_POST['bs_content_id'];

?>

[{hidden: true}
 <?php
	if($arr_sys_code['premiere_plugin_use_yn']['use_yn'] == 'Y'){
 ?>
,{
		text :_text('MN02504'),
		hidden:true,
		id: 'import_premiere_sequence_menu_item',
		icon: '/led-icons/drive_go.png',
		handler : function(e){
				_premiere_open_project_sequece();
		}
	}
	,{
		icon: '/led-icons/sort_number.png',
		id: 'create_a_premiere_sequence_menu_item',
		hidden:true,
		text: _text('MN02505'),
		handler: function(b, e) {
			_premiere_open_create_a_sequece();
		}

	}
	,{
		icon: '/led-icons/sort_number.png',
		id: 'create_ame_archive',
		hidden:true,
		text: 'AME 아카이브',
		handler: function(b, e) {
			var sm = Ext.getCmp('tab_warp').getActiveTab().get(0).getSelectionModel();
			var sel = sm.getSelected();
			var content_id = sel.get('content_id');		
			_ame_archive(content_id);
		}

	}


<?php
	}
?>

<?php
 if($arr_sys_code['photoshop_plugin_use_yn']['use_yn'] == 'Y'){
?>
,{
	 text :_text('MN02510'),
	 hidden:true,
	 id: 'import_image_item',
	 icon: '/led-icons/drive_go.png',
	 handler : function(e){
			 _photoshop_get_image();
	 }
 }
<?php
 }
?>


<?php
// loudness
if( $arr_sys_code['interwork_loudness']['use_yn'] == 'Y' && checkAllowUdContentGrant($user_id, $ud_content_id, GRANT_LOUDNESS) ){
?>
	,{
		text: _text('MN02242'), // Loudness
		icon: '/led-icons/drive_go.png',
		menu: [{
			icon: '/led-icons/counter.png',
			text: _text('MN02243'), // Loudness Measurement
			handler: function(b, e) {
				Ext.Msg.show({
					title: _text('MN00024'),
					msg: _text('MSG02094'),
					modal: true,
					minWidth: 100,
					icon: Ext.MessageBox.QUESTION,
					buttons: Ext.Msg.YESNOCANCEL,
					fn: function(btnId) {
						if(btnId=='cancel') return;

						var sm = Ext.getCmp('tab_warp').getActiveTab().get(0).getSelectionModel();
						var sel = sm.getSelected();
						var content_id = sel.get('content_id');
						var is_correct = 'N';

						if(btnId == 'yes') {
							is_correct = 'Y';
						}

						var w = Ext.Msg.wait(_text('MSG02086'));

						Ext.Ajax.request({
							url: '/store/nps_work/request_loudness.php',
							params: {
								content_id: content_id,
								action: 'measure',
								is_correct: is_correct
							},
							callback: function(opt, success, response) {
								w.hide();
								if (success) {
									var res = Ext.decode(response.responseText);
									if(res.success) {
										Ext.Msg.alert( _text('MN00023'), res.msg);
									} else {
										Ext.Msg.alert( _text('MN01039'), res.msg);
									}
								} else {
									Ext.Msg.alert(_text('MN01039'), response.statusText);
								}
							}
						});
					}
				});
			}
		},{
			icon: '/led-icons/sort_number.png',
			text: _text('MN02244'), // Loudness Correction
			handler: function(b, e) {
				Ext.Msg.show({
					title: _text('MN00024'),
					msg: _text('MSG02088'),
					modal: true,
					minWidth: 100,
					icon: Ext.MessageBox.QUESTION,
					buttons: Ext.Msg.OKCANCEL,
					fn: function(btnId) {
						if(btnId=='cancel') return;

						var sm = Ext.getCmp('tab_warp').getActiveTab().get(0).getSelectionModel();
						var sel = sm.getSelected();
						var content_id = sel.get('content_id');

						var w = Ext.Msg.wait(_text('MSG02086'));

						Ext.Ajax.request({
							url: '/store/nps_work/request_loudness.php',
							params: {
								content_id: content_id,
								action: 'adjust'
							},
							callback: function(opt, success, response) {
								w.hide();
								if (success) {
									var res = Ext.decode(response.responseText);
									if(res.success) {
										Ext.Msg.alert( _text('MN00023'), res.msg);
									} else {
										Ext.Msg.alert( _text('MN01039'), res.msg);
									}
								} else {
									Ext.Msg.alert(_text('MN01039'), response.statusText);
								}
							}
						});
					}
				});
			}
		}]
	}
<?php
}
?>
<?php
/*Approve*/ 
if (checkAllowUdContentGrant($user_id, $ud_content_id, GRANT_ACCESS_APPROVAL_CONTENT) && $arr_sys_code['approval_content_yn']['use_yn'] == 'Y'){
?>
	,{
		text: _text('MN02544'),
		id: 'approval_content_menu_item',
		//icon: '/led-icons/approval_content_icon.png',
		handler: function (btn, e) {
			var option = '<?=$arr_sys_code['approval_content_yn']['ref1']?>';
			var sm = Ext.getCmp('tab_warp').getActiveTab().get(0).getSelectionModel();
			var records = sm.getSelections();
			var rs=[];
			Ext.each(records, function(r){
				rs.push(r.get('content_id'));
			});

			if(option == '1'){
				/* simple approval workflow */
				Ext.Ajax.request({
					url: '/store/content_approval.php',
					params: {
						mode: '1',
						job: 'approve',
						content_list: Ext.encode(rs)
					},
					callback: function(self, success, response) {
						if(success){
							Ext.getCmp('tab_warp').getActiveTab().items.items[0].store.reload();
						}else{

						}
					}
				});
			}else if(option == '2'){
				/* complex approval workflow */
				alert('complex');
			}
		}
	}
<?php
}
?>
<?php 
/*Un-pprove*/ 
if (checkAllowUdContentGrant($user_id, $ud_content_id, GRANT_ACCESS_APPROVAL_CONTENT) && $arr_sys_code['approval_content_yn']['use_yn'] == 'Y'){
?>
	,{
		text: _text('MN02545'),
		id: 'un_approval_content_menu_item',
		//icon: '/led-icons/approval_content_icon.png',
		handler: function (btn, e) {
			var option = '<?=$arr_sys_code['approval_content_yn']['ref1']?>';
			var sm = Ext.getCmp('tab_warp').getActiveTab().get(0).getSelectionModel();
			var records = sm.getSelections();
			var rs=[];
			Ext.each(records, function(r){
				rs.push(r.get('content_id'));
			});

			if(option == '1'){
				/* simple approval workflow */
				Ext.Ajax.request({
					url: '/store/content_approval.php',
					params: {
						mode: '1',
						job: 'unapprove',
						content_list: Ext.encode(rs)
					},
					callback: function(self, success, response) {
						if(success){
							Ext.getCmp('tab_warp').getActiveTab().items.items[0].store.reload();
						}else{

						}
					}
				});
			}else if(option == '2'){
				/* complex approval workflow */
				alert('complex');
			}
		}
	}
<?php
}
?>

<?php
//archive
if( checkAllowUdContentGrant($user_id, $ud_content_id, GRANT_ARCHIVE) && ARCHIVE_USE_YN == 'Y' ){
?>
	,{
		text: _text('MN00056'),
		id: 'archive_menu_item',
		icon: '/led-icons/drive_go.png',
		handler: function (btn, e) {
			Ext.Ajax.timeout = 300000; // 300 seconds

			var sm = Ext.getCmp('tab_warp').getActiveTab().get(0).getSelectionModel();

			var _check = sm.getSelections();
			var _ori_del_flag = 'N';
			Ext.each(_check, function(r, i, a){
				if(r.get('ori_status') == '1' || r.get('ori_size') <= 0) {
					_ori_del_flag = 'Y';
				}
			});
			if(_ori_del_flag == 'Y') {
				//Please select content again with HR media stored in storage
				
				Ext.Msg.alert(_text('MN00023'), _text('MSG02163'));
				return;
			}

			<?php
				if( $arr_sys_code['interwork_flashnet']['use_yn'] == 'Y' ){
			?>
			var send_win = new Ext.Window({
				title: _text('MN01057'),
				width: 300,
				height: 100,
				modal: true,
				layout: 'fit',
				resizable: false,
				items: [{
					xtype: 'form',
					id: 'req_archive_form',
					padding: 5,
					labelWidth: 100,
					labelAlign: 'right',
					labelSeparator: '',
					defaults: {
						xtype:'textfield',
						width:'90%'
					},
					items: [{
						xtype: 'combo',
						readOnly: false,
						anchor: '95%',
						triggerAction: 'all',
						fieldLabel: _text('MN01057'),
						allowBlank: false,
						name : 'archive_group',
						editable : false,
						forceSelection: true,
						displayField : 'name',
						valueField : 'code',
						hiddenName: 'archive_group',
						store : new Ext.data.JsonStore({
							url:'/store/get_archive_group.php',
							autoLoad: true,
							root: 'data',
							fields: [
								'code','name'
							]
						})
					}]
				}],
				buttonAlign: 'center',
				buttons: [{
					text: _text('MN00066'),
					handler: function(b,e){
						var form_valid = Ext.getCmp('req_archive_form').getForm().isValid();
						if(!form_valid) {
							Ext.Msg.alert(_text('MN00023'), _text('MSG01017'));
							return;
						}
						var values = b.ownerCt.ownerCt.get(0).getForm().getValues();

						var rs = [];
						var _rs = sm.getSelections();
						/*
						Ext.each(_rs, function(r, i, a){
							rs.push({
								content_id: r.get('content_id'),
								archive_group: values.archive_group
							});
						});
						*/
						Ext.each(_rs, function(r, i, a){
							rs.push(r.get('content_id'));
						});

						b.ownerCt.ownerCt.close();
						
						Ext.Ajax.request({
								<?php if( $arr_sys_code['interwork_archive_confirm']['use_yn'] == 'Y'){ ?>
									url: '/store/archive/insert_archive_request.php',
									params: {
										case_management: 'request',
								<?php }else{?>
									url: '/store/archive/insert_archive_request_not_confirm.php',
									params: {
								<?php }?>
									//comment: request_reason,
									job_type: 'archive',
									contents: Ext.encode(rs),
									bs_content_id : '<?=$bs_content_id?>',
									ud_content_id : '<?=$ud_content_id?>',
									archive_group: values.archive_group
								},
								callback: function(self, success, response) {
									
									if (success) {
										try {
											Ext.getCmp('tab_warp').getActiveTab().items.items[0].store.reload();
											//var result = Ext.decode(response.responseText);
											//Ext.Msg.alert(_text('MN00023'), result.msg );
										}
										catch (e) {
											Ext.Msg.alert(e['name'], e['message'] );
										}
									} else {
										Ext.Msg.alert(_text('MN00022'), response.statusText + '(' + response.status + ')');
									}
									
								}
							});
						//requestAction('archive', '아카이브 하시겠습니까?', rs);MN00056 MSG01007
						//requestAction('archive', _text('MN00056')+'. '+_text('MSG01007'), rs);
					}
				},{
					text: _text('MN00031'),
					handler: function(b,e){
						b.ownerCt.ownerCt.close();
					}
				}]
			}).show();
			<?php
				}else if( $arr_sys_code['interwork_oda_ods_l']['use_yn'] == 'Y'){
			?>
				var win = new Ext.Window({
					layout:'fit',
					title: _text('MN02423'),
					modal: true,
					width:500,
					height:170,
					buttonAlign: 'center',
					items:[{
						id:'reason_request_inform',
						xtype:'form',
						border: false,
						frame: true,
						padding: 5,
						labelWidth: 70,
						cls: 'change_background_panel',
						defaults: {
							anchor: '95%'
						},
						items: [{
							id:'request_reason',
							xtype: 'textarea',
							height: 50,
							fieldLabel:_text('MN02423'),
							allowBlank: false,
							blankText: '<?=_text('MSG02187')?>',
							msgTarget: 'under'
						}]
					}],
					buttons:[{
						text : '<span style="position:relative;top:1px;"><i class="fa fa-paper-plane-o" style="font-size:13px;"></i></span>&nbsp;'+_text('MN02197'),
						scale: 'medium',
						handler: function(btn,e){
							var isValid = Ext.getCmp('request_reason').isValid();
							if ( ! isValid) {
								Ext.Msg.show({
									icon: Ext.Msg.INFO,
									title: _text('MN00024'),//확인
									msg: '<?=_text('MSG02183')?>',
									buttons: Ext.Msg.OK
								});
								return;
							}

							var request_reason = Ext.getCmp('request_reason').getValue();
							var job_type = 'archive';
							var records = sm.getSelections();

							var rs = [];
							var _rs = sm.getSelections();

							Ext.each(records, function(r, i, a){
								rs.push(r.get('content_id'));
							});

							Ext.Ajax.request({
								<?php if( $arr_sys_code['interwork_archive_confirm']['use_yn'] == 'Y'){ ?>
									url: '/store/archive/insert_archive_request.php',
									params: {
										case_management: 'request',
								<?php }else{?>
									url: '/store/archive/insert_archive_request_not_confirm.php',
									params: {
								<?php }?>
									comment: request_reason,
									job_type: job_type,
									contents: Ext.encode(rs),
									bs_content_id : '<?=$bs_content_id?>',
									ud_content_id : '<?=$ud_content_id?>'
								},
								callback: function(self, success, response) {
									if (success) {
										try {
											Ext.getCmp('tab_warp').getActiveTab().items.items[0].store.reload();
											//var result = Ext.decode(response.responseText);
											//Ext.Msg.alert(_text('MN00023'), result.msg );
										}
										catch (e) {
											Ext.Msg.alert(e['name'], e['message'] );
										}
									} else {
										Ext.Msg.alert(_text('MN00022'), response.statusText + '(' + response.status + ')');
									}
								}
							});
							win.destroy();
						}
					},{
						text : '<span style="position:relative;top:1px;"><i class="fa fa-close style="font-size:13px;"></i></span>&nbsp;'+_text('MN00004'),
						scale: 'medium',
						handler: function(btn,e){
							win.destroy();
						}
					}]
				});
				win.show();

			<?php
				}else if( $arr_sys_code['interwork_oda_ods_d']['use_yn'] == 'Y'){
			?>
				var records = sm.getSelections();

				var rs = [];
				var _rs = sm.getSelections();
				var is_archived = 0;
				Ext.each(records, function(r, i, a){
					if(r.get('archive_yn') =='Y'){
						is_archived = 1;
					}
					rs.push(r.get('content_id'));
				});
				if(is_archived == 0){
					var win = new Ext.Window({
						layout:'fit',
						title: _text('MN02423'),
						modal: true,
						width:500,
						height:170,
						buttonAlign: 'center',
						items:[{
							id:'reason_request_inform',
							xtype:'form',
							border: false,
							frame: true,
							padding: 5,
							labelWidth: 70,
							cls: 'change_background_panel',
							defaults: {
								anchor: '95%'
							},
							items: [{
								id:'request_reason',
								xtype: 'textarea',
								height: 50,
								fieldLabel:_text('MN02423'),
								allowBlank: false,
								blankText: '<?=_text('MSG02187')?>',
								msgTarget: 'under'
							}]
						}],
						buttons:[{
							text : '<span style="position:relative;top:1px;"><i class="fa fa-paper-plane-o" style="font-size:13px;"></i></span>&nbsp;'+_text('MN02197'),
							scale: 'medium',
							handler: function(btn,e){
								var isValid = Ext.getCmp('request_reason').isValid();
								if ( ! isValid) {
									Ext.Msg.show({
										icon: Ext.Msg.INFO,
										title: _text('MN00024'),//확인
										msg: '<?=_text('MSG02183')?>',
										buttons: Ext.Msg.OK
									});
									return;
								}

								var request_reason = Ext.getCmp('request_reason').getValue();
								var job_type = 'archive';
								Ext.Ajax.request({
								<?php if( $arr_sys_code['interwork_archive_confirm']['use_yn'] == 'Y'){ ?>
									url: '/store/archive/insert_archive_request.php',
									params: {
										case_management: 'request',
								<?php }else{?>
									url: '/store/archive/insert_archive_request_not_confirm.php',
									params: {
								<?php }?>
										job_type: job_type,
										contents: Ext.encode(rs),
										bs_content_id : '<?=$bs_content_id?>',
										ud_content_id : '<?=$ud_content_id?>',
										comment: request_reason
									},
									callback: function(self, success, response) {
										if (success) {
											try {
												win.destroy();
												Ext.getCmp('tab_warp').getActiveTab().items.items[0].store.reload();
												//var result = Ext.decode(response.responseText);
												//Ext.Msg.alert(_text('MN00023'), result.msg );
											}
											catch (e) {
												Ext.Msg.alert(e['name'], e['message'] );
											}
										} else {
											Ext.Msg.alert(_text('MN00022'), response.statusText + '(' + response.status + ')');
										}
									}
								});
								/*
								Ext.Msg.show({
									title : _text('MN00023'),
									msg : _text('MN00056') + ' : ' + _text('MSG02039'),
									buttons : Ext.Msg.OKCANCEL,
									fn : function(btnId, text, opts){
										if(btnId == 'ok'){
											var job_type = 'archive';
											Ext.Ajax.request({
											<?php if( $arr_sys_code['interwork_archive_confirm']['use_yn'] == 'Y'){ ?>
												url: '/store/archive/insert_archive_request.php',
												params: {
													case_management: 'request',
											<?php }else{?>
												url: '/store/archive/insert_archive_request_not_confirm.php',
												params: {
											<?php }?>
													job_type: job_type,
													contents: Ext.encode(rs),
													bs_content_id : '<?=$bs_content_id?>',
													ud_content_id : '<?=$ud_content_id?>',
													comment: request_reason
												},
												callback: function(self, success, response) {
													if (success) {
														try {
															win.destroy();
															Ext.getCmp('tab_warp').getActiveTab().items.items[0].store.reload();
															var result = Ext.decode(response.responseText);
															Ext.Msg.alert(_text('MN00023'), result.msg );
														}
														catch (e) {
															Ext.Msg.alert(e['name'], e['message'] );
														}
													} else {
														Ext.Msg.alert(_text('MN00022'), response.statusText + '(' + response.status + ')');
													}
												}
											});
										}
									}
								});
								*/
								win.destroy();
							}
						},{
							text : '<span style="position:relative;top:1px;"><i class="fa fa-close style="font-size:13px;"></i></span>&nbsp;'+_text('MN00004'),
							scale: 'medium',
							handler: function(btn,e){
								win.destroy();
							}
						}]
					});
					win.show();
				} else {
					Ext.Msg.alert(_text('MN00023'),	_text('MSG02139'));
				}
			<?php
				}
			?>
		}
	}
<?php
}
?>


<?php
/*save xml metadata*/ 
// if (checkAllowUdContentGrant($user_id, $ud_content_id, GRANT_ACCESS_APPROVAL_CONTENT) && $arr_sys_code['approval_content_yn']['use_yn'] == 'Y'){
?>
	,{
        text: 'Save Metadata',
        hidden: true,
		id: 'save_xml_meta',
		//icon: '/led-icons/approval_content_icon.png',
		handler: function (btn, e) {
			//var option = '<?=$arr_sys_code['approval_content_yn']['ref1']?>';
			var sm = Ext.getCmp('tab_warp').getActiveTab().get(0).getSelectionModel();
			var records = sm.getSelections();
			var rs=[];
			Ext.each(records, function(r){
				rs.push(r.get('content_id'));
			});
			var win = new Ext.Window({
						layout:'fit',
						title: 'Save Metadata',
						modal: true,
						width:500,
						// height:170,
						buttonAlign: 'center',
						// items:[{
						// 	id:'reason_request_inform',
						// 	xtype:'form',
						// 	border: false,
						// 	frame: true,
						// 	padding: 5,
						// 	labelWidth: 70,
						// 	cls: 'change_background_panel',
						// 	defaults: {
						// 		anchor: '95%'
						// 	},
						// }],
						buttons:[
						{
							text : '<span style="position:relative;top:1px;"><i class="fa fa-paper-plane-o" style="font-size:13px;"></i>XML</span>&nbsp;',
							scale: 'medium',
							handler: function(btn,e){
								Ext.Ajax.request({
									url: '/store/archive/save_metadata_request.php',
									params: {
										//comment: request_reason,
										job_type: 'save_xml_meta',
										contents: Ext.encode(rs),
										bs_content_id : '<?=$bs_content_id?>',
										ud_content_id : '<?=$ud_content_id?>'
										
									},
									callback: function(self, success, response) {
										
										if (success) {
											try {
												Ext.getCmp('tab_warp').getActiveTab().items.items[0].store.reload();
												//var result = Ext.decode(response.responseText);
												//Ext.Msg.alert(_text('MN00023'), result.msg );
											}
											catch (e) {
												Ext.Msg.alert(e['name'], e['message'] );
											}
										} else {
											Ext.Msg.alert(_text('MN00022'), response.statusText + '(' + response.status + ')');
										}
										
									}
								});
								win.destroy();
							}
						},{
							text : '<span style="position:relative;top:1px;"><i class="fa fa-paper-plane-o" style="font-size:13px;"></i>TXT</span>&nbsp;',
							scale: 'medium',
							handler: function(btn,e){
								Ext.Ajax.request({
				
								url: '/store/archive/save_metadata_request.php',
								params: {
									//comment: request_reason,
									job_type: 'save_text_meta',
									contents: Ext.encode(rs),
									bs_content_id : '<?=$bs_content_id?>',
									ud_content_id : '<?=$ud_content_id?>'
									
								},
								callback: function(self, success, response) {
									
									if (success) {
										try {
											Ext.getCmp('tab_warp').getActiveTab().items.items[0].store.reload();
											//var result = Ext.decode(response.responseText);
											//Ext.Msg.alert(_text('MN00023'), result.msg );
										}
										catch (e) {
											Ext.Msg.alert(e['name'], e['message'] );
										}
									} else {
										Ext.Msg.alert(_text('MN00022'), response.statusText + '(' + response.status + ')');
									}
									
								}
							});
								win.destroy();
							}
						},{
							text : '<span style="position:relative;top:1px;"><i class="fa fa-paper-plane-o" style="font-size:13px;"></i>JSON</span>&nbsp;',
							scale: 'medium',
							handler: function(btn,e){
								Ext.Ajax.request({
				
								url: '/store/archive/save_metadata_request.php',
								params: {
									//comment: request_reason,
									job_type: 'save_json_meta',
									contents: Ext.encode(rs),
									bs_content_id : '<?=$bs_content_id?>',
									ud_content_id : '<?=$ud_content_id?>'
									
								},
								callback: function(self, success, response) {
									
									if (success) {
										try {
											Ext.getCmp('tab_warp').getActiveTab().items.items[0].store.reload();
											//var result = Ext.decode(response.responseText);
											//Ext.Msg.alert(_text('MN00023'), result.msg );
										}
										catch (e) {
											Ext.Msg.alert(e['name'], e['message'] );
										}
									} else {
										Ext.Msg.alert(_text('MN00022'), response.statusText + '(' + response.status + ')');
									}
									
								}
							});
								win.destroy();
							}
						},{
							text : '<span style="position:relative;top:1px;"><i class="fa fa-close style="font-size:13px;"></i></span>&nbsp;'+_text('MN00004'),
							scale: 'medium',
							handler: function(btn,e){
								win.destroy();
							}
						}]
						
					});
			win.show();
			
		}
	}
<?php
//}
?>

<?php
//resotre
if( checkAllowUdContentGrant($user_id, $ud_content_id, GRANT_RESTORE) && ARCHIVE_USE_YN == 'Y'  ) {
?>
	,{
		text: _text('MN01021'),
		id: 'restore_menu_item',
		icon: '/led-icons/drive_disk.png',
		handler: function(b, e){
			<?php
				if( $arr_sys_code['interwork_flashnet']['use_yn'] == 'Y' ){
			?>
				b.restore_handler(b.parentMenu.initialConfig.ownerCt, 'restore');
			<?php
				}else if( $arr_sys_code['interwork_oda_ods_l']['use_yn'] == 'Y' ){
			?>
				b.restore_oda(b.parentMenu.initialConfig.ownerCt, 'restore');
			<?php
				}else if( $arr_sys_code['interwork_oda_ods_d']['use_yn'] == 'Y' ){
			?>
				b.restore_oda_ods_d(b.parentMenu.initialConfig.ownerCt, 'restore');
			<?php
				}
			?>
		},
		restore_handler : function (self, type) {
			var sm = Ext.getCmp('tab_warp').getActiveTab().get(0).getSelectionModel();

			var rs = [];
			var _rs = sm.getSelections();
			var number_selected_content = _rs.length;

			var has_online = false;
			Ext.each(_rs, function(r, i, a){
				if(r.get('ori_status') !='1'){
					has_online = true;	
				}
			});

			if(has_online){
				Ext.Msg.show({
					icon: Ext.Msg.INFO,
					title: _text('MN00024'),
					msg: '<?=_text('MSG01021')?>',
					buttons: Ext.Msg.OK
				});
				return;
			}

			if(number_selected_content > 10){
				Ext.Msg.alert(_text('MN00023'), _text('MSG01010')+'(max:10)');
			}else{
				Ext.each(_rs, function(r, i, a){
					rs.push(r.get('content_id'));
				});

				Ext.Ajax.request({
					<?php if( $arr_sys_code['interwork_archive_confirm']['use_yn'] == 'Y'){ ?>
						url: '/store/archive/insert_archive_request.php',
						params: {
							case_management: 'request',
					<?php }else{?>
						url: '/store/archive/insert_archive_request_not_confirm.php',
						params: {
					<?php }?>
						job_type: type,
						contents: Ext.encode(rs),
						bs_content_id : '<?=$bs_content_id?>',
						ud_content_id : '<?=$ud_content_id?>'
					},
					callback: function(self, success, response) {
						if (success) {
							try {
								Ext.getCmp('tab_warp').getActiveTab().items.items[0].store.reload();
								var result = Ext.decode(response.responseText);
								Ext.Msg.alert(_text('MN00023'), result.msg );
							}
							catch (e) {
								Ext.Msg.alert(e['name'], e['message'] );
							}
						} else {
							Ext.Msg.alert(_text('MN00022'), response.statusText + '(' + response.status + ')');
						}
					}
				});
			}
			//START NAKAN
			/*
			

			var sel = sm.getSelected();
			var content_id = sel.get('content_id');

			if(type == 'restore') {
				requestAction(type, _text('MN01021')+'. '+_text('MSG01007'), rs);
			} else {
				var self = Ext.getCmp('tab_warp').getActiveTab().get(0);
				self.load = new Ext.LoadMask(Ext.getBody(), {msg: '<?=_text('MSG00143')?>'});
				self.load.show();
				var that = self;

				if ( !Ext.Ajax.isLoading(self.isOpen) )
				{
					self.isOpen = Ext.Ajax.request({
						url: '/javascript/ext.ux/Ariel.DetailWindow.php',
						params: {
							content_id: content_id,
							record: Ext.encode(sel.json),
							page_mode: 'pfr'
						},
						callback: function(self, success, response){
							if (success)
							{
								that.load.hide();
								try
								{

									if (sel.get('status') == -1)
									{
										Ext.Msg.show({
											title: '경고'
											,msg: _text('MSG00216')
											,icon: Ext.Msg.WARNING
											,buttons: Ext.Msg.OK
											,fn: function(btnId, txt, opt){
												var r = Ext.decode(response.responseText);
											}
										});
									}
									else
									{
										var r = Ext.decode(response.responseText);
									}

									if ( r !== undefined && !r.success)
									{
										Ext.Msg.show({
											title: '경고'
											,msg: r.msg
											,icon: Ext.Msg.WARNING
											,buttons: Ext.Msg.OK
										});
									}
								}
								catch (e)
								{
								}
							}
							else
							{
								Ext.Msg.alert('<?=_text('MN00022')?>', response.statusText+'('+response.status+')');
							}
						}
					});
				} else {
                    that.load.hide();
                }
			}
			*/
			//END NAKAN
		},
		restore_oda : function(self, type){
			var sm = Ext.getCmp('tab_warp').getActiveTab().get(0).getSelectionModel();
			var records = sm.getSelections();
			var has_online = false;
			Ext.each(records, function(r, i, a){
				if(r.get('ori_status') !='1'){
					has_online = true;	
				}
			});

			if(has_online){
				Ext.Msg.show({
					icon: Ext.Msg.INFO,
					title: _text('MN00024'),
					msg: '<?=_text('MSG02506')?>',
					buttons: Ext.Msg.OK
				});
				return;
			}
			var win = new Ext.Window({
					layout:'fit',
					title: _text('MN02423'),
					modal: true,
					width:500,
					height:170,
					buttonAlign: 'center',
					items:[{
						id:'reason_request_inform',
						xtype:'form',
						border: false,
						frame: true,
						padding: 5,
						labelWidth: 70,
						cls: 'change_background_panel',
						defaults: {
							anchor: '95%'
						},
						items: [{
							id:'request_reason',
							xtype: 'textarea',
							height: 50,
							fieldLabel:_text('MN02423'),
							allowBlank: false,
							blankText: '<?=_text('MSG02187')?>',
							msgTarget: 'under'
						}]
					}],
					buttons:[{
						text : '<span style="position:relative;top:1px;"><i class="fa fa-paper-plane-o" style="font-size:13px;"></i></span>&nbsp;'+_text('MN02197'),
						scale: 'medium',
						handler: function(btn,e){
							var isValid = Ext.getCmp('request_reason').isValid();
							if ( ! isValid) {
								Ext.Msg.show({
									icon: Ext.Msg.INFO,
									title: _text('MN00024'),//확인
									msg: '<?=_text('MSG02183')?>',
									buttons: Ext.Msg.OK
								});
								return;
							}

							var request_reason = Ext.getCmp('request_reason').getValue();
							var sm = Ext.getCmp('tab_warp').getActiveTab().get(0).getSelectionModel();
							var records = sm.getSelections();

							var rs = [];
							var _rs = sm.getSelections();

							Ext.each(records, function(r, i, a){
								rs.push(r.get('content_id'));
							});

							Ext.Ajax.request({
								<?php if( $arr_sys_code['interwork_archive_confirm']['use_yn'] == 'Y'){ ?>
									url: '/store/archive/insert_archive_request.php',
									params: {
										case_management: 'request',
								<?php }else{?>
									url: '/store/archive/insert_archive_request_not_confirm.php',
									params: {
								<?php }?>
									job_type: type,
									comment: request_reason,
									contents: Ext.encode(rs),
									bs_content_id : '<?=$bs_content_id?>',
									ud_content_id : '<?=$ud_content_id?>'
								},
								callback: function(self, success, response) {
									if (success) {
										try {
											Ext.getCmp('tab_warp').getActiveTab().items.items[0].store.reload();
											//var result = Ext.decode(response.responseText);
											//Ext.Msg.alert(_text('MN00023'), result.msg );
										}
										catch (e) {
											Ext.Msg.alert(e['name'], e['message'] );
										}
									} else {
										Ext.Msg.alert(_text('MN00022'), response.statusText + '(' + response.status + ')');
									}
								}
							})

							/*
							Ext.Msg.show({
								title : _text('MN00023'),
								msg : _text('MN01021') + ' : ' + _text('MSG02039'),
								buttons : Ext.Msg.OKCANCEL,
								fn : function(btnId, text, opts){
									if(btnId == 'ok'){
										//b.parentMenu.initialConfig.ownerCt.request_oda('archive');
										//var job_type = 'restore';
										var sm = Ext.getCmp('tab_warp').getActiveTab().get(0).getSelectionModel();
										var records = sm.getSelections();

										var rs = [];
										var _rs = sm.getSelections();

										Ext.each(records, function(r, i, a){
											rs.push(r.get('content_id'));
										});

										Ext.Ajax.request({
											<?php if( $arr_sys_code['interwork_archive_confirm']['use_yn'] == 'Y'){ ?>
												url: '/store/archive/insert_archive_request.php',
												params: {
													case_management: 'request',
											<?php }else{?>
												url: '/store/archive/insert_archive_request_not_confirm.php',
												params: {
											<?php }?>
												job_type: type,
												comment: request_reason,
												contents: Ext.encode(rs),
												bs_content_id : '<?=$bs_content_id?>',
												ud_content_id : '<?=$ud_content_id?>'
											},
											callback: function(self, success, response) {
												if (success) {
													try {
														Ext.getCmp('tab_warp').getActiveTab().items.items[0].store.reload();
														var result = Ext.decode(response.responseText);
														Ext.Msg.alert(_text('MN00023'), result.msg );
													}
													catch (e) {
														Ext.Msg.alert(e['name'], e['message'] );
													}
												} else {
													Ext.Msg.alert(_text('MN00022'), response.statusText + '(' + response.status + ')');
												}
											}
										})
									}
								}
							});
							*/

							win.destroy();
						}
					},{
						text : '<span style="position:relative;top:1px;"><i class="fa fa-close style="font-size:13px;"></i></span>&nbsp;'+_text('MN00004'),
						scale: 'medium',
						handler: function(btn,e){
							win.destroy();
						}
					}]
				});
				win.show();
		},
		restore_oda_ods_d : function(self, type){
			var sm = Ext.getCmp('tab_warp').getActiveTab().get(0).getSelectionModel();
			if(sm.getCount() > 1) {
				//Information
				//In ODA ODS_D module, Restore can be one by one.
				Ext.Msg.alert(_text('MN00023'), _text('MSG02154'));
				return;
			}
			var records = sm.getSelections();
			var rs = [];
			var _rs = sm.getSelections();
			var is_archived = 0;
			Ext.each(records, function(r, i, a){
				if(r.get('archive_yn') =='N'){
					is_archived = 1;
				}
				rs.push(r.get('content_id'));
			});
			if(is_archived == 0){
				var win = new Ext.Window({
					layout:'fit',
					title: _text('MN02423'),
					modal: true,
					width:500,
					height:170,
					buttonAlign: 'center',
					items:[{
						id:'reason_request_inform',
						xtype:'form',
						border: false,
						frame: true,
						padding: 5,
						labelWidth: 70,
						cls: 'change_background_panel',
						defaults: {
							anchor: '95%'
						},
						items: [{
							id:'request_reason',
							xtype: 'textarea',
							height: 50,
							fieldLabel:_text('MN02423'),
							allowBlank: false,
							blankText: '<?=_text('MSG02187')?>',
							msgTarget: 'under'
						}]
					}],
					buttons:[{
						text : '<span style="position:relative;top:1px;"><i class="fa fa-paper-plane-o" style="font-size:13px;"></i></span>&nbsp;'+_text('MN02197'),
						scale: 'medium',
						handler: function(btn,e){
							var isValid = Ext.getCmp('request_reason').isValid();
							if ( ! isValid) {
								Ext.Msg.show({
									icon: Ext.Msg.INFO,
									title: _text('MN00024'),//확인
									msg: '<?=_text('MSG02183')?>',
									buttons: Ext.Msg.OK
								});
								return;
							}

							var request_reason = Ext.getCmp('request_reason').getValue();
							Ext.Ajax.request({
									<?php if( $arr_sys_code['interwork_archive_confirm']['use_yn'] == 'Y'){ ?>
										url: '/store/archive/insert_archive_request.php',
										params: {
											case_management: 'request',
									<?php }else{?>
										url: '/store/archive/insert_archive_request_not_confirm.php',
										params: {
									<?php }?>
										job_type: type,
										comment: request_reason,
										contents: Ext.encode(rs),
										bs_content_id : '<?=$bs_content_id?>',
										ud_content_id : '<?=$ud_content_id?>'
									},
									callback: function(self, success, response) {
										if (success) {
											try {
												Ext.getCmp('tab_warp').getActiveTab().items.items[0].store.reload();
												//var result = Ext.decode(response.responseText);
												//Ext.Msg.alert(_text('MN00023'), result.msg );
											}
											catch (e) {
												//Ext.Msg.alert(e['name'], e['message'] );
											}
										} else {
											//Ext.Msg.alert(_text('MN00022'), response.statusText + '(' + response.status + ')');
										}
									}
								});
							win.destroy();
						}
					},{
						text : '<span style="position:relative;top:1px;"><i class="fa fa-close style="font-size:13px;"></i></span>&nbsp;'+_text('MN00004'),
						scale: 'medium',
						handler: function(btn,e){
							win.destroy();
						}
					}]
				});
				win.show();
			} else {
				Ext.Msg.alert(_text('MN00023'),	_text('MSG02141'));
			}
		}
}
<?php
}
?>

<?php
if ( checkAllowUdContentGrant($user_id, $ud_content_id, GRANT_CONTENT_ACCEPT) && $check_accept == 'Y' && $ud_content_id != 4000296) {//GRANT_CONTENT_ACCEPT
?>
,{
	icon: '/led-icons/accept.png',
	text: _text('MN00206'),
	handler: function(btn, e){
		Ext.Msg.show({
			title: '확인',
			msg: '승인하시겠습니까?',
			minWidth: 100,
			modal: true,
			icon: Ext.MessageBox.QUESTION,
			buttons: Ext.Msg.OKCANCEL,
			fn: function(btnId){
				if(btnId=='cancel') return;

				var sm = Ext.getCmp('tab_warp').getActiveTab().get(0).getSelectionModel();

				var records = sm.getSelections();

				Ext.each(records, function(r){
					var content_id = r.get('content_id');
					var w = Ext.Msg.wait('승인 처리중...');

					if( r.get('status') != <?=CONTENT_STATUS_COMPLETE?> )
					{
						Ext.Ajax.request({
							url: '/store/update_content.php',
							params:{
								content_id : content_id
							},

							callback: function(options,success,response){
								w.hide();
								if(success)
								{
									var res = Ext.decode(response.responseText);
									if(res.success)
									{
										Ext.Msg.show({
											title : '승인',
											msg : '승인 처리 되었습니다.',
											minWidth : 120,
											modal: true,
											buttons : Ext.Msg.OKCANCEL
										});
										Ext.getCmp('tab_warp').getActiveTab().items.items[0].store.reload();

									}
									else
									{
										Ext.Msg.alert( _text('MN00023'), res.msg);
										Ext.getCmp('tab_warp').getActiveTab().items.items[0].store.reload();
									}
								}
								else
								{
									Ext.Msg.alert('승인','작업 실패',response.statusText);
								}
							}
						});
					}

				});
			}
		});
	}
}
<?php
}
?>

<?php
//if (checkAllowUdContentGrant($user_id, $ud_content_id, GRANT_YOUTUBE_TRANSFER) &&  $hrdk_yn == 'Y' && $content_info[status] == CONTENT_STATUS_COMPLETE) {
?>
/*,{
	icon: '/led-icons/delivery.png',
	text:'유투브 전송 요청',
	handler: function(btn, e){
		var sm = Ext.getCmp('tab_warp').getActiveTab().get(0).getSelectionModel();
		var records = sm.getSelections();
		var rs=[];

		Ext.each(records, function(r){
			rs.push(r.get('content_id'));
		});

		Ext.Ajax.request({
			url: '/store/execute_task.php',
			params: {
				channel: 'YOUTUBE_TRANSFER',
				content_list: Ext.encode(rs)
			},
			callback: function (self, success, response) {
				if (success) {
					try {
						var result = Ext.decode(response.responseText);
						Ext.Msg.alert('확인', '유투브 전송 요청 완료');
					} catch (e) {
						Ext.Msg.alert(e['name'], e['message']);
					}
				} else {
					Ext.Msg.alert('서버 오류', response.statusText + '(' + response.status + ')');
				}
			}
		});
	}
}*/
<?php
//}
?>

<?php
if ($user_id && checkAllowUdContentGrant($user_id, $ud_content_id, GRANT_CONTENT_DELETE)) {	
?>
,{
    hidden: true,
	icon: '/led-icons/delete.png',
	text: _text('MN01078'),//delete HR
	handler: function(btn, e){
		var activeTab = Ext.getCmp('tab_warp').getActiveTab();
		var sm = activeTab.get(0).getSelectionModel();
		var selections = sm.getSelections();
		var isArchive = true;
		
		for (i = 0; i < selections.length; i++) {			
			if(selections[i].data.archive_yn == 'N'){
				isArchive = false;
				break;
			}
		}

		if(!isArchive){
			Ext.Msg.alert(_text('MN00023'), _text('MSG02120'));
			return;
		}

		var win = new Ext.Window({
				layout:'fit',
				title:'<?=_text('MN00128')?>',
				modal: true,
				width:500,
				height:150,
				buttonAlign: 'center',
				items:[{
					id:'delete_inform',
					xtype:'form',
					border: false,
					frame: true,
					padding: 5,
					labelWidth: 70,
					cls: 'change_background_panel',
					defaults: {
						anchor: '100%'
					},
					items: [{
						id:'delete_reason',
						xtype: 'textarea',
						height: 50,
						fieldLabel:'<?=_text('MN00128')?>',
						allowBlank: false,
						blankText: _text('MSG01062'),//'삭제 사유를 적어주세요',
						msgTarget: 'under'
					}]
				}],
				buttons:[{
					text : '<span style="position:relative;top:1px;"><i class="fa fa-trash" style="font-size:13px;"></i></span>&nbsp;'+_text('MN00034'),
					scale: 'medium',
					handler: function(btn,e){

						var isValid = Ext.getCmp('delete_reason').isValid();
						if (!isValid)
						{
							Ext.Msg.show({
								icon: Ext.Msg.INFO,
								title: '<?=_text('MN00024')?>',
								msg: _text('MSG01062'),//'삭제사유를 적어주세요.',
								buttons: Ext.Msg.OK
							});
							return;
						}


						var sm = Ext.getCmp('tab_warp').getActiveTab().get(0).getSelectionModel();
						var tm = Ext.getCmp('delete_reason').getValue();

						var rs = [];
						var _rs = sm.getSelections();
						Ext.each(_rs, function(r, i, a){
							rs.push({
								content_id: r.get('content_id'),
								delete_his: tm
							});
						});

						Ext.Msg.show({
							icon: Ext.Msg.QUESTION,
							title: '<?=_text('MN00024')?>',
							msg: '<?=_text('MSG00145')?>',

							buttons: Ext.Msg.OKCANCEL,
							fn: function(btnId, text, opts){
								if(btnId == 'cancel') return;

								var ownerCt = Ext.getCmp('tab_warp').getActiveTab().get(0);
								ownerCt.sendAction('delete_hr', rs, ownerCt);
								win.destroy();
							}
						});
					}
				},{
					text : '<span style="position:relative;top:1px;"><i class="fa fa-close style="font-size:13px;"></i></span>&nbsp;'+_text('MN00004'),
					scale: 'medium',
					handler: function(btn,e){
						win.destroy();
					}
				}]
		});
		win.show();
	}
}
<?php
}?>

<?php
if($user_id && checkAllowUdContentGrant($user_id, $ud_content_id, GRANT_ACCESS_DELETE_MY_CONTENT)) {
	$hasGrantContentDeleteMyContent = true;
}

if ($user_id && checkAllowUdContentGrant($user_id, $ud_content_id, GRANT_CONTENT_DELETE) ||
	$hasGrantContentDeleteMyContent) {
?>
,{
	hidden:true,
	icon: '/led-icons/delete.png',
	text: _text('MN00034'),//삭제
	handler: function(btn, e){
		var activeTab = Ext.getCmp('tab_warp').getActiveTab();
		var sm = activeTab.get(0).getSelectionModel();
		var selections = sm.getSelections();
		var isSameRegister = true;

		<?php
		if($hasGrantContentDeleteMyContent) {
			echo 'var hasGrantContentDeleteMyContent = true;';			
		}
		?>
		
		//관리자가 아닐경우 동영상, 이미지, 문서 사용자 정의 콘텐츠는 본인이 등록한 콘텐츠만 삭제가능하도록 수정 - 2017.12.29 Alex
		for (i = 0; i < selections.length; i++) {
			if('<?=$is_admin?>' == 'N' && 
				(hasGrantContentDeleteMyContent !== undefined && 
					hasGrantContentDeleteMyContent && 
					!checkAllowGrantForMyContent(selections[i].data.reg_user_id, '<?php echo $user_id; ?>')) ) {

				isSameRegister = false;
				break;
				
			}
		}

		if(!isSameRegister) {
			Ext.Msg.alert(_text('MN00023'), _text('MSG02528'));
			return;
		}

		var win = new Ext.Window({
				layout:'fit',
				title: _text('MN00128'),
				modal: true,
				width:500,
				height:150,
				buttonAlign: 'center',
				items:[{
					id:'delete_inform',
					xtype:'form',
					border: false,
					frame: true,
					padding: 5,
					labelWidth: 70,
					cls: 'change_background_panel',
					defaults: {
						anchor: '95%'
					},
					items: [{
						id:'delete_reason',
						xtype: 'textarea',
						height: 50,
						fieldLabel:_text('MN00128'),
						allowBlank: false,
						blankText: '<?=_text('MSG02015')?>',
						msgTarget: 'under'
					}]
				}],
				buttons:[{
					text : '<span style="position:relative;top:1px;"><i class="fa fa-trash" style="font-size:13px;"></i></span>&nbsp;'+_text('MN00034'),
					scale: 'medium',
					handler: function(btn,e){
						var isValid = Ext.getCmp('delete_reason').isValid();
						if ( ! isValid) {
							Ext.Msg.show({
								icon: Ext.Msg.INFO,
								title: _text('MN00024'),//확인
								msg: '<?=_text('MSG02015')?>',
								buttons: Ext.Msg.OK
							});
							return;
						}

						var sm = Ext.getCmp('tab_warp').getActiveTab().get(0).getSelectionModel();
						var tm = Ext.getCmp('delete_reason').getValue();

						var rs = [];
						var _rs = sm.getSelections();
						Ext.each(_rs, function(r, i, a){
							rs.push({
								content_id: r.get('content_id'),
								delete_his: tm
							});
						});

						Ext.Msg.show({
							icon: Ext.Msg.QUESTION,
							title: _text('MN00024'),
							msg: _text('MSG00145'),
							buttons: Ext.Msg.OKCANCEL,
							fn: function(btnId, text, opts){
								if(btnId == 'cancel') return;

                                var ownerCt = Ext.getCmp('tab_warp').getActiveTab().get(0);
								ownerCt.sendAction('delete', rs, ownerCt);
								win.destroy();
							}
						});
					}
				},{
					text : '<span style="position:relative;top:1px;"><i class="fa fa-close style="font-size:13px;"></i></span>&nbsp;'+_text('MN00004'),
					scale: 'medium',
					handler: function(btn,e){
						win.destroy();
					}
				}]
		});
		win.show();
	}
}
<?php
}?>
<?php
// $context_menu_list = $db->queryAll("
// 						SELECT	B.NAME, B.CODE, C.REGISTER,
// 									(
// 										SELECT CODE
// 										FROM    BC_CODE
// 										WHERE   ID = C.ICON_URL
// 									 ) AS ICON_URL
// 						FROM	CONTEXT_MENU A, BC_CODE B, BC_TASK_WORKFLOW C
// 						WHERE	A.CODE_ID = B.ID
// 						AND		A.WORKFLOW_ID = C.TASK_WORKFLOW_ID
//                         AND		C.ACTIVITY = '1'
//                         AND     C.IS_SHOW='Y'
// 						ORDER BY A.WORKFLOW_ID
// 					");
//2017-12-29 이승수. CJO, 팝업메뉴 워크플로우 추가시 동적생성 안함.
$context_menu_list = array();

foreach ($context_menu_list as $context_menu) {
	if (checkAllowUdContentGrant($user_id, $ud_content_id, (int)$context_menu['code'])) {
		//동적 메뉴에서 아카이브와 리스토어 제외 처리
		//if(!in_array((int)$context_menu['code'], array(16, 16384)))

		{
echo <<<EOD

,{
	icon: '/css/icons/'+'{$context_menu['icon_url']}',
	text: '{$context_menu['name']}',
	handler: function(btn) {
		var channel = '{$context_menu['register']}';
		var sm = Ext.getCmp('tab_warp').getActiveTab().get(0).getSelectionModel();
		var register = '{$context_menu['register']}';
		var register_sub = register.substring(0, 3);
		var rs = [];
		var _rs = sm.getSelections();
		Ext.each(_rs, function(r, i, a){
			rs.push(r.get('content_id'));
		});
		if(register_sub == 'sns'){

				Ext.Ajax.request({
				url: '/pages/sns/get_sns_content_metadata.php',
				params: {
					content_ids: Ext.encode(rs),
					ud_content_id: {$ud_content_id}
				},
				callback: function(options, success, response) {
					var res = Ext.decode(response.responseText);
					var sns_objects = [];
					if(res.success) {
						var win = new Ext.Window({
							layout:'fit',
							title:_text('MN02381'),
							modal: true,
							width:500,
							height:200,
							//autoHeight:true,
							autoScroll:true,
							items:[{
								id: 'sns_message_form',
								cls: 'change_background_panel',
								autoScroll: true,
								xtype:'form',
								border: false,
								//frame: true,
								padding: 5,
								labelWidth: 70,
								defaults: {
									anchor: '100%'
								},
								items: [],
								listeners:{
									render: function(self){
										var content_data = res.data;
										for(var i=0; i<content_data.length;i++){
											var sns_title = content_data[i].title;
											var sns_message ='';
											for(var j=0; j<content_data[i].sns_message.length;j++){
												sns_message += content_data[i].sns_message[j]+'\\n';
											}
											sns_message = sns_message.substr(0, sns_message.length-1);
											self.add(
												{
													layout:'form',
													defaults: {
														anchor: '100%'
													},
													padding: '5px 5px 0px 5px',
													items: [
														{
															id:'sns_message_title_'+i,
															xtype: 'textfield',
															height: 20,
															fieldLabel:_text('MN00249'),
															value: sns_title
														},{
															id:'sns_message_text_'+i,
															xtype: 'textarea',
															height: 50,
															fieldLabel:_text('MN02381'),
															//allowBlank: false,
															msgTarget: 'under',
															value: sns_message
														}
													]
												},{
													xtype: 'displayfield',
													height: 1
												}
											);
										}
									}
								},
								buttonAlign: 'center',
								buttons:[{
									text : '<span style="position:relative;top:1px;"><i class="fa fa-paper-plane-o" style="font-size:13px;"></i></span>&nbsp;'+_text('MN02197'),
									scale: 'medium',
									handler: function(btn,e){
										var rm = Ext.getCmp('tab_warp').getActiveTab().get(0).getSelectionModel().getSelections();
										var number_selected_content = rm.length;
										for(var i=0; i<number_selected_content;i++){
											sns_object = new Object();
											var title = Ext.getCmp('sns_message_title_'+i).getValue();
											var content = Ext.getCmp('sns_message_text_'+i).getValue();
											sns_object.id = rm[i].id;
											sns_object.title = title;
											sns_object.content = content;
											sns_objects.push(sns_object);
										}
										win.destroy();
										var context_mask = new Ext.LoadMask(Ext.getBody(), '');
										context_mask.show();

										Ext.Ajax.request({
											url: '/store/start_task_context_menu.php',
											params: {
												//content_ids: Ext.encode(rs),
												channel: channel,
												sns: JSON.stringify(sns_objects)
											},
											callback: function(options, success, response) {
												context_mask.hide();
												var res = Ext.decode(response.responseText);
												if(res.success) {
													Ext.Msg.alert('Success', res.msg);
												} else {
													Ext.Msg.alert('Error', res.msg);
												}
											}
										});

									}
								},{
									text : '<span style="position:relative;top:1px;"><i class="fa fa-close" style="font-size:13px;"></i></span>&nbsp;'+_text('MN00004'),
									scale: 'medium',
									handler: function(btn,e){
										win.destroy();
									}
								}]

							}],
						listeners:{
							afterrender: function(self){
								if(res.data.length == 2){
									self.setHeight(300);
								}else if(res.data.length >2){
									self.setHeight(400);
								}
							}
						}
						});
						win.show();
					} else {
						Ext.Msg.alert('Error', res.msg);
					}

				}
			});
		}else{
			var context_mask = new Ext.LoadMask(Ext.getBody(), '');
			context_mask.show();
			Ext.Ajax.request({
				url: '/store/start_task_context_menu.php',
				params: {
					content_ids: Ext.encode(rs),
					channel: channel
				},
				callback: function(options, success, response) {
					context_mask.hide();
					var res = Ext.decode(response.responseText);
					if(res.success) {
						Ext.Msg.alert('Success', res.msg);
					} else {
						Ext.Msg.alert('Error', res.msg);
					}
				}
			});
		}
	}
}
EOD;
		}
	}
}
?>
/* 플레이리스트추가 */
<?php
if ($arr_sys_code['cuesheet_use_yn']['use_yn'] == 'Y' && checkAllowUdContentGrant($user_id, $ud_content_id, GRANT_CUESHEET)) {
?>
,{
	text: _text('MN02474'),
	icon: '/led-icons/doc_film.png',
	handler: function(b,e) {
		var sm = Ext.getCmp('tab_warp').getActiveTab().get(0).getSelectionModel();
		var records = sm.getSelections();
		var contents=[];

		Ext.each(records, function(r){
			contents.push({
				id: r.get('content_id'),
				title: r.get('title')
			});
		});

		var cuesheet_lists = Ext.getCmp('cuesheet_list').getSelectionModel();
		if (cuesheet_lists.hasSelection()) {
			var cuesheet_id = cuesheet_lists.getSelected().get('cuesheet_id');
			Ext.Ajax.request({
				url: '/store/cuesheet/cuesheet_action.php',
				params: {
					action: 'add-items',
					cuesheet_id: cuesheet_id,
					contents: Ext.encode(contents)
				},
				callback: function (self, success, response) {
					if ( success ) {
						try {
							var result = Ext.decode(response.responseText);
							var record = Ext.getCmp('cuesheet_list').getSelectionModel().getSelected();
							if (record) {
								record.set('modified_date', Date.parseDate(result.modified_date, 'YmdHis'));
								record.commit();
							}
							Ext.getCmp('cuesheet_items').getStore().reload();
						}
						catch ( e ) {
							Ext.Msg.alert(e['name'], e['message']);
						}
					} else {
						Ext.Msg.alert(_text('MN01098'), response.statusText + '(' + response.status + ')');
					}
				}
			});
		} else {
			Ext.Msg.alert(_text('MN00023'), _text('MN02475'));
		}
	}
}
<?php
}?>
<?php 
// 미디어 콘텐츠 심의 요청 매뉴
if (true){
?>
,{
	text:'심의 요청',
    //itemId: 'reviewRequest',
	handler: function(self,e){
		var sm = Ext.getCmp('tab_warp').getActiveTab().get(0).getSelectionModel();	
		var _rs = sm.getSelections();
      

			Ext.Ajax.request({
				url: '/javascript/ext.ux/BatchEditMetaPanel/review.js',
				callback: function(option, success, response){
					if(success){
      
					var win = Ext.decode(response.responseText);
						win.selectedTitle=_rs[0].get('title');
						win.selectedId=_rs[0].get('content_id');
                    win.show();
                 
                   
					}else{
						Ext.Msg.alert(_text('MN00022'), response.statusText+'('+response.status+')');
					}     
				}
			})
	}
}
<?php
}
?>


<?php 
if (checkAllowUdContentGrant($user_id, $ud_content_id, GRANT_EDIT)):
?>
,{
    hidden:true,
	text: _text('MN02479'),
	id: 'batch_edit_menu_item',
	icon: '/led-icons/page_2_copy.png',
	handler: function(b,e) {
		var sm = Ext.getCmp('tab_warp').getActiveTab().get(0).getSelectionModel();
		var rs = [];
		var _rs = sm.getSelections();
		Ext.each(_rs, function(r, i, a){
			rs.push(r.get('content_id'));
		});
		Ext.Ajax.request({
			url: '/javascript/ext.ux/Ariel.BatchEditMetaWindow.php',
			params: {
				bs_content_id : '<?=$bs_content_id?>',
				ud_content_id : '<?=$ud_content_id?>',
				content_ids: Ext.encode(rs)
			},
			callback: function(option,success,response){
				if(success){
					var result = Ext.decode(response.responseText);
				}
				else{
					Ext.Msg.alert(_text('MN00022'), response.statusText+'('+response.status+')');
				}
			}
		});
	}
}
<?php endif ?>


<?php
//권한 적용되기 이전에 임시로 적용 - 2017.12.30 Alex

//if(!in_array(SC_GROUP, $_SESSION['user']['groups']) ) {
	if(defined('CUSTOM_ROOT') && class_exists('\ProximaCustom\core\ContentAction')) {
		\ProximaCustom\core\ContentAction::renderContextMenuItems($user_id, $ud_content_id);					
	}
//}
?>
]
