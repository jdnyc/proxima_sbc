<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/lang.php');
fn_checkAuthPermission($_SESSION);

?>

(function(){
	var myMask = new Ext.LoadMask(Ext.getBody(), {msg:"loading..."});

	var treegrid = {
		xtype: 'treegrid',
		border: false,
		cls: 'proxima_customize_gridtree',
		layout: 'fit',
		id: 'content_category_grid',
		enableDD: false,
		//columnResize : true,
		enableSort: false,
		viewConfig:{
			forceFit:true
		},
		selModel: new Ext.tree.MultiSelectionModel({
		}),
		columns:[
			//{ header: "No", dataIndex: 'no' , width: 70 ,sortType: 'asInt', hidden: true },
			//{ header: "sort", dataIndex: 'sort' , width: 150, hidden: true },
			//{ header: "Category ID", dataIndex: 'category_id' , width: 150, hidden: true },
			{ header: _text('MN00271'), dataIndex: 'title' , width: 250 },
			{ header: _text('MN02165'), dataIndex: 'contents_cnt' , width: 150, align: 'right' },
			{ header: _text('MN02365'), dataIndex: 'original_total_size' , width: 150, align: 'right' },
			{ header: _text('MN02366'), dataIndex: 'proxy_total_size' , width: 150, align: 'right' },
			{ header: _text('MN02367'), dataIndex: 'last_regist_date' , width: 150, align: 'center' }
		],
		listeners: {
			click: function(node, e){
				if ( Ext.isEmpty(node) || node.attributes.isNew ) return;

				Ext.getCmp('content_category_grid').getLoader().baseParams.beforePath = node.getPath();
			}
		},
		loader: new Ext.tree.TreeLoader({
			baseParams: {
			},
			listeners: {
				beforeload: function( self, node, response ){
					myMask.show();
				},
				load: function( self,  node, response ){
					myMask.hide();
					var content_category_grid_element = document.getElementById('content_category_grid').getElementsByClassName('x-treegrid-col');
					for(var i = 0; i<content_category_grid_element.length; i++){
						var node_i = content_category_grid_element[i];
						if(node_i.childNodes.length == 4){
							node_i.className = 'grid_tree_remove_border x-treegrid-col';
						}
					}
				}
			}
			,dataUrl: '/pages/statistics_new/content_store.php',
		})
	};

	return {
		xtype: 'panel',
		layout: 'fit',
		title: '<span class="user_span"><span class="icon_title"><i class=""></i></span><span class="main_title_header">'+_text('MN00321')+'</span></span>',
		cls: 'grid_title_customize',
		border: false,
		tbar: [{
			//icon: '/led-icons/arrow_refresh.png',
			//text: "<?=_text('MN00390')?>",
			cls: 'proxima_button_customize',
			width: 30,
			text: '<span style="position:relative;top:1px;" title="'+_text('MN00390')+'"><i class="fa fa-refresh" style="font-size:13px;color:white;"></i></span>',
			handler: function(){
				Ext.getCmp('content_category_grid').getLoader().load( Ext.getCmp('content_category_grid').getRootNode() );
			}
		},{
			xtype : 'button',
			//text : '엑셀출력',
			//icon : '/led-icons/doc_excel_table.png',
			cls: 'proxima_button_customize',
			width: 30,
			text: '<span style="position:relative;top:1px;" title="'+_text('MN02359')+'"><i class="fa fa-file-excel-o" style="font-size:13px;color:white;"></i></span>',
			handler : function(self, e){
				var grid = Ext.getCmp('content_category_grid');
				if(grid.root.childNodes.length == 0 )
				{
					Ext.Msg.alert( _text('MN00023'), _text('MSG02051'));//'출력하실 내용이 없습니다.'
					return;
				}
				else
				{
					excelData('program', '/pages/statistics_new/content_store.php', grid.columns, '', '', '');
				}
			}
		}],
		items:[treegrid]
	}
})()