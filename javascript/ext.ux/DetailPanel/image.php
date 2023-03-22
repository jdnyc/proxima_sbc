<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');

$user_id		= $_SESSION['user']['user_id'];
$mode = $_REQUEST['mode'];
$content_id = $_POST['content_id'];

$record = json_decode( $_POST['record'], true);

$content = $db->queryRow("select c.thumbnail_content_id, c.ud_content_id, c.title, c.bs_content_id, c.is_group, m.ud_content_title as meta_type_name, c.reg_user_id from bc_content c, bc_ud_content m where c.content_id={$content_id} and c.ud_content_id=m.ud_content_id");
$ud_content_id = $content['ud_content_id'];
$is_group = $content['is_group'];
$bs_content_id = $content['bs_content_id'];

if( !empty($content['thumbnail_content_id']) ){
	$thumbnail_media = $db->queryRow("select * from bc_media where media_type='proxy' and content_id={$content['thumbnail_content_id']}");
	if( !empty($thumbnail_media['path']) ){
		$record['proxy_path'] =$thumbnail_media['path'];
	}
}
$medias = \Api\Models\Media::where('content_id',$content_id)
->with('storage')->get();

foreach($medias as $media){
    if( $media->media_type == 'proxy' ){
        if( $media->status != 1 && $media->storage ){
            $proxyImagePath = rtrim($media->storage->virtual_path,'/') .'/'. ltrim($media->path,'/') ;
        };
    }
    if( $media->media_type == 'original' ){
        if( $media->status != 1 && $media->storage ){
            $originalImagePath = rtrim($media->storage->virtual_path,'/').'/'. ltrim($media->path,'/') ;
        };
    }
}
$image_path = $proxyImagePath ?? $originalImagePath;

if(empty($image_path ))
{
	$image_path = '/img/incoming_proxy.png';
}
?>

