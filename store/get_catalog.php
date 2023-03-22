<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');

    $user_id = $_SESSION['user']['user_id'];
	$content_id = $_POST['content_id'];
    $content = $db->queryRow("select c.ud_content_id, c.title, c.bs_content_id, c.is_group, m.ud_content_title as meta_type_name, c.reg_user_id from bc_content c, bc_ud_content m where c.content_id={$content_id} and c.ud_content_id=m.ud_content_id");
    $ud_content_id = $content['ud_content_id'];
	if ( $content['bs_content_id'] == MOVIE ) {
		$pfr_err_msg = '';
		$ori_media = $db->queryRow("select * from bc_media where content_id='".$content_id."' and media_type='original'");
		$ori_media['delete_date'] = trim($ori_media['delete_date']);
		$path_array = explode('.', $ori_media['path']);
		$ori_ext = array_pop($path_array);
		if(strtoupper($ori_ext) == 'MOV') {
		} else if (strtoupper($ori_ext) == 'MXF') {
			$arr_sys_meta = $db->queryRow("select * from bc_sysmeta_movie where sys_content_id='".$content_id."'");
			if( strstr($arr_sys_meta['sys_video_codec'], 'mpeg2video') ) {
				//MXF는 이 경우에만 가능
			} else {
				//$pfr_err_msg = "MXF영상은 mpeg2video인 경우에 가능합니다.";
				$pfr_err_msg = _text('MSG02514');
			}
		} else {
			//$pfr_err_msg = "MXF, MOV 영상만 구간추출이 가능합니다.";
			$pfr_err_msg = _text('MSG02513');
		}

		//원본 존재 여부
		if( (empty($ori_media['status'])) && empty($ori_media['delete_date']) &&  $ori_media['filesize'] > 0){
			$flag_ori = 'Y';
		} else {
			$flag_ori = 'N';
		}
	}

	$check_flashnet = $arr_sys_code['interwork_flashnet']['use_yn'];

	$frame_rate = $db->queryOne("select sys_frame_rate from bc_sysmeta_movie where sys_content_id = '".$content_id."'");
	$frame_rate = floatval($frame_rate);

	if (!$frame_rate){
		$frame_rate = FRAMERATE;
	}
?>

