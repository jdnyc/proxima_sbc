<?php
	require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
	session_start();
	require_once($_SERVER['DOCUMENT_ROOT'].'/lib/lib.php');
	$user_ids = $_REQUEST['user_ids'];
	$group_ids = $_REQUEST['group_ids'];
	$array_ids = explode(',', $user_ids);
	$array_ids_group = explode(',', $group_ids);

	if( count($array_ids_group) > 0 && !empty($group_ids) ){
		$query_g = "
			SELECT	*
			FROM		BC_MEMBER_GROUP
			WHERE	MEMBER_GROUP_ID IN(".$_REQUEST['group_ids'].")
		";
		$info_selected_group = $db->queryAll($query_g);
		$info_group = array();
		foreach( $info_selected_group as $groups ){
			array_push($info_group, $groups['member_group_name']);
		}
		$set_group = join(',', $info_group)."\\r";
	}

	if( count($array_ids) > 0  &&  !empty($user_ids) ){
		$query = "
			SELECT	*
			FROM		BC_MEMBER
			WHERE	MEMBER_ID IN(".$_REQUEST['user_ids'].")
		";
		$info_selected = $db->queryAll($query);
		$user_infos = array();
		foreach( $info_selected as $info ){
			$user_info = array();
			$user_info[0] = $info['member_id'];
			$user_info[1] = $info['user_id'];
			$user_info[2] = $info['user_nm'];
			$user_info[3] = $info['dept_nm'];
			array_push($user_infos, $user_info);
		}
		$store_selected = json_encode($user_infos);
	}else{
		$store_selected = "[]";
	}
	//['3', 'test', 'sss', 'sssfff']
?>

