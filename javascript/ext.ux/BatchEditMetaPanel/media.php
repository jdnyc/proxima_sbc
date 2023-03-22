<?php
$show_thumb_grid = true;
?>
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
			
			var data_store = new Ext.data.JsonStore({
				url: '/store/batch_edit_meta/get_user_metadata.php',
				autoLoad: false,
				root: 'data',
				fields: [
					'content_id', 'title', 'path', 'virtual_path'
				],
				baseParams: {
					content_ids: Ext.encode(<?= $content_ids ?>),
					bs_content_id: <?=$bs_content_id?>,
					job: 'get_list_movie_data'
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
									bodyStyle:'background-color:black;',
									html: '<video id="player3" class="vjs-skin-twitchy video-js vjs-big-play-centered" preload="auto" controls style="width:100%;height:100%;" data-setup=\'{ "inactivityTimeout": 0, "playbackRates": [0.5, 1, 1.5, 2, 3, 4, 8] }\'><source src="" type="video/mp4"></video>',
									id : 'preview_content',
									xtype : 'panel',
									listeners: {
										afterrender: function(self){
											var frame_rate = 29.97;
											videojs('player3').ready(function(){
												var timer;
												var playbackRates = JSON.parse(document.getElementById("player3").getAttribute('data-setup')).playbackRates;
												var videojs_player = this,
												controlBar;

												var review_btn = document.createElement('div');
												review_btn.id = 'review_btn';
												review_btn.className = 'vjs-control-custom';
												var review_text = document.createElement('span');
												review_text.className = 'fa fa-lg fa-backward';
												review_btn.appendChild(review_text);
												review_btn.title = _text('MN02425');
												review_btn.onclick = function () {
													
													var curent_rate = videojs_player.playbackRate();
													if (curent_rate > 1) {
														videojs_player.playbackRate(1);
													} else {
														for (var i = 0; i < playbackRates.length; i++) {
															if (playbackRates[i] == curent_rate && i != 0){
																videojs_player.playbackRate(playbackRates[i-1]);
															}
														}	
													}
												};

												var frame_back_btn = document.createElement('div');
												frame_back_btn.id = 'frame_back_btn';
												frame_back_btn.className = 'vjs-control-custom';
												var frame_back_text = document.createElement('span');
												frame_back_text.className = 'fa fa-lg fa-step-backward';
												frame_back_btn.appendChild(frame_back_text);
												frame_back_btn.title = _text('MN02426');
												frame_back_btn.onclick = function () {
													var cur_time = videojs_player.currentTime();
													videojs_player.currentTime(cur_time - 1/frame_rate);
												};

												var frame_next_btn = document.createElement('div');
												frame_next_btn.id = 'frame_next_btn';
												frame_next_btn.className = 'vjs-control-custom';
												var frame_next_text = document.createElement('span');
												frame_next_text.className = 'fa fa-lg fa-step-forward';
												frame_next_btn.appendChild(frame_next_text);
												frame_next_btn.title = _text('MN02429');
												frame_next_btn.onclick = function () {
													var cur_time = videojs_player.currentTime();
													videojs_player.currentTime(cur_time + 1/frame_rate);
												};

												var fast_forward_btn = document.createElement('div');
												fast_forward_btn.id = 'fast_forward_btn';
												fast_forward_btn.className = 'vjs-control-custom';
												var fast_forward_text = document.createElement('span');
												fast_forward_text.className = 'fa fa-lg fa-forward';
												fast_forward_btn.appendChild(fast_forward_text);
												fast_forward_btn.title = _text('MN02430');
												fast_forward_btn.onclick = function () {
													var curent_rate = videojs_player.playbackRate();
													if (curent_rate < 1) {
														videojs_player.playbackRate(1);
													} else {
														for (var i = 0; i < playbackRates.length; i++) {
															if (playbackRates[i] == curent_rate && i != playbackRates.length){
																videojs_player.playbackRate(playbackRates[i+1]);
															}
														}
													}
												};

												var space_div = document.createElement('div');
												space_div.className = 'vjs-control-space-custom';

												// Get control bar and insert before elements
												controlBar = document.getElementsByClassName('vjs-custom-control-spacer')[0];
												var remaining_time = document.getElementsByClassName('vjs-remaining-time')[0];
												remaining_time.style.display = "none";

												var insertBeforeNode = document.getElementsByClassName('vjs-volume-menu-button')[0];
												var play_btn = document.getElementsByClassName('vjs-play-control')[0];
												// Insert the icon div in proper location
												controlBar.appendChild(frame_next_btn);
												controlBar.insertBefore(play_btn,frame_next_btn);
												controlBar.insertBefore(frame_back_btn,play_btn);
												videojs_player.hotkeys({
													volumeStep: 0.1,
													seekStep: 1/frame_rate,
												});
												/*
												videojs_player.on('loadedmetadata', function(){
													this.bigPlayButton.hide();
												});
												*/

												videojs_player.on('play', function() {
													this.bigPlayButton.hide();
												});

												videojs_player.on('pause', function() {
													this.bigPlayButton.show();
												});
											});
											var videoplayer = document.getElementById("player3");
											var player3 = videojs(videoplayer, {}, function(){});

											if (videoplayer.addEventListener) {
												videoplayer.addEventListener('contextmenu', function(e) {
													e.preventDefault();
												}, false);
											} else {
												videoplayer.attachEvent('oncontextmenu', function() {
													window.event.returnValue = false;
												});
											}
										}
									}
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
											store: data_store,
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
														var path_proxy = virtual_path+'/'+proxy_path;

														videojs('player3').src(path_proxy);

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
											split: false,
											height: Ext.getBody().getViewSize().height*0.9-60,
											items: [],
											listeners: {
											}
										}
									]
								},
								{
									region: 'east',
									xtype: 'panel',
									bodyStyle: 'border-left:1px solid #d0d0d0;',
									title: _text('MN02525'),
									cls: 'proxima_panel_title_customize',
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
				self.destroy();
			}
		},
	});
	new Ariel.BatchEditMetaWindow().show();
})()