(function(){

return build();

	function build(){
		var scene_type_list =[{'scene_type' : 'S' }];
		var store_images = new Ext.data.JsonStore({
			autoLoad: true,
			url: '/store/catalog/get.php',
			baseParams: {
				content_id: <?=$content_id?>,
				scene_type_list: Ext.encode(scene_type_list)
			},
			root: 'data',
			fields: [
				'scene_id', 
				'url', 
				'sort', 
				'timecode', 
				'comments', 
				'current_frame', 
				'media_id',
				'scene_type',
				'is_poster',
				'title',
				//'peoples',
				'content',
				'start_frame',
				'end_frame',
				'end_tc',
				'story_board_id', 
				'is_sub_story_board', 
				'time_code_start_sec', 
				'time_code_end_sec',
				'xml_path',
				'truepeak',
				'momentary',
				'loudnessrange',
				'integrate',
				'shortterm',

				]
		});

		var dataview_images = new Ext.DataView({
		title: _text('MN00389'),
		id: 'images-view',
		cls: 'cata_log_images_view',
		store: store_images,
		tpl : '',
		setTemplate: function(template){
			this.tpl = template;
			this.refresh();
		},
		autoScroll: true,
		overClass: 'x-view-over',
		selectedClass: 'x-view-selected',
		itemSelector: 'div.thumb-wrap',
		padding: 5,
		//height: 670,
		//autoHeight: true,
		emptyText: _text('MSG00207'),
		contextmenu: true,
		multiSelect: true,

		listeners: {
			afterrender: function(self){
				var element = self.getEl();				
				
				element.on('click', function(event, elem,a){
					if((!Ext.isIE && event.target.classList.contains('expand-collapse-image')) || (Ext.isIE &&(event.target.className == 'expand-collapse-image cursor-class fa fa-fw fa-lg fa-minus' || event.target.className == 'expand-collapse-image cursor-class fa fa-fw fa-lg fa-plus'))){
						var t = Ext.get(elem);
						var list_image_cmp = t.parent().next('.template_content');
						var height = list_image_cmp.getHeight();

						if (height < 0){
							t.addClass('fa-minus');
							t.set({'title': _text('MN02303')});
							t.removeClass('fa-plus');
						} else if (height > 0){
							list_image_cmp.setHeight(0);
							t.addClass('fa-plus');
							t.set({'title': _text('MN02304')});
							t.removeClass('fa-minus');
						} else if (height == 0 && t.hasClass('fa-plus')) {
							list_image_cmp.setHeight('auto');
							t.addClass('fa-minus');
							t.set({'title': _text('MN02303')});
							t.removeClass('fa-plus');
						}
					}
					if ((!Ext.isIE && event.target.classList.contains('expand-collapse-title')) || (Ext.isIE &&(event.target.className == 'expand-collapse-title cursor-class' || event.target.className == 'cursor-class'))){
						var el = Ext.get(elem);
						var list_image_cmp = el.parent().next('.template_content');
						var height = list_image_cmp.getHeight();
						var t = el.prev();

						if (height < 0){
							t.addClass('fa-minus');
							t.set({'title': _text('MN02303')});
							t.removeClass('fa-plus');
						} else if (height > 0){
							list_image_cmp.setHeight(0);
							t.addClass('fa-plus');
							t.set({'title': _text('MN02304')});
							t.removeClass('fa-minus');
						} else if (height == 0 && t.hasClass('fa-plus')) {
							list_image_cmp.setHeight('auto');
							t.addClass('fa-minus');
							t.set({'title': _text('MN02303')});
							t.removeClass('fa-plus');
						}
					}
					if (event.target.classList.contains('total_story_board_text')) {
						if (element.getHeight() < parseFloat(element.dom.scrollHeight)) {
							element.scroll('bottom',element.dom.children[1].scrollHeight);
						}
					}
				});
           		
			},
			click: function(self, index, node, e){
				Ext.get('images-view').select('.thumb-wrap-disable').removeClass('sb-view-selected');
				if(e.ctrlKey === true){
					if (self.isSelected(index)){
						self.deselect( index, e );
					} else {
						self.select( index, true, e );
					}
					var images_view = Ext.getCmp('images-view');
					var length = images_view.selected.elements.length;
					var selected_items = [];
					for (i=0; i< length; i++){
						var index1 = images_view.selected.elements[i].viewIndex;
						selected_items.push(index1);
					}
					for (j=0; j< selected_items.length; j++){
						self.deselect(selected_items[j],e);
					}
					return false;
				} else {
					var frames = self.getRecord(node).get('current_frame');
					var sec = frames / <?=$frame_rate?>;

					var player3 = videojs(document.getElementById('player3'), {}, function(){
					});
					player3.currentTime(sec);
				}
			},
			dbclick: function(self, idx, n, e){

			},
			contextmenu: function(self, index, node, dataViewEvent){
				dataViewEvent.stopEvent();
				var images_view = Ext.getCmp('images-view');
				var length = images_view.selected.elements.length;
				var single_selected = true;
				if (length >= 2){
					single_selected = false;
				}
				//alert(single_selected);
				var menu = new Ext.menu.Menu({
					items: [{
						text: '새로고침',
						handler: function(btn, e){
                            var images_view = Ext.getCmp('images-view');
                            images_view.store.reload();
						}
					},{
					
						//!!text: '대표이미지로 지정',
						text: _text('MN00162'),
						disabled: !single_selected,
						handler: function(btn, e){
							Ext.Msg.show({
								icon: Ext.Msg.QUESTION,
								title: _text('MN00023'),
								//!!msg: '대표이미지로 지정 하시겠습니까?',
								msg: _text('MSG00208'),
								buttons: Ext.Msg.YESNO,
								fn: function(btnId, text, opts){
									if(btnId == 'no') return;

									var r = self.getRecord(node);
									self.sendAction('change', r, self);
								}
							});
						}
					},{
					
						text: _text('MN02293'),
				                        disabled: single_selected,
                        				hidden: false,
						handler: function(btn, e){
							var images_view = Ext.getCmp('images-view');
							var length = images_view.selected.elements.length;

							if (length < 2) {
								Ext.Msg.alert(_text('MN01039'), _text('MSG02111'));
								return;
							}
							var setInFrame, setOutFrame;
							var setInTC = Ext.getCmp('images-view').selected.elements[0].viewIndex;
							var setOutTC = Ext.getCmp('images-view').selected.elements[length-1].viewIndex;
							setInFrame = parseInt(images_view.store.getAt(setInTC).get('current_frame'));
							setOutFrame = parseInt(images_view.store.getAt(setOutTC).get('current_frame'));
							if (setInFrame > setOutFrame){
								var temp = setOutFrame;
								setOutFrame = setInFrame;
								setInFrame = temp;
							}

							var media_id = Ext.getCmp('images-view').store.getAt(setInTC).get('media_id');
							var sm = Ext.getCmp('tab_warp').getActiveTab().get(0).getSelectionModel();
							var title = sm.getSelected().get('title');

							new Ext.Window({
								layout: 'fit',
								height: 200,
								width: 600,
								modal: true,
								title: _text('MN02293'),
								buttonAlign: 'center',
								items: [{
									xtype: 'form',
									border: false,
									padding: 5,
									labelWidth: 50,
									cls: 'change_background_panel',
									items: [{
										xtype: 'textfield',
										anchor: '100%',
										fieldLabel: _text('MN00249'),//'제목'
										name: 'title',
										value: title
									},{
										xtype     : 'textarea',
										anchor: '100%',
										fieldLabel: _text('MN02311'),//'제목'
										name: 'content'
									},{
										xtype: 'textfield',
										anchor: '100%',
										hidden: true,
										fieldLabel: _text('MN02312'),//'제목'
										name: 'peoples'
									}]

								}],

								buttons: [{
									text: '<span style=\"position:relative;top:1px;\"><i class=\"fa fa-plus\" style=\"font-size:13px;\"></i></span>&nbsp;'+_text('MN00033'),
									scale: 'medium',
									handler: function(btn) {
										var win = btn.ownerCt.ownerCt;
										var form = win.get(0).getForm();
										if (form.getValues().title.trim() == '') {
											Ext.Msg.alert(_text('MN01039'), _text('MSG00090'));
											return;
										}
										var wait_msg = Ext.Msg.wait( _text('MSG02036'), _text('MN00066'));//('등록중입니다.', '요청');
										Ext.Ajax.request({
											url: '/store/catalog/add.php',
											params: {
												action: 'add_sub_story_board',
												media_id: media_id,
												title: form.getValues().title,
												content: form.getValues().content,
												//peoples: form.getValues().peoples,
												//vr_meta: Ext.encode(values),
												start_frame: setInFrame,
												end_frame: setOutFrame,
												content_id: <?=$content_id?>
											},
											callback: function(opts, success, response){
												wait_msg.hide();
												if (success) {
													try {
														var r = Ext.decode(response.responseText);
														if (r.success) {
															win.close();
															var images_view = Ext.getCmp('images-view');
															images_view.store.reload();
														} else {
															Ext.Msg.alert( _text('MN00003'), r.msg);//'확인'
														}
													} catch(e) {
														Ext.Msg.alert( _text('MN01039'), response.responseText);//'오류'
													}
												} else {
													Ext.Msg.alert( _text('MN01098'), response.statusText);//'서버 오류'
												}
											}
										});
									}
								},{
									text: '<span style=\"position:relative;top:1px;\"><i class=\"fa fa-close\" style=\"font-size:13px;\"></i></span>&nbsp;'+_text('MN00004'),
									scale: 'medium',
									handler: function(btn) {
										btn.ownerCt.ownerCt.close();
									}
								}]
							}).show();

						}
					},{
						
						text: _text('MN02140'),
						//disabled: single_selected,
                        <?php if(!checkAllowUdContentGrant($user_id, $ud_content_id, GRANT_PFR) || $arr_sys_code['pfr_use_yn']['use_yn'] != 'Y'){?>
                        hidden: true,	
                        <?php }?>
						handler: function(btn, e){
							if('<?=$pfr_err_msg?>' != '') {
								Ext.Msg.alert( _text('MN00023'), '<?=$pfr_err_msg?>');//알림
								return;
							}
							var images_view = Ext.getCmp('images-view');
							var length = images_view.selected.elements.length;
							var setInSec, setOutSec, setInTC, setOutTC;

							if (length < 2) {
								var images_view_el = Ext.get('images-view');
								var images_view_el_length = images_view_el.select('.sb-view-selected').elements.length;
								if (images_view_el_length == 0){
									Ext.Msg.alert(_text('MN01039'), _text('MSG02111'));
									return;
								} else {
									setInSec = parseInt(images_view_el.select('.sb-view-selected').elements[0].getAttribute('current_frame') / 30);
									setOutSec = parseInt(images_view_el.select('.sb-view-selected').elements[images_view_el_length-1].getAttribute('current_frame') / 30);
								}
							} else {
								setInTC = Ext.getCmp('images-view').selected.elements[0].viewIndex;
								setOutTC = Ext.getCmp('images-view').selected.elements[length-1].viewIndex;
								setInSec = parseInt(images_view.store.getAt(setInTC).get('current_frame') / 30);
								setOutSec = parseInt(images_view.store.getAt(setOutTC).get('current_frame') / 30);
							}
							if (setInSec > setOutSec){
								var temp = setOutSec;
								setOutSec = setInSec;
								setInSec = temp;
							}

							var sm = Ext.getCmp('tab_warp').getActiveTab().get(0).getSelectionModel();
							var title = sm.getSelected().get('title');

							if ('<?=$flag_ori?>' == 'Y' ){
								//alert(\"Test\");
								new Ext.Window({
									layout: 'fit',
									height: 120,
									width: 600,
									modal: true,
									title: _text('MN02140'),
									buttonAlign: 'center',
									items: [{
										xtype: 'form',
										border: false,
										cls: 'change_background_panel',
										padding: 5,
										labelWidth: 50,

										items: [{
											xtype: 'textfield',
											anchor: '100%',
											fieldLabel: _text('MN00249'),//'제목'
											name: 'title',
											value: title
										}]

									}],

									buttons: [{
										text: '<span style=\"position:relative;top:1px;\"><i class=\"fa fa-plus\" style=\"font-size:13px;\"></i></span>&nbsp;'+_text('MN00033'),
										scale: 'medium',
										handler: function(btn) {
											var win = btn.ownerCt.ownerCt;
											var form = win.get(0).getForm();
											if (form.getValues().title.trim() == '') {
												Ext.Msg.alert(_text('MN01039'), _text('MSG00090'));
												return;
											}
											var wait_msg = Ext.Msg.wait( _text('MSG02036'), _text('MN00066'));//('등록중입니다.', '요청');
											Ext.Ajax.request({
												url: '/store/create_new_content.php',
												params: {
													content_id: <?=$content_id?>,
													title: form.getValues().title,
													//vr_meta: Ext.encode(values),
													start: setInSec,
													end: setOutSec
												},
												callback: function(opts, success, response){
													wait_msg.hide();
													if (success) {
														try {
															var r = Ext.decode(response.responseText);
															if (r.success) {
																win.close();
																Ext.Msg.show({
																	title: _text('MN00003'),//'확인'
																	msg: _text('MSG02037')+'</br>'+_text('MSG00190'),//'등록되었습니다.<br />창을 닫으시겠습니까?'
																	icon: Ext.Msg.QUESTION,
																	buttons: Ext.Msg.OKCANCEL,
																	fn: function(btnId){
																		if (btnId == 'ok') {
																			Ext.getCmp('winDetail').close();
																		}
																	}
																});
															} else {
																Ext.Msg.alert( _text('MN00003'), r.msg);//'확인'
															}
														} catch(e) {
															Ext.Msg.alert( _text('MN01039'), response.responseText);//'오류'
														}
													} else {
														Ext.Msg.alert( _text('MN01098'), response.statusText);//'서버 오류'
													}
												}
											});
										}
									},{
										text: '<span style=\"position:relative;top:1px;\"><i class=\"fa fa-close\" style=\"font-size:13px;\"></i></span>&nbsp;'+_text('MN00004'),
										scale: 'medium',
										handler: function(btn) {
											btn.ownerCt.ownerCt.close();
										}
									}]
								}).show();
								return;
							} else if ( '<?=$flag_ori?>' == 'N' ){
								if( '<?=ARCHIVE_USE_YN?>' == 'N' ){
									Ext.Msg.alert(_text('MN00023'), _text('MSG00031'));//원본 파일이 없습니다
								}else if ('<?=$check_flashnet?>' == 'Y' ){
									Ext.Msg.show({
										title: _text('MN00024'),//MN00024 '확인',
										msg: _text('MSG01043'),//MSG01043 'PFR작업을 요청합니다.';
										icon: Ext.Msg.QUESTION,
										buttons: Ext.Msg.OKCANCEL,
										fn: function(btnId){
											if (btnId == 'ok')
											{
												var pfr_list = [];
												pfr_list.push({
													'in': setInSec,
													'out': setOutSec,
													'tc_in': setInTC,
													'tc_out': setOutTC
												});
												var wait_msg = Ext.Msg.wait( _text('MSG02036'), _text('MN00066'));//('등록중입니다.', '요청');
												Ext.Ajax.request({
													url: '/store/tc_edit.php',
													params: {
														mode: 'pfr_request',
														content_id: <?=$content_id?>,
														pfr_list: Ext.encode(pfr_list)
													},
													callback: function(opt, success, res) {
														wait_msg.hide();
														var r = Ext.decode(res.responseText);
														if(!r.success) {
															Ext.Msg.alert(_text('MN00023'),r.msg);
														} else {
															Ext.Msg.alert(_text('MN00023'), _text('MN01021') + ' ' + _text('MSG01009'));
														}
													}
												});
											}
										}
									});
								} else if( '<?=$arr_sys_code['interwork_oda_ods_l']['use_yn']?>' == 'Y'){
									var win = new Ext.Window({
										layout:'fit',
										title: _text('MN02423'),
										modal: true,
										width:500,
										height:190,
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
													xtype: 'textfield',
													id:'pfr_new_title',
													//anchor: '100%',
													allowBlank: false,
													fieldLabel: _text('MN00249'),//'제목'
													name: 'title',
													value: title
												},{
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
												var isValid_new_title = Ext.getCmp('pfr_new_title').isValid();
												if ( ! isValid || !isValid_new_title) {
													Ext.Msg.show({
														icon: Ext.Msg.INFO,
														title: _text('MN00024'),//확인
														msg: '<?=_text('MSG02183')?>',
														buttons: Ext.Msg.OK
													});
													return;
												}

												var request_reason = Ext.getCmp('request_reason').getValue();
												var pfr_new_title = Ext.getCmp('pfr_new_title').getValue();
												
												var request_reason = Ext.getCmp('request_reason').getValue();
												var pfr_new_title = Ext.getCmp('pfr_new_title').getValue();

												var wait_msg = Ext.Msg.wait( _text('MSG02036'), _text('MN00066'));
												var rs = [];
												rs.push(<?=$content_id?>);

												Ext.Ajax.request({
													<?php if( $arr_sys_code['interwork_archive_confirm']['use_yn'] == 'Y'){ ?>
														url: '/store/archive/insert_archive_request.php',
														params: {
															case_management: 'request',
													<?php }else{?>
														url: '/store/archive/insert_archive_request_not_confirm.php',
														params: {	
													<?php }?>
														job_type: 'pfr',
														new_title: pfr_new_title,
														comment: request_reason,
														contents: Ext.encode(rs),
														ud_content_id : '<?=$ud_content_id?>',
														start: setInSec,
														end: setOutSec
													},
													callback: function(opt, success, res) {
														wait_msg.hide();
														var r = Ext.decode(res.responseText);
														if(!r.success) {
															Ext.Msg.alert(_text('MN00023'),r.msg);
														} else {
															//Ext.Msg.alert(_text('MN00023'), _text('MN01021') + ' ' + _text('MSG01009'));
														}
													}
												});
												/*
												Ext.Msg.show({
													title: _text('MN00024'),//MN00024 '확인',
													msg: _text('MSG01043'),//MSG01043 'PFR작업을 요청합니다.';
													icon: Ext.Msg.QUESTION,
													buttons: Ext.Msg.OKCANCEL,
													fn: function(btnId){
														if (btnId == 'ok'){
															////('등록중입니다.', '요청');
															var wait_msg = Ext.Msg.wait( _text('MSG02036'), _text('MN00066'));
															var rs = [];
															rs.push(<?=$content_id?>);

															Ext.Ajax.request({
																<?php if( $arr_sys_code['interwork_archive_confirm']['use_yn'] == 'Y'){ ?>
																	url: '/store/archive/insert_archive_request.php',
																	params: {
																		case_management: 'request',
																<?php }else{?>
																	url: '/store/archive/insert_archive_request_not_confirm.php',
																	params: {	
																<?php }?>
																	job_type: 'pfr',
																	new_title: pfr_new_title,
																	comment: request_reason,
																	contents: Ext.encode(rs),
																	ud_content_id : '<?=$ud_content_id?>',
																	start: setInSec,
																	end: setOutSec
																},
																callback: function(opt, success, res) {
																	wait_msg.hide();
																	var r = Ext.decode(res.responseText);
																	if(!r.success) {
																		Ext.Msg.alert(_text('MN00023'),r.msg);
																	} else {
																		Ext.Msg.alert(_text('MN00023'), _text('MN01021') + ' ' + _text('MSG01009'));
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
											text : '<span style="position:relative;top:1px;"><i class="fa fa-close" style="font-size:13px;"></i></span>&nbsp;'+_text('MN00004'),
											scale: 'medium',
											handler: function(btn,e){
												win.destroy();
											}
										}]
									});
									win.show();
									
								} else if('<?=$arr_sys_code['interwork_oda_ods_d']['use_yn']?>' == 'Y'){
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
												var wait_msg = Ext.Msg.wait( _text('MSG02036'), _text('MN00066'));
												var rs = [];
												rs.push(<?=$content_id?>);

												Ext.Ajax.request({
													<?php if( $arr_sys_code['interwork_archive_confirm']['use_yn'] == 'Y'){ ?>
														url: '/store/archive/insert_archive_request.php',
														params: {
															case_management: 'request',
													<?php }else{?>
														url: '/store/archive/insert_archive_request_not_confirm.php',
														params: {	
													<?php }?>
														job_type: 'pfr',
														comment: request_reason,
														contents: Ext.encode(rs),
														ud_content_id : '<?=$ud_content_id?>',
														start: setInSec,
														end: setOutSec
													},
													callback: function(opt, success, res) {
														wait_msg.hide();
														var r = Ext.decode(res.responseText);
														if(!r.success) {
															Ext.Msg.alert(_text('MN00023'),r.msg);
														} else {
															//Ext.Msg.alert(_text('MN00023'), _text('MN01021') + ' ' + _text('MSG01009'));
														}
													}
												});
												/*
												Ext.Msg.show({
													title: _text('MN00024'),//MN00024 '확인',
													msg: _text('MSG01043'),//MSG01043 'PFR작업을 요청합니다.';
													icon: Ext.Msg.QUESTION,
													buttons: Ext.Msg.OKCANCEL,
													fn: function(btnId){
														if (btnId == 'ok'){
															////('등록중입니다.', '요청');
															var wait_msg = Ext.Msg.wait( _text('MSG02036'), _text('MN00066'));
															var rs = [];
															rs.push(<?=$content_id?>);

															Ext.Ajax.request({
																<?php if( $arr_sys_code['interwork_archive_confirm']['use_yn'] == 'Y'){ ?>
																	url: '/store/archive/insert_archive_request.php',
																	params: {
																		case_management: 'request',
																<?php }else{?>
																	url: '/store/archive/insert_archive_request_not_confirm.php',
																	params: {	
																<?php }?>
																	job_type: 'pfr',
																	comment: request_reason,
																	contents: Ext.encode(rs),
																	ud_content_id : '<?=$ud_content_id?>',
																	start: setInSec,
																	end: setOutSec
																},
																callback: function(opt, success, res) {
																	wait_msg.hide();
																	var r = Ext.decode(res.responseText);
																	if(!r.success) {
																		Ext.Msg.alert(_text('MN00023'),r.msg);
																	} else {
																		Ext.Msg.alert(_text('MN00023'), _text('MN01021') + ' ' + _text('MSG01009'));
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
								}
							}
						}
					},{						
						text: _text('MN02448'),
						disabled: single_selected,
						handler: function(btn, e){
							var frame_rate = <?=$frame_rate?>;
							var content_id = <?=$content_id?>;
							var images_view = Ext.getCmp('images-view');
							var length = images_view.selected.elements.length;

							if (length < 2) {
								Ext.Msg.alert(_text('MN01039'), _text('MSG02111'));
								return;
							}
							var setInFrame, setOutFrame;
							var setInTC = Ext.getCmp('images-view').selected.elements[0].viewIndex;
							var setOutTC = Ext.getCmp('images-view').selected.elements[length-1].viewIndex;
							setInFrame = parseInt(images_view.store.getAt(setInTC).get('current_frame'));
							setOutFrame = parseInt(images_view.store.getAt(setOutTC).get('current_frame'));
							if (setInFrame > setOutFrame){
								var temp = setOutFrame;
								setOutFrame = setInFrame;
								setInFrame = temp;
							}

							<?php
								if( $arr_sys_code['shot_list_yn']['use_yn'] == 'Y' ){
									if($is_group == 'G') {
										$save_type = " save_type = 'group-add'; ";
									}
									echo "
												var save_type = 'add';
												".$save_type."
												var start_sec = setInFrame /frame_rate;
												var end_sec = setOutFrame /frame_rate;
												var grid = Ext.getCmp('timeline-grid');
												if(grid.id =='timeline-grid' ){
													Ext.getCmp('player_warp').insertTimeLine( grid, start_sec, end_sec ,false);
												}else if( grid.id =='preview-edit-grid' ){
													Ext.getCmp('player_warp').insertTimeLine( grid, start_sec, end_sec , true );
												}
												grid.isDirty = true;
												var datas = [];
												var datalist = grid.getStore().getRange();
												Ext.each(datalist,function(r){
													datas.push(r.data);
												});
												Ext.getCmp('player_warp').Save(grid, content_id, 'shot_list', save_type, datas , true ,true );
									";
								}
							?>
						}
					}

					/*,{
						//!!text: '내용 수정',
						icon: '/led-icons/page_white_edit.png',
						text: _text('MN00157'),
						handler: function(b, e){
							var r = self.getRecord(node);

							self.buildEditBox( r.get('scene_id'), r.get('comments') );

						}
					},{
						//!!text: '다운로드',
						icon: '/led-icons/download_sicon.jpg',
						text: _text('MN00050'),
						handler: function(b, e){
							var r = self.getRecord(node);
							self.downloadImage( r.get('scene_id') );

						}
					}*/]
				});

				menu.showAt(dataViewEvent.getXY());
			}
		},

		downloadImage : function(scene_id){
			window.open('/store/download.php?content_id='+<?=$_POST['content_id']?>+'&&scene_id='+scene_id+'&&page_loc=catalog');
		},

		buildEditBox: function(scene_id, comments){
			var that = this;

			comments = comments.replace(/<br \/>/g, "\r\n");

			var w = new Ext.Window({
				title: _text('MN00067'),
				width: 350,
				height: 230,
				modal: true,
				layout: 'fit',

				items: [{
					xtype: 'textarea',
					value: comments
				}],

				listeners: {
					beforeclose: function(self){
/*
						Ext.Msg.show({
							title
						})
*/
					}
				},

				buttons: [{
					text: _text('MN00043'),
					handler: function(b, e){
						Ext.Msg.show({
							title: _text('MN01036'),
							//!!msg: '수정하신 내용을 저장하시겠습니까?',
							msg: _text('MSG00175'),
							buttons: Ext.Msg.OKCANCEL,
							fn: function(btnId){
								if (btnId == 'ok')
								{
									that.doEdit( scene_id, b.ownerCt.ownerCt.get(0).getValue(), b.ownerCt.ownerCt );
								}
							}
						})
					}
				},{
					text: _text('MN00004'),
					handler: function(b, e){
						b.ownerCt.ownerCt.close();
					}
				}]
			}).show();
		},

		doEdit: function(scene_id, comments, w){
			var that = this;

			Ext.Ajax.request({
				url: '/store/catalog/edit.php',
				params: {
					action: 'edit_comment',
					scene_id: scene_id,
					comments: comments
				},
				callback: function(opts, success, response){
					if(success){
						try{
							var r = Ext.decode(response.responseText);
							if(!r.success){
								Ext.Msg.alert(_text('MN00022'), r.msg);
								return;
							}

							that.store.reload();

							w.close();
						}
						catch(e){
							Ext.Msg.alert(_text('MN00022'), e);
						}
					}else{
						Ext.Msg.alert(_text('MN00022'), response.statusText);
					}

				}
			})
		},

		sendAction: function(action, record, self){
			Ext.Ajax.request({
				url: '/store/change_thumb.php',
				params: {
					<?php if($content['is_group'] == 'G'){ ?>
						is_group: 'Y',
						content_id: <?=$_POST['content_id']?>, 
					<?php } ?>
					action: action,
					scene_id: record.get('scene_id')
				},
				callback: function(opts, success, response){
					if(success){
						try{
							var r = Ext.decode(response.responseText);
							if(!r.success){
								Ext.Msg.alert(_text('MN00022'), r.msg);
								return;
							} else{
								var images_view = Ext.getCmp('images-view');
								images_view.store.reload();
							}
						}
						catch(e){
							Ext.Msg.alert(_text('MN00022'), e);
						}
					}else{
						Ext.Msg.alert(_text('MN00022'), response.statusText);
					}

				}
			})
		}
	});

		store_images.on( 'load', function( store, records, options ) {
			var duration = store_images.reader.jsonData.duration;
			var total_images = store_images.reader.jsonData.total_images;
			var total_story_board = store_images.reader.jsonData.total_story_board;

			var tpl = new Ext.XTemplate(
				'<div class="template_header" style="padding: 5px 0px 5px 0px;"><span class="expand-collapse-image cursor-class fa fa-fw fa-lg fa-minus" title="'+_text('MN02303')+'"></span>',
				'<span title="'+_text("MN02292")+'" class="expand-collapse-title cursor-class"> '+_text('MN02292')+'</span>',
				'<span title="'+total_story_board+' '+_text("MN00269")+'" class="total_story_board_text cursor-class" > ('+total_story_board+' '+_text('MN00269')+') </span></div>',
				'<div class="template_content" style="overflow-y:auto; width: 100%;">',
				'<tpl for=".">',			
					'<tpl if="!this.isEmpty(title) && !this.isEmpty(story_board_id)">',
							'</div>',
						'</div>',
						'<div class="template_container line_separator">',
							'<div style="margin-left: 12px;" class="template_header text_ellipsis"><span class="expand-collapse-image cursor-class fa fa-fw fa-lg fa-plus" style="color:aliceblue" title="'+_text('MN02304')+'" ></span>',
								'<span class="expand-collapse-title cursor-class" style="color:aliceblue" title="'+_text('MN00249')+': {title}&#013;'+_text('MN02311')+': {content}" onclick="sub_story_board_selection(this);">[{time_code_start_sec} ~ {time_code_end_sec}] {title}</span>',
							'</div>',
								'<i class="fa fa-fw fa-lg fa-trash cursor-class" title="'+_text('MN02302')+'" style="float:right;margin: -17px 5px 0px 0px;color:antiquewhite;" onclick="delete_sub_story_board({story_board_id});"></i>',
								'<i class="fa fa-fw fa-lg fa-edit cursor-class" title="'+_text('MN02301')+'" style="float:right;margin: -16px 26px 0px 0px;color:antiquewhite;" sb_content="{content}" sb_title="{title}" sb_id="{story_board_id}" onclick="return edit_sub_story_board(this);"></i>',
								//'<img class="drag_sub_story_board" src="/led-icons/download_bicon.png" start_frame="{start_frame}" end_frame="{end_frame}" xml_path="{xml_path}" width="17px" title="<?=_text('MSG02029')?>"  />',
							
						'<div class="template_content" style="overflow-y:hidden; height:0px; width: 100%;">',
					'</tpl>',
					'<tpl if="!this.isEmpty(title) && this.isEmpty(story_board_id)">',
						'<div class="template_container" style="margin-top: 10px;">',
							'<div class="template_header" style="margin-top: 10px;"><span class="expand-collapse-image fa fa-fw fa-lg fa-minus cursor-class" title="'+_text('MN02303')+'" ></span>',
							'<span>'+_text('MN02292')+'</span></div>',
							'<div class="template_content" style="overflow-y:hidden; width: 100%;">',
					'</tpl>',
					'<tpl if="this.isEmpty(title) && this.isEmpty(story_board_id) && this.isEmpty(is_sub_story_board)">',
						'<tpl if="(scene_type==\'S\')">',
						'<div style="display: inline-block;">',
						'</tpl>',
						'<tpl if="(scene_type==\'Q\')">',
						'<div style="width: 100%; display: inline-block;">',
						'</tpl>',
						'<tpl if="(scene_type==\'L\')">',
						'<div style="width: 100%; display: inline-block;">',
						'</tpl>',
						'<tpl if="!this.isEmpty(comments)">',
							'<div class="thumb-wrap comments" id="s-{sort}">',
						'</tpl>',
						'<tpl if="this.isEmpty(comments)">',
							'<div class="thumb-wrap" id="s-{sort}">',
						'</tpl>',
						'<tpl if="(scene_type==\'Q\')">',
							'<tpl if="(is_poster == 1)">',
								'<i class="fa fa-square fa-stack-1x icon_square_poster" style="margin-left:30px;color:red;"></i>',
								'<i class="fa fa-picture-o fa-stack-1x fa-inverse icon_file_poster" style="margin-left:32px" title="대표 이미지"></i>',
							'</tpl>',
							'<i class="fa fa-square fa-stack-1x icon_square_error"  ></i>',
							'<strong class="fa fa-inverse fa-text  fa-stack-1x icon_text_thumb" title="'+_text('MN02290')+'&#013;Quality Type: No audio samples&#013;'+_text('MN02296')+': {timecode}&#013;'+_text('MN02297')+': {timecode}">QC</strong>',
							'<div class="thumb"><img class="thumb_img_storyboard_dragable" current_frame="{current_frame}" end_frame="{end_frame}" src="{url}" width="50" ext:qtip="{comments}"></div>',
							'<span class="x-editable">{timecode}</span></div>',
							'<div class="sb_metadata"><span class="x-editable" >'+_text('MSG02123')+'</span><br><br>',
							'<span class="x-editable" >'+_text('MN02296')+': {timecode}</span><br><br>',
							'<span class="x-editable">'+_text('MN02297')+': {end_tc}</span></div>',
						'</tpl>',
						'<tpl if="(scene_type==\'L\')">',
							'<tpl if="(is_poster == 1)">',
								'<i class="fa fa-square fa-stack-1x icon_square_poster" style="margin-left:30px;color:red;"></i>',
								'<i class="fa fa-picture-o fa-stack-1x fa-inverse icon_file_poster" style="margin-left:32px" title="대표 이미지"></i>',
							'</tpl>',
							'<i class="fa fa-align-right fa-rotate-90 fa-stack-1x icon_square_error" style="font-size:17px;"  title="'+_text('MN02291')+'&#013;'+_text('MN02252')+': {truepeak}&#013;'+_text('MN02254')+': {momentary}&#013;'+_text('MN02256')+': {loudnessrange}&#013;'+_text('MN02253')+': {integrate}&#013;'+_text('MN02255')+': {shortterm}"></i>',
							//'<i class="fa fa-check fa-stack-1x icon_text_loudness_thumb" style="padding-top: 0px;padding-left: 17.5px;" title="'+_text('MN02291')+'&#013;'+_text('MN02252')+': {truepeak}&#013;'+_text('MN02254')+': {momentary}&#013;'+_text('MN02256')+': {loudnessrange}&#013;'+_text('MN02253')+': {integrate}&#013;'+_text('MN02255')+': {shortterm}"></i>',
							'<div class="thumb"><img class="thumb_img_storyboard_dragable" current_frame="{current_frame}" end_frame="{end_frame}" src="{url}" width="50" ext:qtip="{comments}"></div>',
							'<span class="x-editable">{timecode}</span></div>',
							'<div class="sb_metadata"><span class="x-editable" >'+_text('MN02252')+': {truepeak}</span><br><br>',
							'<span class="x-editable" >'+_text('MN02254')+': {momentary}</span><br><br>',
							'<span class="x-editable">'+_text('MN02256')+': {loudnessrange}</span></div>',
							'<div class="sb_metadata">',
							'<span class="x-editable" >'+_text('MN02253')+': {integrate}</span><br><br>',
							'<span class="x-editable">'+_text('MN02255')+': {shortterm}</span></div>',
						'</tpl>',
						'<tpl if="(scene_type==\'S\')">',
							'<tpl if="(is_poster == 1)">',
								'<i class="fa fa-square fa-stack-1x icon_square_poster" style="color:red;"></i>',
								'<i class="fa fa-picture-o fa-stack-1x fa-inverse icon_file_poster" title="대표 이미지"></i>',
							'</tpl>',
							'<div class="thumb"><img class="thumb_img_storyboard_dragable" current_frame="{current_frame}" end_frame="{end_frame}" src="{url}" width="50" ext:qtip="{comments}"></div>',
							'<span class="x-editable">{timecode}</span></div>',
						'</tpl>',
						'</div>',
					'</tpl>',
					'<tpl if="this.isEmpty(title) && this.isEmpty(story_board_id) && !this.isEmpty(is_sub_story_board)">',
						'<tpl if="!this.isEmpty(comments)">',
//							'<div class="thumb-wrap-disable comments " current_frame="{current_frame} xml_path="{xml_path}" onclick="sub_story_board_selection(this);">',
							'<div class="thumb-wrap-disable comments" current_frame="{current_frame} xml_path="{xml_path}" onclick="sub_story_board_item_selection(this,{current_frame},<?=$frame_rate?>);">',
						'</tpl>',
						'<tpl if="this.isEmpty(comments)">',
							//'<div class="thumb-wrap-disable" current_frame="{current_frame}" xml_path="{xml_path}" onclick="sub_story_board_selection(this);">',
'<div class="thumb-wrap-disable" current_frame="{current_frame}" xml_path="{xml_path}" onclick="sub_story_board_item_selection(this,{current_frame},<?=$frame_rate;?>);">',
						'</tpl>',
						'<tpl if="(scene_type==\'Q\')">',
							//'<i class="fa fa-square fa-stack-1x icon_square_error"></i>',
							//'<i class="fa fa-file-audio-o fa-stack-1x fa-inverse icon_file_thumb" title="'+_text('MN02290')+'"></i>',
							'<i class="fa fa-square fa-stack-1x icon_square_error"  ></i>',
							'<strong class="fa fa-inverse fa-text  fa-stack-1x icon_text_thumb" title="'+_text('MN02290')+'">QC</strong>',
						'</tpl>',
						'<tpl if="(scene_type==\'L\')">',
							//'<i class="fa fa-square fa-stack-1x icon_square_error"></i>',
							//'<i class="fa fa-file-audio-o fa-stack-1x fa-inverse icon_file_thumb" title="'+_text('MN02291')+'"></i>',
							'<i class="fa fa-square fa-stack-1x icon_square_error"  ></i>',
							'<strong class="fa fa-inverse fa-text  fa-stack-1x icon_text_loudness_thumb" title="'+_text('MN02291')+'">LN</strong>',
						'</tpl>',
						'<tpl if="(scene_type==\'S\')">',
							//'<i class="fa fa-square fa-stack-1x icon_square"></i>',
							//'<i class="fa fa-file-audio-o fa-stack-1x fa-inverse icon_file_thumb" title="'+_text('MN02287')+'"></i>',
								'<i class="fa fa-square fa-stack-1x icon_square"  ></i>',
							'<strong class="fa fa-inverse fa-text  fa-stack-1x icon_text_thumb" title="'+_text('MN02287')+'"></strong>',
						'</tpl>',
						'<div class="thumb"><img src="{url}" width="50" ext:qtip="{comments}"></div>',
						'<span class="x-editable" style="color:aliceblue">{timecode}</span></div>',
					'</tpl>',
				'</tpl>',
				'<div class="x-clear"></div>',


				
				{
					isEmpty: function(s){
						return Ext.isEmpty(s);
					}
				}
			);
			dataview_images.setTemplate(tpl);

			if(Ext.isChrome){
				var thumb_img_drag = document.getElementsByClassName('drag_sub_story_board');
				var i;
				for (i=0; i<thumb_img_drag.length; i++){
					var host = window.location.host;
					var ori_ext = 'xml';
					var root_path = '<?=ATTACH_ROOT?>';
					var filename = thumb_img_drag[i].getAttribute('xml_path');
					//var path = 'application/'+ori_ext+':file:///' + root_path +'/' +filename;
					var path = root_path +'/'+filename;
					var edl_path = 'application/gmsdd:{"medias":["'+path+'"]}';

					if( !Ext.isEmpty(thumb_img_drag[i]) ){
						thumb_img_drag[i].addEventListener("dragstart",function (evt){
							evt.dataTransfer.setData("start_frame", evt.target.getAttribute('start_frame'));
							evt.dataTransfer.setData("end_frame", evt.target.getAttribute('end_frame'));
							evt.dataTransfer.setData("DownloadURL",edl_path);
						},false);
						thumb_img_drag[i].addEventListener("dragenter", function(e){
						}, false);
						thumb_img_drag[i].addEventListener('dragleave', function(e){
						}, false);
					}
				}

				
				var thumb_img_storyboard_dragable = document.getElementsByClassName('thumb_img_storyboard_dragable');
				var j;
				for (j=0; j<thumb_img_storyboard_dragable.length; j++){
					if( !Ext.isEmpty(thumb_img_storyboard_dragable[j]) ){
						thumb_img_storyboard_dragable[j].addEventListener("dragstart",function (evt){
							var images_view = Ext.getCmp('images-view');
							var length = images_view.selected.elements.length;
							if (length > 0){
								var setInFrame, setOutFrame;
								var setInTC = Ext.getCmp('images-view').selected.elements[0].viewIndex;
								var setOutTC = Ext.getCmp('images-view').selected.elements[length-1].viewIndex;
								setInFrame = parseInt(images_view.store.getAt(setInTC).get('current_frame'));
								setOutFrame = parseInt(images_view.store.getAt(setOutTC).get('current_frame'));
								if (setInFrame > setOutFrame){
									var temp = setOutFrame;
									setOutFrame = setInFrame;
									setInFrame = temp;
								}
								evt.dataTransfer.setData("start_frame", setInFrame);
								evt.dataTransfer.setData("end_frame", setOutFrame);
							}else{
								evt.dataTransfer.setData("start_frame", evt.target.getAttribute('current_frame'));
								evt.dataTransfer.setData("end_frame", evt.target.getAttribute('end_frame'));
							}
						},false);
					}
				}
			}
		});
		return dataview_images;
	}

})()