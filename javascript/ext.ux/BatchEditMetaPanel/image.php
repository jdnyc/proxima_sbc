(function(){
	Ext.ns('Ariel');

	Ariel.BatchEditMetaWindow = Ext.extend(Ext.Window, {
		id: 'batchEditMetaWin',
		title: _text('MN02479'),
		width: Ext.getBody().getViewSize().width*0.9,
		height: Ext.getBody().getViewSize().height*0.9,
		editing: false,
		modal: true,
		layout: 'fit',
		
		initComponent: function(config){
			Ext.apply(this, config || {});
			
			var image_data_store = new Ext.data.JsonStore({
				url: '/store/batch_edit_meta/get_user_metadata.php',
				autoLoad: false,
				root: 'data',
				fields: [
					'content_id', 'title', 'path', 'virtual_path'
				],
				baseParams: {
					content_ids: Ext.encode(<?= $content_ids ?>),
					bs_content_id: <?=$bs_content_id?>,
					job: 'get_list_image_data'
				}
			});
			
			this.items = [
				{
					border: false,
					layout: 'border',
					split: true,
					items: 
					[
						{
							layout: 'border',
							region: 'center',
							border: false,
							width: '33%',
							items: 
							[
								{
									region: 'center',
									border: false,
									bodyStyle:'background-color:black;background-image:url("");background-position:center center;background-repeat:no-repeat;background-size:contain;text-align:center;',
									id : 'preview_content',
									xtype : 'panel',
								},{
									//List of title
									flex: 1,
									region: 'south',
									xtype: 'panel',
									layout: 'fit',
									border: false,
									height: Ext.getBody().getViewSize().height*0.9/2 -25,
									id: 'list_of_title_content',
									items: [
										{	
											xtype: 'grid',
											id : 'list_content_grid',
											cls: 'proxima_grid_header proxima_customize_grid_for_group',
											border: false,
											flex: 1,
											stripeRows: true,
											enableColumnMove: false,
											store: image_data_store,
											viewConfig: {
												forceFit: true
											},
											colModel: new Ext.grid.ColumnModel({
												columns: [
													new Ext.grid.RowNumberer({width: 30}),
													{header: _text('MN00249'), dataIndex: 'title'},
												]
											}),
											sm: new Ext.grid.RowSelectionModel({
												singleSelect: true,
												listeners: {
													selectionchange: function(self) {
													},
													rowselect: function(selModel, rowIndex, e) {
														var self = selModel.grid;
														var record = selModel.getSelected();
														var content_id = record.get('content_id');
														var virtual_path = record.get('virtual_path');
														var proxy_path = record.get('path');

														var path_preview = virtual_path+'/'+proxy_path;

														if( Ext.getCmp('preview_content').el.dom.firstChild.firstChild ){
															Ext.getCmp('preview_content').el.dom.firstChild.firstChild.style.backgroundImage = "url('"+path_preview+"')";
														}

														var preview_metadata_panel = Ext.getCmp('preview_metadata_panel');
								                        Ext.Ajax.request({
															url: '/store/batch_edit_meta/get_user_metadata.php',
															params: {
																bs_content_id: <?=$bs_content_id?>,
																ud_content_id: <?=$ud_content_id?>,
																job: 'get_user_meta_data_preview',
																content_id: content_id
															},
															callback: function(opts, success, response){
																if(success){
																	try {
																		var r = Ext.decode(response.responseText);
																		preview_metadata_panel.removeAll();
																		preview_metadata_panel.add(r);
																		preview_metadata_panel.doLayout();
																		preview_metadata_panel.activate(0);
																	}catch(e){
																		Ext.Msg.alert('오류', e+'<br />'+response.responseText);
																	}
																}else{
																	Ext.Msg.alert('오류', opts.url+'<br />'+response.statusText+'('+response.status+')');
																}
															}
														});
													}
												}
											}),
											listeners: {
												render: function(grid) {
													grid.getStore().on('load', function() {
														grid.getSelectionModel().selectRow(0);
													});
													grid.getStore().load();
												}
											}
										}
									]
								}	
							]
						},{
							//right part
							region: 'east',
							xtype: 'panel',
							layout: 'border',
							id: 'left_side_panel_metadata',
							width: '67%',
							bodyStyle: 'border-left:1px solid #d0d0d0;',
							border: false,
							items: 
							[
								{
									region: 'center',
									xtype: 'panel',
									title: _text('MN02524'),
									cls: 'proxima_panel_title_customize',
									border: false,
									width: '50%',
									items:[
										{
											region: 'center',
											id: 'preview_metadata_panel',
											cls: 'proxima_tabpanel_customize',
											xtype: 'tabpanel',
											border: false,
											height: Ext.getBody().getViewSize().height*0.9-60,
											split: false,
											items: [],
											listeners: {
											}
										}
									]
								},
								{
									region: 'east',
									xtype: 'panel',
									title: _text('MN02525'),
									cls: 'proxima_panel_title_customize',
									bodyStyle: 'border-left:1px solid #d0d0d0;',
									border: false,
									width: '50%',
									items:[
										{
											region: 'center',
											id: 'edit_metadata_panel',
		                    				cls: 'proxima_tabpanel_customize',
											xtype: 'tabpanel',
											border: false,
											split: false,
		                    				height: Ext.getBody().getViewSize().height*0.9-60,
		                    				items: [],
		                    				listeners:{
		                    					afterrender: function(self){
							                        var list_content = <?=$content_ids?>;
							                        Ext.Ajax.request({
															url: '/store/batch_edit_meta/get_user_metadata.php',
															params: {
																bs_content_id: <?=$bs_content_id?>,
																ud_content_id: <?=$ud_content_id?>,
																job: 'get_user_meta_data_layout',
																content_id: list_content[0]
															},
															callback: function(opts, success, response){
																if(success){
																	try {
																		var r = Ext.decode(response.responseText);
																		self.removeAll();
																		self.add(r);
																		self.doLayout();
																		self.activate(0);
																	}catch(e){
																		//Ext.Msg.alert('오류', e+'<br />'+response.responseText);
																	}
																}else{
																	//Ext.Msg.alert('오류', opts.url+'<br />'+response.statusText+'('+response.status+')');
																}
															}
														});
		                    					},
		                    					beforetabchange: function(self){
		                    						
		                    						var active_tab = self.getActiveTab();
		                    						
		                    						if(!Ext.isEmpty(active_tab)){
		                    							var form_values = active_tab.getForm().getValues();

		                    							var is_on = false;
		                    							for(var propertyName in form_values) {
														   if(form_values[propertyName] == 'on'){
																is_on = true;
																break;
															}
														}

														if(is_on){
															Ext.Msg.show({
																icon: Ext.Msg.QUESTION,
																title: _text('MN00003'),
																msg: 'aaaaaaaa',
																buttons: Ext.Msg.YESNO,
																fn: function(btnId, text, opts){
																	if(btnId == 'no') return;
																}
															});
														}
		                    						}
		                    						
		                    					}
		                    				}
										}
									]
								}
							]
						}
					]
				}
			];

			Ariel.BatchEditMetaWindow.superclass.initComponent.call(this);
		},

		listeners: {
			render: function(self){
				Ext.getCmp('grid_thumb_slider').hide();
				Ext.getCmp('grid_summary_slider').hide();
			},
			close: function(self){
				Ext.getCmp('tab_warp').getActiveTab().get(0).getStore().reload();
				Ext.getCmp('grid_thumb_slider').show();
				Ext.getCmp('grid_summary_slider').show();
			}
		},
	});
	new Ariel.BatchEditMetaWindow().show();
})()