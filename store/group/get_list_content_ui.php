<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');
	$content_id = $_POST['content_id'];
	$content = $db->queryRow("SELECT * FROM BC_CONTENT WHERE CONTENT_ID =".$content_id);
	$bs_content_id = $content['bs_content_id'];
	$thumb_id = $content['thumbnail_content_id'];
	$ud_content_id = $content['ud_content_id'];

	$root_category_info = $db->queryRow("
		SELECT	C.*
		FROM	BC_CATEGORY C,
				BC_CATEGORY_MAPPING CM
		WHERE	C.CATEGORY_ID = CM.CATEGORY_ID
		AND		CM.UD_CONTENT_ID = ".$ud_content_id."
	");

	$root_category_id = $root_category_info['category_id'];
	$root_category_text = $root_category_info['category_title'];
?>

(function(){

return build();

	function build(){
		var store_images = new Ext.data.JsonStore({
			autoLoad: true,
			url: '/store/group/get_child_list.php',
			baseParams: {
				content_id: <?=$content_id?>,
				bs_content_id: <?=$bs_content_id?>
			},
			root: 'data',
			fields: [
				'content_id', 'title', 'bs_content_id', 'thumb', 'sys_ori_filename', 'ori_path', 'ori_media_id',
				'proxy_path', 'proxy_media_id', 'thumbnail_content_id','file_size_original'
			]
		});

		var dataview_images = new Ext.DataView({
		title: _text('MN00389'),
		id: 'images-view',
		store: store_images,
		tpl : '',
		mode: 1,
		setMode: function(av_mode){
			this.mode = av_mode;
			if(av_mode == 1){
				this.setTemplate(this.template.thumbnail_view);
			}else if(av_mode == 2){
				this.setTemplate(this.template.summary_view);
			}else if(av_mode == 3){
				this.setTemplate(this.template.list_view);
			}
		},
		getMode: function(){
			return this.mode;
		},
		setTemplate: function(template){
			this.tpl = template;
			this.refresh();
		},
		template:{
			thumbnail_view: new Ext.XTemplate(
				'<div class="template_content" style="overflow-y:auto; width: 100%;">',
				'<tpl for=".">',
					'<div class="thumb-wrap" style="display: inline-block;cursor: pointer;">',
						'<tpl if="(content_id == <?=$content_id?>)">',
							'<i class="fa fa-square fa-stack-1x icon_square_poster" style=""></i>',
							'<i class="fa fa-picture-o fa-stack-1x fa-inverse icon_file_poster" style="color:red;" title="Master"></i>',
						'</tpl>',
						'<tpl if="(content_id == thumbnail_content_id)">',
							'<i class="fa fa-square fa-stack-1x icon_square_poster" style=""></i>',
							'<i class="fa fa-picture-o fa-stack-1x fa-inverse icon_file_poster" style="" title="Poster"></i>',
						'</tpl>',
						'<div class="thumb"><img class="" src="<?=LOCAL_LOWRES_ROOT?>/{thumb}" ext:qtip="{title}" width="80px"></div>',
						'<div class="user_info_text_ellipsis group_image_content_title_text" style="width: 80px;">{title}</div>',
                    '</div>',
				'</tpl>',
				'</div>',
				'<div class="x-clear"></div>',
				{
					isEmpty: function(s){
						return Ext.isEmpty(s);
					}
				}
			),

			summary_view: new Ext.XTemplate(
				'<div class="template_content" style="overflow-y:auto; width: 100%;">',
				'<tpl for=".">',
					'<div class="thumb-wrap" style="display: inline-block;cursor: pointer;">',
						'<tpl if="(content_id == <?=$content_id?>)">',
							'<i class="fa fa-square fa-stack-1x icon_square_poster" style=""></i>',
							'<i class="fa fa-picture-o fa-stack-1x fa-inverse icon_file_poster" style="color:red;" title="Master"></i>',
						'</tpl>',
						'<tpl if="(content_id == thumbnail_content_id)">',
							'<i class="fa fa-square fa-stack-1x icon_square_poster" style=""></i>',
							'<i class="fa fa-picture-o fa-stack-1x fa-inverse icon_file_poster" style="" title="Poster"></i>',
						'</tpl>',
						'<div class="thumb" style="float: left;"><img class="" src="<?=LOCAL_LOWRES_ROOT?>/{thumb}" ext:qtip="{title}" width="80px"></div>',
						'<div class="metadata_content" style="float: left;">',
							'<div class="user_info_text_ellipsis group_image_content_title_text" style="width: 150px;">'+_text("MN00249")+': {title}</div>',
							'<div class="user_info_text_ellipsis" style="width: 150px;padding: 5px 0px 5px 3px;">'+_text("MN00301")+': {file_size_original}</div>',
						'</div>',
                    '</div>',
				'</tpl>',
				'</div>',
				'<div class="x-clear"></div>',
				{
					isEmpty: function(s){
						return Ext.isEmpty(s);
					}
				}
			),

			list_view: new Ext.XTemplate(
				'<div class="template_content" style="overflow-y:auto; width: 100%;">',
					'<div class="list_header" style="display: block;height: 30px;font-weight: bold;background-color: #333337; color:#FFFFFF;">',
						'<div style="float:left;width:19%;padding-left:8px;padding-top:8px;">'+_text("MN02325")+'</div>',
						'<div style="float:left;width:19%;padding-left:8px;padding-top:8px;"></div>',
						'<div style="float:left;width:39%;padding-top:8px;">'+_text("MN00249")+'</div>',
						'<div style="float:left;width:20%;padding-top:8px;">'+_text("MN00301")+'</div>',
					'</div>',
				'<tpl for=".">',
					'<div class="thumb-wrap" style="display: inline-block;cursor: pointer;width: 98%;margin: 0px;">',
						'<div class="image-preview" style="float:left;width:10%">',
							'<tpl if="(content_id == <?=$content_id?>)">',
								'<i class="fa fa-square fa-stack-1x icon_square_poster" style=""></i>',
								'<i class="fa fa-picture-o fa-stack-1x fa-inverse icon_file_poster" style="color:red;" title="Master"></i>',
							'</tpl>',
							'<tpl if="(content_id == thumbnail_content_id)">',
								'<i class="fa fa-square fa-stack-1x icon_square_poster" style=""></i>',
								'<i class="fa fa-picture-o fa-stack-1x fa-inverse icon_file_poster" style="" title="Poster"></i>',
							'</tpl>',
						'</div>',
						'<div class="user_info_text_ellipsis" style="width:20%; float:right">{file_size_original}</div>',
						'<div class="user_info_text_ellipsis" style="width:40%; float:right">{title}</div>',
						'<div class="user_info_text_ellipsis" style="width:20%; float:right"><img class="" src="<?=LOCAL_LOWRES_ROOT?>/{thumb}" width="45px" height="30px"></div>',
                    '</div>',
				'</tpl>',
				'</div>',
				'<div class="x-clear"></div>',
				{
					isEmpty: function(s){
						return Ext.isEmpty(s);
					}
				}
			)
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
			},
			click: function(self, index, node, e){

				var image_current = self.getStore().getAt(index);
				var path_preview = '<?=LOCAL_LOWRES_ROOT?>/'+image_current.data.proxy_path;
				if( Ext.getCmp('preview').el.dom.firstChild.firstChild ){
					Ext.getCmp('preview').el.dom.firstChild.firstChild.style.backgroundImage = 'url('+path_preview+')';
				}

				var content_id = image_current.data.content_id;
				var bs_content_id = image_current.data.bs_content_id;
/*
				// get User Metadata
				var basic_infor_tab = Ext.getCmp('user_metadata_'+<?=$ud_content_id?>);
				basic_infor_tab.removeAll();
				
				var rs_content = [];
				rs_content.push(content_id);
               	
                Ext.Ajax.request({
                    url: '/store/group/get_user_metadata.php',
                    params: {
                    	content_ids: Ext.encode(rs_content),
                    	ud_content_id: <?=$ud_content_id?>
                    },
                    callback: function(options, success, response) {
                    	var res = Ext.decode(response.responseText);
                    	
                    	if(res.success) {
                    		var content_data = res.data[content_id];
                    		var title_category = content_data[0];

                    		basic_infor_tab.add(
                    			{xtype: 'hidden', name: 'k_content_id', value: content_id}
								,{xtype: 'hidden', name: 'k_ud_content_id', value: '<?=$ud_content_id?>'}
								,{xtype: 'textfield', allowBlank: false, autoCreate: {tag: 'input', type: 'text', autocomplete: 'off', maxlength: '150'}, fieldLabel: '&nbsp;&nbsp;&nbsp;Title', name: 'k_title',
									listeners:{
										render: function(self){
											self.setValue(title_category['title']);
										}
									}	
								}
								,{
									xtype: 'treecombo',
									flex: 1,
									id: 'category',
									fieldLabel: _text('MN00387'),
									name: 'c_category_id',
									value: title_category['category_full_path'],
									pathSeparator: ' > ',
									rootVisible: true,
									loader: new Ext.tree.TreeLoader({
										url: '/store/get_categories.php',
										baseParams: {
											action: 'get-folders',
											path: title_category['category_full_path'],
											ud_content_tab: <?=$ud_content_id ?>
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
													for(i=1; i < path.length; i++) {
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
										id: '<?=$root_category_id?>',
										text: '<?=$root_category_text?>',
										expanded: true
									}),
									listeners: {
										select: function(self, node) {
											Ext.getCmp('category').setValue(node.id);
										}
									}
								}
                    		);
                    		//user_metadata
                			for(var i = 1; i < content_data.length;i++){
                				if(content_data[i].is_show == '1'){
                					if(content_data[i].usr_meta_field_type == 'textarea'){
                						if(content_data[i].is_required == '0'){
		                					basic_infor_tab.add(
			                					{
			                						xtype: content_data[i].usr_meta_field_type,
			                						fieldLabel: content_data[i].usr_meta_field_title,
			                						value: content_data[i].value,
			                						name: content_data[i].usr_meta_field_id,
			                						height: 200, 
			                						maxLength: 4000,
			                						regexText: _text('MSG02065')
			                					}
			                				);
		                				}else if(content_data[i].is_required == '1'){
		                					basic_infor_tab.add(
			                					{
			                						xtype: content_data[i].usr_meta_field_type,
			                						fieldLabel: '<font color=red>*&nbsp;</font>'+content_data[i].usr_meta_field_title,
			                						value: content_data[i].value,
			                						name: content_data[i].usr_meta_field_id,
			                						allowBlank: false,
			                						height: 200, 
			                						maxLength: 4000,
			                						regexText: _text('MSG02065')
			                						
			                					}
			                				);
		                				}
	                				}else{
	                					if(content_data[i].is_required == '0'){
		                					basic_infor_tab.add(
			                					{
			                						xtype: content_data[i].usr_meta_field_type,
			                						fieldLabel: content_data[i].usr_meta_field_title,
			                						value: content_data[i].value,
			                						name: content_data[i].usr_meta_field_id,
			                						
			                					}
			                				);
		                				}else if(content_data[i].is_required == '1'){
		                					basic_infor_tab.add(
			                					{
			                						xtype: content_data[i].usr_meta_field_type,
			                						fieldLabel: '<font color=red>*&nbsp;</font>'+content_data[i].usr_meta_field_title,
			                						value: content_data[i].value,
			                						name: content_data[i].usr_meta_field_id,
			                						allowBlank: false
			                						
			                					}
			                				);
		                				}
	                				}
                				}
                			}
                			basic_infor_tab.add(
                				{xtype: 'textfield', disabled: true, fieldLabel: _text('MN02149'), value: title_category['reg_user_id']}
								, {xtype: 'textfield', disabled: true, fieldLabel: _text('MN02150'), value: title_category['user_nm']}
								, {xtype: 'textfield', disabled: true, fieldLabel: _text('MN02217'), value: title_category['created_date_time']}
                			);
                    		basic_infor_tab.doLayout();
                    	} else {
                    		Ext.Msg.alert('Error', res.msg);
                    	}
                    	
                	}
                });
                
				// End User Metadata
*/
				// get System Metadata
				Ext.Ajax.request({
					url: '/store/group/get_sysmeta.php',
					params: {
						content_id: content_id,
						bs_content_id: bs_content_id
					},
					callback: function(opts, success, response) {
						if(success) {
							try {
								var r = Ext.decode(response.responseText);
								if(r.success === false) {
									Ext.Msg.alert( _text('MN00022'), r.msg);
								} else {
									var media_info = Ext.getCmp('detail_panel').find('name', 'media_info_tab');
									if(media_info[1]){
										media_info[1].getForm().loadRecord(r);
									}
								}
							} catch(e) {
								Ext.Msg.alert(e['name'], e['message']);
							}
						}else{
							Ext.Msg.alert( _text('MN00022'), opts.url+'<br />'+response.statusText+'('+response.status+')');
						}
					}
				});
				// Media files in the media list information, the store load
				var media_list = Ext.getCmp('media_list');
				if(media_list){
					media_list.getStore().load({
						params: {
							content_id: content_id
						}
					});
				}
			
			},
			dbclick: function(self, idx, n, e){
				
			},
			contextmenu: function(self, index, node, dataViewEvent){
				dataViewEvent.stopEvent();
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
					menu_f.showAt(dataViewEvent.getXY());
            	}else{
            		Ext.Msg.alert(_text('MN00023'),_text('MSG02515'));
					return;
            	}
			}
		},
	});

		store_images.on( 'load', function( store, records, options ) {
			var active_mode = dataview_images.getMode();

			if(active_mode == 1){
				dataview_images.setTemplate(dataview_images.template.thumbnail_view);
			}else if(active_mode == 2){
				dataview_images.setTemplate(dataview_images.template.summary_view);
			}else if(active_mode == 3){
				dataview_images.setTemplate(dataview_images.template.list_view);
			}else{
			}
			
		});
		return dataview_images;
	}

})()