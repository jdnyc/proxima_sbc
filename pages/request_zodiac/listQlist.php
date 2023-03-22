<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');
fn_checkAuthPermission($_SESSION);
?>
{
	xtype : 'panel',
	id: 'news_list_rundow',
	layout : { type: 'vbox',  align: 'stretch' },
	tbar:[
		{
			xtype: 'displayfield',
			width : 90,
			value: '<div align="right" style="margin-top:3px;">'+_text('MN00180')+'&nbsp;</div>'//방송일자
		},{
			xtype : 'button',
			//text: '하루 전날',
			//text : '<span style="position:relative;top:1px;"><i class="fa fa-chevron-left" style="font-size:13px;"></i></span>',
			//icon: '/led-icons/day_before.png',
			cls: 'proxima_button_customize',
			width: 30,
			height: 32,
			text: '<span style="position:relative;top:1px;" title="'+_text('MN02375')+'"><i class="fa fa-chevron-left" style="font-size:13px;color:white;"></i></span>',
			handler: function(){
				this.ownerCt.get(2).setValue(this.ownerCt.get(2).getValue().add(Date.DAY, -1).format('Y-m-d'));
			}
		},{
			xtype: 'datefield',
			id: 'broad_ymd',
			name : 'broad_ymd',
			width : 95,
			format: 'Y-m-d',
			value : new Date()
		},{
			xtype : 'button',
			//text: '하루 다음날',
			//text : '<span style="position:relative;top:1px;"><i class="fa fa-chevron-right" style="font-size:13px;"></i></span>',
			//icon: '/led-icons/day_after.png',
			cls: 'proxima_button_customize',
			width: 30,
			height: 32,
			text: '<span style="position:relative;top:1px;" title="'+_text('MN02376')+'"><i class="fa fa-chevron-right" style="font-size:13px;color:white;"></i></span>',
			handler: function(){
				this.ownerCt.get(2).setValue(this.ownerCt.get(2).getValue().add(Date.DAY, 1).format('Y-m-d'));
			}
		},{
			xtype: 'displayfield',
			width : 70,
			value: '<div align="right" style="margin-top:3px;">'+_text('MN00303')+'&nbsp;</div>'//프로그램명
		},{
			xtype: 'textfield',
			id: 'pgm_nm',
			name : 'pgm_nm',
			width : 207
		},{
			xtype: 'button',
			cls: 'proxima_button_customize',
			width : 30,
			height: 32,
			//icon: '/led-icons/find.png',
			text :  '<span style="position:relative;" title="'+_text('MN00059')+'"><i class="fa fa-search" style="font-size:13px;color:white;"></i></span>',//조회
			handler: function(self, e){
				var search_text = new Object();
				search_text.broad_ymd = Ext.getCmp('broad_ymd').getValue().format('Y-m-d').trim();
				search_text.pgm_nm = Ext.getCmp('pgm_nm').getValue();
				
				Ext.getCmp('grid_program').store.reload({
					params:{
						action : 'list_program',
						search : Ext.encode(search_text)
					}
				});

				Ext.getCmp('grid_article_q').store.removeAll();
				Ext.getCmp('grid_detail_q').store.removeAll();
			}
		}
		/*
		{
			xtype: 'form',
			id : 'search_form_q',
			frame: false,
			padding: 10,
			region : 'north',
			flex : 1,
			height : 60,
			border: false,
			labelWidth: 1,
			style: {
					paddingTop: '3px',
					background : '#FFFFFF'
				},
			defaults: {
				//labelStyle: 'text-align:center;',
				//anchor: '95%'
			},
			autoScroll: true,
			items:[{
				xtype: 'compositefield',
				style: {
					background : '#FFFFFF'
				},
				items:[{
					xtype: 'displayfield',
					width : 90,
					value: '<div align="right" style="margin-top:3px;">'+_text('MN00180')+'&nbsp;</div>'//방송일자
				},{
					xtype : 'button',
					id: 'news_rundown_prev_btn',
					//text: '하루 전날',
					//text : '<span style="position:relative;top:1px;"><i class="fa fa-chevron-left" style="font-size:13px;"></i></span>',
					//icon: '/led-icons/day_before.png',
					cls: 'proxima_button_customize',
					width: 30,
					text: '<span style="position:relative;top:1px;" title="'+_text('MN02375')+'"><i class="fa fa-chevron-left" style="font-size:13px;color:white;"></i></span>',
					handler: function(){
						this.ownerCt.get(2).setValue(this.ownerCt.get(2).getValue().add(Date.DAY, -1).format('Y-m-d'));
					}
				},{
					xtype: 'datefield',
					name : 'broad_ymd',
					width : 95,
					format: 'Y-m-d',
					value : new Date()
				},{
					xtype : 'button',
					id: 'news_rundown_next_btn',
					//text: '하루 다음날',
					//text : '<span style="position:relative;top:1px;"><i class="fa fa-chevron-right" style="font-size:13px;"></i></span>',
					//icon: '/led-icons/day_after.png',
					cls: 'proxima_button_customize',
					width: 30,
					text: '<span style="position:relative;top:1px;" title="'+_text('MN02376')+'"><i class="fa fa-chevron-right" style="font-size:13px;color:white;"></i></span>',
					handler: function(){
						this.ownerCt.get(2).setValue(this.ownerCt.get(2).getValue().add(Date.DAY, 1).format('Y-m-d'));
					}
				},{
					xtype: 'displayfield',
					width : 70,
					value: '<div align="right" style="margin-top:3px;">'+_text('MN00303')+'&nbsp;</div>'//프로그램명
				},{
					xtype: 'textfield',
					name : 'pgm_nm',
					width : 207
				},{
					xtype: 'button',
					id: 'news_rundown_search_btn',
					width : 30,
					//icon: '/led-icons/find.png',
					text :  '<span style="position:relative;" title="'+_text('MN00059')+'"><i class="fa fa-search" style="font-size:13px;color:white;"></i></span>',//조회
					handler: function(self, e){
						Ext.getCmp('grid_program').store.reload({
							params:{
								action : 'list_program',
								search : Ext.encode(Ext.getCmp('search_form_q').getForm().getValues())
							}
						});
					}
				}]
			}]
		}
		
		*/
	],
	items : [{
		 xtype: 'tab_article',
		 id: 'grid_program',
		 flex : 2,
		 region : 'center',
		 border: false,
		 gridtype : 'listProgram'
	},{
		 xtype: 'tab_article',
		 id: 'grid_article_q',
		 flex : 3,
		 region : 'center',
		 border: false,
		 gridtype : 'listArticle'
	},{
		 xtype: 'tab_article',
		 region : 'south',
		 id: 'grid_detail_q',
		 border: false,
		 flex : 3,
		 //height: 120,
		 gridtype : 'listImage'
	}]
}