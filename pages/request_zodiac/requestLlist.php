<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/lang.php');
fn_checkAuthPermission($_SESSION);

$data_type = $_REQUEST['t'];
?>

(function(){
	var myMask = new Ext.LoadMask(Ext.getBody(), {msg:"loading..."});
	var store = new Ext.data.JsonStore({
		url: '/store/request_zodiac/request_list.php',
		root: 'data',
		totalProperty: 'total',
		idProperty: 'ord_id',
		fields:[
			{ name : 'ord_id' },
			{ name : 'ord_ctt' },
			{ name : 'ord_div_cd' },
			{ name : 'inputr_id' },
			{ name : 'inputr_name' },
			{ name : 'graphic_reqest_ty'},
			{ name : 'graphic_reqest_ty_ln'},
			{name: 'worker', convert: function(v, record) {
				if(Ext.isEmpty(record.inputr_name)){
					record.inputr_name = '-';
				}
				return record.inputr_id + '(' + record.inputr_name + ')';
			}},
			{name: 'register', convert: function(v, record) {
				if(Ext.isEmpty(record.ord_work_name)){
					return '';
				}else{
					return record.ord_work_id + '(' + record.ord_work_name + ')';
				}

			}},
			{ name : 'ord_meta_cd' },
			{ name : 'dept_cd' },
			{ name : 'dept_name' },
			{ name : 'ord_status' },
			{ name : 'title' },
			{ name : 'ch_div_cd' },
			{ name : 'ord_work_ctt' },
			{ name : 'artcl_id' },
			{ name : 'rd_id' },
			{ name : 'rd_seq' },
			{ name : 'updtr_id' },
			{ name : 'ord_work_id' },
			{ name : 'ord_work_name' },
			{ name : 'input_dtm', type:'date', dateFormat:'YmdHis' },
			{ name : 'updt_dtm', type:'date', dateFormat:'YmdHis' },
			{ name : 'expt_ord_end_dtm', type:'date', dateFormat:'YmdHis' },
			{name: 'request_flag_read', convert: function(v, record) {
					if(record.read_flag == 1){
						record.read_flag = '<span class="fa-stack " style="position:relative;height: 17px;margin-top: -3px;"><i class="fa fa-certificate fa-stack-1x" style="font-size:17px;color:#ff6600;"></i><strong class="fa fa-inverse fa-stack-1x fa-text" style="position:relative;font-size:10px;font-weight:bold;">N</strong></span>';
					}else{
						record.read_flag = '';
					}
					return record.read_flag;
				}
			}
		],
		listeners: {
			load: function(store, records, opts){
				myMask.hide();
			}
		}
	});

	var d = new Date();

	function showDetail(rowRecord) {
        Ext.Ajax.request({
            url: '/javascript/withZodiac/viewDetailRequest.php',
            params: {
                ord_id : rowRecord.get('ord_id'),
				title : rowRecord.get('title'),
				ord_ctt : rowRecord.get('ord_ctt'),
				ord_work_id : rowRecord.get('ord_work_id'),
				ord_type : rowRecord.get('ord_meta_cd'),
				artcl_id : rowRecord.get('artcl_id'),
				rd_id : rowRecord.get('rd_id'),
				rd_seq : rowRecord.get('rd_seq'),
				graphic_reqest_ty: rowRecord.get('graphic_reqest_ty')
            },
            callback: function (options, success, response) {
                if (success) {
                    try {
                        Ext.decode(response.responseText);
                    } catch (e) {
                        Ext.Msg.alert(e.name, e.message);
                    }
                } else {
                    Ext.Msg.alert( _text('MN01098'), response.statusText);//'서버 오류'
                }
            }
        });
    }

	function updateStatus(ord_id){
		 Ext.Ajax.request({
            url: '/store/request_zodiac/request_list.php',
            params: {
                ord_id : ord_id,
				action : 'update_status'
            },
            callback: function (options, success, response) {
                if (success) {
                    try {
                        var r = Ext.decode(response.responseText);
						if(r.success){
							//Ext.Msg.alert( _text('MN00023'), _text('MSG02024'));//('알림','작업 완료처리 되었습니다.');
							Ext.getCmp('request_list').getStore().reload();
						}
                    } catch (e) {
                        Ext.Msg.alert(e.name, e.message);
                    }
                } else {
                    Ext.Msg.alert( _text('MN01098'), response.statusText);//'서버 오류'
                }
            }
        });
	}

	function insertTime(ord_id, form){
		Ext.Ajax.request({
            url: '/store/request_zodiac/request_list.php',
            params: {
                ord_id : ord_id,
				action : 'insert_time',
				values : Ext.encode(form)
            },
            callback: function (options, success, response) {
                if (success) {
                    try {
                        var r = Ext.decode(response.responseText);
						if(r.success){
							//Ext.Msg.alert( _text('MN00023'), _text('MSG02024') );//('알림','입력 되었습니다.')
							Ext.getCmp('request_list').getStore().reload();
						}
                    } catch (e) {
                        Ext.Msg.alert(e.name, e.message);
                    }
                } else {
                    Ext.Msg.alert( _text('MN01098'), response.statusText);//'서버 오류'
                }
            }
        });
	}

	function calendarClock() {
		var time = new Date()
		var year = time.getYear()
		var month = time.getMonth() +1
		var day = time.getDate()

		var hour = time.getHours()
		var minute = time.getMinutes()
		var second = time.getSeconds()
		var store = " "

		store += ((hour > 12) ? (hour - 12) : hour)
		store += ((minute < 10) ? ":0" : ":") + minute
		store += ((second < 10) ? ":0" : ":") + second
		store += (hour >= 12) ? " P.M" : " A.M"

		document.time.clock.value = store
		document.time.calendar.value = " "+ year + "년" + month + "월" + day + "일";
		setTimeout("calendarClock()", 1000)
	}

	function windowTime(ord_id){
		var win_time = new Ext.Window({
			title: _text('MN02098'),//'종료시각 입력',
			modal:true,
			width: 300,
			height: 180,
			layout: 'fit',
			items: [{
				xtype: 'form',
				border:false,
				frame : false,
				labelWidth : 50,
				defaults : {
					anchor : '98%',
					margins: '10 10 10 10',
					labelSeparator: ''
				},
				bodyStyle:{"background-color":"white"},
				buttons:[{
					text : '<span style="position:relative;top:1px;"><i class="fa fa-check" style="font-size:13px;"></i></span>&nbsp;'+_text('MN00046'),//'저장'
					handler:function(self){
						var form = self.ownerCt.ownerCt.getForm();
						if(form.isValid()){
							insertTime(ord_id, form.getValues());
							win_time.destroy();
						}else{
							Ext.Msg.alert( _text('MN00023'), _text('MSG00125'));//필수 항목에 값을 넣어주세요.
							return;
						}
					}
				},{
					text : '<span style="position:relative;top:1px;"><i class="fa fa-close" style="font-size:13px;"></i></span>&nbsp;'+_text('MN00031'),//'닫기'
					handler: function(self){
						self.ownerCt.ownerCt.ownerCt.destroy();
					}
				}],
				items: [{
					xtype : 'displayfield',
					value : ''
				},{
					xtype : 'datefield',
					width : 95,
					allowBlank: false,
					name : 'day',
					fieldLabel : '&nbsp;&nbsp;'+ _text('MN00354'),//'&nbsp;&nbsp;날짜',
					format: 'Y-m-d',
					listeners: {
						render: function(self){
							self.setMinValue(d.format('Y-m-d'));
							self.setValue(d.format('Y-m-d'));
						}
					}
				},{
					xtype : 'compositefield',
					fieldLabel :  '&nbsp;&nbsp;'+_text('MN02173'),//시간
					style : {
						background : 'white'
					},
					items : [{
						xtype:'combo',
						width : 105,
						allowBlank: false,
						hideLabel : true,
						//fieldLabel : '&nbsp;&nbsp;'+ _text('MN02173'),//'&nbsp;&nbsp;시',
						triggerAction: 'all',
						editable: false,
						store: new Ext.data.JsonStore({
							autoLoad: true,
							url: '/store/request_zodiac/request_list.php',
							baseParams: {
								action : 'hour'
							},
							root: 'data',
							fields: [
								{name: 'name', type: 'string'},
								{name: 'value', type: 'int'}
							]
						}),
						mode: 'remote',
						hiddenName: 'hour',
						hiddenValue: 'value',
						valueField: 'value',
						displayField: 'name',
						typeAhead: true,
						triggerAction: 'all',
						forceSelection: true,
						editable: false,
						value : '00',
						listeners: {
							select: function (cmb, record, index) {
							}
						}
					},{
					xtype : 'displayfield',
					width : 5,
					value : ':'
				},{
						xtype:'combo',
						width : 105,
						allowBlank: false,
						hideLabel : true,
						//fieldLabel : '&nbsp;&nbsp;'+ _text('MN00184'),//'&nbsp;&nbsp;분',
						triggerAction: 'all',
						editable: false,
						store: new Ext.data.JsonStore({
							autoLoad: true,
							url: '/store/request_zodiac/request_list.php',
							baseParams: {
								action : 'minute'
							},
							root: 'data',
							fields: [
								{name: 'name', type: 'string'},
								{name: 'value', type: 'int'}
							]
						}),
						mode: 'remote',
						hiddenName: 'minute',
						hiddenValue: 'value',
						valueField: 'value',
						displayField: 'name',
						typeAhead: true,
						triggerAction: 'all',
						forceSelection: true,
						editable: false,
						value : '00',
						listeners: {
							select: function (cmb, record, index) {
							}
						}
					}]
				}]}
			]
		})	.show();
	}

	function deleteList(ord_id){
		Ext.Ajax.request({
            url: '/store/request_zodiac/request_list.php',
            params: {
                ord_id : ord_id,
				action : 'delete_list'
            },
            callback: function (options, success, response) {
                if (success) {
                    try {
                        var r = Ext.decode(response.responseText);
						if(r.success){
							//Ext.Msg.alert( _text('MN00023'),'삭제 되었습니다.');
							Ext.getCmp('request_list').getStore().reload();
						}
                    } catch (e) {
                        Ext.Msg.alert(e.name, e.message);
                    }
                } else {
                    Ext.Msg.alert( _text('MN01098'), response.statusText);//'서버 오류'
                }
            }
        });
	}

	function showRequest(self){
		Ext.Ajax.request({
            url: '/pages/request_zodiac/requestListG.php',
            params: {
				type_content : self.ownerCt.ownerCt.ownerCt.title
            },
            callback: function (options, success, response) {
                if (success) {
                    try {
                        Ext.decode(response.responseText);
                    } catch (e) {
                        Ext.Msg.alert(e.name, e.message);
                    }
                } else {
                    Ext.Msg.alert( _text('MN01098'), response.statusText);//'서버 오류'
                }
            }
        });
	}

	function searchArticle(){
		var type;
		if( Ext.getCmp('request_tp').getSelectionModel().getSelectedNode().attributes.title == '<span class="user_span"><span class="icon_title"><i class="fa fa-play"></i></span><span class="main_title_header">'+_text('MN02087')+'</span></span>' ){//'영상'
			type = 'video';
		}else{
			type = 'graphic';
		}
		Ext.getCmp('request_list').getStore().load({
			params : {
				action : 'list',
				start_date : Ext.getCmp('start_date').getValue().format('Ymd')+'000000',
				end_date : Ext.getCmp('end_date').getValue().format('Ymd')+'235959',
				dept : Ext.getCmp('work_team').getValue(),
				//status : Ext.getCmp('work_status').getValue(),
				status : Ext.getCmp('work_status').getValue().inputValue,
				search_text : Ext.getCmp('search_text').getValue(),
				type : type
			}
		});
	}
	function cellStyle(v){
        return '<span style=\"font-size:15px;\">' + v + '</span>'
	}
	var title_panel;
	if('<?=$data_type?>' == 'video'){
		title_panel = _text('MN02087');
	}else{
		title_panel = _text('MN02088');
	}
	return {
		xtype : 'panel',
		id: 'request_L_list',
		layout : 'border',
		title: '<span class="user_span"><span class="icon_title"><i class=""></i></span><span class="main_title_header">'+title_panel+'</span></span>',
		cls: 'grid_title_customize',
		align:'stretch',
		border: false,
		tbar: [
			{
				xtype : 'displayfield',
				value :  '<span class="x-form-item" style="padding-left:15px;">'+_text('MN00181')+'&nbsp;</span>'
			},{
				xtype: 'combo',
				width: 130,
				id: 'work_team',
				//fieldLabel :  _text('MN00181'),//'부서'
				triggerAction: 'all',
				editable: false,
				store: new Ext.data.JsonStore({
					//autoLoad: true,
					url: '/store/request_zodiac/request_list.php',
					baseParams: {
						action : 'dept'
					},
					root: 'data',
					idProperty: 'module_info_id',
					fields: [
						{name: 'name', type: 'string'},
						{name: 'value', type: 'int'}
					]
				}),
				mode: 'remote',
				hiddenName: 'name',
				hiddenValue: 'value',
				valueField: 'value',
				displayField: 'name',
				typeAhead: true,
				triggerAction: 'all',
				forceSelection: true,
				editable: false,
				value : _text('MN00008'),
				listeners: {
					select: function (cmb, record, index) {
					}
				}
			},{
				hidden:true,
				xtype : 'displayfield',
				value :  '<span class="x-form-item" style="padding-left:15px;">'+_text('MN00138')+'&nbsp;</span>'
				
			},{
				hidden:true,
				xtype: 'combo',
				//bodyStyle:{"background-color":"#FFFFFF"},
				width: 100,
				id: 'work_status_hidden',
				//fieldLabel :  _text('MN00138'),//'상태'
				triggerAction: 'all',
				editable: false,
				mode: 'local',
				store: [
					['all', _text('MN00008')],//'전체'
					['ready','요청'],//'대기'
					['working', '진행중'],//'할당'
					['cancel', '취소'],//'취소'
					['complete', _text('MN02178')]//'완료'
				],
				value: 'all',
				listeners: {
	
				}
			},{
				xtype : 'displayfield',
				value :  '<span class="x-form-item" style="padding-left:15px;">'+_text('MN02349')+'&nbsp;</span>'
			},{
				xtype : 'textfield',
				width: 200,
				//style: {float: 'left'},
				//fieldLabel : _text('MN02349'),
				id : 'search_text',
				listeners:{
					render: function(self){
						var search_text_dom = document.getElementById("search_text");
						search_text_dom.placeholder = _text('MN00249')+' '+_text('MN02093');
					},
					specialkey: function(field, e){
						if(e.getKey() == e.ENTER)
						{
							searchArticle();
						}
					}
				}
			},{
				xtype : 'displayfield',
				value :  '<span class="x-form-item" style="padding-left:15px;">'+'요청일시'+'&nbsp;</span>'
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
				width: 3,
				value : ' '
			},{
				xtype : 'displayfield',
				width: 10,
				value : '~'
			},{
				xtype: 'datefield',
				style: {"float":"left"},
				width: 100,
				id: 'end_date',
				format: 'Y-m-d',
				altFormats: 'Y-m-d|Ymd',
				value: d,
				maxValue: d.clearTime(),
				autoCreate: {tag: 'input', type: 'text', size: '10', autocomplete: 'off', maxlength: '10'}
			},'-','상태',{
				xtype:'tbspacer',
				width:10
			},{
				xtype:'radiogroup',
				id: 'work_status',
                name: 'work_status',
				width: 220,
				columns:[.25,.22,.28,.25],
                items: [
                    { boxLabel: '전체', name: 'work_status', inputValue: 'all', checked: true },
                    { boxLabel: '요청', name: 'work_status', inputValue: 'ready' },
                    { boxLabel: '진행중', name: 'work_status', inputValue: 'working' },
                    { boxLabel: '완료', name: 'work_status', inputValue: 'complete' }
                ]
			},{
				xtype : 'button',
				cls: 'proxima_button_customize',
				width: 30,
			   	height: 32,
				text : '<span style="position:relative;top:1px;" title="'+_text('MN00059')+'"><i class="fa fa-search" style="font-size:13px;color:white;"></i></span>',
				handler : function(btn, e){
					searchArticle();
				}
			},{
				xtype : 'button',
				cls: 'proxima_button_customize',
				width: 30,
				height: 32,
				text : '<span style="position:relative;top:1px;" title="'+_text('MN02096')+'"><i class="fa fa-rotate-left" style="font-size:13px;color:white;"></i></span>',
				handler : function(btn, e){
					Ext.getCmp('work_team').setValue(_text('MN00008'));
					Ext.getCmp('work_status').setValue(_text('MN00008'));
					Ext.getCmp('search_text').setValue('');
					Ext.getCmp('start_date').setValue(d.add(Date.MONTH, -1).format('Y-m-d'));
					Ext.getCmp('end_date').setValue(d.format('Y-m-d'));
					
					var type;
					if( Ext.getCmp('request_tp').getSelectionModel().getSelectedNode().attributes.title == '<span class="user_span"><span class="icon_title"><i class="fa fa-play"></i></span><span class="main_title_header">'+_text('MN02087')+'</span></span>' ){//'영상'
						type = 'video';
					}else{
						type = 'graphic';
					}
					Ext.getCmp('request_list').getStore().load({
						params : {
							action : 'list',
							type : type
						}
					});
					
				}
			}
		],
		items : [{
			xtype: 'grid',
			id: 'request_list',
			cls: 'proxima_customize',
			stripeRows: true,
			region : 'center',
			border: false,
			loadMask: true,
			style: {
				borderTop: '1px solid #d0d0d0'
			},
			store: store,
			cm: new Ext.grid.ColumnModel({
				columns:[
					new Ext.grid.RowNumberer(),
					//{header: 'ID', dataIndex: 'ord_id', width: 30, hidden: true},
					{header: '유형', dataIndex: 'ord_meta_cd', width: 30, align: 'center', 
						renderer:function(v){
							switch(v){
								case 'Graphic':
									return cellStyle('그래픽의뢰')
								break;
								case 'Video':
									return cellStyle('영상편집의뢰')
								break;
							}
						}
					},//'구분'
					{
                        header: '의뢰유형', id: 'graphic_reqest_ty', dataIndex: 'graphic_reqest_ty_ln', width: 20, align: 'left',
              
                    },
					//{header: '<span style="position:relative;top:1px;"><img src="/led-icons/new_1.png"/></span>', dataIndex: 'request_flag_read',width: 15,align: 'center'},//'icon'
					{header: '<span class="fa-stack " style="position:relative;height: 17px;margin-top: -9px;"><i class="fa fa-certificate fa-stack-1x" style="font-size:17px;color:#ff6600;"></i><strong class="fa fa-inverse fa-stack-1x fa-text" style="position:relative;font-size:10px;font-weight:bold;">N</strong></span>', dataIndex: 'request_flag_read',width: 15,align: 'center', hidden:true},//'icon'
					{header: _text('MN00249'), dataIndex: 'title',renderer:cellStyle},//'제목'
					{header: '내용', dataIndex: 'ord_ctt',renderer:cellStyle},//'request'
					{header: '의뢰자', dataIndex: 'register', width: 40, hidden:true},//'요청자'
					{header: '의뢰자', width: 40, 
						renderer:function(v,p,record){
							if(record.get('inputr_id') !== ''){
								return cellStyle(record.get('inputr_id')+'('+record.get('inputr_name')+')');
							}
						}
					},//'요청자'
					{header: '담당자', width: 40, align: 'center', 
						renderer:function(v,p,record){
                            if(record.get('ord_work_id') !== ''){
								return cellStyle(record.get('ord_work_id')+'('+record.get('ord_work_name')+')');
							};
						}
					},//'작업자'
					{header: '담당자', dataIndex: 'worker', width: 40, align: 'center', hidden:true},//'작업자'
					{header: _text('MN00181'), dataIndex: 'dept_name', width: 30, hidden:true},//'부서'
					
					//{header: '요청일시', dataIndex: 'input_dtm', align: 'center', renderer: Ext.util.Format.dateRenderer('Y-m-d'), width: 30},//'업무 의뢰일'
					{header: '요청일시', dataIndex: 'input_dtm', align: 'center', 
						renderer: function(v){
							if(!Ext.isEmpty(v)){
								var date = v.format('Y-m-d');
								return cellStyle(date);
							}
						}, width: 30
					},
					//{header: '완료일시', dataIndex: 'expt_ord_end_dtm', width: 40, renderer: Ext.util.Format.dateRenderer('Y-m-d H:i')},//'종료시각'
					{header: '완료일시', dataIndex: 'expt_ord_end_dtm', width: 40, 
						renderer: function(v){
							if(!Ext.isEmpty(v)){
								var date = v.format('Y-m-d');
								return cellStyle(date);
							}
						}
					},
					{header: '의뢰상태', dataIndex: 'ord_status', width: 20, align: 'center',
						renderer:function(v){
							switch(v){
								case 'Queued':
                                    return cellStyle('요청');
								break;
								case 'Assigned':
									return cellStyle('작업중');
								break;
								case 'Completed':
                                    return cellStyle('완료');
								break;
								case 'Cancel':
                                    return cellStyle('취소');
								break;
							}
						}
					},//'상태'
					
					

				]
			}),
			view: new Ext.ux.grid.BufferView({
				scrollDelay: false,
				forceFit : true,
				getRowClass: function (record, rowIndex, rp, ds) {
					return 'custom-grid-row';
                },
				emptyText: _text('MSG00148')//결과 값이 없습니다
			}),
			sm: new Ext.grid.RowSelectionModel({
				singleSelect: true
			}),
			bbar: {
				xtype: 'paging',
				pageSize: 20,
				displayInfo: true,
				store: store
			},
			listeners: {
				afterrender: function(self){
					var graphicReqestTyIndex = self.getColumnModel().getIndexById('graphic_reqest_ty');

					var type;
					if(self.ownerCt.ownerCt.ownerCt.get(0).getSelectionModel().getSelectedNode().attributes.title == '<span class="user_span"><span class="icon_title"><i class="fa fa-play"></i></span><span class="main_title_header">'+_text('MN02087')+'</span></span>'){
						type = 'video';
						self.getColumnModel().setHidden(graphicReqestTyIndex, true);
					}else{
						type = 'graphic';
						self.getColumnModel().setHidden(graphicReqestTyIndex, false);
					}

					self.getStore().load({
						params : {
							action : 'list',
							start_date : Ext.getCmp('start_date').getValue().format('Ymd')+'000000',
							end_date : Ext.getCmp('end_date').getValue().format('Ymd')+'235959',
							//dept : Ext.getCmp('work_team').getValue(),
							//status : Ext.getCmp('work_status').getValue(),
							type : type
						}
					});
				},
				 rowdblclick: function (self, row_index, e) {
					var rowRecord = self.getSelectionModel().getSelected();
					showDetail(rowRecord);
				},
				rowcontextmenu: function (self, row_index, e) {
					e.stopEvent();

					self.getSelectionModel().selectRow(row_index);

					var rowRecord = self.getSelectionModel().getSelected();
					var ord_id = rowRecord.get('ord_id');
					var status_row = rowRecord.get('ord_status');

					if(status_row == _text('MN02097')){//'할당'
						var menu = new Ext.menu.Menu({
							items: [{
								text: _text('MN00234'),//작업 완료
								icon: '/led-icons/accept.png',
								handler: function (btn, e) {
									updateStatus(ord_id);
									menu.hide();
								}
							},{
								text: _text('MN02098'),//'종료시간 입력'
								icon: '/led-icons/pencil.png',
								handler: function (btn, e) {
									windowTime(ord_id);
									menu.hide();
								}
							},{
								text: _text('MN00034'),//'삭제'
								icon: '/led-icons/delete.png',
								handler: function (btn, e) {
									Ext.Msg.show({
										title : _text('MN00024'),//'알림'
										msg : _text('MSG00140'),//'삭제 하시겠습니까?'
										buttons: Ext.Msg.OKCANCEL,
										fn: function(btnID){
											if (btnID == 'ok') {
												deleteList(ord_id);
											}
										}
									});
									menu.hide();
								}
							}]
						});
					}else{
						var menu = new Ext.menu.Menu({
							items: [{
								text: _text('MN00034'),//'삭제'
								icon: '/led-icons/delete.png',
								handler: function (btn, e) {
									Ext.Msg.show({
										title : _text('MN00024'),//'알림'
										msg : _text('MSG00140'),//'삭제 하시겠습니까?'
										buttons: Ext.Msg.OKCANCEL,
										fn: function(btnID){
											if (btnID == 'ok') {
												deleteList(ord_id);
											}
										}
									});
									menu.hide();
								}
							}]
						});
					}

					menu.showAt(e.getXY());
				}
			}
		}],
		listeners: {
			afterrender:function(self){
				var tbar = self.getTopToolbar();
				var endDateIndex = tbar.items.items.indexOf(Ext.getCmp('end_date'));
				var radioDay = new Custom.RadioDay({
					width:160,
					dateFieldConfig: {
						startDateField: Ext.getCmp('start_date'),
						endDateField: Ext.getCmp('end_date')
					},
				});
                tbar.insert(endDateIndex+1, radioDay);
				tbar.doLayout();      
			}
        }
	}
})()