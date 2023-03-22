<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/lang.php');
fn_checkAuthPermission($_SESSION);
?>

(function(){
	function searchList(){
		Ext.getCmp('grid_tr').getStore().load({
			params : {
				action : 'list_tr',
				content_ud : Ext.getCmp('content_ud').getValue(),
				tr_status : Ext.getCmp('tr_status').getValue(),
				tr_date : Ext.getCmp('tr_date').getValue(),
				start_date : Ext.getCmp('start_date').getValue().format('Ymd')+'000000',
				end_date : Ext.getCmp('end_date').getValue().format('Ymd')+'235959',
				content_title : Ext.getCmp('content_title').getValue(),
				ord_keep_yn : Ext.getCmp('ord_keep_yn').getValue()
			}
		});
	}

	function renderTaskMonitorStatus(v) {
		switch(v){
			case 'complete':
				//>>v = '성 공';
				v = _text('MN00011');
			break;

			case 'down_queue':
			case 'watchFolder':
			case 'queue':
				//>>v = '대 기';
				v = _text('MN00039');
			break;

			case 'error':
				//>>v = '실 패';
				v = _text('MN00012');
			break;

			case 'processing':
			case 'progressing':
				//>>v = '처리중';
				v = _text('MN00262');
			break;

			case 'cancel':
				//>>v = '취소 대기중';
				v = _text('MN00004');
			break;

			case 'canceling':
				//>>v = '취소 중';
				v = _text('MN00004');
			break;

			case 'canceled':
				//>>v = '취소됨';
				v = _text('MN00004');
			break;

			case 'retry':
				//>>v = '재시작';
				v = _text('MN00006');
			break;

			case 'delete':
				//>>v = '재시작';
				v = '삭제';
			break;
		}

		return v;
	}

	function updateKeepStatus(ord_tr_ids, first_yn){
		Ext.Ajax.request({
            url: '/store/request_zodiac/request_list.php',
            params: {
				action : 'update_keep_status',
				ord_tr_id : Ext.encode(ord_tr_ids),
				user_id  : '<?=$_SESSION['user']['user_id']?>',
				status : first_yn
            },
            callback: function (options, success, response) {
				var r = Ext.decode(response.responseText);
                if (r.success) {
                    try {
                        //Ext.Msg.alert('<?=_text('MN00023')?>','<?=_text('MSG02024')?>');
						searchList();

                    } catch (e) {
                        Ext.Msg.alert(e.name, e.message);
                    }
                } else {
                    Ext.Msg.alert( _text('MN01098'), response.statusText);//'서버 오류'
                }
            }
        });
	}

	function deleteContentT(ord_tr_ids){
		Ext.Ajax.request({
            url: '/store/request_zodiac/request_list.php',
            params: {
				action : 'delete_content_tr',
				ord_tr_id : Ext.encode(ord_tr_ids)
            },
            callback: function (options, success, response) {
				var r = Ext.decode(response.responseText);
                if (r.success) {
                    try {
						Ext.Msg.alert('<?=_text('MN00024')?>','<?=_text('MSG00017')?>');//확인, 삭제성공
                    } catch (e) {
                        Ext.Msg.alert(e.name, e.message);
                    }
                } else {
                    Ext.Msg.alert('<?=_text('MN02008')?>', response.statusText);//서버 오류
                }
            }
        });
	}

	var myMask = new Ext.LoadMask(Ext.getBody(), {msg:"loading..."});
	var store_tr = new Ext.data.JsonStore({
		url: '/store/request_zodiac/request_list.php',
		root: 'data',
		totalProperty: 'total',
		idProperty: 'ord_tr_id',
		fields:[
			{ name : 'ord_tr_id' },
			{ name : 'ord_content_id' },
			{ name : 'ord_create_time', type:'date', dateFormat:'YmdHis'  },
			{ name : 'ord_delete_time' , type:'date', dateFormat:'YmdHis' },
			{ name : 'ord_keep_yn' },
			{ name : 'ord_task_id' },
			{ name : 'task_user_id' },
			{ name : 'task_creation_time' , type:'date', dateFormat:'YmdHis' },
			{ name : 'task_complete_time', type:'date', dateFormat:'YmdHis'  },
			{ name : 'task_status' },
			{ name : 'content_ud' },
			{ name : 'content_ud_name' },
			{ name : 'content_title' },
			{ name : 'member_name' },{ name : 'tr_status' },
			{name: 'request_user', convert: function(v, record) {
				var user_name;
				if(Ext.isEmpty(record.member_name)){
					user_name = '';
				}else{
					user_name =  '(' + record.member_name + ')';
				}
				return record.task_user_id + user_name;
			}}
		],
		listeners: {
			load: function(store, records, opts){
				myMask.hide();
			}
		}
	});

	var d = new Date();

	return {
		xtype : 'panel',
		//cls: 'proxima_customize',
		title: '<span class="user_span"><span class="icon_title"><i class=""></i></span><span class="main_title_header">'+_text('MN02145')+'</span></span>',
		cls: 'grid_title_customize proxima_customize',
		border: false,
		layout : 'border',
		align:'stretch',
		border: false,
		items : [{
			xtype: 'form',
			frame: false,
			border : false,
			region : 'north',
			style: {
				paddingTop: '15px',
				background : '#F0F0F0'
			},
			height : 80,
			border: false,
			bodyStyle:{"background-color":"#F0F0F0"},
			defaults: {
				labelStyle: 'text-align:right;',
				anchor: '100%',
				labelSeparator: '',
				labelWidth : 80
			},
			autoScroll: true,
			items : [{
				layout:'hbox',
				frame: false,
				border : false,
				bodyStyle:{"background-color":"#F0F0F0"},
				items:[{
					layout: 'form',
					labelSeparator: '',
					frame: false,
					border : false,
					bodyStyle:{"background-color":"#F0F0F0"},
					defaults: {
						labelStyle: 'text-align:right;',
						anchor: '100%',
						labelWidth : 30
					},
					width: 250,
					items:[{
						xtype: 'combo',
						fieldLabel :  _text('MN02084'),//'구분'
						id: 'content_ud',
						width: 130,
						triggerAction: 'all',
						editable: false,
						mode: 'local',
						store: [
							['all', '<?=_text('MN00008')?>'],//전체
							['506', '<?=_text('MN02087')?>'],//비디오
							['518', '<?=_text('MN02088')?>']//그래픽
						],
						value: 'all',
						listeners: {

						}
					},{
						xtype: 'combo',
						fieldLabel :  _text('MN00243')+_text('MN00138'),//'전송상태'
						id: 'tr_status',
						width: 130,
						triggerAction: 'all',
						editable: false,
						mode: 'local',
						store: [
							['all', '<?=_text('MN00008')?>'],//전체
							['queue', '<?=_text('MN00039')?>'],//대기
							['processing', '<?=_text('MN00262')?>'],//처리중
							['complete', '<?=_text('MN00011')?>']//완료
						],
						value: 'all',
						listeners: {

						}
					}]
				},{
					layout: 'form',
					labelSeparator: '',
					frame: false,
					border : false,
					bodyStyle:{"background-color":"#F0F0F0"},
					defaults: {
						labelStyle: 'text-align:right;',
						anchor: '100%'

					},
					labelWidth : 150,
					width: 300,
					items:[{//영구보관
						xtype: 'combo',
						fieldLabel :  _text('MN02086'),//'영구보관'
						id: 'ord_keep_yn',
						width: 130,
						triggerAction: 'all',
						editable: false,
						mode: 'local',
						store: [
							['all', _text('MN00008')],//전체
							['Y', 'Y'],
							['N', 'N']
						],
						value: 'all',
						listeners: {

						}
					},{
						xtype : 'textfield',
						fieldLabel :  _text('MN00249'),//'제목'
						width : 130,
						id : 'content_title',
						listeners:{
							specialkey: function(field, e){
								if(e.getKey() == e.ENTER)
								{
									searchList();
								}
							}
						}
					}]
				},{
					layout: 'form',
					labelSeparator: '',
					frame: false,
					border : false,
					bodyStyle:{"background-color":"#F0F0F0"},
					defaults: {
						labelStyle: 'text-align:right;',
						anchor: '100%',
						labelWidth : 5
					},
					style: {
						paddingLeft: '50px',
						background : '#F0F0F0'
					},
					width: 420,
					items:[{
						xtype: 'compositefield',
						hideLabel: true,
						style :{"background-color":"#F0F0F0"},
						fieldLabel: '&nbsp;',//날짜

						invalidClass : 'x-form-invalid2',
						items: [{
							xtype: 'combo',
							fieldLabel :  _text('MN00354'),//'날짜'
							id: 'tr_date',
							width: 140,
							triggerAction: 'all',
							editable: false,
							mode: 'local',
							store: [
								//['all', _text('MN00244')],//전체
								['ord_create_time', '<?=_text('MN00243')?>'+'<?=_text('MN00066')?>'+'<?=_text('MN00405')?>'],//전송 요청일
								['task_creation_time', '<?=_text('MN00243')?>'+'<?=_text('MN02085')?>'+'<?=_text('MN00405')?>'],//전송 시작일
								['task_complete_time', '<?=_text('MN00243')?>'+'<?=_text('MN02080')?>'+'<?=_text('MN00405')?>'],//전송 완료일
								['ord_delete_time', '<?=_text('MN00034')?>'+'<?=_text('MN00405')?>']//삭제일
							],
							//value: 'all',
							listeners: {

							}
						},{
							xtype: 'datefield',
							width: 100,
							id: 'start_date',
							format: 'Y-m-d',
							altFormats: 'Y-m-d|Ymd',
							value: d.add(Date.MONTH, -1).format('Y-m-d'),
							maxValue: d.clearTime(),
							autoCreate: {tag: 'input', type: 'text', size: '10', autocomplete: 'off', maxlength: '10'},
							listeners: {
								select: function(self, date){
									Ext.getCmp('end_date').setMinValue(self.value);
								}
							}
						},{
							xtype : 'displayfield',
							value : '~'
						},{
							xtype: 'datefield',
							width: 100,
							id: 'end_date',
							format: 'Y-m-d',
							altFormats: 'Y-m-d|Ymd',
							value: d,
							maxValue: d.clearTime(),
							autoCreate: {tag: 'input', type: 'text', size: '10', autocomplete: 'off', maxlength: '10'}
						}]
					}]
				},{
					layout: 'form',
					labelSeparator: '',
					frame: false,
					border : false,
					margins : '-3 0 0 0',
					bodyStyle:{"background-color":"#F0F0F0"},
					defaults: {
						//labelStyle: 'text-align:right;',
						//anchor: '80%',
						//labelWidth : 5
					},
					//width: 110,
					items:[{
						xtype : 'button',
						cls: 'proxima_button_customize',
						width: 30,
						height:28,
						style: {float: 'left'},
						text : '<span style="position:relative;top:1px;" title="'+_text('MN00059')+'"><i class="fa fa-search" style="font-size:13px;color:white;"></i></span>',
						handler : function(btn, e){
							searchList();
						}
					},{
						xtype : 'button',
						cls: 'proxima_button_customize',
						width: 30,
						height:28,
						//hidden : true,
						style: {float: 'left'},
						text : '<span style="position:relative;top:1px;" title="'+_text('MN02096')+'"><i class="fa fa-rotate-left" style="font-size:13px;color:white;"></i></span>',
						handler : function(btn, e){
							//console.log(btn.ownerCt.ownerCt.ownerCt);
							//console.log(btn.ownerCt.ownerCt.ownerCt.getForm());
							btn.ownerCt.ownerCt.ownerCt.getForm().reset();
							
							Ext.getCmp('grid_tr').getStore().load({
								params : {
									action : 'list_tr',
									content_ud : Ext.getCmp('content_ud').getValue(),
									task_status : Ext.getCmp('task_status').getValue(),
									tr_date : Ext.getCmp('tr_date').getValue(),
									start_date : Ext.getCmp('start_date').getValue().format('Ymd')+'000000',
									end_date : Ext.getCmp('end_date').getValue().format('Ymd')+'235959',
									content_title : Ext.getCmp('content_title').getValue(),
									ord_keep_yn : Ext.getCmp('ord_keep_yn').getValue()
								}
							});
							
						}
					}]
				}]
			}]
		},{
			xtype: 'grid',
			stripeRows: true,
			region : 'center',
			id: 'grid_tr',
			autoScroll: true,
			border: false,
			loadMask: true,
			store: store_tr,
			cm: new Ext.grid.ColumnModel({
				columns:[
					new Ext.grid.RowNumberer(),
					{header: 'ID', dataIndex: 'ord_tr_id', width: 40, align: 'center'},
					{header: '<?=_text('MN02084')?>', dataIndex: 'content_ud_name', width: 20, align: 'center'},//구분
					{header: '<?=_text('MN00243')?>'+'<?=_text('MN00138')?>', dataIndex: 'tr_status', width: 20, align: 'center', renderer : renderTaskMonitorStatus},//전송 상태
					{header: '<?=_text('MN00243')?>'+'<?=_text('MN00066')?>'+'<?=_text('MN00405')?>', dataIndex: 'ord_create_time', align: 'center', renderer: Ext.util.Format.dateRenderer('Y-m-d h:i:s'), width: 50, align: 'center'},//전송 요청 시간
					{header: '<?=_text('MN00243')?>'+'<?=_text('MN02085')?>'+'<?=_text('MN00405')?>', dataIndex: 'task_creation_time', renderer: Ext.util.Format.dateRenderer('Y-m-d h:i:s'), width: 50, align: 'center'},//전송 시작 시간
					{header: '<?=_text('MN00243')?>'+'<?=_text('MN02080')?>'+'<?=_text('MN00405')?>', dataIndex: 'task_complete_time', renderer: Ext.util.Format.dateRenderer('Y-m-d h:i:s'), width: 50, align: 'center'},//전송 완료 시간
					{header: '<?=_text('MN00034')?>'+'<?=_text('MN00405')?>', dataIndex: 'ord_delete_time',renderer: Ext.util.Format.dateRenderer('Y-m-d h:i:s'), width: 50, align: 'center'},//삭제 시간
					{header: '<?=_text('MN00249')?>', dataIndex: 'content_title'},//제목
					{header: '<?=_text('MN02086')?>', dataIndex: 'ord_keep_yn', width: 20, align: 'center'}//영구 보관
				]
			}),
			view: new Ext.ux.grid.BufferView({
				scrollDelay: false,
				forceFit : true,
				emptyText: '<?=_text('MSG00148')?>'//결과 값이 없습니다
			}),
			bbar: new Ext.PagingToolbar({
				store: store_tr,
				pageSize: 50
			}),
			sm: new Ext.grid.RowSelectionModel({
				//singleSelect: true
			}),
			listeners: {
				afterrender: function(self){

					self.getStore().load({
						params : {
							action : 'list_tr',
							content_ud : Ext.getCmp('content_ud').getValue(),
							task_status : Ext.getCmp('task_status').getValue(),
							tr_date : Ext.getCmp('tr_date').getValue(),
							start_date : Ext.getCmp('start_date').getValue().format('Ymd')+'000000',
							end_date : Ext.getCmp('end_date').getValue().format('Ymd')+'235959',
							content_title : Ext.getCmp('content_title').getValue(),
							ord_keep_yn : Ext.getCmp('ord_keep_yn').getValue()
						}
					});
				},
				 rowdblclick: function (self, row_index, e) {
					var rowRecord = self.getSelectionModel().getSelected();
				},
				rowcontextmenu: function (self, row_index, e) {

					//self.getSelectionModel().selectRow(row_index);
					var name_menu, icon;
					var ord_tr_ids = new Array();
					var ord_tr_ids_o = new Array();
					var rowRecord = self.getSelectionModel().getSelections();
					var first_yn = rowRecord[0].get('ord_keep_yn');
					if( first_yn == 'N' ){
						name_menu = '<?=_text('MN02089')?>';//영구보관 신청
						icon = '/led-icons/asterisk_orange.png';
						msg = '<?=_text('MN02089')?>'+' : '+'<?=_text('MSG02039')?>';//영구보관 신청 : 이 작업을 진행하시겠습니까?
					}else{
						name_menu = '<?=_text('MN02090')?>';//영구보관 취소
						icon = '/led-icons/cancel.png';
						msg = '<?=_text('MN02090')?>'+' : '+'<?=_text('MSG02039')?>';//영구보관 취소 : 이 작업을 진행하시겠습니까?
					}
					Ext.each(rowRecord, function(r){
						if(r.get('ord_keep_yn') == first_yn){
							ord_tr_ids.push(r.get('ord_tr_id'));
						}
					});

					Ext.each(rowRecord, function(r){
						ord_tr_ids_o.push(r.get('ord_tr_id'));
					});


					var menu = new Ext.menu.Menu({
						items: [{
							text: name_menu,
							icon: icon,
							handler: function (btn, e) {
								Ext.Msg.show({
									title : '<?=_text('MN00024')?>',//확인
									msg : msg,
									buttons: Ext.Msg.OKCANCEL,
									fn: function(btnID){
										if (btnID == 'ok') {
											updateKeepStatus(ord_tr_ids, first_yn);
										}
									}
								});
								menu.hide();
							}
						},{
							text: '<?=_text('MN00034')?>',//삭제
							icon: '/led-icons/delete.png',
							handler: function (btn, e) {
								if( ord_tr_ids.length < 1 ){
									Ext.Msg.alert('<?=_text('MN00023')?>',  '<?=_text('MSG00022')?>');//알림, 삭제하실 항목을 선택하여주세요
								}else{
									Ext.Msg.show({
										title : '<?=_text('MN00024')?>',//확인
										msg :  '<?=_text('MSG00140')?>',//삭제 하시겠습니까?
										buttons: Ext.Msg.OKCANCEL,
										fn: function(btnID){
											if (btnID == 'ok') {
												deleteContentT(ord_tr_ids_o);
												searchList();
											}
										}
									});
								}
								menu.hide();
							}
						}]
					});
					e.stopEvent();

					menu.showAt(e.getXY());
				}
			}
		}]
	}
})()