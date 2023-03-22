<?php
/*
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
$user_id = $_SESSION['user']['user_id'];
$user_name= $mdb->queryone("select name from member where user_id ='$user_id'");
*/
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<title>EBS DAS::my page</title>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<link rel="stylesheet" type="text/css" href="/ext/resources/css/ext-all.css" />
	<script type="text/javascript" src="/ext/adapter/ext/ext-base.js"></script>
	<script type="text/javascript" src="/ext/ext-all-debug.js"></script>

	<script type="text/javascript">
	Ext.onReady(function(){
	var myPageSize_upload = 5;
	
/////////////////////메세지 함수///////////////////////////////
	var msg = function(title, msg){
		Ext.Msg.show({
			title: title,
			msg: msg,
			minWidth: 100,
			modal: true,
			icon: Ext.Msg.INFO,
			buttons: Ext.Msg.OK
		});
	};
////////////////////////store/////////////////////////////////


	var upload_store = new Ext.data.JsonStore({//등록한영상 스토어
		url: '/php/mypage/uploadmedia.php',
		root: 'data',
		totalProperty: 'total',
		fields: [
			{name: 'title'},
			{name: 'created_time', type: 'date', dateFormat: 'YmdHis'}
		],
		listeners: {
			load: function(self){
			},
			beforeload: function(self, opts){
				self.baseParams = {
					limit: myPageSize_upload,
					start: 0
				}
				
			}
		}
	});
	upload_store.load({params:{start:0, limit:myPageSize_upload}});

/////////////////////////페이징바///////////////////////
	var upload_bbar = new Ext.PagingToolbar({
		store: upload_store,
		pageSize: myPageSize_upload,
		columnWidth: 1
	});//등록한영상 페이징


///////////////////////grid////////////////////////////////
	var upload_contents_grid = new Ext.grid.GridPanel({//등록한 영상 그리드
		title:'등록한 영상',
		flex: 1,
		frame: true,
		store: upload_store,	
		columnWidth: 1,
		columns: [
			{header: '영상 제목', dataIndex: 'title', align:'center',sortable:'true'},
			{header: '등록 날짜', dataIndex: 'created_time', align:'center', renderer: Ext.util.Format.dateRenderer('Y-m-d H:i:s'),sortable:'true'}
		],viewConfig: {
			forceFit: true
		},
		bbar : [upload_bbar]
	});

/////////////////////레이아웃패널///////////////////////////

	var upload_panel = new Ext.Panel({//등록한 영상 패널
		flex: 1.1,				
		layout:'fit',
		padding : 10,	
		items: [upload_contents_grid]
	});
	});

//////////////////////onReady//////////////////////////////
	/*
	Ext.onReady(function(){
		var panel = new Ext.Panel({
			id: 'mypage',
			
			frame: true,
			autoWidth: true,
			height: 850,
			padding : 10,
			collapsible: false,
			title: 'My page',
			layout: 'vbox',
			layoutConfig: {
				align: 'stretch',
				pack: 'start'
			},

			
			//monitorResize: true,
			items: [
				upload_panel
			]
		

		panel.render(document.body);
	});
	*/
	</script>
</head>
<body>
</body>
</html>