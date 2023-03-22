<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/lib.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');
//checkLogin();
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


		.wait_list_modified {
		background-color: #FFFFBB;
	}
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
	<script type="text/javascript" src="/js/functions.php"></script>

	<script type="text/javascript" src="/js/component/timecode.js"></script>

	<script type="text/javascript">

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
		var del_sel_array = Ext.getCmp('ingest_list').getSelectionModel().getSelectedNodes();
		var del_sel =del_sel_array[0];
		var complete_check = true;
		if(!del_sel)
			msg('알림', '삭제하실 목록을 선택해주세요');
		else{
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
					}else
					{
						for(var i=0; i < del_sel_array.length; i++)
						{
							var del_id = del_sel_array[i].attributes.id;
							var list = del_sel_array[i].attributes.iconCls;
							var leaf = del_sel_array[i].attributes.leaf;

							Ext.Ajax.request({//리스트면 타임코드까지 삭제, 타임코드면 타임코드만삭제
								url: '/pages/menu/config/ingest/ingest_tree_del.php',
								params: {
									id: del_id,
									list: list,
									leaf: leaf
								},
								callback: function(options, success, response){
									if (success)
									{

										try
										{
											var r = Ext.decode(response.responseText);
											if (r.success)
											{
												complete_check = true;

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
						if(complete_check)
						{
							msg('알림', '삭제 완료');
							Ext.getCmp('ingest_list').getRootNode().reload();
						}

					}
				}

			});
		}
	}
	function get_mywork()
	{
		var get_sel_array = Ext.getCmp('ingest_list').getSelectionModel().getSelectedNodes();
		var is_tc = get_sel_array[0].attributes.iconCls;

		if(is_tc =='tc'){//타임코드면 부모노드 리스트를 선택
			get_sel_array[0] = get_sel_array[0].parentNode;
		}

		Ext.Msg.show({
			icon: Ext.Msg.QUESTION,
			title: '확인',
			msg: '내 작업으로 가져오시겠습니까?',
			buttons: Ext.Msg.OKCANCEL,
			fn: function(btnId, text, opts)
			{
				if(btnId == 'cancel') return;

				var get_id = get_sel_array[0].attributes.id;

				Ext.Ajax.request({//내 작업으로 가져오기
					url: '/pages/menu/config/ingest/get_mywork.php',
					params: {
						id: get_id
					},
					callback: function(options, success, response){
						if (success)
						{

							try
							{
								var r = Ext.decode(response.responseText);
								if (r.success)
								{
									//Ext.Msg.alert( _text('MN00023'), '작업할당 성공');
									Ext.getCmp('ingest_list').getRootNode().reload();
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
		});
	}

	function set_work()//작업할당함수
	{
		var member_store = new Ext.data.JsonStore({
		    root: 'data',
            url: '/pages/menu/config/ingest/member_store.php',
            autoLoad: true,
            fields: [
                {
                    name: 'd'
                },
                {
                    name: 'v'
                }
            ]
		});
		var	dept_store =new Ext.data.JsonStore({
		    root: 'data',
            url: '/pages/menu/config/ingest/dept_store.php',
            autoLoad: true,
            fields: [
				{name: 'd'},
                {name: 'v'}
            ]
		});

		var win = new Ext.Window({//수정 창
			id: 'set_work',
			width: 300,
			height: 450,
			layout: 'fit',
			modal: true,
			resizable: false,
			title: '작업 할당하기',

			items: [{
				id: 'insert_form',
				xtype: 'form',
				border: false,
				layout: 'form',
				frame: true,
				padding: '5',
				labelWidth: 50,
				defaults: {
					anchor: '100%'
				},
				items: [{
					xtype: 'compositefield',
					fieldLabel: '부 서',
					items: [{
						xtype: 'combo',
						id: 'dept_nm',
						displayField: 'd',
						valueField: 'v',
						store: dept_store,
						value: '부서를 선택해주세요.',
						triggerAction: 'all'
					},{
						xtype: 'button',
						text: '검색',
						handler: function(){
							member_store.reload({
								params: {
									dept_nm: Ext.getCmp('dept_nm').getValue()
								}
							});
						}
					}]
				},{
					xtype: 'listview',
					id: 'member_list',
					border: false,
					frame: false,
					height: 330,
					columnResize: false,
					columnSort: false,
					singleSelect: true,
					store: member_store,
					columns: [
						{header: '아이디', dataIndex: 'v'},
						{header: '이름', dataIndex: 'd'}
					],
					viewConfig: {
						forceFit: true
					}
				}],
				buttons: [{
					scale: 'medium',
					text: '할당하기',
					handler: function(){
						var record = Ext.getCmp('member_list').getSelectedRecords();
						var worker = record[0].get('v');
						var get_sel_array = Ext.getCmp('ingest_list').getSelectionModel().getSelectedNodes();
						var is_tc = get_sel_array[0].attributes.iconCls;

						if(is_tc =='tc'){//타임코드면 부모노드 리스트를 선택
							get_sel_array[0] = get_sel_array[0].parentNode;
						}
						var get_id = get_sel_array[0].attributes.id;

						Ext.getCmp('insert_form').getForm().submit({
							url:'/pages/menu/config/ingest/set_work.php',
							method: 'POST',
							waitMsg: '등록중..',
							success: function(){
								msg('success','등록 완료');
								win.destroy();
								Ext.getCmp('ingest_list').getRootNode().reload();
							},
							failure: function(){
								msg('failure','등록 실패');
							},
							params: {
								user_id: worker,
								id: get_id
							}
						});
					}
				},{
					scale: 'medium',
					text: '취소',
					handler: function(){
						win.destroy();
					}
				}]

			}]
		}).show();

	}

	Ext.ns('Ariel');
	Ariel.cur_page = 1;
	Ariel.total_page = 1;
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
						if( p.id != '<?=CLEAN?>' )
						{
							ingest_panel.getBottomToolbar().setVisible(true);
							Ext.Ajax.request({
								url: '/pages/menu/config/ingest/treegrid_panel.php',
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
						else
						{
							ingest_panel.getBottomToolbar().setVisible(false);
							Ext.Ajax.request({
								url: '/pages/menu/config/ingest/tvinsert_panel.php',
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
				//title: '인제스트 요청 리스트',
				//frame: true,
			//	height: 500,
			//	width: 500,
				region: 'center',
				//autoScroll: true,
				layout: 'fit',

				bbar: [{
					//text: '첫페이지',
					icon: '/ext/resources/images/default/grid/page-first.gif',
					handler: function(b, e){
						Ariel.cur_page = 1;
						var t = Ext.getCmp('ingest_list');
						t.getLoader().baseParams.start =0;
						t.getLoader().load(t.getRootNode());
					}
				},{
					//text: '이전',
					icon: '/ext/resources/images/default/grid/page-prev.gif',
					handler: function(b, e){
						Ariel.cur_page--;
						var t = Ext.getCmp('ingest_list');
						t.getLoader().baseParams.start -= Ariel.limit;
						t.getLoader().load(t.getRootNode());
					}
				},{
					xtype: 'textfield',
					id: 'start_page',
					width: 40,
					value: 0,
					listeners: {
						specialKey: function(self, e){
							var k = e.getKey();
							var value = self.getValue();
							var start = value*Ariel.limit;
							if (k == e.ENTER)
							{
								Ariel.cur_page = value;
								var search_val = Ext.getCmp('search').getValue();
								var loader = Ext.getCmp('ingest_list').getLoader();
								Ext.apply(loader.baseParams, {
    								start_date: Ext.getCmp('start_date').getValue().format('Ymd000000'),
    								end_date: Ext.getCmp('end_date').getValue().format('Ymd240000'),
    								meta_table_id: tabs.getActiveTab().getId(),
    								start: start,
    								limit: Ariel.limit,
                                    search: search_val
    							});
								loader.load( Ext.getCmp('ingest_list').getRootNode() );
							}
						}
					}
				},'/','0',{
					//text: '다음',
					icon: '/ext/resources/images/default/grid/page-next.gif',
					handler: function(b, e){
						var t = Ext.getCmp('ingest_list');
						Ariel.cur_page++;
						t.getLoader().baseParams.start += Ariel.limit;
						t.getLoader().load(t.getRootNode());
					}
				},{
					//text: '마지막페이지',
					icon: '/ext/resources/images/default/grid/page-last.gif',
					handler: function(b, e){
						Ariel.cur_page = Ariel.total_page;
						var t = Ext.getCmp('ingest_list');
						t.getLoader().baseParams.start =Ariel.total_page*Ariel.limit;
						t.getLoader().load(t.getRootNode());
					}
				},
				{
					xtype: 'tbseparator',
					width: 20
				},
				{
					//text:'새로고침',
                    icon: '/led-icons/arrow_refresh.png',
				//	scale: 'medium',
					handler: function(){
						Ariel.cur_page=1;
						Ext.getCmp('search').setValue();
						var loader = Ext.getCmp('ingest_list').getLoader();

                        Ext.apply(loader.baseParams, {
								start_date: Ext.getCmp('start_date').getValue().format('Ymd000000'),
								end_date: Ext.getCmp('end_date').getValue().format('Ymd240000'),
								meta_table_id: tabs.getActiveTab().getId(),
								start: 0,
								limit: Ariel.limit,
                                search:''
						});

                        loader.load( Ext.getCmp('ingest_list').getRootNode() );
					}
				}],

				tbar: [{
					xtype: 'tbspacer',
					width: 10
				},/*{
					xtype: 'button',
					icon: '/led-icons/page_2.png',
					text: '종합정보DB(방송자료)',
					handler: function(btn, e){
						var w = window.open('/total_information', 'new');
						w.focus();
					}
				},{
					xtype: 'tbseparator',
					width: 20
				},{
					text:'  추가  ',
					scale: 'medium',
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
					width: 20
				},{
					text:'수정',
					scale: 'medium',
					handler: function(){
						var get_sel = Ext.getCmp('ingest_list').getSelectionModel().getSelectedNodes();
						var sel = get_sel[0];

						if(!sel)
						{
							msg('알림', '수정하실 목록을 선택해주세요');
						}
						else
						{
							var id = sel.attributes.id;
							Ext.Ajax.request({
								url: '/javascript/ext.ux/ingest_edit_panel.php',
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
					width: 20
				},{
					text:'삭제',
					scale: 'medium',
					handler: function(){
						delete_list();
					}
				},{
					xtype: 'tbseparator',
					width: 20
				},*/
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
							self.setValue(d.add(Date.DAY, -6).format('Y-m-d'));
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
						Ariel.cur_page=1;
						Ext.getCmp('search').setValue();
						var loader = Ext.getCmp('ingest_list').getLoader();

                        Ext.apply(loader.baseParams, {
								start_date: Ext.getCmp('start_date').getValue().format('Ymd000000'),
								end_date: Ext.getCmp('end_date').getValue().format('Ymd240000'),
								meta_table_id: tabs.getActiveTab().getId(),
								start: 0,
								limit: Ariel.limit,
                                search:''
						});

                        loader.load( Ext.getCmp('ingest_list').getRootNode() );
					}
				},/*{
					xtype: 'tbseparator',
					width: 20
				},{
					xtype: 'button',
					icon: '/led-icons/disk.png',
					text: '엑셀로 저장',
					handler: function(btn, e){
						var start_date = Ext.getCmp('start_date').getValue().format('Ymd000000');
						var end_date = Ext.getCmp('end_date').getValue().format('Ymd240000');
						var meta_table_id = tabs.getActiveTab().getId();
						window.location.href = '/pages/menu/config/ingest/ingest_list_excel.php?meta_table_id='+meta_table_id+'&start_date='+start_date+'&end_date='+end_date+'&start='+Ariel.start+'&limit='+Ariel.limit;
					}
				},*/{
					xtype: 'tbseparator',
					width: 20
				},'Tape No 조회:',{
					xtype: 'textfield',
					id: 'search',
					editable: true,
					listeners: {
						render: function(self){
						}
					}
				},{
					icon: '/led-icons/find.png',
					//style:'border-style:outset;',
					text: '조회',
					handler: function(btn, e){//테입번호 검색
						Ariel.cur_page=1;
                        var search_val = Ext.getCmp('search').getValue();
                        var loader = Ext.getCmp('ingest_list').getLoader();
                        if(Ext.isEmpty(search_val))
                        {
                            msg('확인','번호를 입력해주세요.');
                        }
                        else
                        {
                            Ext.apply(loader.baseParams, {
    								start_date: Ext.getCmp('start_date').getValue().format('Ymd000000'),
    								end_date: Ext.getCmp('end_date').getValue().format('Ymd240000'),
    								meta_table_id: tabs.getActiveTab().getId(),
    								start: 0,
    								limit: Ariel.limit,
                                    search: search_val
    						});
                            //loader.load( Ext.getCmp('ingest_list').getRootNode() );
                        }
                        loader.load( Ext.getCmp('ingest_list').getRootNode() );
					}
				},{
					xtype: 'tbseparator',
					width: 20
				},{
					text:'  추가  ',
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
				    style:'border-style:outset;',
					//scale: 'medium',
					handler: function(){
						var get_sel = Ext.getCmp('ingest_list').getSelectionModel().getSelectedNodes();
						var sel = get_sel[0];

						if(!sel)
						{
							msg('알림', '수정하실 목록을 선택해주세요');
						}
						else
						{
							var id = sel.attributes.id;
							Ext.Ajax.request({
								url: '/javascript/ext.ux/ingest_edit_panel.php',
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
//		var win = new Ext.Window({
//			title: '인제스트 요청',
//			width: 1300,
//			height:	800,
//			closable: false,
//			maximizable: true,
//			maximized: true,
//			autoScroll: true,
//			border:	false,
//			layout:	'border',
//			listeners: {
//				move: function(self, x,	y){
//					if(x < 0){
//						self.setPosition(0, y);
//					}
//					if(y < 0){
//						self.setPosition(x, 0);
//					}
//				},
//				show: function(self, x,	y){
//					self.setPosition(0, 0);
//				}
//			},
//			defaults: {
//				border:	false
//			},
//			items: [
//				ingest_panel
//			]
//		});
//		win.show();

		//ingest_panel.render(document.body);
	});
	</script>
</head>
<body>
</body>
</html>

