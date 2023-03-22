<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/libs/functions.php');

$user_name = $_SESSION['user']['KOR_NM'];
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>

	<title>EBS DAS::일괄 수정</title>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />

	<link rel="stylesheet" type="text/css" href="/ext/resources/css/ext-all.css" />

	<script type="text/javascript" src="/ext/adapter/ext/ext-base.js"></script>
	<script type="text/javascript" src="/ext/ext-all.js"></script>

	<script type="text/javascript" src="/js/flowplayer/example/flowplayer-3.2.4.min.js"></script>
    <script type="text/javascript" src="/ext.ux/Ext.ux.TreeCombo.js"></script>

    <script type="text/javascript" src="/ext/src/locale/ext-lang-ko.js"></script>

	<script type="text/javascript">
	function getStreamURL(id){
		Ext.Ajax.request({
			url: '/store/getStreamURL.php',
			params: {
				content_id: id
			},
			callback: function(opts, success, response){
				if ( success )
				{
					try
					{
						var r = Ext.decode(response.responseText);

						if ( Ext.isEmpty(r.stream_url))
						{
							Ext.Msg.alert('확인', '스트리밍 정보가 없습니다.');
							return;
						}

						$f().play({
							url: 'mp4:'+r.stream_url,
							autoPlay: false,
							autoBuffering: true,
							scaling: 'fit',
							provider: 'rtmp'
						});
						$f().pause();
					}
					catch(e)
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

	function updateReview(autoClose){
		var records = Ext.getCmp('batch_review_list').store.getRange(),
			_r = [];

		for (var i=0; i<records.length; i++)
		{
			if ( Ext.isEmpty(records[i].data.receiver) )
			{
				Ext.Msg.alert('정보', '수신자를 선택해주세요.');
				return;
			}

			_r.push(records[i].data);
		}


		var w = Ext.Msg.wait('심의하신 내용을 저장 중입니다...');
		Ext.Ajax.request({
			url: '/store/update_review.php',
			params: {
				params: Ext.encode(_r)
			},
			callback: function(opts, success, response){
				if (success)
				{
					try
					{
						var r = Ext.decode(response.responseText);
						if (!r.success)
						{
							Ext.Msg.alert('오류', r.msg);
						}
						else
						{
							var w = Ext.Msg.wait('수신자에게 메일을 발송 중입니다...');
							Ext.Ajax.request({
								url: '/store/send_mail.php',
								params: {
									params: Ext.encode(_r)
								},
								callback: function(opts, success, response){
									w.hide();
									if (success)
									{
										try
										{
											var r = Ext.decode(response.responseText);
											if (!r.success)
											{
												Ext.Msg.alert('오류', r.msg);
											}
											else
											{
												Ext.Msg.show({
													title: '확인',
													msg: '심의가 완료되었습니다.',
													buttons: Ext.Msg.OK,
													fn: function(){
														if (autoClose)
														{
															Ext.getCmp('batch_review_win').close();
															Ext.getCmp('batch_review_list').store.reload();
														}
													}
												})
												Ext.Msg.alert('확인', '심의가 완료되었습니다.');
												//Ext.getCmp('review_list').store.reload();
											}
										}
										catch(e)
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
					catch(e)
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

	Ext.onReady(function(){

		var panel = new Ext.Panel({
			renderTo: Ext.getBody(),
			layout: 'border',

			items: [{
				region: 'west',
				layout: 'border',
				width: 450,

				items: [{
					region: 'north',
					id: 'revierplayer',
					height: 300,
					listeners: {
						render: function(self){
							$f(self.body.dom, {src: "/js/flowplayer/flowplayer-3.2.4.swf", wmode: 'opaque'}, {
								clip: {
									autoPlay: false,
									autoBuffering: true,
									scaling: 'fit',
									provider: 'rtmp'
								},
								plugins: {
									rtmp: {
										url: '/js/flowplayer/flowplayer.rtmp-3.2.3.swf',
										netConnectionUrl: '<?=STREAMER_ADDR?>'
									}
								}
							});
						}
					}
				},{
					xtype: 'grid',
					id: 'batch_review_list',
					region: 'center',
					previousRecord: {
						id: 'notYet'
					},

					store: new Ext.data.ArrayStore({
						fields: [
							'changed',
							'content_id',
							'title',
							'status',
							'receiver',
							'manager',
							'comment',
							'reviewed_time',
							'created_time',
							'request_user',
							'proxy'
						]
					}),

					sm: new Ext.grid.RowSelectionModel({
						singleSelect: true,
						listeners: {
	//						beforerowselect: function(self, rowIndex, keepExisting, record){
	//							console.log(Ext.getCmp('batch_review_comment').getValue());
	//							record.set('comment', Ext.getCmp('batch_review_comment').getValue());
	//
	//							return true;
	//						}
						}
					}),

					cm: new Ext.grid.ColumnModel({
						defaults: {
							menuDisabled: true
						},
						columns: [
							new Ext.grid.RowNumberer(),
							{header: '제목', dataIndex: 'title'},
							{header: '상태', dataIndex: 'changed'}
						]
					}),

					listeners: {
						rowclick: function(self, rowIndex, e){
							try
							{
								self.getSelectionModel().selectRow(rowIndex);
								var selected_row = self.getSelectionModel().getSelected();


								if ( self.previousRecord.id != selected_row.id )
								{
									if ( self.previousRecord.set )
									{
										self.previousRecord.set('comment', Ext.getCmp('batch_review_comment').getValue());
									}
									//console.log(self.previousRecord);
									self.previousRecord = selected_row;
								}

								Ext.getCmp('batch_review_status').setValue( selected_row.get('status') );
								Ext.getCmp('batch_review_comment').setValue( selected_row.get('comment') );
								if ( !Ext.isEmpty(selected_row.get('created_time')) )
								{
									Ext.getCmp('batch_review_created_time').setValue( new Date.parseDate(selected_row.get('created_time'), 'YmdHis').format('Y-m-d H:i:s') );
								}
								if (!Ext.isEmpty(selected_row.get('receiver')))
								{
									var receiver_store = Ext.getCmp('batch_receiver_list').store;
									receiver_store.removeAll();

									var receivers = Ext.decode(selected_row.get('receiver'));
									Ext.each(receivers, function(receiver){
										var item = new receiver_store.recordType( receiver );
										receiver_store.add( item );
									});
								}

								getStreamURL( selected_row.get('content_id') );
							}
							catch (e)
							{
								Ext.Msg.alert(e['name'], e['message']);
							}
						}
					},

					viewConfig: {
						forceFit: true
					},

					buttons: [{
						text: 'GOM플레이어',
						handler: function(btn, evt){
							var sm = Ext.getCmp('batch_review_list').getSelectionModel();
							if (!sm.hasSelection())
							{
								Ext.Msg.alert('확인', '플레이하실 영상을 선택하여주세요.');
								return;
							}


							try
							{
								var proxy_file = sm.getSelected().get('proxy');
								if (Ext.isEmpty(proxy_file))
								{
									Ext.Msg.alert('확인', '프록시 파일이 존재하지 않습니다.');
									return;
								}

								proxy_file = '<?=FILESERVER2?>' + proxy_file.replace(/\//gi, '\\');
								if ( !GeminiAxCtrl.IsInstalled('<?=GOM_PATH?>') )
								{
									Ext.Msg.alert('확인', '플레이어가 존재하지 않습니다.');
									return;
								}

								GeminiAxCtrl.ProcessStart('<?=GOM_PATH?>', proxy_file);
							}
							catch (e)
							{
								Ext.Msg.alert(e['name'], e['message']);
							}
						}
					}]
				}]
			},{
				region: 'center',
				xtype: 'form',
				id: 'batch_review_form',
				autoScroll: true,
				border: false,
				frame: true,
				padding: 5,
				labelAlign: 'right',
				defaultType: 'textfield',
				defaults: {
					anchor: '95%'
				},

				items: [{
					xtype: 'combo',
					id: 'batch_review_status',
					mode: 'local',
					triggerAction: 'all',
					typeAhead: true,
					editable: false,
					fieldLabel: '심의상태',
					store:  [
						[2, '심의비대상'],
						[3, '심의대기중'],
						[4, '승인'],
						[5, '반려'],
						[6, '조건부승인']
					],
					value: 3,
					listeners: {
						select: function(self, r, i){
							Ext.getCmp('batch_review_form').isChange = 'Y';

							var r = Ext.getCmp('batch_review_list').getSelectionModel().getSelected();
							r.set('status', self.getValue());
							r.set('changed', '변경됨');
						}
					}
				},{
					xtype: 'textarea',
					id: 'batch_review_comment',
					fieldLabel: '심의결과 내용',
					height: 100,
					enableKeyEvents: true,
					listeners: {
						keypress: function(self, e){
							Ext.getCmp('batch_review_form').isChange = 'Y';

							var r = Ext.getCmp('batch_review_list').getSelectionModel().getSelected();
							r.set('comment', self.getValue());
							r.set('changed', '변경됨');
						}
					}
				},{
					xtype: 'grid',
					id: 'batch_receiver_list',
					fieldLabel: '수신자 목록',
					width: '90%',
					height: 200,
					frame: true,
					store: new Ext.data.JsonStore({
						url: '/store/get_reviewer_list.php',
						root: 'data',
						fields: [
							'KOR_NM',
							'EMPNO',
							'DEPT_NM',
							'EMAIL'
						],
						listeners: {
							add: function(self, r, i){
								Ext.getCmp('batch_review_form').isChange = 'Y';

								var result = [];
								var selected_row = Ext.getCmp('batch_review_list').getSelectionModel().getSelected();
								var t = self.getRange();
								Ext.each(t, function(i){
									result.push(i.data);
								});
								selected_row.set('receiver', Ext.encode(result) );
							},
							remove: function(self, r, i){
								Ext.getCmp('batch_review_form').isChange = 'Y';

								var result = [];
								var selected_row = Ext.getCmp('batch_review_list').getSelectionModel().getSelected();
								var t = self.getRange();
								Ext.each(t, function(i){
									result.push(i.data);
								});
								selected_row.set('receiver', Ext.encode(result) );
							}
						}
					}),
					cm: new Ext.grid.ColumnModel({
						defaults: {
							menuDisabled: true,
							sortable: false,
							align: 'center'
						},
						columns: [
							{header: '사번', dataIndex: 'EMPNO', width: 55},
							{header: '이름', dataIndex: 'KOR_NM', width: 60},
							{header: '부서', dataIndex: 'DEPT_NM'},
							{header: '이메일', dataIndex: 'EMAIL', width: 150}
						]
					}),
					viewConfig: {
						emptyText: '등록된 수신자가 없습니다.'
					},

					tbar: [{
						text: '검색',
						handler: function(btn, e){
							winSearchRecevie().show();

						}
					},{
						text: '삭제',
						handler: function(b, e){
							Ext.getCmp('batch_review_form').isChange = 'Y';
							Ext.getCmp('batch_review_list').getSelectionModel().getSelected().set('changed', '변경됨');

							var g = Ext.getCmp('batch_receiver_list');
							if (g.getSelectionModel().hasSelection()) {
								g.store.remove(g.getSelectionModel().getSelections());
							} else {
								Ext.Msg.alert('정보', '삭제하실 수신자를 선택하여주세요.');
							}
						}
					}]
				},{
					fieldLabel: '심의자',
					name: 'manager',
					disabled: true,
					value: '<?=$_SESSION['user']['KOR_NM']?>'
				},{
					fieldLabel: '심의일시',
					name: 'reviewed_time',
					disabled: true
				},{
					id: 'batch_review_created_time',
					fieldLabel: '심의요청일',
					disabled: true
				}],

				buttons: [{
					text: '저장',
					scale: 'medium',
					handler: function(b, e){
						if (!Ext.getCmp('batch_review_list').getSelectionModel().hasSelection())
						{
							Ext.Msg.alert("확인", "콘텐츠를 선택하여 주세요.");
						}
						else
						{
							if (Ext.isEmpty(Ext.getCmp('batch_review_status').getValue()))
							{
								Ext.Msg.alert("확인", "심의 상태를 선택하여주세요.");
								return;
							}
							else if ( Ext.getCmp('batch_review_status').getValue() == 3 )
							{
								Ext.Msg.alert("확인", "심의 상태값을 변경하여주세요.");
								return;
							}
							/*
							else if (Ext.isEmpty(Ext.getCmp('batch_review_comment').getValue()))
							{
								Ext.Msg.alert("확인", "심의 내용를 입력하여주세요.");
								return;
							}
							*/
							else
							{
								Ext.getCmp('batch_preview').stop();
								Ext.Msg.show({
									title: '확인',
									msg: '심의하신 내용을 저장하시겠습니까?',
									buttons: Ext.Msg.YESNO,
									fn: function(btnId){
										if (btnId == 'yes')
										{
											Ext.getCmp('batch_review_form').isChange = 'N';
											updateReview(false);
											//Ext.getCmp('batch_review_form').store.commitChanges();
										}
									}
								});
							}
						}
					}
				},{
					text: '닫기',
					scale: 'medium',
					handler: function(b, e){
						if ( Ext.getCmp('batch_review_form').isChange == 'Y' )
						{
							Ext.Msg.show({
								icon: Ext.Msg.QUESTION,
								title: '확인',
								msg: '심의 변경된 내용이 있습니다. 적용 후 닫으시겠습니까?',
								buttons: Ext.Msg.YESNO,
								fn: function(btnId){
									if (btnId == 'yes')
									{
										if (Ext.isEmpty(receivers)) {
											Ext.Msg.alert('정보', '수신자를 선택해주세요.');
											return;
										}
										updateReview(true);
									}
									else
									{
										Ext.getCmp('batch_review_win').close();
									}
								}
							});
						}
						else
						{
							Ext.getCmp('batch_review_win').close();
						}
					}
				}]
			}],

			listeners: {
				render: function(self){
					var ids = [];
					Ext.each(self.data, function(i){
						ids.push(i.get('content_id'));
					});
					Ext.Ajax.request({
						url: '/store/get_review_status.php',
						params: {
							contents_id: ids.join(',')
						},
						callback: function(opts, success, response){
							if (success)
							{
								try
								{
									var rtn = Ext.decode(response.responseText);
									if (rtn.success)
									{
										var s = Ext.getCmp('batch_review_list').store;

										Ext.each(rtn.data, function(i){
											s.add(new s.recordType(i));
										});

										Ext.getCmp('batch_review_list').getSelectionModel().selectFirstRow();
										Ext.getCmp('batch_review_list').getSelectionModel().fireEvent('rowselect', Ext.getCmp('batch_review_list'), 0);
									}
									else
									{
										Ext.Msg.alert('오류', rtn.msg);
									}
								}
								catch(e)
								{
									Ext.Msg.alert(e['name']+'<?=basename(__FILE__)?>', e['message']);
								}
							}
							else
							{
								Ext.Msg.alert( _text('MN01098'), response.statusText);//'서버 오류'
							}
						}
					});
				}
			},

			data: Ext.decode($_GET['args'])
		});

	});
	</script>
</head>

<body>

</body>
</html>