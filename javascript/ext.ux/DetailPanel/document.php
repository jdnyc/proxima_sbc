<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');

$mode = $_REQUEST['mode'];

$content = $db->queryRow("select c.title, u.ud_content_title as meta_type_name
							from bc_content c, bc_ud_content u
							where c.content_id={$_POST['content_id']} and c.bs_content_id=u.bs_content_id");

?>

(function(){
	Ext.ns('Ariel');

	Ariel.DetailWindow = Ext.extend(Ext.Window, {
		id: 'winDetail',
		title: '상세보기 [<?=$content['title']?>]',
		editing: <?=$editing ? 'true,' : 'false,'?>
		//width: 600,
		//height: 670,
		//minHeight: 500,
		//minWidth: 600,
		modal: true,
		layout: 'fit',
		//maximizable: true,
		//maximized: true,
		draggable : false,//prevent move
		width: Ext.getBody().getViewSize().width*0.9,
		height: Ext.getBody().getViewSize().height*0.9,
		listeners: {
			render: function(self){
				// Ext.getCmp('grid_thumb_slider').hide();
				// Ext.getCmp('grid_summary_slider').hide();
				self.mask.applyStyles({
					"opacity": "0.5",
					"background-color": "#000000"
				});

				//self.setSize(500,680);
				//self.setPosition('150','100');
			},
			move: function(self, x, y){//창이 윈도우 포지션을 벗어났을때 0으로 셋팅
				var pos = self.getPosition();
				if(pos[0]<0)
				{
					self.setPosition(0,pos[1]);
				}
				else if(pos[1]<0)
				{
					self.setPosition(pos[0],0);
				}
			},

			close: function(self){
				Ext.getCmp('tab_warp').getActiveTab().get(0).getStore().reload();
				// Ext.getCmp('grid_thumb_slider').show();
				// Ext.getCmp('grid_summary_slider').show();
				if(Ext.getCmp('topic-tree')) {
					var root = Ext.getCmp('topic-tree').getRootNode();
					Ext.getCmp('topic-tree').getLoader().load(root);
				}
				/*
				var p = Ext.getCmp('detail_panel').checkModified();
				if (p == false)
				{
					return false;
				}
				*/
			},
			show : function(win) {
				document.onkeyup = function(evt) {
				    evt = evt || window.event;
				    if (evt.keyCode == 27) {
				        win.close();
				    }
				};
	        }
		},
		/* 2010-11-09  주석처리 (탭 show/hide 기능 제거 by CONOZ)
		tools: [{
			id: 'right',
			handler: function(e, toolEl, p, tc){

				var _w = 400;

				if (tc.id == 'right')
				{
					tc.id = 'left';
					Ext.getCmp('detail_panel').setWidth(_w);//setVisible(false);
					p.setWidth( p.getWidth() + _w );
				}
				else
				{
					tc.id = 'right';
					Ext.getCmp('detail_panel').setWidth(0);//setVisible(false);
					p.setWidth( p.getWidth() - _w );
				}

				p.center();
			}
		}],
		*/

		initComponent: function(config){
			Ext.apply(this, config || {});

			this.items = [{
				border: false,
				layout: 'border',
				border: false,
				split: true,

				items: [{
					layout: 'border',
					region: 'center',
					border: false,
					//autoScroll: true,

					items: [{
						hidden: true,
						region: 'south',
						xtype: 'grid',
						title: '미디어파일 리스트',
						collapsed: true,
						collapsible: true,
						split: true,
						height: 120,
						store: new Ext.data.JsonStore({
							id: 'detail_media_grid',
							url: '/store/get_media.php',
							root: 'data',
							fields: [
								'content_id',
								'media_id',
								'storage_id',
								'type',
								'path',
								'filesize',
								{name: 'created_time', type: 'date', dateFormat: 'YmdHis'}
							],
							listeners: {
								exception: function(self, type, action, opts, response, args){
									Ext.Msg.alert('오류', response.responseText);
								}
							}

						}),
						columns: [
							{header: '파일용도', dataIndex: 'type', width: 65, renderer: function(value, metaData, record, rowIndex, colIndex, store){
								switch(value){
									case 'original':
										var tip = '원본 자료입니다';
										value = '원본';
									break;

									case 'thumb':
										var tip = '리스트 썸네일 이미지입니다.';
										value = '대표이미지';
									break;

									case 'proxy':
										var tip = '미리보기용 프록시 파일입니다.';
										value = '프록시 파일';
									break;

									case 'download':
										var tip = '사용자 다운로드 자료입니다.';
										value = '다운로드';
									break;
								}

								metaData.attr = 'ext:qtip="'+tip+'"';
								return value;
							}},
							{header: '저장경로', dataIndex: 'path', width: 400},
							{header: '파일용량', dataIndex: 'filesize', width: 70, align: 'center'},
							{header: '생성일', dataIndex: 'created_time', renderer: Ext.util.Format.dateRenderer('Y-m-d H:i:s'), width: 120, align: 'center'}
						],
						sm: new Ext.grid.RowSelectionModel({
							singleSelect: true
						}),
						listeners: {
							rowcontextmenu: function(self, idx, e){
										<?php
										/*
										$meta_table = $mdb->queryOne("select meta_table_id from content where content_id = $content_id");
										$user = $_SESSION['user']['groups'];
										foreach($user as $v){
											$right = $mdb->queryOne("select name from content_grants where meta_table_id=$meta_table and member_group_id=$v and name = 'download'");
										}
										*/
										?>
										var r = self.getSelectionModel().selectRow(idx);
										e.stopEvent();

										var menu = new Ext.menu.Menu({
											items: [{
												icon: '/led-icons/disk.png',
												text: '다운로드',
												handler: function(b, e) {
													new Ext.Window({
														title: '다운로드 사유 기입',
														width: 300,
														height: 200,
														modal: true,
														border: false,
														layout: 'fit',

														items: {
															xtype: 'textarea',
															name: 'download_summary'
														},

														buttons: [{
															text: '확인',
															handler: function(b, e){
																b.ownerCt.ownerCt.close();
															}
														},{
															text: '취소',
															handler: function(b, e){
																b.ownerCt.ownerCt.close();
															}
														}]
													}).show();
												}
											}]
										});
										menu.showAt(e.getXY());
									},
							viewready: function(self){
								self.getStore().load({
									params: {
										content_id: <?=$content_id?>
									}
								});
							}
						}
					}]
				},{
					region: 'center',
					xtype: 'panel',
					layout: 'border',
					border: false,
					items:
					[
					{
						region: 'south',
						xtype: 'form',
						height: 35,
					    id: 'tag_list_in_content',
						hidden: true,
					    bodyStyle: 'background: #eaeaea;padding-top:3px;',
					    items: [],
					    listeners :{
					    	render: function(){
					    		var tag_list_in_content_form = Ext.getCmp('tag_list_in_content');
					      	
						      	Ext.Ajax.request({
						                url: '/store/tag/tag_action.php',
						                params: {
						                  action: 'get_tag_list_of_content',
						                  content_id: <?=$content_id?>
						                },
						                callback: function(opt, success, response){
						                    if(success) {
						                        var result = Ext.decode(response.responseText);
						                        var result_data = result.data;
						                        
						                        tag_list_in_content_form.add({
					                          	 	xtype : 'button',
						                           	cls: 'proxima_button_customize',
													width: 30,
						                           text: '<span style=\"position:relative;top:1px;\" title=\"'+_text('MN02240')+'\"><i class=\"fa fa-eraser\" style=\"font-size:13px;color:white\"></i></span>',
						                           style: {
						                              float: 'left',
						                              marginRight: '2px'
						                           },
						                           listeners: {
						                              render: function(c){
						                                 c.getEl().on('click', function(){
						                                    var content_id_array_2 = [];
	  														content_id_array_2.push({
	  																content_id: <?=$content_id ?>
	  															});
	
	  														Ext.Ajax.request({
	  															url: '/store/tag/tag_action.php',
	  															params: {
	  																content_id: Ext.encode(content_id_array_2),
	  																action: "clear_tag_for_content"
	  															},
	  															callback: function(opts, success, response) {
	  																Ext.getCmp('tag_list_in_content').reset_list_of_tag_form();
	  															}
	  														});					
						                                 }, c);
						                              }
						                           }
						
						                     	});
						                     	
						                     	tag_list_in_content_form.add({
						                           	xtype : 'button',
						                           	cls: 'proxima_button_customize',
													width: 30,
						                           text: '<span style=\"position:relative;top:1px;\" title=\"'+_text('MN02239')+'\"><i class=\"fa fa-cog\" style=\"font-size:13px;color:white;\"></i></span>',
						                           style: {
						                              float: 'left',
						                              marginRight: '5px'
						                           },
						                           listeners: {
						                              render: function(c){
						                                 c.getEl().on('click', function(){
						                                    tag_management_windown('detail_content');					
						                                 }, c);
						                              }
						                           }
						
						                     	});
					                        	for(i = 0; i < result_data.length; i++){
					                           		if(i<10){
					                           			if(result_data[i].is_checked == '1'){
					                           				tag_list_in_content_form.add({
						                                 		xtype: 'label',
							                     				html: '<div title=\"'+result_data[i].tag_category_title+'\" style=\"position: relative;float:left;height:1px;width:18px;padding-right:5px;\"><i class=\"fa fa-circle\" style=\"position: absolute;font-size:15px;margin-top:5px;color:'+result_data[i].tag_category_color+';border: 2px solid '+result_data[i].tag_category_color+';\"></i></div>'
						                              		});
					                           				
					                           			}else{
					                           				tag_list_in_content_form.add({
						                                 		xtype: 'label',
							                     				html: '<div tag_id_data =\"'+result_data[i].tag_category_id+'\" style=\"position: relative;float:left;height:1px;width:18px\"><i class=\"fa fa-circle\" style=\"position: absolute;font-size:15px;margin-right: 5px;margin-top:7px;color:'+result_data[i].tag_category_color+';padding-right: 4px;\" title=\"'+result_data[i].tag_category_title+'\"></i><i class=\"fa fa-check\" style=\"position: absolute;font-size:16px;margin-top:1px; display:none;\"></i></div>',
							                     				listeners: {
							                        				render: function(c){
							                           					var tag_category_id = c.getEl().dom.children[0].getAttribute('tag_id_data');
							                           					c.getEl().on('click', function(){
							                           						var content_id_array_2 = [];
							                           						content_id_array_2.push({
				  																	content_id: <?=$content_id ?>
			  																});
			  																change_tag_content('change_tag_content', content_id_array_2, tag_category_id,'no_reload_data');
							                           					}, c);
							                        				}
							                     				}
						                              		});
					                           			}
						                        		
						                           }else{
														if(result_data[i].is_checked == '1'){
					                           				tag_list_in_content_form.add({
						                                 		xtype: 'label',
							                     				html: '<div title=\"'+result_data[i].tag_category_title+'\" style=\"position: relative;float:left;height:1px;width:18px;padding-right:5px;\"><i class=\"fa fa-circle\" style=\"position: absolute;font-size:15px;margin-top:2px;color:'+result_data[i].tag_category_color+';border: 2px solid '+result_data[i].tag_category_color+';\"></i></div>'
						                              		});
					                           				
					                           			}
						                           }
						                        }
						                        if(result_data.length > 10){
						                           tag_list_in_content_form.add({
						                              	xtype : 'button',
						                              	cls: 'proxima_button_customize',
														width: 30,
						                              text: '<span style=\"position:relative;top:1px;\" title=\"'+_text('MN02288')+'\"><i class=\"fa fa-ellipsis-h\" style=\"font-size:13px;color:white;\"></i></span>',
						                              style: {
						                                 float: 'left',
						                                 marginRight: '5px'
						                              },
						                              listeners: {
						                                 render: function(c){
						                                    c.getEl().on('click', function(event){
						                                       tag_list_windown(<?=$content_id ?>);
						                                    }, c);
						                                 }
						                              }
						                           });
						                        }
						                        tag_list_in_content_form.doLayout();
						                    }
						                }
						            });
					    	}
					    },
					    reset_list_of_tag_form: function(){
				    		Ext.getCmp('tag_list_in_content').removeAll();
				    		var tag_list_in_content_form = Ext.getCmp('tag_list_in_content');
					      	Ext.Ajax.request({
					                url: '/store/tag/tag_action.php',
					                params: {
					                  action: 'get_tag_list_of_content',
					                  content_id: <?=$content_id?>
					                },
					                callback: function(opt, success, response){
					                    if(success) {
					                        var result = Ext.decode(response.responseText);
					                        var result_data = result.data;
					                        
					                        tag_list_in_content_form.add({
					                           xtype : 'button',
					                           cls: 'proxima_button_customize',
												width: 30,
					                           text: '<span style=\"position:relative;top:1px;\" title=\"'+_text('MN02240')+'\"><i class=\"fa fa-eraser\" style=\"font-size:13px;color:white;\"></i></span>',
					                           style: {
					                              float: 'left',
					                              marginRight: '2px'
					                           },
					                           listeners: {
					                              render: function(c){
					                                 c.getEl().on('click', function(){
					                                    var content_id_array_2 = [];
  														content_id_array_2.push({
  																content_id: <?=$content_id ?>
  															});

  														Ext.Ajax.request({
  															url: '/store/tag/tag_action.php',
  															params: {
  																content_id: Ext.encode(content_id_array_2),
  																action: "clear_tag_for_content"
  															},
  															callback: function(opts, success, response) {
  																Ext.getCmp('tag_list_in_content').reset_list_of_tag_form();
  															}
  														});					
					                                 }, c);
					                              }
					                           }
					
					                     	});
					                     	
					                     	tag_list_in_content_form.add({
					                           xtype : 'button',
					                           cls: 'proxima_button_customize',
												width: 30,
					                           text: '<span style=\"position:relative;top:1px;\" title=\"'+_text('MN02239')+'\"><i class=\"fa fa-cog\" style=\"font-size:13px;color:white;\"></i></span>',
					                           style: {
					                              float: 'left',
					                              marginRight: '5px'
					                           },
					                           listeners: {
					                              render: function(c){
					                                 c.getEl().on('click', function(){
					                                    tag_management_windown('detail_content');					
					                                 }, c);
					                              }
					                           }
					
					                     	});

				                        	for(i = 0; i < result_data.length; i++){
				                           		if(i<10){
				                           			if(result_data[i].is_checked == '1'){
				                           				tag_list_in_content_form.add({
					                                 		xtype: 'label',
						                     				html: '<div title=\"'+result_data[i].tag_category_title+'\" style=\"position: relative;float:left;height:1px;width:18px;padding-right:5px;\"><i class=\"fa fa-circle\" style=\"position: absolute;font-size:15px;margin-top:5px;color:'+result_data[i].tag_category_color+';border: 2px solid '+result_data[i].tag_category_color+';\"></i></div>'
					                              		});
				                           				
				                           			}else{
				                           				tag_list_in_content_form.add({
					                                 		xtype: 'label',
						                     				html: '<div tag_id_data =\"'+result_data[i].tag_category_id+'\" style=\"position: relative;float:left;height:1px;width:18px\"><i class=\"fa fa-circle\" style=\"position: absolute;font-size:15px;margin-right: 5px;margin-top:7px;color:'+result_data[i].tag_category_color+';padding-right: 4px;\" title=\"'+result_data[i].tag_category_title+'\"></i><i class=\"fa fa-check\" style=\"position: absolute;font-size:16px;margin-top:1px; display:none;\"></i></div>',
						                     				listeners: {
						                        				render: function(c){
						                           					var tag_category_id = c.getEl().dom.children[0].getAttribute('tag_id_data');
						                           					c.getEl().on('click', function(){
						                           						var content_id_array_2 = [];
						                           						content_id_array_2.push({
			  																	content_id: <?=$content_id ?>
		  																});
		  																change_tag_content('change_tag_content', content_id_array_2, tag_category_id,'no_reload_data');
						                           					}, c);
						                        				}
						                     				}
					                              		});
				                           			}
					                        		
					                           }else{
													if(result_data[i].is_checked == '1'){
				                           				tag_list_in_content_form.add({
					                                 		xtype: 'label',
						                     				html: '<div title=\"'+result_data[i].tag_category_title+'\" style=\"position: relative;float:left;height:1px;width:18px;padding-right:5px;\"><i class=\"fa fa-circle\" style=\"position: absolute;font-size:15px;margin-top:2px;color:'+result_data[i].tag_category_color+';border: 2px solid '+result_data[i].tag_category_color+';\"></i></div>'
					                              		});
				                           				
				                           			}
					                           }
					                        }
					                        if(result_data.length > 10){
					                           tag_list_in_content_form.add({
					                              	xtype : 'button',
					                              	cls: 'proxima_button_customize',
													width: 30,
					                              text: '<span style=\"position:relative;top:1px;\" title=\"'+_text('MN02288')+'\"><i class=\"fa fa-ellipsis-h\" style=\"font-size:13px;color:white;\"></i></span>',
					                              style: {
					                                 float: 'left',
					                                 marginRight: '5px'
					                              },
					                              listeners: {
					                                 render: function(c){
					                                    c.getEl().on('click', function(event){
					                                       tag_list_windown(<?=$content_id ?>);
					                                    }, c);
					                                 }
					                              }
					                           });
					                        }

					                        tag_list_in_content_form.doLayout();
					                    }
					                }
					            });
					    }
						
					},
					{
						region: 'center',
						id: 'detail_panel',
						xtype: 'tabpanel',
						title: '메타데이터',
						border: false,
						split: false,
	
						listeners: {
							afterrender: function(self){
								Ext.Ajax.request({
									url: '/store/get_detail_metadata.php',
									params: {
										mode: '<?=$mode?>',
										content_id: <?=$content_id?>
									},
									callback: function(opts, success, response){
										if(success){
											try {
												var r = Ext.decode(response.responseText);
												self.add(r)
												self.doLayout();
												self.activate(0);
											}catch(e){
												Ext.Msg.alert('오류', e+'<br />'+response.responseText);
											}
										}else{
											Ext.Msg.alert('오류', opts.url+'<br />'+response.statusText+'('+response.status+')');
										}
									}
								})
							}
						}
					}
					]
				}]
			}];

			Ariel.DetailWindow.superclass.initComponent.call(this);
		},

		loadForm: function(content_id){
			Ext.Ajax.request({
				url: '/store/get_detail_form.php',
				callback: function(self, type, action, response, arg){

				}
			})
		}
	});

	new Ariel.DetailWindow().show();
})()