<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');
?>

//var gridregwaitlist ={
//	xtype:'contentgrid',
//	id:'regwait_id',
//	title:'등록대기',
//
//	store: new Ext.data.JsonStore({
//
//		url: '/store/mypage/waitDB.php',
//		baseParams: {
//			start: 0,
//			limit: 20
//		},
//		root: 'data',
//		totalProperty: 'total',
//
//		fields: [
//			{name: 'title'},
//			{name: 'contentsType'},
//			{name: 'created_time', type: 'date', dateFormat: 'YmdHis'},
//			{name: 'status'},
//			{name: 'id'}
//		]
//	}),
//
//	<!--store.load({params:{start:0, limit:20}}),-->
//
//		columns: [
//				 new Ext.grid.RowNumberer(),
//				{header: '제목', dataIndex: 'title', align:'center',sortable:'true'},
//				{header: '콘텐츠종류', dataIndex: 'contentsType', align:'center',sortable:'true'},
//				{header: '등록 일시', dataIndex: 'created_time', align:'center', renderer: Ext.util.Format.dateRenderer('Y-m-d H:i:s'),sortable:'true'},
//				{header: '등록 상태', dataIndex: 'status', align:'center',sortable:'true'},
//				{header: 'id', dataIndex: 'id',hidden:true}
//			],
//
//		contextmenu: new Ext.menu.Menu({
//
//				items:[{
//					id: 'regist_id',
//					text: '등록 완료하기',
//					icon: '/led-icons/application_get.png'
//				}],
//
//				listeners:{
//
//					itemclick:function(item){
//
//						Ext.Msg.show({
//
//							title: '등록 확인',
//							msg: '등록을 완료하시겠습니까?',
//							minWidth: 100,
//							modal: true,
//							icon: Ext.MessageBox.QUESTION,
//							buttons: Ext.Msg.OKCANCEL,
//							fn: function(btnId){
//
//								if(btnId=='ok'){
//										var sel = regist_wait_grid.getSelectionModel();
//										var id = sel.getSelected().get('id');
//
//										Ext.Ajax.request({
//											url: 'wait_regist_update.php',
//											params:{
//												id: id
//											},
//
//											callback: function(options,success,response){
//												if(success){
//													Ext.Msg.alert('등록','등록완료 성공');
//													registWait_store.reload();
//												}
//												else{
//													Ext.Msg.alert('등록','등록완료 실패',response.statusText);
//												}
//											}
//										});
//								}
//
//								if(btnId=='cancel'){
//									Ext.Msg.alert('등록','등록이 취소되었습니다.');
//								}
//
//							}
//						});
//					}
//				}
//			}),
//
//			listeners:{
//				rowcontextmenu: function(self,rowIndex,e){
//
//					var cell = self.getSelectionModel();
//					if (!cell.isSelected(rowIndex)){
//						cell.selectRow(rowIndex);
//					}
//
//					e.stopEvent();
//					self.contextmenu.showAt(e.getXY());
//				}
//			},
//			viewConfig: {
//				forceFit: true
//			},
//			bbar : new Ext.PagingToolbar({
//						store: registWait_store,
//						pageSize: 20,
//						columnWidth: 1
//			})
//}