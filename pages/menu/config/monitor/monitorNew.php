<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
session_start();
fn_checkAuthPermission($_SESSION);
$query = "
	SELECT	*
	FROM	BC_SYS_CODE
	WHERE	CODE = 'AUTOMATIC_REFRESH_TIME'
";
$auto_refresh = $db->queryRow($query);
$interval_time = empty($auto_refresh['ref1']) ? 0 : $auto_refresh['ref1'];

?>

(function(){

	var store = new Ext.data.JsonStore({
		url: '/store/config/get_task.php',
		root: 'data',
		baseParams: {
			action : 'list_task'
		},
		fields: [
			'content_id', //콘텐츠ID
			'content_title', //제목
			'workflow_name', //작업흐름 명
			'count_complete',//완료 작업 수
			'count_task',//작업 수 
			{name: 'task_info', convert: function(v, record) {
				return record.workflow_name + '(' + record.count_complete + '/' + record.count_task + ')';
			}},//작업명(작업완료 수/작업 수)
			'total_progress', //진행율 합
			{name: 'ca_progress', convert: function(v, record) {
				return record.total_progress/record.count_task;
			}},//진행율(%)
			'task_status_name',//작업상태
			'task_job_name',//작업명
			'reg_user_id', //등록자
			{name: 'register', convert: function(v, record) {
				return record.reg_user_id + '(' + record.user_nm + ')';
			}},//등록자ID(등록자이름)
			{name: 'start_datetime', type: 'date', dateFormat: 'YmdHis'},//작업시작
			{name: 'complete_datetime', type: 'date', dateFormat: 'YmdHis'},//작업완료
			{name: 'creation_datetime', type: 'date', dateFormat: 'YmdHis'},//작업생성
			'task_workflow_id',
			'root_task'
		]
	});

	function onSearch() {
		var v_items = Ext.getCmp('grid_monitoring').getTopToolbar().items.items;
		var v_obj = {};
		Ext.each(v_items, function(r){
			if(r.id.indexOf('ext') == -1 && r.xtype != 'button' ){
				v_obj[r.id] = Ext.getCmp(r.id).getValue();
			}
		});
		
		Ext.getCmp('grid_monitoring').getStore().load({
			params: {
				search : Ext.encode(v_obj)
			}
		});
	}

	function showTaskDetail(workflow_id, content_id, root_task) {

        Ext.Ajax.request({
            url: '/javascript/ext.ux/viewInterfaceWorkFlow.php',
            params: {
                workflow_id: workflow_id,
                content_id: content_id,
				root_task: root_task,
				screen_width : window.innerWidth,
				screen_height : window.innerHeight
            },
            callback: function (options, success, response) {
                if (success) {
                    try {
                        var r = Ext.decode(response.responseText);
						r.show();
                    } catch (e) {
                        Ext.Msg.alert(e.name, e.message);
                    }
                } else {
                    Ext.Msg.alert( _text('MN01098'), response.statusText);//'서버 오류'
                }
            }
        });
    }



	return {

		layout:	'fit',
		border: false,
		title: '<span class="user_span"><span class="icon_title"><i class=""></i></span><span class="main_title_header">'+_text('MN02377')+'</span></span>',
		cls: 'grid_title_customize proxima_customize proxima_customize_progress',

		items: [{
			xtype: 'grid',
			border: false,
			id: 'grid_monitoring',
			cls: 'proxima_customize proxima_customize_progress',
			stripeRows: true,
			frame: false,
			defaults: {
				border: false,
				margins: '10 10 10 10'
			},
			frame: false,
			//title: _text('MN02128'),//'작업 내역'
			//title : '<span class="user_span"><span class="icon_title"><i class="fa fa-list"></i></span><span class="main_title_header">'+_text('MN02128')+'</span></span>',
			cls: 'proxima_customize',
			loadMask: true,
			store: store,
			viewConfig: {
				forceFit: true,
				emptyText: _text('MSG00148')//결과 값이 없습니다
			},
			listeners: {
				viewready: function (self) {
					onSearch();
				},

				rowcontextmenu: function (self, row_index, e) {
					/*
					e.stopEvent();

					self.getSelectionModel().selectRow(row_index);

					var rowRecord = self.getSelectionModel().getSelected();
					var workflow_id = rowRecord.get('workflow_id');
					var content_id = rowRecord.get('content_id');
					var root_task = rowRecord.get('root_task');


					var menu = new Ext.menu.Menu({
						items: [{
							text: '<span style="position:relative;top:1px;"><i class="fa fa-list" style="font-size:13px;"></i></span>&nbsp;'+_text('MN00241'),//'작업흐름보기'
							//icon: '/led-icons/chart_organisation.png',
							handler: function (btn, e) {
								showTaskDetail(workflow_id, content_id, root_task);
								menu.hide();
							}
						}, {
							hidden: true,
							text: '<span style="position:relative;top:1px;"><i class="fa fa-check-square-o" style="font-size:13px;"></i></span>&nbsp;'+_text('MN02129'),//'완료 처리'
							icon: '/led-icons/chart_organisation.png',
							handler: function (btn, e) {

								// console.log(workflow_id, content_id);

								// manualTaskComplete(workflow_id, content_id);
								menu.hide();
							}
						}]
					});
					menu.showAt(e.getXY());
					*/
				},

				rowdblclick: function (self, row_index, e) {
					/**/
					var rowRecord = self.getSelectionModel().getSelected();
					var workflow_id = rowRecord.get('task_workflow_id');
					var content_id = rowRecord.get('content_id');
					var root_task = rowRecord.get('root_task');

					showTaskDetail(workflow_id, content_id, root_task);
					
				}
			},
			colModel: new Ext.grid.ColumnModel({
				defaults: {
					//menuDisabled: true
				},
				columns: [//콘텐츠ID, 제목, 작업명, 진행율(%), 상태, 진행중작업, 등록자, 등록시간, 작업시작, 작업종료
					{header: '<center>'+_text('MN00287')+'</center>',dataIndex: 'content_id', width: 50},//콘텐츠ID
					{header: '<center>'+_text('MN00249')+'</center>', dataIndex: 'content_title'},//제목
					{header: '<center>'+_text('MN01112')+'</center>', dataIndex: 'task_info', width: 110},//작업흐름 명
					new Ext.ux.ProgressColumn({
						header: '<center>'+_text('MN00261')+'</center>',
						width: 60,
						dataIndex: 'ca_progress',
						align: 'center',
						renderer: function (value, meta, record, rowIndex, colIndex, store, pct) {
							return Ext.util.Format.number(pct, "0%");
						}
					}),//진행율(%)
					{header: '<center>'+_text('MN00237')+'</center>', dataIndex: 'task_status_name', width: 70},//작업상태
					{header: '<center>'+_text('MN00236')+'</center>', dataIndex: 'task_job_name', width: 100},//작업명
					{header: '<center>'+_text('MN00120')+'</center>', dataIndex: 'register', width: 70},//등록자
					{
						header: '<center>'+_text('MN01023')+'</center>',
						dataIndex: 'creation_datetime',
						align: 'center',
						renderer: Ext.util.Format.dateRenderer('Y-m-d H:i:s'),
						width: 100
					},//작업생성일
					{
						header: '<center>'+_text('MN00233')+'</center>',
						dataIndex: 'start_datetime',
						align: 'center',
						renderer: Ext.util.Format.dateRenderer('Y-m-d H:i:s'),
						width: 100
					},//작업 시작
					{
						header: '<center>'+_text('MN00234')+'</center>',
						dataIndex: 'complete_datetime',
						align: 'center',
						renderer: Ext.util.Format.dateRenderer('Y-m-d H:i:s'),
						width: 100
					}//작업 종료
				]
			}),
			sm: new Ext.grid.RowSelectionModel({
				singleSelect: true
			}),
			tbar: [{
				xtype: 'combo',
				id : 'combo_monitoring_type',
				itemId: 'filter',
				mode: 'local',
				store: [
					['All', _text('MN02131')],//'전체 자료'
					['mine', _text('MN02132')]//'내 자료'
				],
				value: 'mine',
				width: 90,
				triggerAction: 'all',
				typeAhead: true,
				editable: false,
				listeners: {
					select: function () {
						onSearch();
					}
				}
			},{
				width : 10
			},{
				xtype: 'datefield',
				id : 'start_date_monitoring',
				format: 'Y-m-d',
				width: 90,
				value: new Date().add(Date.DAY, -3).format('Y-m-d')
			}, _text('MN00183'),//' 부터 ' 
			{
				xtype: 'datefield',
				id : 'end_date_monitoring',
				format: 'Y-m-d',
				width: 90,
				value: new Date()
			},{
				width : 10
			},{
				xtype: 'combo',
				width: 90,
				id : 'combo_monitoring_task',
				itemId: 'filter_status',
				mode: 'remote',
				triggerAction: 'all',
				typeAhead: true,
				editable: false,
				hiddenName: 'name',
				hiddenValue: 'status',
				valueField: 'status',
				displayField: 'name',
				forceSelection: true,
				value : _text('MN00008'),
				store: new Ext.data.JsonStore({
					//autoLoad: true,
					url: '/store/config/get_task.php',
					baseParams: {
						action : 'list_task_status'
					},
					root: 'data',
					//idProperty: 'task_id',
					fields: [
						{name: 'status', type: 'string'},
						{name: 'name', type: 'string'}
					]
				}),
				listeners: {
					select: function (self) {
						onSearch();
					}
				}
			},{
				width : 10
			},{
				xtype: 'combo',
				width: 120,
				id : 'combo_monitoring_progress',
				itemId: 'filter_type',
				mode: 'remote',
				triggerAction: 'all',
				typeAhead: true,
				editable: false,
				hiddenName: 'name',
				hiddenValue: 'type',
				valueField: 'type',
				displayField: 'name',
				forceSelection: true,
				value : _text('MN00008'),
				store: new Ext.data.JsonStore({
					//autoLoad: true,
					url: '/store/config/get_task.php',
					baseParams: {
						action : 'list_task_type'
					},
					root: 'data',
					//idProperty: 'task_id',
					fields: [
						{name: 'type', type: 'string'},
						{name: 'name', type: 'string'}
					]
				}),
				listeners: {
					select: function () {
						onSearch();
					}
				}
			},{
				width : 10
			},{
				id: 'value_search',
				xtype: 'textfield',
				width: 300,
				emptyText: _text('MN00249'),
				listeners: {
					specialkey: function (field, e) {
						if (e.getKey() == e.ENTER) {
							onSearch();
						}
					}
				}
			},{
				xtype: 'button',
				//id : 'search_monitoring',
				cls: 'proxima_button_customize',
				width: 30,
				height: 25,
				text: '<span style="position:relative;" title="'+_text('MN00037')+'"><i class="fa fa-search" style="font-size:13px;color:white;"></i></span>',//'조회'
				handler: onSearch
				//scope: _this
			},'->'
			<?php
				if( $auto_refresh['use_yn'] == 'Y' ){
			?>
			,{
				xtype: 'checkbox',
				//boxLabel: '자동 새로고침',
				boxLabel: _text('MN00229'),
				checked: false,
				listeners: {
					check: function (self, checked) {
						var TaskListTabPanel = self.ownerCt.ownerCt;
						if (checked) {
							TaskListTabPanel.runAutoReload(TaskListTabPanel);
						}
						else {
							TaskListTabPanel.stopAutoReload();
						}
					},
					render : function(self){
						var TaskListTabPanel = self.ownerCt.ownerCt;
						TaskListTabPanel.stopAutoReload();
					}
				}
			}
			<?php
				}
			?>
			],
			runAutoReload: function(thisRef){
				this.stopAutoReload();

				var time_interval = <?=$interval_time?> * 1000;

				this.intervalID = setInterval(function (e) {
					if (thisRef ) {
						thisRef.getStore().reload();
					}
				}, time_interval);
			},

			stopAutoReload: function(){
				if (this.intervalID) {
					clearInterval(this.intervalID);
				}
			},
			bbar: {
				xtype: 'paging',
				pageSize: 19,
				displayInfo: true,
				store: store
			}
		}]
	}
})()