(function(){
	var user_search_store = new Ext.data.JsonStore({
		url: '/pages/menu/config/user/php/get.php',
		remoteSort: true,
		sortInfo: {
			field: 'user_id',
			direction: 'ASC'
		},
		idProperty: 'member_id',
		root: 'data',
		fields: [
			'member_id',
			'user_id',
			'user_nm',
			'group',
			'occu_kind',
			'job_position',
			'job_duty',
			'dep_tel_num',
			'breake',
			'dept_nm',
			{name: 'created_time', type: 'date', dateFormat: 'YmdHis'},
			{name: 'last_login', type: 'date', dateFormat: 'YmdHis'},
			{name: 'hired_date', type: 'date', dateFormat: 'YmdHis'},
			{name: 'retire_date', type: 'date', dateFormat: 'YmdHis'}
		],
		listeners: {
			exception: function(self, type, action, opts, response, args){
				try {
					var r = Ext.decode(response.responseText);
					if(!r.success) {
						//>>Ext.Msg.alert('정보', r.msg);
						Ext.Msg.alert(_text('MN00023'), r.msg);
					}
				}
				catch(e) {
					//>>Ext.Msg.alert('디코드 오류', e);
					Ext.Msg.alert(_text('MN00022'), e);
				}
			}
		}
	});

	var user_list = new Ext.grid.GridPanel({
		id: 'user_list',
		cls: 'proxima_customize',
		stripeRows: true,
		flex : 1,
		height : 330,
		border: true,
		store: user_search_store,
		loadMask: true,
		listeners: {
			viewready: function(self){
				self.getStore().load({
					params: {
						start: 0,
						limit: 20
					}
				});
			},
			rowdblclick: function (self, row_index, e) {
				var sel = Ext.getCmp('user_list').getSelectionModel().getSelected();
				viewUser(sel, search);
			}
		},
		colModel: new Ext.grid.ColumnModel({
			defaults: {
				sortable: true
			},
			columns: [
				new Ext.grid.RowNumberer(),
				//>>{header: 'number', dataIndex:'member_id',hidden:'true'},
				//>>{header: '사번', dataIndex: 'user_id',	align:'center' },
				//>>{header: '성명',   dataIndex: 'name',		align:'center'},
				//>>{header: '부서',   dataIndex: 'dept_nm',	align:'center'}

				{header: _text('MN00195'), dataIndex: 'user_id',	align:'center' },
				{header: 'number', dataIndex:'member_id',hidden:'true'},
				{header: _text('MN00223'),   dataIndex: 'user_nm',		align:'center'},
				{header: _text('MN00181'),   dataIndex: 'dept_nm',	align:'center'}
			]
		}),
		viewConfig: {
			forceFit: true
		},
		sm: new Ext.grid.RowSelectionModel({
			//singleSelect: true
		}),
		tbar: [{
			xtype: 'combo',
			id: 'search_f',
			width: 120,
			triggerAction: 'all',
			editable: false,
			mode: 'local',
			store: [
				//>>['s_created_time', '등록일자'],
				//>>['s_user_id', '아이디'],
				//>>['s_name', '이름'],
				//>>['s_dept_nm','부서']

				['s_created_time', _text('MN00102')],
				['s_user_id', _text('MN00195')],
				['user_nm', _text('MN00223')],
				['s_dept_nm',_text('MN00181')]
			],
			value: 'user_nm',
			listeners: {
				select: function(self, r, i){
					if (i == 0)
					{
						self.ownerCt.get(2).setVisible(true);
						self.ownerCt.get(3).setVisible(false);
					}
					else
					{
						self.ownerCt.get(3).setVisible(true);
						self.ownerCt.get(2).setVisible(false);
					}
				}
			}
		},' ',{
			hidden: true,
			xtype: 'datefield',
			id: 'search_v1',
			format: 'Y-m-d',
			width: 100,
			listeners: {
				render: function(self){
					self.setValue(new Date());
				}
			}
		},{

			allowBlank: false,
			xtype: 'textfield',
			id: 'search_v2',
			listeners: {
				specialKey: function(self, e){
					var w = self.ownerCt.ownerCt;
					if (e.getKey() == e.ENTER && self.isValid())
					{
						e.stopEvent();
						w.doSearch(w.getTopToolbar(), user_search_store);
					}
				}
			}
		},' ',{
			//icon:'/led-icons/magnifier_zoom_in.png',
			xtype: 'button',
			//>>text: '조회'
			//text: '<span style="position:relative;top:1px;"><i class="fa fa-search" style="font-size:13px;"></i></span>&nbsp;'+_text('MN00037'),
			cls: 'proxima_button_customize',
			width: 30,
			text: '<span style="position:relative;top:1px;" title="'+_text('MN00037')+'"><i class="fa fa-search" style="font-size:13px;color:white;"></i></span>',
			handler: function(b, e){
				var w = b.ownerCt.ownerCt;
				w.doSearch(w.getTopToolbar(), user_search_store);
			}
		},{
			//icon: '/led-icons/arrow_refresh.png',
			//>>text: '초기화',Clear Conditions
			//text: '<span style="position:relative;top:1px;"><i class="fa fa-rotate-left" style="font-size:13px;"></i></span>&nbsp;'+_text('MN02096'),
			cls: 'proxima_button_customize',
			width: 30,
			text: '<span style="position:relative;top:1px;" title="'+_text('MN02096')+'"><i class="fa fa-rotate-left" style="font-size:13px;color:white;"></i></span>',
			handler: function(btn, e){
				Ext.getCmp('search_v1').setValue('');
				Ext.getCmp('search_v2').setValue('');
				Ext.getCmp('user_list').getStore().load({
						params:{
							start:0,
							limit:20
						}
				});
			}
		}],
		bbar: new Ext.PagingToolbar({
			store: user_search_store,
			pageSize: 20
		}),
		doSearch: function(tbar, store){
			var combo_value = tbar.get(0).getValue(),
				params = {};
				params.start = 0;
				params.limit = 20;

			if (combo_value == 's_created_time')
			{
				params.search_field = combo_value;
				params.search_value = tbar.get(2).getValue().format('Y-m-d');
			}
			else
			{
				params.search_field = combo_value;
				params.search_value = tbar.get(3).getValue();
			}
			if(Ext.isEmpty(params.search_field) || Ext.isEmpty(params.search_value)){
				//>>Ext.Msg.alert('정보', '검색어를 입력해주세요.');
				Ext.Msg.alert(_text('MN00023'), _text('MSG00007'));
			}else{
				Ext.getCmp('user_list').getStore().load({
					params: params
				});
			}
		}
	});

	var toUserStore = new Ext.data.ArrayStore({//new Ext.data.JsonStore({
        autoLoad: false,
        fields: [
			{name: "member_id", type: "string"},
			{name: "user_id", type: "string"},
			{name: "user_nm", type: "string"},
			{name: "dept_nm", type: "string"}
		],
		data : <?=$store_selected?>
    });


	var selected_list = new Ext.grid.GridPanel({
		id: 'selected_list',
		cls: 'proxima_customize',
		stripeRows: true,
		flex : 1,
		height : 330,
		//margins: '27 0 0 0',
		autoScroll : true,
		border: true,
		store: toUserStore,
		loadMask: true,
		listeners: {
			viewready: function(self){

			},
			rowdblclick: function (self, row_index, e) {

			}
		},
		colModel: new Ext.grid.ColumnModel({
			defaults: {
				sortable: true
			},
			columns: [
				new Ext.grid.RowNumberer(),
				//>>{header: 'number', dataIndex:'member_id',hidden:'true'},
				//>>{header: '사번', dataIndex: 'user_id',	align:'center' },
				//>>{header: '성명',   dataIndex: 'name',		align:'center'},
				//>>{header: '부서',   dataIndex: 'dept_nm',	align:'center'}

				{header: _text('MN00195'), dataIndex: 'user_id',	align:'center' },
				{header: 'number', dataIndex:'member_id',hidden:'true'},
				{header: _text('MN00223'),   dataIndex: 'user_nm',		align:'center'},
				{header: _text('MN00181'),   dataIndex: 'dept_nm',	align:'center'}
			]
		}),
		viewConfig: {
			forceFit: true
		},
		sm: new Ext.grid.RowSelectionModel({
			//singleSelect: true
		}),
		tbar: [{
			xtype: 'displayfield',
			padding : 10,
			value : _text('MN02136'),//수신
			height : 22
		}]
	});

	function array_search(needle, haystack) {
		for(var i in haystack) {
			if(haystack[i] == needle) return i;
		}
		return false;
	}

	var user_win =  new Ext.Window({
		//>>title: '사용자 검색',MN00190
		title: _text('MN00190'),
		modal:true,
		width: 850,
		height: 420,
		layout: 'fit',
		items: [{
			xtype: 'panel',
			layout: 'hbox',
			border:false,
			padding : 10,
			buttons:[{
				//icon: '/led-icons/accept.png',
				text: '<span style="position:relative;top:1px;"><i class="fa fa-check" style="font-size:13px;"></i></span>&nbsp;'+_text('MN00046'),//'저장'
				handler:function(){
					var set_re;
					var users = new Array();
					var ids = new Array();
					var sel = Ext.getCmp('selected_list').getStore();
					Ext.each(sel.data.items, function(r){
						users.push(r.data.user_nm+' ['+r.data.user_id+']');
						ids.push(r.data.member_id);
					});
					Ext.getCmp('to_list').setValue('<?=$set_group?>'+users.join());
					Ext.getCmp('to_user_ids').setValue(ids.join());
					user_win.destroy();
				}
			},{
				//icon:'/led-icons/cancel.png',
				text: '<span style="position:relative;top:1px;"><i class="fa fa-close" style="font-size:13px;"></i></span>&nbsp;'+ _text('MN00031'),//'닫기'
				handler: function(self){
					user_win.close();
				}
			}],
			items: [
				user_list,
				{
					//xtype : 'panel',
					layout : 'fit',
					height : 330,
					width : 50,
					border : false,
					items : [{
						layout: {
							type:'vbox',
							pack:'center',
							align:'center'
						},
						defaults:{margins:'0 0 10 0'},
						border : false,
						style: {
								background : 'white'
						},

						items :[{
							xtype : 'button',
							//width : 25,
							//text : '<span style="position:relative;top:1px;"><i class="fa fa-chevron-right" style="font-size:13px;"></i></span>',
							cls: 'proxima_button_customize',
							width: 30,
							text: '<span style="position:relative;top:1px;"><i class="fa fa-chevron-right" style="font-size:13px;color:white;"></i></span>',
							handler : function(self){
								var sel = self.ownerCt.ownerCt.ownerCt.get(0).getSelectionModel().getSelections();
								var arr_sel = new Array();
								if( sel.length < 1 ){
									Ext.Msg.show({
										title : _text('MN00023'),//알림
										msg : _text('MSG01005'),//먼저 대상을 선택 해 주시기 바랍니다.
										buttons : Ext.Msg.OK
									});
									return;
								}else{
									var selectedStore = self.ownerCt.ownerCt.ownerCt.get(2).store;
									var array_store = new Array();
									Ext.each(selectedStore.data.items, function(r){
										array_store.push(r.data.member_id);
									});

									//array_search(needle, haystack)

									var check_in;
									Ext.each(sel, function(se){
										check_in = array_search(se.get('member_id'), array_store);
										if(check_in){
											Ext.Msg.show({
													title : _text('MN00023'),
													msg : _text('MSG02064')+'</br>'+se.get('user_nm')+' ['+se.get('user_id')+']',
													buttons : Ext.Msg.OK
												});//알림, 이미 선택하였습니다.
										}else{
											selectedStore.add(se);
										}
									});
								}
							}
						},{
							xtype : 'button',
							//width : 25,
							//text : '<span style="position:relative;top:1px;"><i class="fa fa-chevron-left" style="font-size:13px;"></i></span>',
							cls: 'proxima_button_customize',
							width: 30,
							text: '<span style="position:relative;top:1px;"><i class="fa fa-chevron-left" style="font-size:13px;color:white;"></i></span>',
							handler : function(self){
								var sel = self.ownerCt.ownerCt.ownerCt.get(2).getSelectionModel().getSelections();
								if( sel.length < 1 ){
									Ext.Msg.show({
										title : _text('MN00023'),//알림
										msg : _text('MSG01005'),//먼저 대상을 선택 해 주시기 바랍니다.
										buttons : Ext.Msg.OK
									});
									return;
								}else{
									self.ownerCt.ownerCt.ownerCt.get(2).store.remove(sel);
								}
							}
						}]
					}]
				},
				selected_list
			],
			listeners : {
				afterrender : function(self){
				}
			}
		}]
	});

	return user_win;
})()