<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');

$content_id = $_REQUEST['content_id'];


$content = $db->queryRow("select c.title, c.content_type_id, m.name as meta_type_name from content c, meta_table m where c.content_id={$content_id} and c.meta_table_id=m.meta_table_id");

if ($content['content_type_id'] == SOUND)
{
	$stream_file = $db->queryOne("select path from media where content_id={$content_id} and type='".STREAM_FILE."' order by media_id");
	if (empty($stream_file))
	{
		$stream_file = $db->queryOne("select path from media where content_id={$content_id} and type='original' order by media_id");
	}
}
else
{
	$stream_file = $db->queryOne("select path from media where content_id={$content_id} and type='".STREAM_FILE."' order by media_id");
}

$stream_file = addslashes(trim($stream_file, '/'));
$flashVars = "mp3:$stream_file";
if ($content['content_type_id'] == MOVIE)
{
	$thumb_file = $db->queryOne("select path from media where content_id={$content_id} and type='thumb'");
	if (empty($thumb_file))
	{
		$thumb_file = 'img/process.jpg';
	}

	$thumb_file = addslashes($thumb_file);
	$flashVars = str_replace("mp3:", "mp4:", $flashVars);
}
?>
(function(){
	Ext.ns('Ariel');

	var that = this;
	function renderPFRStatus(v){
		var r;
		switch (v)
		{
			case 'queue':
				r = '대기중';
			break;

			case 'progressing':
				r = '처리중';
			break;

			case 'canceled':
				r = '취소';
			break;

			case 'error':
				r = '오류';
			break;

			default:
				r = v;
			break;
		}

		return r;
	}

	function renderPFRType(type){
		var r;
		switch (type)
		{
			case 'pfr':
				r = '구간추출';
			break;

			case 'pfr_high':
				r = '고해상도 구간추출';
			break;

			case 'pfr_low':
				r = '저해상도 구간추출';
			break;

			default:
				r = type;
			break;
		}

		return r;
	}

	function open_cjmall_send_form(){
		new Ext.Window({
			title: '전송 메타데이터 입력',
			width: '450',
			height: '650',
			modal: true,
			layout: 'fit',

			items: new Ext.FormPanel({
				frame: true,
				id: 'cjmallsendform',
				defaultType: 'textfield',
				defaults: {
					anchor: '95%'
				},

				border: false,
				items: [{
					fieldLabel: '제목',
					name : 'title'
				},{
					fieldLabel: '콘텐츠 유형',
					name : 'genre'
				},{
					fieldLabel: '키워드',
					name : 'keyword'
				},{
					xtype: 'compositefield',
					labelWidth: 130,
					fieldLabel: '사용기간',
				    items: [{
						xtype: 'datefield',
						format: 'Y-m-d',
						altFormats: 'Y-m-d|Ymd|YmdHis',
						name : 'use_term_start'
					},{
						xtype: 'datefield',
						altFormats: 'Y-m-d|Ymd|YmdHis',
						format: 'Y-m-d',
						name : 'use_term_end'
					}]
				},{
					xtype: 'datefield',
					format: 'Y-m-d H:i:s',
					fieldLabel: '방송일시',
					name : 'broad_date'
				},{
					fieldLabel: 'PGM네임',
					name : 'pgm'
				},{
					fieldLabel: 'PD',
					name : 'pd'
				},{
					fieldLabel: '출연자',
					name : 'actor'
				},{
					fieldLabel: '쇼호스트',
					name : 'showhost'
				},{
					xtype: 'textarea',
					fieldLabel: '상품설명',
					name : 'kinds'
				},
				/*고객 요구로 카테고리 항목 제거 2010.10.19 김형기
				{
					fieldLabel: '카테고리',
					name : 'category'
				},
				*/
				{
					fieldLabel: '파일출처',
					name : 'fl_source',
					editable : false
				},{
					xtype: 'grid',
					id: 'transfer_ch_item_list',
					fieldLabel: '상품목록',
					loadMask: true,
					name: 'transfer_items_list',
					height: 200,
					frame: true,
					border: true,
					store: new Ext.data.JsonStore({
						autoLoad: true,
						url: '/store/getPgmItem.php',
						root: 'data',
						fields: [
							'content_id',
							'item_cd',
							'item_nm'
						],
						baseParams: {
							content_id: <?=$content_id?>
						}
					}),
					cm: new Ext.grid.ColumnModel({
						defaults: {
							menuDisabled: true,
							sortable: false
						},
						columns: [
							{header: '코드', dataIndex: 'item_cd', width: 100, align: 'center'},
							{header: '상품명', dataIndex: 'item_nm', width: 500}
						]
					}),

					viewConfig: {
						emptyText: '등록된 상품이 없습니다.'
					},

					tbar: [{
						icon: '/led-icons/add.png',
						text: '추가',
						handler: function(b, e){
							Ext.Ajax.request({
								url: '/store/component/searchItemTransfer.js',
								callback: function(opts, success, resp){
									if (success)
									{
										try
										{
											Ext.decode(resp.responseText);
										}
										catch (e)
										{
											Ext.Msg.alert(e['name'], e['message']);
										}
									}
									else
									{
										Ext.Msg.alert( _text('MN01098'), resp.statusText);//'서버 오류'
									}
								}
							});
						}
					},{
						icon: '/led-icons/delete.png',
						text: '삭제',
						handler: function(b, e){
							var g = Ext.getCmp('transfer_ch_item_list');
							var sm = Ext.getCmp('transfer_ch_item_list').getSelectionModel();
							var selectedList = sm.getSelections();

							g.store.remove(selectedList);
						}
					}]
				},{
					name : 'content_id',
					hidden: true,
					value: <?=$content_id?>
				},{
					name : 'pgm_code',
					hidden: true
				},{
					xtype: 'datefield',
					altFormats: 'Y-m-d|Ymd|YmdHis',
					format: 'Y-m-d H:i:s',
					name : 'created_time',
					hidden: true
				}],

				listeners: {
					render: function(self){
						Ext.Ajax.request({
							url: '/store/getCJMallFormMeata.php',
							params: {
								content_id: <?=$content_id?>
							},
							callback: function(opts, success, response){
								if (success)
								{
									try
									{
										var r = Ext.decode(response.responseText);
										//console.log(r);
										//console.log(self);
										self.getForm().loadRecord(r);
									}
									catch (e)
									{
										Ext.Msg.alert(e['name'], e['message']);
									}
								}
								else
								{
									Ext.Msg.alert('오류', opts.url+'<br />'+response.statusText+'('+response.status+')');
								}
							}
						});

					}
				}
			}),

			buttons: [{
				text: '전송',
				handler: function(b, e){
					if (Ext.getCmp('user_pfr_list').store.getCount() == 0)
					{
						Ext.Msg.alert('확인', '구간추출된 파일이 없습니다.');
						return;
					}

					var pfr_list = [];
						Ext.getCmp('user_pfr_list').store.each(function(i){
							pfr_list.push({
								tcin: i.get('setInSec'),
								tcout: i.get('setOutSec')
							});
						});

					var p = Ext.getCmp('cjmallsendform').getForm().getValues();
					var _items = Ext.getCmp('transfer_ch_item_list').store.getRange(),
						items = [];
					Ext.each(_items, function(i){
						items.push(i.data);
					});

					Ext.applyIf(p, {
						set_in: pfr_list[0].tcin,
						set_out: pfr_list[0].tcout,
						items: Ext.encode(items)
					});
					//console.log(p);

					Ext.Ajax.request({
						url: '/store/send_cjmall_xml.php',
						params: p,
						callback: function(opts, success, response){
							if(success)
							{
								try
								{
									var r = Ext.decode(response.responseText);
									if(r.success)
									{
										Ext.getCmp('work_list').getStore().reload();

										Ext.Msg.alert('확인', '전송 요청이 성공적으로 수행되었습니다.');
										Ext.getCmp('user_pfr_list').store.removeAll();

										b.ownerCt.ownerCt.close();
									}
									else
									{
										Ext.Msg.alert('오류', r.msg);
									}
								}
								catch(e)
								{
									Ext.Msg.alert(e['name'], e['message']);
								}
							}
							else
							{
								Ext.Msg.alert('오류', response.statusText+'('+response.status+')');
							}
						}

					})

				}
			},{
				text: '취소',
				handler: function(b, e){
					b.ownerCt.ownerCt.close();
				}
			}]
		}).show();
	}

	function deleteItem()
	{
		Ext.Msg.show({
			title: '확인',
			msg: '선택하신 항목을 삭제하시겠습니까?',
			icon: Ext.Msg.QUESTION,
			buttons: Ext.Msg.OKCANCEL,
			fn: function(btnId){
				if (btnId == 'ok')
				{
					Ext.getCmp('user_pfr_list').store.remove(Ext.getCmp('user_pfr_list').getSelectionModel().getSelections());
				}
			}
		})
	}

	Ariel.DetailWindow = Ext.extend(Ext.Window, {
		id: 'winDetail',
		title: '상세보기 [<?=addslashes($content['title'])?>]',
		editing: <?=$editing ? 'true,' : 'false,'?>
		width: '100%',
		top: 50,
		height: 700,
		modal: true,
		layout: 'fit',
		maximizable: true,
		listeners: {
			move: function(self, x, y){//창이 윈도우 포지션을 벗어났을때 0으로 셋팅
				var pos = self.getPosition();
				if(pos[0]<0)
				{
					self.setPosition(0,pos[1]);
				}
				else if(pos[1]<0)
				{
					self.setPosition(pos[0],0);
				}
			}
		},
		/* 2010-11-09  주석처리 (탭 show/hide 기능 제거 by CONOZ)
		tools: [{
			id: 'right',
			handler: function(e, toolEl, p, tc){

				var _w = 450;

				if (tc.id == 'right')
				{
					tc.id = 'left';
					Ext.getCmp('detail_panel').setWidth(_w);
					p.setWidth( p.getWidth() + _w );
				}
				else
				{
					tc.id = 'right';
					Ext.getCmp('detail_panel').setWidth(0);
					p.setWidth( p.getWidth() - _w );
				}

				p.center();
			}

		}],
		*/


		listeners: {
			render: function(self){
				//console.log(self);
				//self.clearAnchor();
			},
			destroy: function(self){
				delete that;
			}
		},

		initComponent: function(config){
			Ext.apply(this, config || {});

			this.items = {
				border: false,
				layout: 'border',

				items: [{
					layout: 'border',
					region: 'center',
					border: false,

					items: [{
						region: 'center',
						html: '<a href="<?=$flashVars?>" id="player2"></a>'
					},{
						region: 'south',
						height: 350,
						split: true,
						layout: {
							type: 'vbox',
							align: 'stretch'
						},

						items: [{
							xtype: 'toolbar',
							items: [{
								xtype: 'label',
								text: '구간 추출'
							},'-',{
								text: 'Set In 】',
								handler: function(b, e){
									var _s = $f('player2').getTime();

									var H = parseInt( _s / 3600 );
									var i = parseInt( (_s / 60) - (H * 60) );
									//var s = parseInt( _s % 60 );
									var s = _s % 60;
//									//var mils =  _s - parseInt(_s);

//									console.log(H);
//									console.log(i);
//									console.log(s);

									Ext.getCmp('secIn').setValue( sprintf("%02d:%02d:%02d" , H, i, s) );
								}
							},{
								xtype: 'textfield',
								id: 'secIn',
								width: 55,
								value: '00:00:00'
							},'-',{
								text: 'Set Out【',
								handler: function(b, e){
									var _s = $f('player2').getTime();

									var H = parseInt( _s / 3600 );
									var i = parseInt( (_s / 60) - (H * 60) );
									//var s = parseInt( _s % 60 );
									var s = _s % 60;
//									//var mils =  _s - parseInt(_s);

//									console.log(H);
//									console.log(i);
//									console.log(s);

									Ext.getCmp('secOut').setValue( sprintf("%02d:%02d:%02d" , H, i, s) );
								}
							},{
								xtype: 'textfield',
								id: 'secOut',
								width: 55,
								value: '00:00:00'
							},'-',{
								text: 'Go To: ',
								id: 'btnGoto',
								handler: function(){
									var _seek = Ext.getCmp('goto').getValue();
									if (Ext.isEmpty(_seek)) {
										Ext.getCmp('goto').setValue(0);
										_seek = 0;
									}
									$f('player2').seek(_seek);
								}
							},{
								xtype: 'numberfield',
								id: 'goto',
								width: 30,
								value: 0,
								listeners: {
									specialKey: function(self, e){
										//console.log(e);
										if (e.getKey() == e.ENTER)
										{
											Ext.getCmp('btnGoto').fireEvent('click');
										}
									}
								}
							},'-',{
								icon: '/led-icons/add.png',
								text: '추가',
								handler: function(b, e){

									$f('player2').pause();

									var setInSec, setOutSec;

									setInTC = Ext.getCmp('secIn').getValue();
									setOutTC = Ext.getCmp('secOut').getValue();

									setInSec = timecodeToSec(Ext.getCmp('secIn').getValue());
									setOutSec = timecodeToSec(Ext.getCmp('secOut').getValue());

									var txt = checkInOut(setInSec, setOutSec);
									if ( !Ext.isEmpty(txt) )
									{
										Ext.Msg.alert('정보', txt);
										return;
									}

									var duration = secToTimecode(setOutSec - setInSec);

									Ext.getCmp('user_pfr_list').store.loadData([
										[setInSec, setOutSec, setInTC, setOutTC, duration, '대기', new Date().format('YmdHis')]
									], true);
								}
							},{
								icon: '/led-icons/bin_closed.png',
								text: '삭제',
								handler: function(b, e){
									deleteItem();
								}
							}]
						},{
							flex: 1,
							layout: {
								type: 'vbox',
								align: 'stretch'
							},
							border: false,

							items: [{
								flex: 0.1,
								xtype: 'grid',
								loadMask: true,
								frame: true,
								id: 'user_pfr_list',
								title: 'IN / OUT 리스트',

								tools: [{
									hidden: true,
									id: 'refresh',
									handler: function(e, toolEl, p, tc){
										p.store.reload();
									}
								}],

								listeners: {
									rowcontextmenu: function(self, rowIndex, e){
										e.stopEvent();

										var m = new Ext.menu.Menu({
											items: [{
												icon: '/led-icons/bin_closed.png',
												text: '삭제',
												handler: function(b, e){
													deleteItem();
												}
											}]
										});

										m.showAt(e.getXY());
									}
								},

								store: new Ext.data.ArrayStore({
									fields: [
										'setInSec',
										'setOutSec',
										'setInTC',
										'setOutTC',
										'duration',
										'status',
										{name: 'creation_datetime', type: 'date', dateFormat: 'YmdHis'}
									]
								}),
								viewConfig: {
									emptyText: '등록된 구간 추출 파일이 없습니다.',
									forceFit: true
								},
								sm: new Ext.grid.CheckboxSelectionModel(),
								cm: new Ext.grid.ColumnModel({
									defaults: {
										align: 'center',
										sortable: false,
										menuDisabled: true
									},
									columns: [
										new Ext.grid.CheckboxSelectionModel(),
										{header: 'Set In',		dataIndex: 'setInTC'},
										{header: 'Set Out',	dataIndex: 'setOutTC'},
										{header: '재생시간',	dataIndex: 'duration'},
										{header: '상태',		dataIndex: 'status', width: 50},
										{header: '요청일시',	dataIndex: 'creation_datetime', width: 120, renderer: Ext.util.Format.dateRenderer('Y-m-d H:i:s')}
									]
								}),

								buttons: [{
									<?php
									if (!in_array(CHANNEL_GROUP, $_SESSION['user']['groups'])) {
										echo "disabled: true,";
									}
									?>
									text: '전송',
									handler: function(b, e){
										if (Ext.getCmp('user_pfr_list').store.getCount() == 0)
										{
											Ext.Msg.alert('확인', '구간추출된 파일이 없습니다.');
											return;
										}
										open_cjmall_send_form();
									},
									scope: this
								},{
									hidden: true,
									text: '저해상도',
									handler: function(b, e){
										if (Ext.getCmp('user_pfr_list').store.getCount() == 0)
										{
											Ext.Msg.alert('확인', '구간추출된 파일이 없습니다.');
											return;
										}
										this.taskSend('pfr_low');
									},
									scope: this
								},{
									hidden: true,
									<?php
									if (!in_array(CHANNEL_GROUP, $_SESSION['user']['groups'])
											&& !in_array(NPS_GROUP, $_SESSION['user']['groups']) ) {
										echo "disabled: true,";
									}
									?>
									text: '고해상도',
									handler: function(b, e){
										if (Ext.getCmp('user_pfr_list').store.getCount() == 0)
										{
											Ext.Msg.alert('확인', '구간추출된 파일이 없습니다.');
											return;
										}
										this.taskSend('pfr_high');
									},
									scope: this
								}]
							},{
								hidden: true,
								<?php
								$isDisabledNLEDDButton = checkNLEClient($_SERVER['REMOTE_ADDR']);
								if (empty($isDisabledNLEDDButton))
								{
									echo 'disabled: true,';
								}
								?>
								layout: {
									type: 'hbox',
									pack: 'center',
									align: 'middle'
								},
								height: 25,
								border: false,

								items: [{
									html: '<input type="button" value="이 곳을 Drag 하여 NLE로 Import 하실 수 있습니다." onmousedown="ddToNLE(<?=$content_id?>, \'original\', \'<?=str_replace('\\', '/', FILESERVER)?>\');">'
								}]
							}]
						}]
					}]
				},{
					region: 'east',
					xtype: 'tabpanel',
					id: 'detail_panel',
					title: '메타데이터',
					border: false,
					split: false,
					width: '50%',
					<?php
					$activeTab = 0;
					if (in_array(REVIEW_GROUP, $_SESSION['user']['groups']))
					{
						$activeTab = 2;
					}

					?>

					listeners: {
						afterrender: function(self){
							Ext.Ajax.request({
								url: '/store/get_detail_metadata.php',
								params: {
									content_id: <?=$content_id?>
								},
								callback: function(opts, success, response){
									if(success)
									{
										try
										{
											var r = Ext.decode(response.responseText);

											self.add(r)
											self.doLayout();
											self.activate(0);
										}
										catch(e)
										{
											Ext.Msg.alert(e['name'], e['message']);
										}
									}
									else
									{
										Ext.Msg.alert('오류', opts.url+'<br />'+response.statusText+'('+response.status+')');
									}
								}
							});
						}
					}
				}]
			};

			Ariel.DetailWindow.superclass.initComponent.call(this);
		},

		taskSend: function(type){

			$f('player2').pause();

			Ext.Msg.show({
				icon: Ext.Msg.QUESTION,
				title: '확인',
				msg: '추가하신 일괄 PFR 작업을 등록하시겠습니까?',
				buttons: Ext.Msg.OKCANCEL,
				fn: function(btnID){
					if (btnID == 'ok')
					{
						var w = Ext.Msg.wait('일괄 PFR 작업 등록중...', '정보');

						var pfr_list = [];
						Ext.getCmp('user_pfr_list').store.each(function(i){
							pfr_list.push({
								'in': i.get('setInSec'),
								'out': i.get('setOutSec')
							});
						});

						Ext.Ajax.request({
							url: '/store/add_task_pfr.php',
							params: {
								content_id: <?=$content_id?>,
								type: type,
								pfr_list: Ext.encode(pfr_list)
							},
							callback: function(opts, success, resp){
								if (success)
								{
									try
									{
										w.hide();

										var r = Ext.decode(resp.responseText);
										if (r.success)
										{
											Ext.Msg.alert('정보', '일괄 PFR 작업이 정상적으로 등록되었습니다.');
											Ext.getCmp('user_pfr_list').store.removeAll();
										}
										else
										{
											Ext.Msg.alert('오류', r.msg);
										}
									}
									catch (e)
									{
										Ext.Msg.alert(e['name'], e['message']);
									}
								}
								else
								{
									Ext.Msg.alert( _text('MN01098'), resp.statusText);//'서버 오류'
								}
							}
						})
					}
				}
			});
		}
	});


	new Ariel.DetailWindow().show();

	$f("player2", {src: "/flash/flowplayer/flowplayer.swf", wmode: 'opaque'}, {
		clip: {
			autoPlay: false,
			autoBuffering: true,
			scaling: 'fit',
			provider: 'rtmp'
		},
		plugins: {
			rtmp: {
				url: '/flash/flowplayer/flowplayer.rtmp-3.2.3.swf',
				netConnectionUrl: '<?=STREAMER_ADDR?>'
			}
		}
	});
})()