<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
session_start();
fn_checkAuthPermission($_SESSION);
$type_list = $db->queryAll("select type,name from BC_TASK_TYPE order by show_order ");

$comp_array = array();

$allow_list = array(
	60,80,20,22,11,10,130,30,15
);

foreach($type_list as $list) {
	$name = $list['name'];
	$type = $list['type'];

    if(!in_array($type ,$allow_list)) continue;

    $comp =	"{
        title: '$name',
        layout: 'fit',
        items: new Ariel.monitor.TaskPanel({taskType: $type })
    }";

    $comp_array [] = $comp;
}
?>

(function(){
	
		var taskMonitorPageSize = 20;
	
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
					//>>v = '삭제';
					v = _text('MN01106');
				break;
			}
	
			return v;
		}
	
		function renderMonitorDestination(v, metadata, record, rowIndex, colIndex, store) {
			metadata.attr = 'style="text-align: left"';
			var dest = record.get('destination');
			if (dest) {
				return '<b>'+dest+'</b> ';
			} else {
				return v;
			}
		}
	
		Ext.ns('Ariel.monitor');
		Ext.override(Ext.PagingToolbar, {
			doLoad : function(start){
				var o = {}, pn = this.getParams();
				o[pn.start] = start;
				o[pn.limit] = this.pageSize;
				if(this.fireEvent('beforechange', this, o) !== false){
					var options = Ext.apply({}, this.store.lastOptions);
					options.params = Ext.applyIf(o, options.params);
					this.store.load(options);
				}
			}
		});

		Ext.override(Ext.ux.grid.BufferView, {
			templates: {
				cell: new Ext.Template(
					'<td class="x-grid3-col x-grid3-cell x-grid3-td-{id} x-selectable {css}" style="{style}" tabIndex="0" {cellAttr}>',
					'<div class="x-grid3-cell-inner x-grid3-col-{id}" {attr}>{value}</div>',
					'</td>'
				)
			}
		});
	
		Ariel.monitor.TaskPanel = Ext.extend(Ext.grid.GridPanel, {
			loadMask: true,
			border: false,
			cls: 'proxima_customize proxima_customize_progress',
			listeners: {
				viewready: function(self){
					//self.getStore().reload();
				},
				rowcontextmenu: function(self, rowIndex, e){
	
					e.stopEvent();
	
					var sm = self.getSelectionModel();
					if (!sm.isSelected(rowIndex)) {
						sm.selectRow(rowIndex);
					}
	
					var that = this;
	
				var menu = new Ext.menu.Menu({
					items: [
						buildTbarUrgency(that),
						buildWorkFlow(that),
						buildTBarRetry(that),
						buildTBarComplete(that),
						buildTBarCancel(that),
						buildTbarDelete(that)
					]
				});
	
					menu.showAt(e.getXY());
				}
			},
	
			initComponent: function(){
				var _this = this;
	
				this.store = new Ext.data.JsonStore({
					url: '/store/get_task.php',
					totalProperty: 'total',
					idProperty: 'task_id',
					root: 'data',
					fields: [
						{name: 'id', mapping: 'task_id'},
						{name: 'type'},
						{name: 't_name'},
						{name: 'target'},
						{name: 'source'},
						{name: 'progress'},
						{name: 'reg_user_id'},
						{name: 'status'},
						{name: 'parameter'},
						{name: 'destination'},
						{name: 'start_datetime', type: 'date', dateFormat: 'YmdHis'},
						{name: 'complete_datetime', type: 'date', dateFormat: 'YmdHis'},
						{name: 'creation_datetime', type: 'date', dateFormat: 'YmdHis'},
						{name: 'name'},
						{name: 'value'},
						{name: 'content_id'},
						{name: 'media_id'},
						{name: 'title'},
						{name: 'assign_ip'},
						{name: 'assign_ip_nm'},
						{name: 'job_name'},
						{name: 'user_task_name'},
						{name: 'register', convert: function(v, record) {
							return record.task_user_id + '(' + record.task_user_name + ')';
						}}
					],
					listeners: {
						beforeload: function(self, opts){
							var g = Ext.getCmp('task_tab').getActiveTab().get(0);
							var sdate = Ext.getCmp('task_date_from').getValue().format('Ymd000000');
							var edate = Ext.getCmp('task_date_to').getValue().format('Ymd240000');
							var workflow_channel = Ext.getCmp('s_workflow_chan').getValue();
							var search_title = Ext.getCmp('task_title').getValue();

							self.baseParams = {
								taskType: g.taskType,
								task_status: getChecked(Ext.getCmp('task_tab').getTopToolbar()),
								workflow_channel: workflow_channel,
								title: search_title,
								start_date: sdate,
								end_date: edate,
								limit: taskMonitorPageSize,
								start: 0
							};
						}
					}
				});
				this.bbar = new Ext.PagingToolbar({
					store: this.store,
					pageSize: taskMonitorPageSize
				});
	
				Ext.apply(this, {
					selModel: new Ext.grid.RowSelectionModel({
					//singleSelect: true,
						listeners: {
							rowselect: function(self){
								Ext.getCmp('log').getStore().load();
							},
							rowdeselect: function(self){
								Ext.getCmp('log').getStore().removeAll();
							}
						}
					}),
					columns: [
						{header: _text('MN01112'), dataIndex: 'user_task_name', width: 120},//'작업 흐름명'
						{header: 'ID', dataIndex: 'id', width: 60, hidden: true},
						{header: _text('MN00346'), dataIndex: 't_name', width: 120},//'모듈명'
						{header: _text('MN00236'), dataIndex: 'job_name', width: 170},//'모듈명'
						{header: _text('MN00287'), dataIndex: 'content_id', width: 100, hidden: true },
						{header: _text('MN00171'), dataIndex: 'media_id', width: 100 , hidden: true },
						
						{header: _text('MN00249'), dataIndex: 'title', width: 250},
						{header: _text('MN00120'), dataIndex: 'register', width: 100},
						{header: 'Retry', dataIndex: 'retry_cnt', align: 'center', width: 80, hidden: true},
						{header: _text('MN00138'), dataIndex: 'status', align: 'center', width: 80, renderer: renderTaskMonitorStatus},
						new Ext.ux.ProgressColumn({
							header: _text('MN00261'),
							width: 105,
							dataIndex: 'progress',
							align: 'center',
							renderer: function(value, meta, record, rowIndex, colIndex, store, pct) {
								return Ext.util.Format.number(pct, "0%");
							}
						}),
						{header: 'Priority', dataIndex: 'priority', width: 100 , hidden: true },
						{header: _text('MN00220'), dataIndex: 'source', width: 200},
						{header: _text('MN00242'), dataIndex: 'target', width: 200},
						{header: _text('MN00299'), dataIndex: 'parameter', align: 'center', width: 150 , hidden: true },
						{header: _text('MN00102'), dataIndex: 'creation_datetime', align: 'center', renderer: Ext.util.Format.dateRenderer('Y-m-d H:i:s'), width: 130},
						{header: _text('MN00233'), dataIndex: 'start_datetime', align: 'center', renderer: Ext.util.Format.dateRenderer('Y-m-d H:i:s'), width: 130},
						{header: _text('MN00234'), dataIndex: 'complete_datetime', align: 'center', renderer: Ext.util.Format.dateRenderer('Y-m-d H:i:s'), width: 130},
						{header: _text('MN01048'), dataIndex: 'assign_ip_nm', align: 'center', hidden: false}//'할당IP'
					],
					view: new Ext.ux.grid.BufferView({
						templates: {
							cell: new Ext.Template(
								'<td class="x-grid3-col x-grid3-cell x-grid3-td-{id} x-selectable {css}" style="{style}" tabIndex="0" {cellAttr}>',
								'<div class="x-grid3-cell-inner x-grid3-col-{id}" {attr}>{value}</div>',
								'</td>'
							)
						},
						emptyText: _text('MSG00157'),
						//forceFit: true,
						listeners: {
							refresh: function(self) {
								Ext.getCmp('log').getStore().reload();
							}
						}
					})
				});
	
				Ariel.monitor.TaskPanel.superclass.initComponent.call(this);
			},
	
			request: function(name, action, task_id_list, that, reason){
	
				Ext.Msg.show({
					title: _text('MN00024'),
					msg: name+' : '+ _text('MSG02039'),
					icon: Ext.Msg.WARNING,
					buttons: Ext.Msg.OKCANCEL,
					fn: function(btnId){
						if (btnId == 'ok') {
							Ext.Ajax.request({
								url: '/store/send_task_action.php',
								params: {
									'task_id_list[]': task_id_list,
									action: action,
									reason: reason
								},
								callback: function(options, success, response){
									if(success) {
										try
										{
											var r = Ext.decode(response.responseText);
											if(!r.success) {
												//>>Ext.Msg.alert('오류', r.msg);
												Ext.Msg.alert(_text('MN00022'), r.msg);
											} else {
												Ext.Msg.alert(_text('MN00024'), name+ ' '+ _text('MN00011'));
	
												if(!Ext.isEmpty(that.getStore()))	that.getStore().reload();
											}
										} catch (e) {
											Ext.Msg.alert(e['name'], e['message']);
										}
									} else {
										Ext.Msg.alert(text('MN00022'), opts.url+'<br />'+response.statusText+'( '+response.status+' )');
									}
								}
							})
						}
					}
				});
			},
	
			buildReason: function(name,action, id, that){
				new Ext.Window({
					title: _text('MN01097'),
					width: 400,
					height: 209,
					resizable: false,
					modal: true,
					layout: 'fit',
					items: {
						xtype: 'form',
						baseCls: 'x-plain',
						border: false,
						labelSeparator: '',
						defaults: {
							anchor: '100%',
							border: false
						},
						items: [{
							xtype: 'textarea',
							height: 140,
							hideLabel: true,
							name: 'reason'
						}]
					},
					buttonAlign: 'center',
					buttons: [{
						text: _text('MN00066'),
						scale: 'medium',
						handler: function(btn, e){
							var reason = btn.ownerCt.ownerCt.get(0).get(0).getValue();
							that.request(name,action, id, that, reason);
	
							btn.ownerCt.ownerCt.close();
						}
					},{
						text: _text('MN02065'),
						scale: 'medium',
						handler: function(btn, e){
							btn.ownerCt.ownerCt.close();
						}
					}]
				}).show();
			}
		});
	
		function getChecked(toolbar){
	
			var status_checkbox,
				tmp,
				status_group = new Array();
	
			status_checkbox = toolbar.find('group', 'toggle');
			Ext.each(status_checkbox, function (checkbox) {
				if (checkbox.checked) {
					status_group.push(checkbox.status);
				}
			});
			return "'"+status_group.join("','")+"'";
		};
	
		function buildTBarStatusCheck(){
			return ['-',{
				xtype: 'checkbox',
				//>>boxLabel: '전체',
				boxLabel: _text('MN00246'),
				listeners: {
					check: function(self, checked){
						var toolbar = self.ownerCt;
						Ext.each(toolbar.find('group', 'toggle'), function(i){
							i.suspendEvents();
							i.setValue(checked);
							i.resumeEvents();
						});
	
						var g = Ext.getCmp('task_tab');
						var p_bar = g.getActiveTab().get(0).toolbars[0];
						var activePage = Math.ceil((p_bar.cursor + p_bar.pageSize) / p_bar.pageSize);
	
						Ext.getCmp('task_tab').getActiveTab().get(0).getStore().load();
					}
				}
			},'-',{
				xtype: 'checkbox',
				checked: true,
				//>>boxLabel: '처리 중',
				boxLabel: _text('MN00262'),
				status: 'processing',
				group: 'toggle',
				listeners: {
					check: function(self, checked){
						var g = Ext.getCmp('task_tab');
						var p_bar = g.getActiveTab().get(0).toolbars[0];
						var activePage = Math.ceil((p_bar.cursor + p_bar.pageSize) / p_bar.pageSize);
	
						Ext.getCmp('task_tab').getActiveTab().get(0).getStore().load();
					}
				}
			},'-',{
				xtype: 'checkbox',
				//>>boxLabel: '대기 중',
				boxLabel: _text('MN00160'),
				status: 'queue',
				group: 'toggle',
				listeners: {
					check: function(self, checked){
						var g = Ext.getCmp('task_tab');
						var p_bar = g.getActiveTab().get(0).toolbars[0];
						var activePage = Math.ceil((p_bar.cursor + p_bar.pageSize) / p_bar.pageSize);
	
						Ext.getCmp('task_tab').getActiveTab().get(0).getStore().load();
					}
				}
			},'-',{
				xtype: 'checkbox',
				//>>boxLabel: '성공',
				boxLabel: _text('MN00015'),
				status: 'complete',
				group: 'toggle',
				listeners: {
					check: function(self, checked){
						var g = Ext.getCmp('task_tab');
						var p_bar = g.getActiveTab().get(0).toolbars[0];
						var activePage = Math.ceil((p_bar.cursor + p_bar.pageSize) / p_bar.pageSize);
	
						Ext.getCmp('task_tab').getActiveTab().get(0).getStore().load();
					}
				}
			},'-',{
				xtype: 'checkbox',
				//>>boxLabel: '실패',
				boxLabel: _text('MN00016'),
				status: 'error',
				group: 'toggle',
				listeners: {
					check: function(self, checked){
						var g = Ext.getCmp('task_tab');
						var p_bar = g.getActiveTab().get(0).toolbars[0];
						var activePage = Math.ceil((p_bar.cursor + p_bar.pageSize) / p_bar.pageSize);
	
						Ext.getCmp('task_tab').getActiveTab().get(0).getStore().load();
					}
				}
			},'-',{
				xtype: 'checkbox',
				boxLabel: _text('MN01049'),
				status: 'cancel',
				group: 'toggle',
				listeners: {
					check: function(self, checked){
						var g = Ext.getCmp('task_tab');
						var p_bar = g.getActiveTab().get(0).toolbars[0];
						var activePage = Math.ceil((p_bar.cursor + p_bar.pageSize) / p_bar.pageSize);
	
						Ext.getCmp('task_tab').getActiveTab().get(0).getStore().load();
					}
				}
			},'-',{
				xtype: 'checkbox',
				boxLabel: _text('MN01106'),
				status: 'delete',
				group: 'toggle',
				listeners: {
					check: function(self, checked){
						Ext.getCmp('task_tab').getActiveTab().get(0).getStore().load();
					}
				}
			}]
		}
		function buildTbarUrgency(that, av_mode){
			var button_text;
			if(av_mode == 'button') {
				button_text = '<span style="position:relative;top:1px;" title="'+_text('MN02162')+'"><i class="fa fa-clock-o" style="font-size:13px;color:white;"></i></span>'; 
			} else {
				button_text = '<span style="position:relative;top:1px;"><i class="fa fa-clock-o" style="font-size:13px;"></i></span>&nbsp;&nbsp;'+_text('MN02162');
			}
	
			return{
				text: button_text,
				width: 30,
				cls: 'proxima_button_customize',
				handler: function(btn, e){
					var that = Ext.getCmp('task_tab').getActiveTab().get(0);
					var sm = that.getSelectionModel();
					if(!sm.hasSelection()){
						Ext.Msg.alert(_text('MN00023'),_text('MSG02001'));
						return;
					}
					
					var selected = new Array();
					var unselected = new Array();
					Ext.each(sm.getSelections(), function(r){
						if( r.get('status') == 'queue'){
							selected.push(r.get('id'));
						} else {
							unselected.push(r.get('title'));
						}
					})
	
					var name = _text('MN02162');//'긴급작업 요청'
					var action = 'priority';
	
					if( unselected.length > 0 ){
						Ext.Msg.show({
							title : _text('MN00023'),
							msg : _text('MSG02042') +'</br>'+_text('MN02529') + ':</br>'+unselected.join('</br>'),
							buttons : Ext.Msg.OKCANCEL,
							fn : function(btn){
								if(btn == 'ok' && selected.length > 0){
									that.request(name,action, selected, that, 'priority up');
								} else {
									return;
								}
							}
						});
					} else {
						if( selected.length > 0 ) {
							that.request(name,action, selected, that, 'priority up');
						}
					}
				}
			}
		}
		function buildTBarRetry(that, av_mode){
			var button_text;
			if(av_mode == 'button'){
				button_text = '<span style="position:relative;top:1px;" title="'+_text('MN00045')+'"><i class="fa fa-repeat" style="font-size:13px;color:white"></i></span>'; 
			}else{
				button_text = '<span style="position:relative;top:1px;"><i class="fa fa-repeat" style="font-size:13px;"></i></span>&nbsp;&nbsp;'+_text('MN00045');
			}
	
			return {
				//>>text: '재시작',
				text: button_text,
				cls: 'proxima_button_customize',
				width: 30,
				handler: function(btn, e){
					var that = Ext.getCmp('task_tab').getActiveTab().get(0);
					if(!that.getSelectionModel().hasSelection()){
						//>>Ext.Msg.alert('정보', '재시작 하실 항목을 선택해주세요');
						Ext.Msg.alert(_text('MN00023'), _text('MSG00114'));
						return;
					}
					var sm = that.getSelectionModel(),
						task_id_list = [];
	
					Ext.each(sm.getSelections(), function (r) {
						task_id_list.push(r.get('id'));
					});
	
					var name =  _text('MN00045');
					var action= 'retry';
	
					
					if( task_id_list.length > 0 ){
						that.request(name, action, task_id_list, that);
					}
				},
				scope: this
			}
		};
		function buildTBarComplete(that, av_mode){
			var button_text;
			if(av_mode == 'button'){
				button_text = '<span style="position:relative;top:1px;" title="'+_text('MN02129')+'"><i class="fa fa-check-square-o" style="font-size:13px;color:white;"></i></span>'; 
			}else{
				button_text = '<span style="position:relative;top:1px;"><i class="fa fa-check-square-o" style="font-size:13px;"></i></span>&nbsp;&nbsp;'+_text('MN02129');
			}
			return {
				cls: 'proxima_button_customize',
				width: 30,
				text: button_text,
				handler: function(btn, e){
					var that = Ext.getCmp('task_tab').getActiveTab().get(0);
					var sm = that.getSelectionModel();
					if(!sm.hasSelection()){
						Ext.Msg.alert( _text('MN00023'), _text('MSG02001'));
						return;
					}
	
					var task_ids = new Array();
					Ext.each(sm.getSelections(), function(r){
						if( r.get('id') ){
							task_ids.push(r.get('id'));
						}
					});
	
					if( task_ids.length > 0 ){
						Ext.Msg.show({
							title: _text('MN00024'),
							msg: _text('MN02129')+' : '+ _text('MSG02039'),
							icon: Ext.Msg.WARNING,
							buttons: Ext.Msg.OKCANCEL,
							fn: function(btnId){
								if (btnId == 'ok')
								{
									Ext.Ajax.request({
										url: '/store/send_task_xml_msg.php',
										params: {
											task_id_list : Ext.encode(task_ids),
											action: 'complete'
										},
										callback: function(options, success, response){
											if(success)
											{
												try
												{
													var r = Ext.decode(response.responseText);
													if(!r.success)
													{
														Ext.Msg.alert( _text('MN00023'), r.msg);
													} else {
														if(!Ext.isEmpty(that.getStore()))	that.getStore().reload();
													}
													//that.getStore().reload();
												} catch (e) {
													Ext.Msg.alert(e['name'], e['message']);
												}
											} else {
												Ext.Msg.alert(_text('MN00023'), opts.url+'<br />'+response.statusText+'( '+response.status+' )');
											}
										}
									})
								}
							}
						})
					}
				},
				scope: this
			}
		};
		function buildTBarCancel(that, av_mode){
			var button_text;
			if(av_mode == 'button'){
				button_text = '<span style="position:relative;top:1px;" title="'+_text('MN00004')+'"><i class="fa fa-times-circle" style="font-size:13px;color:white;"></i></span>'; 
			}else{
				button_text = '<span style="position:relative;top:1px;"><i class="fa fa-times-circle" style="font-size:13px;"></i></span>&nbsp;&nbsp;'+_text('MN00004');
			}
			
			return {
				//>>text: '취소',
				cls: 'proxima_button_customize',
				width: 30,
				text: button_text,
				handler: function(){
					var that = Ext.getCmp('task_tab').getActiveTab().get(0);
					var sm = that.getSelectionModel();
					if(!sm.hasSelection()){
						//>>Ext.Msg.alert('정보', '취소 하실 항목을 선택해주세요');
						Ext.Msg.alert(_text('MN00023'), _text('MSG00118'));
						return;
					}
	
					var task_ids = [];
					var other_ids = [];
	
					Ext.each(sm.getSelections(), function(r){
						if( r.get('status') == 'processing' || r.get('status') == 'queue' || r.get('status') == 'assigning' ){
							task_ids.push(r.get('id'));
						}else{
							other_ids.push(r.get('title'));
						}
					})
	
					var name = _text('MN00004');
					var action= 'cancel';
					var store = that.getStore();
	
					if(other_ids.length > 0 ){
						Ext.Msg.show({
							title : _text('MN00023'),
							msg : _text('MSG00119')+'</br>' + _text('MN02529') +' :</br>'+other_ids.join('</br>'),
							buttons : Ext.Msg.OKCANCEL,
							fn : function(btn){
								if( btn == 'ok' && task_ids.length > 0 ){
									that.buildReason(name,action, task_ids, that);
								}
							}
						});
					} else {
						if( task_ids.length > 0){
							that.buildReason(name,action, task_ids, that);
						}
					}
				},
				scope: this
			}
		};
		
	
		function buildTbarDelete(that, av_mode){
			var button_text;
			if(av_mode == 'button'){
				button_text = '<span style="position:relative;top:1px;" title="'+_text('MN00034')+'"><i class="fa fa-ban" style="font-size:13px;color:white;"></i></span>'; 
			}else{
				button_text = '<span style="position:relative;top:1px;"><i class="fa fa-ban" style="font-size:13px;"></i></span>&nbsp;&nbsp;'+_text('MN00034');
			}
	
			return{
				cls: 'proxima_button_customize',
				width: 30,
				text: button_text,
				handler: function(btn, e){
					var that = Ext.getCmp('task_tab').getActiveTab().get(0);
					var sm = that.getSelectionModel();
					if(!sm.hasSelection()){
						//>>Ext.Msg.alert('정보', '재시작 하실 항목을 선택해주세요');
						Ext.Msg.alert(_text('MN00023'),_text('MSG02001'));
						return;
					}
	
					var task_ids = [];
					var other_ids = [];
	
					Ext.each(sm.getSelections(), function(r){
						if( r.get('status') == 'cancel' ){
							task_ids.push(r.get('id'));
						}else{
							other_ids.push(r.get('title'));
						}
					})
	
					var name = '삭제';
					var action= 'delete';
					var store = that.getStore();
	
					if(other_ids.length > 0 ){
						Ext.Msg.show({
							title : _text('MN00023'),
							msg : _text('MSG01035') + '</br>' + _text('MN02529') + ':</br>'+other_ids.join('</br>'),
							buttons : Ext.Msg.OKCANCEL,
							fn : function(btn){
								if( btn == 'ok' && task_ids.length > 0 ){
									that.buildReason(name,action, task_ids, that);
								}
							}
						});
					}else{
						if( btn == 'ok'){
							if( task_ids.length > 0){
								that.buildReason(name,action, task_ids, that);
							}
						}
					}
				}
			}
		};
		function buildWorkFlow(){
			return {
				//>>text: '작업흐름보기',
				text: '<span style="position:relative;top:1px;"><i class="fa fa-sliders" style="font-size:13px;"></i></span>&nbsp;&nbsp;'+_text('MN00241'),
				handler: function(btn, e){
					var that = Ext.getCmp('task_tab').getActiveTab().get(0);
					var sm = that.getSelectionModel();
					if(!sm.hasSelection()){
						//>>Ext.Msg.alert('정보', '먼저 대상을 선택 해 주시기 바랍니다.');
                        Ext.Msg.alert(_text('MN00023'),_text('MSG01005'));
						return;
					}
					var records = sm.getSelected();
					var rs=[];
					Ext.each(records, function(r){
						rs.push(r.get('content_id'));
					});
	
					Ext.Ajax.request({
							url: '/javascript/ext.ux/viewWorkFlow.php',
							params: {
								records: Ext.encode(rs)
							},
							callback: function(options, success, response){
								if(success)
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
									//>>Ext.Msg.alert('서버 오류', response.statusText);
									Ext.Msg.alert(_text('MN00022'), response.statusText);
								}
							}
					});
				}
			}
		};
	
		function searchList(){
			var g = Ext.getCmp('task_tab').getActiveTab().get(0);
			var sdate = Ext.getCmp('task_date_from').getValue().format('Ymd000000');
			var edate = Ext.getCmp('task_date_to').getValue().format('Ymd240000');
			var search_title = Ext.getCmp('task_title').getValue();
	
			g.getStore().load({
				params: {
					start_date: sdate,
					end_date: edate,
					title : search_title
				}
			});
		}
		return {
			layout:	'border',
			border: false,
	
			items: [{
				id: 'task_tab',
				xtype: 'tabpanel',
				region: 'center',
				activeTab: 0,
				plain: true,
				intervalID: null,
				enableTabScroll: true,
				defaults: {
					autoScroll: true
				},
	
				runAutoReload : function(thisRef){
					this.stopAutoReload();
	
					this.intervalID = setInterval(function (e) {
						if (thisRef && thisRef.getActiveTab()) {
	
							var work_tp = Ext.getCmp('work_tp');
							var work_tp_selNode_id = work_tp.getSelectionModel().selNode.id;
							var nps_center = Ext.getCmp('nps_center').getLayout().activeItem;
	
							if(work_tp_selNode_id == "work_monitor" && nps_center == work_tp.ownerCt){
								thisRef.getActiveTab().get(0).getStore().reload();
							}
						}
					}, 5000);
				},
	
				stopAutoReload: function(){
					if (this.intervalID) {
						clearInterval(this.intervalID);
					}
				},
	
				listeners: {
					afterrender: function(self){
						// self.runAutoReload(self);
						// console.log('afterrender');
					},
					close: function(self){
						//console.log('close');
					},
					beforedestroy: function(self){
						//console.log('deforedestory');
					},
					hide: function(self){
						//console.log('hide');
					},
					show: function(self){
						//console.log('show');
					},
					tabchange: function(self, p){
						p.get(0).getStore().reload();
					},
					beforetabchange: function(self, newTab, currentTab){
	
						if (currentTab)
						{
							//console.log(currentTab.initialConfig.items.getTopToolbar());
							//console.log(currentTab.initialConfig.items.getTopToolbar());
							//currentTab.initialConfig.items.stopAutoReload();
							//currentTab.initialConfig.items.getTopToolbar().get(22).setValue(false);
						}
					}
				},
				items: [{
					title: _text('MN00008'),
					layout: 'fit',
					items: new Ariel.monitor.TaskPanel({taskType: 'all'})
				}
				<?=','.join(',', $comp_array)?>
				],
	
				tbar: [_text('MN01023'),{
					xtype: 'datefield',
					width: 90,
					id: 'task_date_from',
					editable: false,
					format: 'Y-m-d',
					allformats: 'Ymd|ymd|Y-m-d|y-m-d|Y/m/d|Y/m/d',
					listeners:{
						select: function(self, date){
							var edate = self.ownerCt.items.items[3];
							edate.setMinValue(date);
						},
						render: function(self){
							var d = new Date();
							self.setMaxValue(d.format('Y-m-d'));
							//self.setValue(new Date().add(Date.DAY, -30));
							self.setValue(d.add(Date.DAY, -1).format('Y-m-d'));
						}
					}
				},'~',{
					xtype:'datefield',
					width: 90,
					id: 'task_date_to',
					editable: false,
					format: 'Y-m-d',
					allformats: 'Ymd|ymd|Y-m-d|y-m-d|Y/m/d|Y/m/d',
					listeners:{
						select: function(self, date){
							var sdate = self.ownerCt.items.items[1];
							sdate.setMaxValue(date);
						},
						render: function(self){
							var d = new Date();
							self.setMinValue(d.format('Y-m-d'));
							self.setValue(d);
						}
					}
				},'-',{
				xtype: 'displayfield',
				value: _text('MN01112')//'작업흐름 명'
			},{
				xtype: 'combo',
				triggerAction: 'all',
				id: 's_workflow_chan',
				name : 's_workflow_chan',
				editable : false,
				allowBlank : false,
				forceSelection: true,
				displayField : 'name',
				valueField : 'value',
				store : new Ext.data.JsonStore({
					autoLoad: true,
					url:'/store/get_workflow_channel.php',
					root: 'data',
					idProperty: 'value',
					fields: [
						{ name:'name' },	{ name:'value' }
					]
				}),
				listeners: {
					afterrender : function(self){
						self.setValue('all');
						self.setRawValue(_text('MN00008'));//'전체'
					}
				}
			},'-',{
					xtype : 'textfield',
					width : 100,
					id : 'task_title',
					listeners :{
						specialkey: function(field, e){
							if(e.getKey() == e.ENTER) {
								Ext.getCmp('task_tab').getActiveTab().get(0).getStore().load();
							}
						}
					}
				},'-',{
					//>>text: '새로고침',
					cls: 'proxima_button_customize',
					width: 30,
					text: '<span style="position:relative;" title="'+_text('MN00139')+'"><i class="fa fa-refresh" style="font-size:13px;color:white;"></i></span>',
					handler: function(){
						Ext.getCmp('task_tab').getActiveTab().get(0).getStore().load();
					},
					scope: this
				},
				buildTBarStatusCheck(),'-',
				buildTbarUrgency(this, 'button'),'-',
				buildTBarRetry(this, 'button'),'-',
				buildTBarComplete(this, 'button'),'-',
				buildTBarCancel(this, 'button'),'-',
				buildTbarDelete(this, 'button'),'->',
				{
					pressed: false,
					enableToggle: true,
					text: _text('MN02551'),
					id: 'taskMonitor_autoload_btn',
					icon: '/led-icons/cross.png',
					handler: function(b, e){
						var TaskListTabPanel = b.ownerCt.ownerCt;
						if (b.pressed) {
							b.setText(_text('MN02550'));
							b.setIcon('/led-icons/accept.png');
							TaskListTabPanel.runAutoReload(TaskListTabPanel);
						} else {
							b.setText(_text('MN02551'));
							b.setIcon('/led-icons/cross.png');
							TaskListTabPanel.stopAutoReload();
						}
					}
				}]
			},{
				id: 'log',
				//>>title:	"로그",
				title:	_text('MN00048'),
				xtype:	'grid',
				region: 'south',
				split: true,
				collapsible: true,
				height: 200,
				loadMask: true,
				autoExpandColumn: 'description',
				store:	new	Ext.data.JsonStore({
					id:	'log_store',
					url: '/store/get_task_log.php',
					totalProperty: 'total',
					idProperty:	'id',
					root: 'data',
					fields:	[
						{name: 'task_log_id'},
						{name: 'task_id'},
						{name: 'description'},
						{name: 'creation_date',	type: 'date', dateFormat: 'YmdHis'}
					],
					listeners: {
						beforeload:	function(self, opts){
							var	sel	= Ext.getCmp('task_tab').getActiveTab().get(0).getSelectionModel().getSelected();
							if (sel) {
								self.baseParams.task_id	= sel.get('id');
							}
						}
					}
				}),
				columns: [
					{header: 'ID', dataIndex: 'task_log_id',	width: 70},
					//>>{header: '생성일', dataIndex: 'creation_date', renderer: Ext.util.Format.dateRenderer('Y-m-d H:i:s'), width: 120, align: 'center'},
					//>>{header: '내용', dataIndex:	'description', id: 'description'}
					{header: _text('MN00107'), dataIndex: 'creation_date', renderer: Ext.util.Format.dateRenderer('Y-m-d H:i:s'), width: 130, align: 'center'},
					{header: _text('MN00156'), dataIndex:	'description', id: 'description'}
				],
				selModel: new Ext.grid.RowSelectionModel({
					singleSelect: true
				}),
				view: new Ext.ux.grid.BufferView({
					templates: {
						cell: new Ext.Template(
							'<td class="x-grid3-col x-grid3-cell x-grid3-td-{id} x-selectable {css}" style="{style}" tabIndex="0" {cellAttr}>',
							'<div class="x-grid3-cell-inner x-grid3-col-{id}" {attr}>{value}</div>',
							'</td>'
						)
					},
					emptyText: _text('MSG00166')
				}),
				tbar: [{
					//text: '새로고침',
					cls: 'proxima_button_customize',
					width: 30,
					text: '<span style="position:relative;" title="'+_text('MN00139')+'"><i class="fa fa-refresh" style="font-size:13px;color:white;"></i></span>',
					handler: function(){
						Ext.getCmp('log').getStore().reload();
					}
				}]
			}]
		}
	})()
	
