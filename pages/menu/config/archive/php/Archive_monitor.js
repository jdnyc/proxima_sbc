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
				//>>v = '재시작';
				v = '삭제';
			break;
		}

		return v;
	}

	function renderMonitorDestination(v, metadata, record, rowIndex, colIndex, store) {
		metadata.attr = 'style="text-align: left"';
		var dest = record.get('destination');
		if (dest) {
			return '<b>'+dest+'</b> ';
		}
		else {
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

	Ariel.monitor.TaskPanel = Ext.extend(Ext.grid.GridPanel, {
		//loadMask: true,
		border: false,
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
						that.buildWorkFlow(that),
						that.buildTBarRetry(that),
						that.buildTBarCancel(that),
						that.buildTbarDelete(that)
					]
				});
				menu.showAt(e.getXY());
			}
		},

		initComponent: function(){

			this.store = new Ext.data.JsonStore({
				url: '/store/get_task.php',
				totalProperty: 'total',
				idProperty: 'task_id',
				root: 'data',
				fields: [
					{name: 'id', mapping: 'task_id'},
					{name: 'type'},
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
					{name: 'assign_ip'}
				],
				listeners: {
					beforeload: function(self, opts){
						var g = Ext.getCmp('task_tab').getActiveTab().get(0);
						self.baseParams = {
							taskType: g.taskType,
							task_status: g.getChecked(g.getTopToolbar()),
							limit: taskMonitorPageSize,
							start: 0
						}
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
					{header: 'ID', dataIndex: 'id', width: 60},
					{header: _text('MN00287'), dataIndex: 'content_id', width: 100, hidden: true },
					{header: _text('MN00171'), dataIndex: 'media_id', width: 100 , hidden: true },
					{header: _text('MN00120'), dataIndex: 'reg_user_id', width: 60},
					{header: _text('MN00249'), dataIndex: 'title', width: 100},
					{header: _text('MN00220'), dataIndex: 'source', width: 200, align: 'center'},
					{header: _text('MN00242'), dataIndex: 'target', width: 200, align: 'center'},
					new Ext.ux.ProgressColumn({
						header: _text('MN00261'),
						width: 105,
						dataIndex: 'progress',
						//divisor: 'price',
						align: 'center',
						renderer: function(value, meta, record, rowIndex, colIndex, store, pct) {
							return Ext.util.Format.number(pct, "0%");
						}
					}),
					{header: _text('MN00138'), dataIndex: 'status', align: 'center', width: 80, renderer: renderTaskMonitorStatus},
					{header: _text('MN00299'), dataIndex: 'parameter', align: 'center', width: 150 , hidden: true },
					{header: _text('MN00102'), dataIndex: 'creation_datetime', align: 'center', renderer: Ext.util.Format.dateRenderer('Y-m-d H:i:s'), width: 120},
					{header: _text('MN00233'), dataIndex: 'start_datetime', align: 'center', renderer: Ext.util.Format.dateRenderer('Y-m-d H:i:s'), width: 120},
					{header: _text('MN00234'), dataIndex: 'complete_datetime', align: 'center', renderer: Ext.util.Format.dateRenderer('Y-m-d H:i:s'), width: 120},
					{header: '할당IP', dataIndex: 'assign_ip', align: 'center', hidden: true}
				],
				tbar: [{
					//>>text: '새로고침',
					text: _text('MN00139'),
					icon: '/led-icons/arrow_refresh.png',
					handler: function(){
						this.getStore().reload();
					},
					scope: this
				},
					this.buildTBarStatusCheck(),

					this.buildTBarRetry(this),
					'-',
					this.buildTBarCancel(this),
					'-',
					this.buildTbarDelete(this)
				],

				viewConfig: {
					//>>emptyText: '등록된 작업이 없습니다.',
					emptyText: _text('MSG00157'),
					//forceFit: true,
					listeners: {
						refresh: function(self) {
							Ext.getCmp('log').getStore().removeAll();
						}
					}
				}
            });

			Ariel.monitor.TaskPanel.superclass.initComponent.call(this);
		},

		getChecked: function(toolbar){

			var status_checkbox,
				tmp,
				status_group = new Array();

			status_checkbox = toolbar.find('group', 'toggle');
			Ext.each(status_checkbox, function (checkbox) {
				if (checkbox.checked) {
					status_group.push(checkbox.status);
				}
			});

//			var task_store = Ext.getCmp('task_tab').getActiveTab().get(0).getStore();
//			task_store.reload({
//				params: {
//					task_status: "'"+status_group.join("','")+"'"
//				}
//			});

			return "'"+status_group.join("','")+"'";
		},
		buildWorkFlow: function(that){
			return {
				icon:'/led-icons/drive_go.png',
				//>>text: '작업흐름보기',
				text: _text('MN00241'),
				handler: function(btn, e){

					var sm = that.getSelectionModel();
					if(!sm.hasSelection()){
						//>>Ext.Msg.alert('정보', '재시작 하실 항목을 선택해주세요');
						Ext.Msg.alert(_text('MN00023'),'목록을 선택해주세요');
						return;
					}

					var records = sm.getSelections();
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
									//>>Ext.Msg.alert('서버 오류', response.statusText);
									Ext.Msg.alert(_text('MN00022'), response.statusText);
								}
							}
					});
				}

			}
		},
		buildTBarStatusCheck: function(){
			var owner = this;

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

						Ext.getCmp('task_tab').getActiveTab().get(0).getStore().reload();
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
						//Ext.getCmp('status_all').setValue(false);
						Ext.getCmp('task_tab').getActiveTab().get(0).getStore().reload();
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
						Ext.getCmp('task_tab').getActiveTab().get(0).getStore().reload();
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
						Ext.getCmp('task_tab').getActiveTab().get(0).getStore().reload();
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
						Ext.getCmp('task_tab').getActiveTab().get(0).getStore().reload();
					}
				}
			},'-',{
				xtype: 'checkbox',
				boxLabel: '취소',
				//>>boxLabel: _text('MN00016'),
				status: 'cancel',
//				group: 'toggle',
				listeners: {
					check: function(self, checked){
						Ext.getCmp('task_tab').getActiveTab().get(0).getStore().reload();
					}
				}
			},'-',{
				xtype: 'checkbox',
				boxLabel: '삭제',
				//>>boxLabel: _text('MN00016'),
				status: 'delete',
//				group: 'toggle',
				listeners: {
					check: function(self, checked){
						Ext.getCmp('task_tab').getActiveTab().get(0).getStore().reload();
					}
				}
			},'-']
		},

		

		buildTBarRetry: function(that){
			return {
				//>>text: '재시작',
				text: _text('MN00045'),
				icon: '/led-icons/arrow_redo.png',
				handler: function(btn, e){
					if(!this.getSelectionModel().hasSelection()){
						//>>Ext.Msg.alert('정보', '재시작 하실 항목을 선택해주세요');
						Ext.Msg.alert(_text('MN00023'), _text('MSG00114'));
						return;
					}

					var sm = this.getSelectionModel(),
						task_id_list = [];

					Ext.each(sm.getSelections(), function (r) {
						task_id_list.push(r.get('id'));
					});
					var name =  _text('MN00045');
					var action= 'retry';

					that.request(name, action, task_id_list, that);
				},
				scope: this
			}
		},
		buildTBarCancel: function(that){
			return {
				//>>text: '취소',
				text: _text('MN00004'),
				icon: '/led-icons/cancel.png',
				handler: function(){
					var sm = this.getSelectionModel();
					if(!sm.hasSelection()){
						//>>Ext.Msg.alert('정보', '취소 하실 항목을 선택해주세요');
						Ext.Msg.alert(_text('MN00023'), _text('MSG00118'));
						return;
					}

					var status = sm.getSelected().get('status');
					if( status == 'complete' ){
						//>>Ext.Msg.alert('정보', '진행중인 작업만 취소 가능합니다.');
						Ext.Msg.alert(_text('MN00023'), '취소할 수 없는 작업입니다');
						return;
					}

					var id = sm.getSelected().get('id');
					var name = _text('MN00004');
					var action= 'cancel';
					var store = that.getStore();

					that.buildReason(name,action, id, that);
				},
				scope: this
			}
		},
		buildTbarDelete: function(that){
			return{

				text: _text('MN00034'),
				icon: '/led-icons/delete.png',
				handler: function(btn, e){
					var sm = that.getSelectionModel();
					if(!sm.hasSelection()){
						//>>Ext.Msg.alert('정보', '재시작 하실 항목을 선택해주세요');
						Ext.Msg.alert(_text('MN00023'),'목록을 선택해주세요');
						return;
					}

					var status = sm.getSelected().get('status');
					if( status != 'cancel' ){

						Ext.Msg.alert(_text('MN00023'), '취소된 작업만 삭제 가능합니다');
						return;
					}

					var status = sm.getSelected().get('status');
					var id = sm.getSelected().get('id');
					var name = '삭제';
					var action= 'delete';
					var store = that.getStore();

					that.buildReason(name,action, id, that);

				}
			}
		},

		request: function(name, action, task_id_list, that, reason){

			Ext.Msg.show({
				title: _text('MN00024'),
				msg: name+' 하시겠습니까?',
				icon: Ext.Msg.WARNING,
				buttons: Ext.Msg.OKCANCEL,
				fn: function(btnId){
					if (btnId == 'ok')
					{
						Ext.Ajax.request({
							url: '/store/send_task_action.php',
							params: {
								'task_id_list[]': task_id_list,
								action: action,
								reason: reason
							},
							callback: function(options, success, response){
								if(success)
								{
									try
									{
										var r = Ext.decode(response.responseText);
										if(!r.success)
										{
											//>>Ext.Msg.alert('오류', r.msg);
											Ext.Msg.alert(_text('MN00022'), r.msg);
										}
										else
										{
											Ext.Msg.alert(_text('MN00024'), name+ ' '+ _text('MN00011'));

											if(!Ext.isEmpty(that.getStore()))	that.getStore().reload();
										}
									}
									catch (e)
									{
										Ext.Msg.alert(e['name'], e['message']);
									}
								}
								else
								{
									Ext.Msg.alert('오류', opts.url+'<br />'+response.statusText+'( '+response.status+' )');
								}
							}
						})
					}
				}
			});
		},

		buildReason: function(name,action, id, that){
			new Ext.Window({
				title: name +' 사유',
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
					text: name+' 요청',
					scale: 'medium',
					handler: function(btn, e){
						var reason = btn.ownerCt.ownerCt.get(0).get(0).getValue();
						that.request(name,action, id, that, reason);

						btn.ownerCt.ownerCt.close();
					}
				},{
					text: '작성 취소',
					scale: 'medium',
					handler: function(btn, e){
						btn.ownerCt.ownerCt.close();
					}
				}]
			}).show();
		}
	});

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
			defaults: {
				autoScroll: true
			},

			plugins: [{
				ptype: 'tabstripcontainer',
				width: 120,
				items: [{
					xtype: 'checkbox',
					boxLabel: '자동 새로고침',
					checked: true,
					listeners: {
						check: function (self, checked) {
							var TaskListTabPanel = self.ownerCt.ownerCt;
							if (checked) {
		                		TaskListTabPanel.runAutoReload(TaskListTabPanel);
							}
							else {
		                		TaskListTabPanel.stopAutoReload();
							}
						}
					}
				}]
			}],

			runAutoReload: function(thisRef){
				this.stopAutoReload();

				this.intervalID = setInterval(function (e) {
					if (thisRef && thisRef.getActiveTab()) {
						thisRef.getActiveTab().get(0).getStore().reload();
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
					self.runAutoReload(self);
					//console.log('afterrender');
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
				//>>title: '카탈로깅',
				title: '전체',
				layout: 'fit',
				items: new Ariel.monitor.TaskPanel({taskType: 'all'})
			},{
				//>>title: '카탈로깅',
				title: _text('MN00270'),
				layout: 'fit',
				items: new Ariel.monitor.TaskPanel({taskType: 10})
			},{
				//>>title: '트랜스코딩',
				title: _text('MN00298'),
				layout: 'fit',
				items: new Ariel.monitor.TaskPanel({taskType: 20})
			},{
				//>>title: '인제스트전송',
				title: _text('MN00226'),
				layout: 'fit',
				items: new Ariel.monitor.TaskPanel({taskType: 60})
			},
			/*
			{
				//>>title: 'ALTO',
				title: _text('MN00398'),
				layout: 'fit',
				items: new Ariel.monitor.TaskPanel({taskType: 110})
			},{
				//>>title: 'Hight Transcoder(Carbon)',
				title: _text('MN00404'),
				layout: 'fit',
				items: new Ariel.monitor.TaskPanel({taskType: 90})
			},
			*/
			{
				//>>title: 'PFR',
				title: _text('MN00401'),
				layout: 'fit',
				items: new Ariel.monitor.TaskPanel({taskType: 30})
			},{
				//>>title: 'REWRAP',
				title: _text('MN00402'),
				layout: 'fit',
				items: new Ariel.monitor.TaskPanel({taskType: 31})
			},{
				//>>title: 'REWRAP',
				title: 'BACKUP',
				layout: 'fit',
				items: new Ariel.monitor.TaskPanel({taskType: 110})
			},{
				//>>title: 'REWRAP',
				title: 'RESTORE',
				layout: 'fit',
				items: new Ariel.monitor.TaskPanel({taskType: 160})
			},{
				//>>title: 'REWRAP',
				title: 'PFR RESTORE',
				layout: 'fit',
				items: new Ariel.monitor.TaskPanel({taskType: 140})
			},{
				//>>title: 'REWRAP',
				title: 'BACKUP DELETE',
				layout: 'fit',
				items: new Ariel.monitor.TaskPanel({taskType: 150})
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
				{header: 'ID', dataIndex: 'task_log_id',	width: 45},
				//>>{header: '생성일', dataIndex: 'creation_date', renderer: Ext.util.Format.dateRenderer('Y-m-d H:i:s'), width: 120, align: 'center'},
				//>>{header: '내용', dataIndex:	'description', id: 'description'}
				{header: _text('MN00107'), dataIndex: 'creation_date', renderer: Ext.util.Format.dateRenderer('Y-m-d H:i:s'), width: 120, align: 'center'},
				{header: _text('MN00156'), dataIndex:	'description', id: 'description'}
			],
			selModel: new Ext.grid.RowSelectionModel({
				singleSelect: true
			}),
			viewConfig: {
				//>>emptyText: '기록된 작업 내용이 없습니다.'
				emptyText: _text('MSG00166')
			},
			tbar: [{
				//text: '새로고침',
				text: _text('MN00139'),
				icon: '/led-icons/arrow_refresh.png',
				handler: function(){
					Ext.getCmp('log').getStore().reload();
				}
			}]
        }]
	}
})()