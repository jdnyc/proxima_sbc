<?php
	require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
	session_start();
	require_once($_SERVER['DOCUMENT_ROOT'].'/lib/lib.php');
	$user_ids = $_POST['user_ids'];
	$group_ids = $_POST['group_ids'];
	$array_ids = explode(',', $user_ids);
	$array_ids_group = explode(',', $group_ids);


	if( count($array_ids) > 0 && !empty($user_ids) ){
		$query_u = "
			SELECT	*
			FROM		BC_MEMBER
			WHERE	MEMBER_ID IN(".$user_ids.")
		";
		$info_selected_user = $db->queryAll($query_u);
		$info_user = array();
		foreach( $info_selected_user as $user ){
			array_push($info_user, $user['user_nm']."[".$user['user_id']."]");
		}
		$set_user = join(',', $info_user);
		$set_line = "\\r";
	}

	if( count($array_ids_group) > 0  &&  !empty($group_ids) ){
		$query = "
			SELECT	*
			FROM		BC_MEMBER_GROUP
			WHERE	MEMBER_GROUP_ID IN(".$group_ids.")
		";
		$info_selected = $db->queryAll($query);
		$user_infos = array();
		foreach( $info_selected as $info ){
			$user_info = array();
			$user_info[0] = $info['member_group_id'];
			$user_info[1] = $info['member_group_name'];
			$user_info[2] = $info['description'];
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
		url: '/pages/menu/config/user/php/get_group.php',
		remoteSort: true,
		sortInfo: {
			field: 'member_group_id',
			direction: 'ASC'
		},
		idProperty: 'member_group_id',
		root: 'data',
		fields: [
			'member_group_id',
			'member_group_name',
			'description',
			{name: 'created_date', type: 'date', dateFormat: 'YmdHis'}
		],
		baseParams : {
			type : 'notice'
		},
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
				//>>{header: 'member_group_id', dataIndex:'member_group_id',hidden:'true'},
				//>>{header: '그룹명', dataIndex: 'member_group_name',	align:'center' },
				//>>{header: '설명',   dataIndex: 'description',		align:'center'},
				//>>{header: '등록일자',   dataIndex: 'created_date',		align:'center'}

				{header: _text('MN02148'), dataIndex: 'member_group_name'},
				{header: 'member_group_id', dataIndex:'member_group_id',hidden:'true'},
				{header: _text('MN00049'),   dataIndex: 'description'},
				{header: _text('MN00102'), dataIndex: 'created_date',	align:'center', renderer : Ext.util.Format.dateRenderer('Y-m-d') }
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
				//>>['created_date', '등록일자'],
				//>>['member_group_name', '그룹명']
				['created_date', _text('MN00102')],
				['member_group_name', _text('MN02148')]
			],
			value: 'member_group_name',
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

			if (combo_value == 'created_date')
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
			{name: "member_group_id", type: "string"},
			{name: "member_group_name", type: "string"},
			{name: "description", type: "string"},
			{name: "created_date", type: 'date', dateFormat: 'YmdHis'}
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
				//>>{header: 'member_group_id', dataIndex:'member_group_id',hidden:'true'},
				//>>{header: '그룹명', dataIndex: 'member_group_name',	align:'center' },
				//>>{header: '설명',   dataIndex: 'description',		align:'center'},
				//>>{header: '등록일자',   dataIndex: 'created_date',		align:'center'}

				{header: 'member_group_id', dataIndex:'member_group_id',hidden:'true'},
				{header: _text('MN02148'), dataIndex: 'member_group_name'},
				{header: _text('MN00049'),   dataIndex: 'description'},
				{header: _text('MN00102'), dataIndex: 'created_date',	align:'center', renderer : Ext.util.Format.dateRenderer('Y-m-d') }
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
					var users = new Array();
					var ids = new Array();
					var sel = Ext.getCmp('selected_list').getStore();
					Ext.each(sel.data.items, function(r){
						users.push(r.data.member_group_name);
						ids.push(r.data.member_group_id);
					});
					var user_lines = '';
					if(users.length > 0){
						user_lines = users.join()+'<?=$set_line?>';
					}
					Ext.getCmp('to_list').setValue(user_lines+'<?=$set_user?>');
					Ext.getCmp('to_group_ids').setValue(ids.join());
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
										array_store.push(r.data.member_group_id);
									});

									//array_search(needle, haystack)

									var check_in;
									Ext.each(sel, function(se){
										check_in = array_search(se.get('member_group_id'), array_store);
										if(check_in){
											Ext.Msg.show({
													title : _text('MN00023'),
													msg : _text('MSG02064')+'</br>'+se.get('member_group_name'),
													buttons : Ext.Msg.OK
												});//알림, 이미 선택하였습니다.
										}else{
											selectedStore.add(se);
										}
									});


									//var selectedStore = self.ownerCt.ownerCt.ownerCt.get(2).store;
									//Ext.each(selectedStore.data.items, function(r){
										//Ext.each(sel, function(se){
											//if( r.data.member_group_id == se.get('member_group_id')){
												//Ext.Msg.show({
													//title : _text('MN00023'),
													//msg : _text('MSG02064')+'</br>'+r.get('user_nm')+' ['+r.get('user_id')+']',
													//buttons : Ext.Msg.OK
												//});//알림, 이미 선택하였습니다.
												//return;
											//}else{
												//arr_sel.push(se);
												//selectedStore.add(se);
											//}
										//});
									//});
									//selectedStore.add(sel);
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