(function(){
	Ext.ns('Ariel');

	Ariel.DetailWindow = Ext.extend(Ext.Window, {
		id: 'winDetail',
		title: '<?=$record['ud_content_title']?> 상세보기 [<?=addslashes($record['title'])?>]',
		editing: <?=$editing ? 'true,' : 'false,'?>
		//width: 1000,
		//height: 670,
		//minHeight: 500,
		//minWidth: 800,
		modal: true,
		layout: 'fit',
		//maximizable: true,
		//maximized: true,
		width: Ext.getBody().getViewSize().width*0.9,
		height: Ext.getBody().getViewSize().height*0.9,
		draggable : false,//prevent move
		listeners: {
			render: function(self){
				// Ext.getCmp('grid_thumb_slider').hide();
				// Ext.getCmp('grid_summary_slider').hide();
				self.mask.applyStyles({
					"opacity": "0.5",
					"background-color": "#000000"
				});
				//self.setSize(1000,680);
				//self.setPosition('150','100');
				var width_side = Ext.getBody().getViewSize().width*0.9/2-8;
				Ext.getCmp('left_side_panel').setWidth(width_side);
			},
			close: function(self){
				Ext.getCmp('tab_warp').getActiveTab().get(0).getStore().reload();
				// Ext.getCmp('grid_thumb_slider').show();
				// Ext.getCmp('grid_summary_slider').show();
				
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
		initComponent: function(config){
			Ext.apply(this, config || {});

			var group_child_store = new Ext.data.JsonStore({
				url: '/store/group/get_child_list.php',
				autoLoad: false,
				root: 'data',
				fields: [
					'content_id', 'title', 'bs_content_id', 'thumb', 'sys_ori_filename', 'ori_path', 'proxy_path',
				],
				baseParams: {
					content_id: <?=$content_id?>,
					bs_content_id: <?=$bs_content_id?>
				}
			});

			function groupChildThumb(v, m, r) {
				var content_id = r.get('content_id');
				//v = "/img/incoming.jpg";
				//var img = '<img id="thumb-group-child-' + content_id + '" onload="resizeImg(this, {w:45, h:30})" src="/data/' + v + '" />';
				var path = '/data/' + v;
				var img = '<div style="height:50px;width:50px;background-image:url('+path+');background-position:center center;background-repeat:no-repeat;background-size:contain;text-align:center;" ></div>';
				return img;
			}

			var group_sub_content = {
					title: _text('MN01001'),
					layout: 'fit',
					items: [],
					name: 'catalog_info_tab',
					id: 'catalog_info_tab',
					tbar:[
					{
						xtype : 'button',
                        cls: 'proxima_button_customize',
                        width: 30,
                        text: '<span style=\"position:relative;top:1px;\" title=\"'+_text('MN00060')+'\"><i class=\"x-toolleft x-tool-tile\" style=\"font-size:13px;color:white;\"></i></span>',
                        handler : function(self, e){
                        	var images_view = Ext.getCmp('images-view');
                        	images_view.setMode(1);
                    	}
					},
					{
						xtype : 'button',
                        cls: 'proxima_button_customize',
                        width: 30,
                        text: '<span style=\"position:relative;top:1px;\" title=\"'+_text('MN00062')+'\"><i class=\"x-toolleft x-tool-list\" style=\"font-size:13px;color:white;\"></i></span>',
                        handler : function(self, e){
                        	var images_view = Ext.getCmp('images-view');
                        	images_view.setMode(2);
                    	}
					},
					{
						xtype : 'button',
                        cls: 'proxima_button_customize',
                        width: 30,
                        text: '<span style=\"position:relative;top:1px;\" title=\"'+_text('MN00061')+'\"><i class=\"x-toolleft x-tool-detail\" style=\"font-size:13px;color:white;\"></i></span>',
                        handler : function(self, e){
                        	var images_view = Ext.getCmp('images-view');
                        	images_view.setMode(3);
                    	}
					},
					{},
					'->',{
                        xtype : 'button',
                        cls: 'proxima_button_customize',
                        width: 30,
                        text: '<span style=\"position:relative;top:1px;\" title=\"'+_text('MN02480')+'\"><i class=\"fa fa-file-image-o\" style=\"font-size:13px;color:white;\"></i></span>',
                        handler : function(self, e){
                        	var images_view = Ext.getCmp('images-view');
                        	var number_selected = images_view.getSelectedRecords().length;
							if(number_selected > 0){
								if(number_selected == 1){
									var selected_content = images_view.getSelectedRecords();
									var thumb_content_id = selected_content[0].data.content_id;
									Ext.Msg.show({
										title: _text('MN00024'),
										msg: _text('MSG00208'),
										buttons: Ext.Msg.YESNO,
										fn: function(btnID, text, opt){
											if (btnID == 'yes')
											{
												Ext.Ajax.request({
													url: '/store/group/change_thumb_image_group.php',
													params:{
														content_id: <?=$content_id?>,
														thumb_content_id: thumb_content_id
													},
													callback: function(opt, success, res) {
														var images_view = Ext.getCmp('images-view');
														images_view.store.reload();
													}
												});
											}
										}
									});
								}else{
									Ext.Msg.alert(_text('MN00023'),_text('MSG00026'));
									return;
								}
							}else{
								Ext.Msg.alert(_text('MN00023'),_text('MSG00026'));
								return;
							}
                        }
                    },{
                    	xtype : 'button',
                        cls: 'proxima_button_customize',
                        width: 30,
                        text: '<span style=\"position:relative;top:1px;\" title=\"'+_text('MN00142')+'\"><i class=\"fa fa-download\" style=\"font-size:13px;color:white;\"></i></span>',
                        handler : function(self, e){

                        	var images_view = Ext.getCmp('images-view');
                        	var selected_content = images_view.getSelectedRecords();
                        	var number_selected = selected_content.length;
                        	if(number_selected >0){

								var list_content = [];
								Ext.each(selected_content, function(r, i, a){
									list_content.push(r.get('content_id'));
								});

	                        	var menu_f = new Ext.menu.Menu({
												cls: 'hideMenuIconSpace',
												items: [
													/* //CJ다운로드런처 사용시
													{
														text: '<i class=\"fa fa-download\" style=\"color: black;\"></i>'+_text('MN02518'),
														handler: function(self, e){
															//window.open('/store/group/download_file.php?content_id='+ Ext.encode(list_content) + '&download_mode=' + 0);
															var contentIds = [];
															for(var i=0; i < selected_content.length; i++){
																var contentId = selected_content[i].get('content_id');
																contentIds.push(contentId);
															}

															downloadContent(contentIds, 'original', false, '그룹 이미지 고해상도 다운로드', 'G');
														}
													},
													{
														text: '<i class=\"fa fa-download\" style=\"color: black;\"></i>'+_text('MN02519'),
														handler: function(self, e){
															//window.open('/store/group/download_file.php?content_id='+ Ext.encode(list_content) + '&download_mode=' + 1);

															var contentIds = [];
															for(var i=0; i < selected_content.length; i++){
																var contentId = selected_content[i].get('content_id');
																contentIds.push(contentId);
															}

															downloadContent(contentIds, 'proxy', false, '그룹 이미지 저해상도 다운로드', 'G');
														}
													}
													*/
													//http다운로드
													{
														text: '<i class=\"fa fa-download\" style=\"color: black;\"></i>'+_text('MN02518'),
														handler: function(self, e){
															window.open('/store/group/download_file.php?content_id='+ Ext.encode(list_content) + '&download_mode=' + 0);
														}
													},
													{
														text: '<i class=\"fa fa-download\" style=\"color: black;\"></i>'+_text('MN02519'),
														handler: function(self, e){
															window.open('/store/group/download_file.php?content_id='+ Ext.encode(list_content) + '&download_mode=' + 1);
														}
													}
												],
												listeners: {
													render: function(self){
													}
												}
											});
								var xyEvent = [e.browserEvent.x, e.browserEvent.y];
								menu_f.showAt(xyEvent);
                        	}else{
                        		Ext.Msg.alert(_text('MN00023'),_text('MSG02515'));
								return;
                        	}
                        	
                    	}
                	},{
                        xtype : 'button',
                        cls: 'proxima_button_customize',
                        width: 30,
                        text: '<span style=\"position:relative;top:1px;\" title=\"'+_text('MN02481')+'\"><i class=\"fa fa-trash-o\" style=\"font-size:13px;color:white;\"></i></span>',
                        <?php
							if (!checkAllowUdContentGrant($user_id, $ud_content_id, GRANT_CONTENT_DELETE)) {
						?>
						hidden: true,
						<?php } ?>
                        handler : function(self, e){
                        	var images_view = Ext.getCmp('images-view');
                        	var selected_content = images_view.getSelectedRecords();

                        	var has_master = false;
                        	var has_poster = false;
                        	var has_sub = false;
                        	Ext.each(selected_content, function(r, i, a){
                    			var current_content_id = r.get('content_id');
                    			var thumbnail_content_id = r.get('thumbnail_content_id');
                    			if(current_content_id == <?=$content_id?>){
                    				has_master = true;
                    			}else if(current_content_id == thumbnail_content_id){
                    				has_poster = true;
                    			}else{
                    				has_sub = true;
                    			}
                    		});

                    		if(has_master){
                    			Ext.Msg.alert(_text('MN00023'),_text('MSG02507'));
								return;
                    		}else if(has_poster){
                    			Ext.Msg.alert(_text('MN00023'),_text('MSG02508'));
								return;
                    		}else if(has_sub){
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
											var tm = Ext.getCmp('delete_reason').getValue();

											var rs = [];
											Ext.each(selected_content, function(r, i, a){
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

													var w = Ext.Msg.wait(_text('MSG00144'));

													Ext.Ajax.request({
														url: '/store/delete_contents.php',
														params: {
															action: 'delete',
															content_id: Ext.encode(rs)
														},
														callback: function(opts, success, response){
															w.hide();
															if(success){
																try{
																	var r = Ext.decode(response.responseText);
																	if(!r.success){
																		//Ext.Msg.alert('알림', r.msg);
																		Ext.Msg.alert( _text('MN00023'), r.msg);
																		return;
																	}
																	else
																	{
																		var images_view = Ext.getCmp('images-view');
																		images_view.store.reload();
																		return;
																	}
																}
																catch (e)
																{
																	Ext.Msg.alert(e['name'], e['message']);
																}
															}else{
																//>>Ext.Msg.alert('오류', response.statusText);
																Ext.Msg.alert(_text('MN00022'), response.statusText);
															}
														}
													});
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
                    }
					],
					listeners: {
						afterrender: function(self){
							Ext.Ajax.request({
								url: '/store/group/get_list_content_ui.php',
								params: {
									content_id: <?= $content_id ;?>
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
				};


			this.items = [{
				border: false,
				layout: 'border',
				split: true,

				items: [{
					layout: 'border',
					region: 'center',
					border: false,
					items: [{
						region: 'center',
						border: false,
						bodyStyle:'background-color:black;background-image:url("<?=addslashes($image_path)?>");background-position:center center;background-repeat:no-repeat;background-size:contain;text-align:center;',
						id : 'preview',
						xtype : 'panel',
						width: 900,
						minWidth: 480,
						minHeight: 300
						}<?php
							if($is_group == 'G') {
								echo "
								,{
										flex: 1,
										xtype: 'tabpanel',
										id: 'image_tabpanel',
										activeTab: 0,
										cls:'proxima_tabpanel_customize proxima_media_tabpanel',
										split: true,
										region: 'south',
										height:320,
										//layout: 'fit',
										//frame: true,
										border: false,
										items: [
											//group_list_panel
											group_sub_content											
										]
								}";
							}
							?>
					]
				},{
					region: 'east',
					xtype: 'panel',
					layout: 'border',
					id: 'left_side_panel',
					//bodyStyle: 'border-left:1px solid #d0d0d0;',
					border: false,
					width: 520,
					split: true,
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
					width: '50%',

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