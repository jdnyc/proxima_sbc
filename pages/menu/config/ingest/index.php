<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/lib.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');

checkLogin();//로그인 체크
checkNewPageAllowGrant();//권한 체크

$user_id = $_SESSION['user']['user_id'];
$member_id=$db->queryOne("select member_id from member where user_id ='$user_id'");

$meta_tables = $db->queryAll("select * from meta_table where content_type_id='506' order by sort");

$meta_table_body=array();
foreach($meta_tables as $meta_table)
{
	array_push($meta_table_body,"{ id: '".$meta_table['meta_table_id']."', title: '".$meta_table['name']."' }");
}

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<title>EBS DAS::인제스트 요청</title>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<link rel="stylesheet" type="text/css" href="/ext/resources/css/ext-all-das.css" rel="stylesheet" />
    <link rel="stylesheet" type="text/css" href="/ext/examples/ux/treegrid/treegrid.css" rel="stylesheet" />
	<style type="text/css">
		.progressing{ background-color:#FFFFBB; }
		.complete{ background-color:#8bd674; }
	 </style>


	<script type="text/javascript" src="/ext/adapter/ext/ext-base-debug.js"></script>
	<script type="text/javascript" src="/ext/ext-all-debug.js"></script>
	<!-- ////////트리 그리드 선언///////  -->
	<script type="text/javascript" src="/ext-3.3.0/examples/ux/treegrid/TreeGridSorter.js"></script>
	<script type="text/javascript" src="/ext/examples/ux/treegrid/TreeGridColumnResizer.js"></script>
	<script type="text/javascript" src="/ext/examples/ux/treegrid/TreeGridNodeUI.js"></script>
	<script type="text/javascript" src="/ext/examples/ux/treegrid/TreeGridLoader.js"></script>
	<script type="text/javascript" src="/ext/examples/ux/treegrid/TreeGridColumns.js"></script>
	<script type="text/javascript" src="/ext/examples/ux/treegrid/TreeGrid.js"></script>

	<script type="text/javascript" src="/ext/examples/ux/BufferView.js"></script>
	<script type="text/javascript" src="/javascript/ext.ux/Ext.ux.grid.PageSizer.js"></script>
	<script type="text/javascript" src="/js/functions.php"></script>

	<script type="text/javascript" src="/js/functions.php"></script>
	<script type="text/javascript" src="/js/component/timecode.js"></script>

	<script type="text/javascript">

	function mappingstatus(value)
	{
	if( value == -3 )
	{
		return '대기';
	}
	else if( value == 1 )
	{
		return '완료';
	}
	else if( value == 2 )
	{
		return '등록중';
	}
}

	function date_format(v)//날짜 변환함수
	{
		var year = v.substr(0,4);
		var mon = v.substr(4,2);
		var day = v.substr(6,2);
		var val = year+'-'+mon+'-'+day;
		return val;
	}

	function buildFormTimeCode()//타임코드 생성창
	{
		var tc_in = tc_form;
		var tc_out = tc_form;

		var w = new Ext.Window({
			title: '타임코드 추가',
			modal: true,
			width: 450,
			height: 150,
			layout: 'fit',

			items: {
				xtype: 'form',
				border: false,
				frame: true,
				padding: 5,
				layout: 'column',
				labelWidth: 30,
				defaults: {
					layout: 'form'
				},

				items: [{
					columnWidth: .5,
					items: tc_in
				},{
					columnWidth: .5,
					items: tc_out
				}]
			},

			buttons: [{
				text: '추가',
				handler: function(b, e){

					var o = b.ownerCt.ownerCt.get(0).getForm().getValues();
					var tc_list = Ext.getCmp('ingest_tc_list').getStore();

					var in_data  = String.leftPad(o.h[0], 2, '0')+':'+String.leftPad(o.i[0], 2, '0')+':'+String.leftPad(o.s[0], 2, '0')+':'+String.leftPad(o.f[0], 2, '0');
					var out_data = String.leftPad(o.h[1], 2, '0')+':'+String.leftPad(o.i[1], 2, '0')+':'+String.leftPad(o.s[1], 2, '0')+':'+String.leftPad(o.f[1], 2, '0');
					var in_data_test = String.leftPad(o.h[0], 2, '0')+String.leftPad(o.i[0], 2, '0')+String.leftPad(o.s[0], 2, '0')+String.leftPad(o.f[0], 2, '0');
					var out_data_test = String.leftPad(o.h[1], 2, '0')+String.leftPad(o.i[1], 2, '0')+String.leftPad(o.s[1], 2, '0')+String.leftPad(o.f[1], 2, '0');

					var tc_data = new tc_list.recordType({
						tc_in: in_data,
						tc_out: out_data
					});
					if( in_data =='00:00:00:00' && out_data=='00:00:00:00' )
					{
						msg('알림','잘못된 입력입니다..');
					}
					else if( in_data_test > out_data_test )
					{
						msg('알림','IN값이 OUT값보다 큽니다.');
					}
					else if( in_data_test == out_data_test )
					{
						msg('알림','IN값이 OUT값이 같습니다.');
					}
					else{
						var tc_list = Ext.getCmp('ingest_tc_list').getStore();


						if (!Ext.isEmpty(tc_list))
						{
							var t1 = tc_list.getRange();
							var param_tc_list = [];
							Ext.each(t1, function(r){
								param_tc_list.push(r.data);
							})
						}
						Ext.Ajax.request({//중복된 타임코드, 동일한 타임코드 체크
							url: '/pages/menu/config/ingest/tc_test.php',
							params: {
								tc_store: Ext.encode(param_tc_list),
								in_data: in_data,
								out_data: out_data
							},
							callback: function(options, success, response){
								if (success)
								{
									try
									{
										var r = Ext.decode(response.responseText);
										if (r.success)
										{
											msg('알림','중복된 타임코드입니다.');
										}
										else
										{
											Ext.Ajax.request({//frame 02부터 시작으로 고정
												url: '/pages/menu/config/ingest/tc_frame.php',
												params: {
													in_data: in_data,
													out_data: out_data
												},
												callback: function(options, success, response){
													if (success)
													{
														try
														{
															var r = Ext.decode(response.responseText);
															if (r.success)
															{
																in_data = r.in_data;
																out_data = r.out_data;
																tc_data = new tc_list.recordType({
																	tc_in: in_data,
																	tc_out: out_data
																});
																tc_list.add(tc_data);
															}
															else
															{
															}
														}
														catch (e)
														{
															Ext.Msg.alert(e['name'], e['message']);
														}
													}
													else
													{
														Ext.Msg.alert( _text('MN01098'), response.statusText);//'서버 오류'
													}
												}
											});
										}
									}
									catch (e)
									{
										Ext.Msg.alert(e['name'], e['message']);
									}
								}
								else
								{
									Ext.Msg.alert( _text('MN01098'), response.statusText);//'서버 오류'
								}
							}
						});
					}
				}
			},{
				text: '닫기',
				handler: function(b, e){
					b.ownerCt.ownerCt.close();
				}
			}]
		});

		w.show();
	}
	var msg = function(title, msg){//메세지 함수
		Ext.Msg.show({
			title: title,
			msg: msg,
			minWidth: 200,
			modal: true,
			icon: Ext.Msg.INFO,
			buttons: Ext.Msg.OK
		});
	};

	function delete_list()//삭제기능
	{
		var records = Ext.getCmp('ingest_list').getSelectionModel().getSelections();

		if( Ext.isEmpty( records ) )
		{
			msg('알림', '삭제하실 목록을 선택해주세요');
		}
		else
		{
			var rs=[];

			Ext.each(records, function(r, i, a){
				rs.push({
					id: r.get('id')
				});
			});

			Ext.Msg.show({
				icon: Ext.Msg.QUESTION,
				title: '확인',
				msg: '삭제 하시겠습니까?',
				buttons: Ext.Msg.OKCANCEL,
				fn: function(btnId, text, opts)
				{
					if(btnId == 'cancel')
					{
						return;
					}
					else
					{
						var w = Ext.Msg.wait('삭제 요청중...');
						Ext.Ajax.request({
							url: '/pages/menu/config/ingest/ingest_list_del.php',
							params: {
								id: Ext.encode(rs)
							},
							callback: function(options, success, response){
								w.hide();
								if (success)
								{
									try
									{
										var r = Ext.decode(response.responseText);
										if (r.success)
										{
											Ext.Msg.show({
												title: '확인',
												msg: '삭제 완료',
												icon: Ext.Msg.QUESTION,
												buttons: Ext.Msg.OKCANCEL,
												fn: function(btnId){
													if (btnId == 'ok')
													{
														Ext.getCmp('ingest_list').getStore().reload();
													}
												}
											});
										}
										else
										{
											Ext.Msg.alert('확인', r.msg);
										}
									}
									catch (e)
									{
										Ext.Msg.alert(e['name'], e['message']);
									}
								}
								else
								{
									Ext.Msg.alert( _text('MN01098'), response.statusText);//'서버 오류'
								}
							}
						});
					}
				}

			});
		}
	}

	Ext.ns('Ariel');

	Ariel.start = 0;
	Ariel.limit = 20;

		Ext.BLANK_IMAGE_URL = '/ext/resources/images/default/s.gif';

		Ext.onReady(function() {
			Ext.QuickTips.init();

			var tabs = new Ext.TabPanel({
				id: 'tab_ingest',
				activeTab: 0,

				listeners: {
					tabchange: function(self, p) {

							Ext.Ajax.request({
								url: '/pages/menu/config/ingest/meta_table_panel.php',
								params: {
									panel_id: p.id
								},
								callback: function(o, s, r){
									p.removeAll();
									p.add(Ext.decode(r.responseText));
									p.doLayout();
								}
							});
					}
				},
				defaults: {
					layout: 'fit'
				},
				items: [
					<?=join(',',$meta_table_body)?>
				]
			});

			ingest_panel = new Ext.Panel({
				region: 'center',
				layout: 'fit',
				tbar: [{
					text: '새로고침',
					icon: '/led-icons/arrow_refresh.png',
					handler: function(btn, e){ //로더에 날짜 파라미터 셋팅
						var store = Ext.getCmp('ingest_list').getStore();
						var d = new Date();
						Ext.getCmp('start_date').setValue(d.add(Date.MONTH, -2).format('Y-m-d'));
						Ext.getCmp('end_date').setValue(d.format('Y-m-d'));
						store.load({
							params: {
								start_date: Ext.getCmp('start_date').getValue().format('Ymd000000'),
    							end_date: Ext.getCmp('end_date').getValue().format('Ymd240000'),
    							meta_table_id: tabs.getActiveTab().getId(),
								start: Ariel.start,
								limit: Ariel.limit
							}
						});
					}

				},{
					xtype: 'tbspacer',
					width: 10
				},
				'기간 : ',
				{
					xtype: 'datefield',
					id: 'start_date',
					editable: false,
					format: 'Y-m-d',
					listeners: {
						render: function(self){
							var d = new Date();
							self.setMaxValue(d.format('Y-m-d'));
							self.setValue(d.add(Date.MONTH, -2).format('Y-m-d'));
						}
					}
				},
				'부터',
				{
					xtype: 'datefield',
					id: 'end_date',
					editable: false,
					format: 'Y-m-d',
					listeners: {
						render: function(self){
							var d = new Date();
							self.setMaxValue(d.format('Y-m-d'));
							self.setValue(d.format('Y-m-d'));
						}
					}
				},{
					icon: '/led-icons/find.png',
					text: '조회',
					handler: function(btn, e){//로더에 날짜 파라미터 셋팅

						var store = Ext.getCmp('ingest_list').getStore();
						store.reload();

					}
				},
				{
					xtype: 'tbseparator',
					width: 20
				},'Tape No 조회:',{
					xtype: 'textfield',
					id: 'search',
					emptyText: 'Tape No를 입력하세요',
					editable: true,
					listeners:{
						specialKey: function(self, e){
							if( e.getKey() == e.ENTER )
							{
								var search_combo = 'Tape NO';
								var search_val = Ext.getCmp('search').getValue();
								var store = Ext.getCmp('ingest_list').getStore();
								if(Ext.isEmpty(search_val))
								{
									msg('확인','번호를 입력해주세요.');
								}
								else
								{
									store.load({
										params: {
											start_date: Ext.getCmp('start_date').getValue().format('Ymd000000'),
											end_date: Ext.getCmp('end_date').getValue().format('Ymd240000'),
											meta_table_id: tabs.getActiveTab().getId(),
											start: Ariel.start,
											limit: Ariel.limit,
											search_combo : search_combo,
											search_text: search_val
										}
									});
							/*		Ext.apply(store.baseParams, {
											start_date: Ext.getCmp('start_date').getValue().format('Ymd000000'),
											end_date: Ext.getCmp('end_date').getValue().format('Ymd240000'),
											meta_table_id: tabs.getActiveTab().getId(),
											start: 0,
											limit: Ariel.limit,
											search_combo : search_combo,
											search_text: search_val
									});
									*/
									//store.reload();
								}
							}
						}
					}
				},{
					icon: '/led-icons/find.png',
					//style:'border-style:outset;',
					text: '조회',
					handler: function(btn, e){//테입번호 검색
						var search_combo = 'Tape NO';
                        var search_val = Ext.getCmp('search').getValue();
                       var store = Ext.getCmp('ingest_list').getStore();
                        if(Ext.isEmpty(search_val))
                        {
                            msg('확인','번호를 입력해주세요.');
                        }
                        else
                        {
                            store.load({
								params: {
									start_date: Ext.getCmp('start_date').getValue().format('Ymd000000'),
									end_date: Ext.getCmp('end_date').getValue().format('Ymd240000'),
									meta_table_id: tabs.getActiveTab().getId(),
									start: Ariel.start,
									limit: Ariel.limit,
									search_combo : search_combo,
									search_text: search_val
								}
							});
                        }
					}
				},{
					xtype: 'tbseparator',
					width: 20
				},{
					text:'  추가  ',
					disabled: true,
                    style:'border-style:outset;',
					//scale: 'medium',
					handler: function(b, e){
						Ext.Ajax.request({
							url: '/javascript/ext.ux/ingest_type_panel.php',
							params: {
							},
							callback: function(options, success, response){
								if (success)
								{
									try
									{
										Ext.decode(response.responseText);
									}
									catch (e)
									{
										Ext.Msg.alert(e['name'], e['message']);
									}
								}
								else
								{
									Ext.Msg.alert( _text('MN01098'), response.statusText);//'서버 오류'
								}
							}
						});
					}
				},{
					xtype: 'tbseparator',
					width: 10
				},{
					text:'수정',
					disabled: true,
				    style:'border-style:outset;',
					//scale: 'medium',
					handler: function(){
						var sel = Ext.getCmp('ingest_list').getSelectionModel().getSelected();

						if(!sel)
						{
							msg('알림', '수정하실 목록을 선택해주세요');
						}
						else
						{
							var id = sel.id;
							Ext.Ajax.request({
								url: '/pages/menu/config/ingest/multi_list.php',
								params: {
									id: id
								},
								callback: function(options, success, response){
									if (success)
									{
										try
										{
											Ext.decode(response.responseText);
										}
										catch (e)
										{
											Ext.Msg.alert(e['name'], e['message']);
										}
									}
									else
									{
										Ext.Msg.alert( _text('MN01098'), response.statusText);//'서버 오류'
									}
								}
							});

						}
					}
				},{
					xtype: 'tbseparator',
					width:10
				},{
					text:'삭제',
					//disabled: true,
                    style:'border-style:outset;',
					//scale: 'medium',
					handler: function(){
						delete_list();
					}
				},{
					xtype: 'tbseparator',
					width: 80
				},{
					xtype: 'button',
					icon: '/led-icons/disk.png',
					style: 'border-style:outset;',
					text: '엑셀로 저장',
					handler: function(btn, e){
						var start_date = Ext.getCmp('start_date').getValue().format('Ymd000000');
						var end_date = Ext.getCmp('end_date').getValue().format('Ymd240000');
						var meta_table_id = tabs.getActiveTab().getId();
						window.location.href = '/pages/menu/config/ingest/ingest_list_excel.php?meta_table_id='+meta_table_id+'&start_date='+start_date+'&end_date='+end_date+'&start='+Ariel.start+'&limit='+Ariel.limit;
					}
				},{
					xtype: 'tbseparator',
					width: 20
				},{
					xtype: 'button',
					style:'border-style:outset;',
					icon: '/led-icons/page_2.png',
					text: '종합정보검색(방송자료)',
					handler: function(btn, e){
						var w = window.open('/total_information', 'new');
						w.focus();
					}
				}],
				items: [
					tabs
				]
			});

		new Ext.Viewport({
			title: '인제스트 요청',
			layout: 'border',
            autoScroll: true,
			items: ingest_panel

		});
	});
	</script>
</head>
<body>
</body>
</html>

