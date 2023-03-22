<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'] . '/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/lib/functions.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/store/metadata/buildMediaListTab.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/store/metadata/buildSystemMeta.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/store/metadata/buildMediaQualityMeta.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/store/metadata/function.php');

//add
require_once($_SERVER['DOCUMENT_ROOT'] . '/store/metadata/buildEditedListTab.php');

require_once($_SERVER['DOCUMENT_ROOT'] . '/lib/lang.php');

fn_checkAuthPermission($_SESSION);
try {
	$content_id = $_REQUEST['content_id'];
	$old_content_id = $_REQUEST['old_content_id'];
	$mode = $_REQUEST['mode'];
	$is_watch_meta = $_REQUEST['is_watch_meta'];
	$content = $db->queryRow("
						SELECT	A.*, COALESCE(BM.USER_NM, '관리자')  AS REG_USER_NM
						FROM
								(
									SELECT	C.*,
											M.UD_CONTENT_TITLE AS META_TYPE_NAME
									FROM	BC_CONTENT C,
											BC_UD_CONTENT M
									WHERE	C.CONTENT_ID = {$content_id}
									AND		C.UD_CONTENT_ID = M.UD_CONTENT_ID
								) A
								LEFT OUTER JOIN BC_MEMBER BM ON BM.USER_ID = A.REG_USER_ID
                ");
    $contentStatus = $db->queryRow("
            SELECT *
            FROM
            BC_CONTENT_STATUS  WHERE	CONTENT_ID = {$content_id}
    ");
	if ($content['approval_yn'] == 'Y') {
		$content_approval_yn = 1;
	} else {
		$content_approval_yn = 0;
	}
	$ud_content_id = $content['ud_content_id'];
	$user_id = $_SESSION['user']['user_id'];
	if (checkAllowUdContentGrant($user_id, $ud_content_id, GRANT_ACCESS_APPROVAL_CONTENT) && $arr_sys_code['approval_content_yn']['use_yn'] == 'Y') {
		$isHideApproveContentCheckbox = 0;
	} else {
		$isHideApproveContentCheckbox = 1;
	}

	$content_approval_mode = $arr_sys_code['approval_content_yn']['ref1'];
	if ($content['bs_content_id'] == MOVIE) {
		$pfr_err_msg = '';
		$ori_media = $db->queryRow("select * from bc_media where content_id='" . $content_id . "' and media_type='original'");
		$path_array = explode('.', $ori_media['path']);
		$ori_ext = array_pop($path_array);
		if (strtoupper($ori_ext) == 'MOV') { } else if (strtoupper($ori_ext) == 'MXF') {
			$arr_sys_meta = $db->queryRow("select * from bc_sysmeta_movie where sys_content_id='" . $content_id . "'");
			if (strstr($arr_sys_meta['sys_video_codec'], 'mpeg2video')) {
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
		if (empty($ori_media['status']) && empty($ori_media['delete_date']) &&  $ori_media['filesize'] > 0) {
			$flag_ori = 'Y';
		} else {
			$flag_ori = 'N';
		}
	}

	if (empty($content_id)) throw new Exception('No content_ids');

	$check_flashnet = isset($arr_sys_code['interwork_flashnet']['use_yn']) ? $arr_sys_code['interwork_flashnet']['use_yn'] : 'N';

	// loudness
	$check_loudness = isset($arr_sys_code['interwork_loudness']['use_yn']) ? $arr_sys_code['interwork_loudness']['use_yn'] : 'N';

	// check comment using
	$comment_yn = isset($arr_sys_code['comment_yn']['use_yn']) ? $arr_sys_code['comment_yn']['use_yn'] : 'N';

	$category_full_path = $content['category_full_path'];
	$category_path = ltrim($category_full_path);
	$map_categories = getCategoryMapInfo();
	$root_category_id = $map_categories[$ud_content_id]['category_id'];
	$root_category_text = $map_categories[$ud_content_id]['category_title'];
	if (empty($category_path))
		$category_path = '0';

	if ($check_flashnet == 'Y') {
		$sgl_log_panel = "
			{	
				xtype: 'grid',
				id: 'sgl_log_panel',
				border: false,
				cls: 'proxima_customize',
				stripeRows: true,
				flex : 1,
				//height: 300,
				autoScroll: true,
				split: true,
				layout: 'fit',
				title: _text('MN01099'),//SGL log
				region: 'south',
				loadMask: true,
				store: new Ext.data.JsonStore({
					autoLoad: true,
					url: '/store/get_sgl_log_data_list.php',
					root: 'data',
					fields: [
						'task_id','logkey','creation_datetime','start_datetime','complete_datetime','status','type_nm'
					],
					baseParams: {
						content_id: $content_id,
						mode: 'all'
					}
				}),
			tools: [{
				id: 'refresh',
				handler: function(e, toolEl, p, tc){
					p.store.reload();
				}
			}],
			listeners: {
				render: function(self){
					self.store.load();
				},
				rowdblclick: function(self, rowIndex, e){
					var sm = self.getSelectionModel().getSelected();
					var logkey = sm.get('logkey');
			
					var win = new Ext.Window({
						title: _text('MN01099'),//SGL log
						width: 500,
						modal: true,
						//height: 150,
						height: 600,
						miniwin: true,
						resizable: false,
						layout: 'vbox',
						buttons: [{
							text: _text('MN00031'),//닫기
							scale: 'medium',
							handler: function(b, e){
								win.close();
							}
						}],
						items:[{
							xtype: 'grid',
							autoScroll: true,
							//height: 120,
							height: 100,
							store: new Ext.data.ArrayStore({
								fields: ['volume','volume_group','status','archived_date']
							}),
							viewConfig: {
								loadMask: true,
								forceFit: true
							},
							columns: [
								{ header: _text('MN02213'), dataIndex: 'volume', sortable:'false' }//Volume Name
								,{ header: _text('MN02214'), dataIndex: 'volume_group', sortable:'false' }//Volume Group
								,{ header: _text('MN02215'), dataIndex: 'status', sortable:'false',width:70 }//Status
								,{ header: _text('MN02216'), dataIndex: 'archived_date', sortable:'false',width:120 }//ArchiveDate
							],
							sm: new Ext.grid.RowSelectionModel({
							})
						},{
							layout: 'fit',
							title: _text('MN00048'),//log
							flex: 1,
							html: '&nbsp',
							padding: 5,
							width: '100%',
							autoScroll: true,
							listeners: {
								render: function(self){
									win.refresh_data(win);
								}
							}
						}],
						refresh_data: function(self) {
							self.el.mask();
							Ext.Ajax.request({
								url: '/store/get_sgl_log_data.php',
								params: {
									content_id: $content_id,
									logkey: logkey
								},
								callback: function(opt, success, response){
									self.el.unmask();
									var res = Ext.decode(response.responseText);
									if(res.success) {
										self.items.get(1).update(res.msg);
										var grid = self.items.get(0);
										Ext.each(res.volume, function(i){
											grid.store.loadData([i], true);
										});
									}
								}
							});
						}
					});
					win.show();
				}
			},
			viewConfig: {
				forceFit: false,
				emptyText: _text('MSG00148')//결과 값이 없습니다
			},
			columns: [
				{header: _text('MN01026'), width: 100, dataIndex: 'type_nm'},//작업유형
				{header: _text('MN00138'), width: 70, dataIndex: 'status'},//상태
				{header: _text('MN01023'), width: 130, dataIndex: 'creation_datetime'},//작업생성일
				{header: _text('MN01024'), width: 130, dataIndex: 'start_datetime'},//작업시작일
				{header: _text('MN01025'), width: 130, dataIndex: 'complete_datetime'}//작업종료일
			]
		}";
		$system_meta = buildSystemMeta($content_id, $content['bs_content_id'], "autoScroll: true, height: 300");
		$media_list = buildMediaListTab($content_id, "flex: 1.5, autoScroll: true");
	} else {
		$system_meta = buildSystemMeta($content_id, $content['bs_content_id'], "autoScroll: true, height: 300");
		$media_list = buildMediaListTab($content_id, "flex: 1, autoScroll: true");
	}

	$media_info = "{
				title: '" . _text('MN00170') . "',
				border: false,
				layout: {
					type : 'vbox',
					align:'stretch'
				},
				autoScroll : true,
				name: 'media_info_tab',
				items: [
						$system_meta,
						$media_list";
	if ($check_flashnet == 'Y') {
		$media_info .= "," . $sgl_log_panel;
	}
	$media_info .= "]
	}";

	$media_quality = buildMediaQualityMetaTab($content_id, "  ");

	// BC_MEDIA_QUALITY_INFO 테이블을 확인하여 IS_CHECKED가 null이거나 N이면 신규, Y이면 업데이트
	$is_checked = $db->queryOne("select is_checked from bc_media_quality_info where content_id = '$content_id'");


	$qc_info = "{
		title: _text('MN02294'),
		layout: {
			type: 'fit',
			align: 'stretch',
			pack: 'start'
		},
		split: true,
		items: [
			new Ariel.Nps.QualityCheck({
				content_id: $content_id
			})
		]
	}";

	// CANPN comment panel at View content popup
	$comment_panel =
		"
				{
					xtype: 'panel',
					id: 'comment_panel',
					title: '" . _text('MN01036') . "',
					//region: 'south',
					//bodyStyle:{\"background-color\":\"#eaeaea\"}, 
					cls: 'change_background_panel',
					border: false,
					//frame: true,
					layout: 'border',
					split: true,
					//height: 295,
					flex : 0.45,
					/*tools:[{
						id: 'refresh',
						qtip: '" . _text('MN00139') . "',
						handler: function(event, toolEl, panel){
							Ext.getCmp('list_comment').getStore().reload();
						}
					}],*/
					items:[],
					listeners: {
						afterrender: function(self){
		                     var store_comment = new Ext.data.JsonStore({
		                         url: '/store/get_comments.php',
		                        root: 'data',
		                        totalProperty: 'total',
		                        baseParams: {
		                           content_id: '" . $content_id . "'
		                        },
		                         fields: ['content_id','parent_content_id', 'user_id', 'user_nm', 'comments', 'seq', 'show_info','datetime_format','is_lasted','version',
		                           {name: 'datetime', type : 'date', dateFormat : 'Y-m-d H:i'}
		                         ]
		                     });
		
		                     store_comment.load();
		                     var comment_area_data = new Ext.DataView({
		                        store: store_comment,
		                        tpl: '',
		                        region: 'center',
		                        id: 'list_comment',
		                        autoScroll: true,
		                        emptyText: '" . _text('MSG02096') . "',
		                        listeners: {
		                           afterrender: function(self){
		                              var element = self.getEl();
		                              element.on('click', function(event, elem,a){
		                                 if(event.target.className == 'comment_del btn_del'){
		                                    Ext.MessageBox.confirm('" . _text('MN00034') . "', '" . _text('MSG02095') . "', function(btn){
		                                       if(btn === 'yes'){
		                                          var content_id = elem.getAttribute('content_id');
		                                          var seq = elem.getAttribute('seq');
		                                          var user_id = elem.getAttribute('comment_user_id');
		                                          var mode = 'delete';
		                                          Ext.Ajax.request({
		                                             url: '/store/edit_comments.php',
		                                             params: {
		                                                mode: mode,
		                                                content_id: content_id,
		                                                comment_user_id : user_id,
		                                                seq: seq
		                                             },
		                                             callback: function(opt, success, response){
		                                                Ext.getCmp('list_comment').getStore().reload();
		                                             }
		                                          });
		
		                                       }
		                                     });
		                                    
		                                 }
		                                 
		                              });
		                           }
		                        },
		                        setTemplate: function(template){
		                           this.tpl = template;
		                           this.refresh();
		                        }
		                     });
		
		                     store_comment.on( 'load', function( store, records, options ) {
		                        var total_comment_count = store_comment.totalLength; 
		                        var tpl = new Ext.XTemplate(
		                           '<div class=\"reply\"><strong><span style=\"\" id=\"total_comment_count\">'+total_comment_count+'</span> '+_text ( 'MN01036' ) +'</strong><ul class=\"wrep_reply\">',                     
		                           '<tpl for=\".\">',
		                              '<li><dl><dt><span><i class=\"fa fa-comment-o\" style=\"padding-right: 3px;\"></i></span><span style=\"padding-right: 5px;\">{user_nm}</span>{datetime_format}</dt><dd class=\"multiline_row_line\">{comments}</dd></dl>',
		                                 '<tpl if=\"is_lasted == 1\">',
		                                 '<a  title=\"'+_text('MN00034')+'\" href=\"#\" content_id=\"" . $content_id . "\" seq=\"{seq}\" comment_user_id=\"{user_id}\"class=\"comment_del btn_del\">'+_text('MN00034')+'</a>',      
		                                 '</tpl>',      
		                              '</li>',
		                           '</tpl>',
		                           '</ul></div>',
		                           '<div class=\"x-clear\"></div>'
		                        );
		
		                        comment_area_data.setTemplate(tpl);
		                     } );
		                              
		                     self.add(comment_area_data);
		                     self.doLayout();
		                  }
					}

				},
				{
					xtype: 'panel',
					id: 'comment_input_and_button',
					region: 'center',
					height: 43,
					layout: 'hbox',
					layoutConfig: {
						align : 'middle'
					},
					defaults: {
						margins:'0 5 0 0'
					},
					items: [{
						xtype: 'textarea',
						margins:'0 5 0 5',
						id: 'comment_text',
						height: 30,
						maxLength : 4000,
						flex: 1
					},{
						xtype: 'button',
						//text: '" . _text('MN01036') . "',
						text : '<span style=\"position:relative;top:1px;\"><i class=\"fa fa-comment-o\" style=\"font-size:13px;\"></i></span>&nbsp;'+_text('MN01036'),
						scale: 'medium',
						id: 'sent_comment_btn',
						width: 70,
						height: 30,
						handler: function(b, e){
							var text = Ext.getCmp('comment_text').getValue();
							var mode = 'insert';
							var content_id = '" . $content_id . "';
							var grid = Ext.getCmp('comments_grid');
							var sent_btn = Ext.getCmp('sent_comment_btn');
							if(Ext.isEmpty(text) || text.length >4000)
							{
								return;
							}
							if(Ext.isEmpty(content_id))
							{
								return;
							}
							sent_btn.disable();
							Ext.Ajax.request({
								url: '/store/edit_comments.php',
								params: {
									mode: mode,
									text: text,
									content_id: content_id
								},
								callback: function(opt, success, response){
									var res = Ext.decode(response.responseText);
									//Ext.Msg.alert('Information', res.msg);
									Ext.getCmp('comment_text').setValue('');
									Ext.getCmp('list_comment').getStore().reload();
									sent_btn.enable();
								}
							});
						}
					}]
				}
			";
	if ($check_loudness == 'Y') {
		$hidden_field_loudness = "hidden : false,";
	} else {
		$hidden_field_loudness = "hidden : true,";
	}

	if (INTERWORK_QC == 'Y') {
		$hidden_field_qc = "hidden : false,";
	} else {
		$hidden_field_qc = "hidden : true,";
	}

	$catalog_panel = "{
		title: _text('MN00269'),
		layout: 'fit',
		items: [],
		name: 'catalog_info_tab',
		//id: 'catalog_info_tab',
		tbar:[{
			xtype      	: 'checkbox',
			hidden		: true,
			cls			: 'newCheckboxStyle',
			boxLabel   	: _text('MN00269'),
			checked 	: true,
			id 			: 'scene_type_checkbox_S',
			listeners: {
							check: function (checkbox, checked) {
				 				var scene_type_list =[];
				 				var scene_type_checkbox_S = Ext.getCmp('scene_type_checkbox_S').getValue();
				 				var scene_type_checkbox_Q = Ext.getCmp('scene_type_checkbox_Q').getValue();
				 				var scene_type_checkbox_L = Ext.getCmp('scene_type_checkbox_L').getValue();
				 				if (scene_type_checkbox_S == false){
				 					scene_type_list.push({'scene_type' : ' ' });
				 				} else {
				 					scene_type_list.push({'scene_type' : 'S' });
				 				}
				 				if (scene_type_checkbox_Q == false){
				 				} else {
				 					scene_type_list.push({'scene_type' : 'Q' });
				 				}
				 				if (scene_type_checkbox_L == false){
				 				} else {
				 					scene_type_list.push({'scene_type' : 'L' });
				 				}
				 				var images_view = Ext.getCmp('images-view');
								images_view.store.reload({
									params: {
										content_id: " . $content_id . ",
										scene_type_list: Ext.encode(scene_type_list)
									}
								});
							}
						}
		},{
			xtype      	: 'checkbox',
			cls			: 'newCheckboxStyle',
            boxLabel   	: _text('MN02290'),
			" . $hidden_field_qc . "
			id 			: 'scene_type_checkbox_Q',
			hidden		: true,
            listeners: {
							check: function (checkbox, checked) {
				 				var scene_type_list =[];
				 				var scene_type_checkbox_S = Ext.getCmp('scene_type_checkbox_S').getValue();
				 				var scene_type_checkbox_Q = Ext.getCmp('scene_type_checkbox_Q').getValue();
				 				var scene_type_checkbox_L = Ext.getCmp('scene_type_checkbox_L').getValue();
				 				if (scene_type_checkbox_S == false){
				 				} else {
				 					scene_type_list.push({'scene_type' : 'S' });
				 				}
				 				if (scene_type_checkbox_Q == false){
				 					scene_type_list.push({'scene_type' : ' ' });
				 				} else {
				 					scene_type_list.push({'scene_type' : 'Q' });
				 				}
				 				if (scene_type_checkbox_L == false){
				 				} else {
				 					scene_type_list.push({'scene_type' : 'L' });
				 				}
				 				var images_view = Ext.getCmp('images-view');
								images_view.store.reload({
									params: {
										content_id: " . $content_id . ",
										scene_type_list: Ext.encode(scene_type_list)
									}
								});
							}
						}
		},{
			xtype      	: 'checkbox',
			cls			: 'newCheckboxStyle',
			boxLabel   	: _text('MN02291'),
			" . $hidden_field_loudness . "
			id 			: 'scene_type_checkbox_L',
			listeners: {
							check: function (checkbox, checked) {
				 				var scene_type_list =[];
				 				var scene_type_checkbox_S = Ext.getCmp('scene_type_checkbox_S').getValue();
				 				var scene_type_checkbox_Q = Ext.getCmp('scene_type_checkbox_Q').getValue();
				 				var scene_type_checkbox_L = Ext.getCmp('scene_type_checkbox_L').getValue();
				 				if (scene_type_checkbox_S == false){
				 				} else {
				 					scene_type_list.push({'scene_type' : 'S' });
				 				}
				 				if (scene_type_checkbox_Q == false){
				 				} else {
				 					scene_type_list.push({'scene_type' : 'Q' });
				 				}
				 				if (scene_type_checkbox_L == false){
				 					scene_type_list.push({'scene_type' : ' ' });
				 				} else {
				 					scene_type_list.push({'scene_type' : 'L' });
				 				}
				 				var images_view = Ext.getCmp('images-view');
								images_view.store.reload({
									params: {
										content_id: " . $content_id . ",
										scene_type_list: Ext.encode(scene_type_list)
									}
								});
							}
						}
		},'->',{
			xtype : 'button',
			cls: 'proxima_button_customize proxima_btn_customize_new proxima_button_excel',
            width: 30,
            hidden: true,
			//text: '<span style=\"position:relative;top:1px;\" title=\"'+_text('MN02359')+'\"><i class=\"fa fa-file-excel-o\" style=\"font-size:13px;color:white;\"></i></span>',
			iconCls: 'proxima-icon-excel',
			tooltip: _text('MN02359'),
			handler : function(self, e){
				var scene_type_list =[];
				var scene_type_checkbox_S = Ext.getCmp('scene_type_checkbox_S').getValue();
				var scene_type_checkbox_Q = Ext.getCmp('scene_type_checkbox_Q').getValue();
				var scene_type_checkbox_L = Ext.getCmp('scene_type_checkbox_L').getValue();
				if (scene_type_checkbox_S == false){
				} else {
					scene_type_list.push({'scene_type' : 'S' });
				}
				if (scene_type_checkbox_Q == false){
				} else {
					scene_type_list.push({'scene_type' : 'Q' });
				}
				if (scene_type_checkbox_L == false){
					scene_type_list.push({'scene_type' : ' ' });
				} else {
					scene_type_list.push({'scene_type' : 'L' });
				}
				var v_scene_type_list = Ext.encode(scene_type_list);
				window.location = '/store/catalog/save_excel_file.php?mode=excel&content_id=" . $content_id . "&scene_type_list='+v_scene_type_list;
			}
		}
		/*,{
			//style: 'border-style:outset;',
			//!!text: '새로고침',
			text: '<span style=\"position:relative;top:1px;\"><i class=\"fa fa-plus\" style=\"font-size:13px;\"></i></span>&nbsp;'+_text('MN02293'),
            hidden: true,
            width : 70,
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
					height: 120,
					width: 600,
					modal: true,
					title: _text('MN02293'),

					items: [{
						xtype: 'form',
						frame: true,
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
						text: '<span style=\"position:relative;top:1px;\"><i class=\"fa fa-check\" style=\"font-size:13px;\"></i></span>&nbsp;'+_text('MN00003'),//'확인'
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
									//vr_meta: Ext.encode(values),
									start_frame: setInFrame,
									end_frame: setOutFrame,
									content_id: " . $content_id . "
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
						text: '<span style=\"position:relative;top:1px;\"><i class=\"fa fa-close\" style=\"font-size:13px;\"></i></span>&nbsp;'+_text('MN00004'),//'취소'
						handler: function(btn) {
							btn.ownerCt.ownerCt.close();
						}
					}]
				}).show();

			}
		},{
			text: '<span style=\"position:relative;top:1px;\"><i class=\"fa fa-external-link\" style=\"font-size:13px;\"></i></span>&nbsp;'+_text('MN02140'),
			width : 70,
			handler: function(btn, e){

				if('" . $pfr_err_msg . "' != '') {
					Ext.Msg.alert( _text('MN00023'), '" . $pfr_err_msg . "');//알림
					return;
				}
				var images_view = Ext.getCmp('images-view');
				var length = images_view.selected.elements.length;
				var setInSec, setOutSec;

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
					var setInTC = Ext.getCmp('images-view').selected.elements[0].viewIndex;
					var setOutTC = Ext.getCmp('images-view').selected.elements[length-1].viewIndex;
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
				
				if ('" . $flag_ori . "' == 'Y' ){
					//alert(\"Test\");
					new Ext.Window({
						layout: 'fit',
						height: 120,
						width: 600,
						modal: true,
						title: _text('MN02140'),

						items: [{
							xtype: 'form',
							frame: true,
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
							text: '<span style=\"position:relative;top:1px;\"><i class=\"fa fa-check\" style=\"font-size:13px;\"></i></span>&nbsp;'+_text('MN00003'),//'확인'
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
										content_id: " . $content_id . ",
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
							text: '<span style=\"position:relative;top:1px;\"><i class=\"fa fa-close\" style=\"font-size:13px;\"></i></span>&nbsp;'+_text('MN00004'),//'취소'
							handler: function(btn) {
								btn.ownerCt.ownerCt.close();
							}
						}]
					}).show();
					return;
				} else if ( '" . $flag_ori . "' == 'N' && '" . $check_flashnet . "' == 'Y' ){
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
										content_id: " . $content_id . ",
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
				}
			}
		},{
			text: '<span style=\"position:relative;top:1px;\"><i class=\"fa fa-external-link\" style=\"font-size:13px;\"></i></span>&nbsp;'+_text('MN02140'),
			width : 70,
			handler: function(btn, e){
			}
		}*/
		],
		listeners: {
			afterrender: function(self){
				Ext.Ajax.request({
					url: '/store/get_catalog.php',
					params: {
						content_id: $content_id
					},
					callback: function(opts, success, response){
						if (success) {
							try {
								var r = Ext.decode(response.responseText);
								if (r.success === false) {
									Ext.Msg.alert( _text('MN00022'), r.msg);
								} else {
									self.add(r);
									self.doLayout();
								}
							} catch(e) {
								Ext.Msg.alert(e['name'], e['message']);
							}
						}else{
							Ext.Msg.alert( _text('MN00022'), opts.url+'<br />'+response.statusText+'('+response.status+')');
						}
					}
				})
			}
		}
	}";

	$parent_content_panel = "
	{
		title:'관련 콘텐츠',
		layout: 'fit',
		itemId:'relationContent',
		items:[],
		relatedButton:false,
		listeners:{
			beforerender: function(self){
				var components = [
					'/custom/ktv-nps/javascript/ext.ux/Custom.ParentContentGrid.js',
					'/custom/ktv-nps/js/archiveManagement/Ariel.archiveManagement.UrlSet.js'
				];

				Ext.Loader.load(components, function (r) {
					var parentContentGrid = new Custom.ParentContentGrid({
						contentId:$content_id,
						relatedButton:self.relatedButton,
					});
                    
                    self.add(parentContentGrid);
                    self.doLayout();
				});
			}
		}
	}
	";

	$loudness_log_panel = "
		{
			title: _text('MN02245'),
			layout: 'fit',
			items: [
				new Ariel.LoudnessLog({
						content_id : $content_id
					})
			]
		}
	";

	//컨테이너 목록
	$query = "SELECT *
			FROM 
				bc_usr_meta_field
			WHERE 
				ud_content_id = {$content['ud_content_id']}
			AND	container_id is not null
			AND	depth = 0
			AND	is_show = '1'
			ORDER BY show_order";
	$containerList = $db->queryAll($query);

	//컨테이너 배열생성
	$container_array = array();

	foreach ($containerList as $container_key => $container) {
        
		// 컨테이너 아이디
		$container_id_tmp = $container['container_id'];

		// 컨테이너 명
		$container_title = addslashes($container['usr_meta_field_title']);
        
		//버튼리스트 생성
		$buttons = buildButtons($container_id_tmp, $content_id, $content['ud_content_id'], $content['bs_content_id'], $content['status'], $_SESSION['user']['user_id'], $content['reg_user_id'], '', $is_watch_meta, $_REQUEST['window_id']);

		//$rsFields = content_meta_value_list( $content_id, $content['ud_content_id'] , $container_id_tmp );
		$rsFields =  MetaDataClass::getFieldValueforContaierInfo('usr', $content['ud_content_id'], $container_id_tmp, $content_id);

		//$logger->addInfo('fields', $rsFields);

		$items = array();

		if (empty($old_content_id)) $old_content_id = $content_id;

		array_push($items, "{xtype: 'hidden', name: 'k_content_id', value: '" . $old_content_id . "'}\n");
		array_push($items, "{xtype: 'hidden', name: 'k_ud_content_id', value: '" . $content['ud_content_id'] . "'}\n");
		array_push($items, "{xtype: 'hidden', name: 'k_category_id', value: '" . $content['category_id'] . "'}\n");
		if ($container_key == 0) {
			//2015-12-16 제목 메타데이터 글자수 제한
			//MN00249 title
			array_push($items, "{xtype: 'textfield', allowBlank: false, autoCreate: {tag: 'input', type: 'text', autocomplete: 'off', maxlength: '150'}, fieldLabel: '" . _text('MN00249') . "', name: 'k_title', value: '" . addslashes($content['title']) . "'}\n");
		}

		// 카테고리 선택적 표시
		if ($container_key == 0) {
			array_push($items, getCustomCategoryTree($category_path, $root_category_id, $root_category_text, $ud_content_tab));
		}

		foreach ($rsFields as $f) {
			if ($f['is_show'] != 0) {
              
				$xtype	= $f['usr_meta_field_type'];
				$is_required = $f['is_required'];
				if ($is_required == 1) {
					$check_star = "<span class=\'usr_meta_required_filed\'>&nbsp;*&nbsp;</span>";
                    $check_label = '<span  style="font-weight:bold">'.addslashes($f['usr_meta_field_title']).'</span>';
				} else {
					$check_star = "&nbsp;";
					$check_label = addslashes($f['usr_meta_field_title']);
				}
				$label	=   $check_label. $check_star;
				$name	= strtolower($f['usr_meta_field_code']);
				// 날짜 형식을 yyyyMMddhhmmss 형태로 변경
				$value	= autoConvertByType($xtype, $f['value']);
				$meta_field_id = $f['usr_meta_field_id'];

				// 커스터마이징 된 메타데이터에 대한 처리
				// The logic for customized metadata
				if (defined('CUSTOM_ROOT') && class_exists('\ProximaCustom\core\MetadataFieldManager')) {
					$control = \ProximaCustom\core\MetadataFieldManager::getFieldControl(
						$f,
						$value,
						$content_id,
						\ProximaCustom\core\MetadataMode::Single,
						$rsFields
					);
					if (!empty($control)) {
						$items[] = $control;
						continue;
					}
				}

				$item = array();

				if ($xtype == 'listview') {
					array_push($item, "xtype:			'panel'");

					$listview_datafields = getListViewDataFields($f['default_value']);
					$listview_columns = getListViewColumns($f['default_value'], $f['usr_meta_field_id']);

					if (!empty($listview_datafields) && !empty($listview_columns)) {

						array_push($item, "layout:			'fit'");
						array_push($item, "frame:			true ");
						array_push($item, "border:			true ");
						array_push($item, "items: [{
								xtype: '$xtype',
								emptyText: '등록된 데이터가 없습니다.',
								height: 250,
								listeners: {
									render: function(self){
										self.store.load({
											params: {
												content_id: '" . $content_id . "'
											}
										});
									}
								},
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
							}] ");
					}
				} else {
					array_push($item, "xtype:			'" . $xtype . "'");
				}
                
				array_push($item, "name:			'" . $name . "'");
				// array_push($item, "id:			'" . $name . "'");

				if ($f['is_editable'] == 0) {
					array_push($item, "readOnly: true");
				}

				if ($f['is_show'] == 0) {
					array_push($item, "hidden: true");
				}

				if ($xtype == 'checkbox') {
					if (!empty($value) && ($value == 'on' || $value == '1')) {
						array_push($item, "checked:			'" . 'true' . "'");
					}
					// \n \r 개행문자 제거
					array_push($item, "value: '" . esc2($value) . "'");
				} else if ($xtype == 'textarea') {
					// \n \r 개행문자 변환 \\n \\r
					array_push($item, "value: '" . esc3($value) . "'");
				} else {
					// \n \r 개행문자 제거
					array_push($item, "value: '" . esc2($value) . "'");
				}

				//array_push($item, "flex: 1");
				// if ($is_required == '1') {
				// 	array_push($item, "allowBlank: false");
				// }

				if ($xtype == 'textarea') {
					array_push($item, "height: 200");
                    //2015-12-16 textarea 4000자 이내로 수정
                    if ($f['data_type'] != 'CLOB') {
                        array_push($item, "maxLength: 4000");
                        //2015-12-29 textareat regexText 추가
                        array_push($item, "regexText: '최대 길이는 4000자 입니다.'");
                    }
				}

				//2015-12-16 textfield 200자 이내로 수정
				if ($xtype == 'textfield') {
					array_push($item, "autoCreate: {tag: 'input', type: 'text', autocomplete: 'off', maxlength: '200'}");
				}

				if ($xtype == 'datefield') {
					array_push($item, "altFormats: 'Ymd|Y-m-d|Y-m-d H:i:s|YmdHis', format: 'Y-m-d'");
					//2015-12-29 datefield regexText 추가
					array_push($item, "regexText: '올바른 날짜 형식이 아닙니다.'");

                    switch($f['usr_meta_field_code']){
						case 'PROD_DE':
							// 등록일자
							// $query = "SELECT created_date FROM bc_content WHERE content_id = {$content['content_id']}";
							// $createdDate = $db->queryOne($query);
							// // 등록일시의 값이 제작일시의 기본값이 된다.
							// array_push($item, "
							// 	listeners:{
							// 		afterrender:function(self){
							// 			  var value = self.getValue();
							// 			  if(!isValue(value)){
							// 				  self.setValue({$createdDate});
							// 			  }
							// 		}	
							// 	}
							// ");
						break;
					};
				} else if ($xtype == 'combo' || $xtype == 'c-tag-combo' || $xtype == 'g-combo') {
					// 코드 콤보박스 정보 획득
					$store = getFieldCodeValue($meta_field_id, $f['usr_meta_field_code']);           
                    if( !empty($store) ){
                        $store = json_encode($store);
                    }
					if (empty($store) || $store == '[]') {
						// 일반 콤보박스
						$store = "[" . getFieldDefaultValue($meta_field_id) . "]";
						//2015-12-29 combo editable true->false 수정
						array_push($item, "editable: false, triggerAction: 'all', typeAhead: true, mode: 'local', store: $store");
					} else {
						// 코드콤보박스 처리
						array_push($item, "editable: false, triggerAction: 'all', typeAhead: true, mode: 'local', valueField: 'key',displayField: 'val',store: new Ext.data.JsonStore({ fields: [{name:'key'},{name:'val'},{name:'use_yn'}],data: $store })
						");
						// 콤보박스 기본값 처리
						switch($f['usr_meta_field_code']){
							case 'CLOR':
								// array_push($item, "
								// 	listeners:{
								// 		afterrender:function(self){
								// 			var value = self.getValue();
								// 			if(!isValue(value)){
								// 				self.setValue('color');
								// 			}
								// 		}	
								// 	}
								// ");
							break;
						}
					}
				} 

				// 벨리데이션 주석 처리
                // if($f['is_required'] == '1'){
				// 	$f['allowBlank'] = false;
				// 	array_push($item, "allowBlank:false");
				// }else{
				// 	$f['allowBlank'] = true;
				// 	array_push($item, "allowBlank:true");
				// };

				array_push($item, "fieldLabel:	'" . $label . "'");
				array_push($items, "{" . join(', ', $item) . "}\n");
			}
		}
        
		if ($container_key == 0) {
            $modified_date = empty($content['last_modified_date']) ? '0000-00-00 00:00:00' : date('Y-m-d H:i:s', strtotime($content['last_modified_date']));

            if( $ud_content_id == 1 ){
                $archiveAt = '-';
            } else if( $contentStatus['archv_trget_at'] == 'N'){
                $archiveAt = '보관안함';
            }else{
                $archiveAt = '보관';
            }
         
			array_push($items, "{xtype: 'textfield', readOnly: true, fieldLabel: _text('MN00287'), value: '" . $content_id . "'}\n"); //'등록자 이름'
            
            array_push($items, "{xtype: 'textfield', disabled: true, fieldLabel:'아카이브 여부', value: '".$archiveAt . "'}\n"); //'아카이브여부'

            array_push($items, "{xtype: 'textfield', disabled: true, fieldLabel: _text('MN02149'), value: '" . addslashes($content['reg_user_id']) . "'}\n"); //'등록자 아이디'
			array_push($items, "{xtype: 'textfield', disabled: true, fieldLabel: _text('MN02150'), value: '" . addslashes($content['reg_user_nm']) . "'}\n"); //'등록자 이름'
			array_push($items, "{xtype: 'textfield', disabled: true, fieldLabel: _text('MN02217'), value: '" . date('Y-m-d H:i:s', strtotime($content['created_date'])) . "'}\n"); //'등록일시'

			// array_push($items, "{xtype: 'textfield', disabled: true, fieldLabel: '수정일시', value: '" . $modified_date."'}\n");
			$title_tab = "";
		} else {
			$title_tab = "title: '{$container_title}',";
		}

		$items_text = '[' . join(', ', $items) . "]\n";

		// 기본정보 탭
		if ($container_key == 0) { // basic info
			$container_text = "	{
				id: 'user_metadata_{$container_id_tmp}',
				xtype: 'form',
				cls: 'change_background_panel_detail_content background_panel_detail_content',
				//region: 'center',
				flex : 1.5,
				autoScroll: true,
				url: '/store/content_edit1.php',
				$title_tab
                padding: 5,
                labelWidth : 110,
				border: false,
				//frame: true,
				defaultType: 'textfield',
				defaults: {
					labelSeparator: '',
					anchor: '95%'
				}
				,buttonAlign: 'left'
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
				},
				items: [ $items_text ],
				buttons: [{
						xtype: 'checkbox',
						hidden: $isHideApproveContentCheckbox,
						cls: 'newCheckboxStyle',
						//value: $content_approval_yn,
            			boxLabel:'<span style=\"color:#FFFFFF\">'+_text('MN02543')+'</span>',
            			listeners:{
		                    afterrender: function(self){
		                        self.setValue($content_approval_yn);
		                    },
		                    check: function(self, checked){
		                    	var isApproval;
		                    	var job;
		                        if(checked){
		                            isApproval = 'Y';
		                            job  = 'approve';
		                        }else{
		                            isApproval = 'N';
		                            job  = 'unapprove';
		                        }
		                        if(!self.isFirst){
									var option = '$content_approval_mode';
									var rs=[];
									rs.push($content_id);

									if(option == '1'){
										/* simple approval workflow */
										Ext.Ajax.request({
											url: '/store/content_approval.php',
											params: {
												mode: '1',
												job: job,
												content_list: Ext.encode(rs)
											},
											callback: function(self, success, response) {
												if(success){
													//Ext.getCmp('tab_warp').getActiveTab().items.items[0].store.reload();
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
						}
					},
					$buttons
				]
			}";

			$container_text = "{
				title: '{$container_title}',
				border: false,
				//layout: 'border',
				layout: {
					type : 'vbox',
					align:'stretch'
				},
				items: [
					" . $container_text;
			if ($comment_yn == 'Y') {
				$container_text .= "," . $comment_panel;
			}
			$container_text .= "
					]
			}";
		} else {
			// 기타 탭
			$container_text = "	{
				id: 'user_metadata_{$container_id_tmp}',
				xtype: 'form',
				cls: 'change_background_panel_detail_content background_panel_detail_content',
				//region: 'center',
				flex : 1.5,
				autoScroll: true,
				url: '/store/content_edit1.php',
				$title_tab
				padding: 5,
				border: false,
                //frame: true,
                labelWidth : 110,
				defaultType: 'textfield',
				defaults: {
					labelSeparator: '',
					anchor: '95%'
				}
				,buttonAlign: 'right'
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
				},
				items: [ $items_text ],
				buttons: [$buttons]
				 }";
		}

		$container_array[] = $container_text;
	}
    
	if ($content['bs_content_id'] == MOVIE || $content['bs_content_id'] == SEQUENCE) {
		//프리뷰노트 정보가 있을떄 패널추가		
		if ($preview_panel) {
			$preview_panel = getPreviewList($content_id);
			$container_array[] = $preview_panel;
		}

		$container_array[] = $media_info;
		//CANPN 20160414 remove comment tab
		/*
		if ($comment_yn == 'Y') {
			$container_array [] = $comment_panel;
		}
		*/
		$container_array[] = $catalog_panel;

		/*
		if( $check_flashnet == 'Y' ){
			$container_array[] = $sgl_log_panel;
		}
		*/

		$container_array[] = $parent_content_panel;

		if ($check_loudness == 'Y') {
			$container_array[] = $loudness_log_panel;
		}

		if (INTERWORK_QC == 'Y') {
			$container_array[] = $qc_info;
		}

		//CANPN 20160216 add history_edited tab
		//changed position 2016.03.10 Alex
		/*
		if ($history_edited_yn == 'Y') {
			$container_array [] = $history_edited;
		}
		*/
		if ($content['bs_content_id'] == MOVIE) {

			// custom tabs
			if (defined('CUSTOM_ROOT') && class_exists('\ProximaCustom\core\DetailPanelTabCustom')) {

				$tabs = \ProximaCustom\core\DetailPanelTabCustom::getCustomTabs($content, $user_id);
				foreach ($tabs as $tab) {
					$container_array[] = $tab;
				}
			}
		}

		if ($content['ud_content_id'] == 4000287) {
			$container_array[] = getLogPanel($content_id);
		}
		//      $container_array[] = $qc_info;


	} else {
		$container_array[] = $media_info;
		//CANPN 20160223 add comment tab
		/*
		if ($comment_yn == 'Y') {
			$container_array [] = $comment_panel;
		}
		*/
		//CANPN 20160216 add history_edited tab
		/*
		if ($history_edited_yn == 'Y') {
			$container_array [] = $history_edited;
		}
		*/
	}

	$containerBody = '[' . join(',', $container_array) . ']';

	echo $containerBody;
} catch (Exception $e) {
	die(json_encode(array(
		'success' => false,
		'msg' => $e->getMessage() . $db->last_query
	)));
}
