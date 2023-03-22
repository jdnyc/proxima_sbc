(function(){

Ext.ns('Ariel.config');

Ariel.config.restore_sms = Ext.extend(Ext.Panel, {
		//>>title: '그룹 관리',MN00115
		//title: '리스토어 요청 SMS 수신관리',		
		renderAuth: function(v,metaData,r,rowIdx,colIdx,s){
			switch (v)
			{
				case '1':
					return '보도';
				break;

				case '2':
					return '제작';
				break;
			}
		},
		method: function(v,metaData,r,rowIdx,colIdx,s){
			switch (v)
			{
				case 'A':
					return '<font color=blue>사용</font>';
					//return '<font color=red> ' + _text('MSG00150');
				break;

				default:
					return '<font color=red>중지</font>';
					//return _text('MSG00151');
				break;
			}
		},

		layout: 'fit',
		initComponent: function(config){
			Ext.apply(this, config || {});

			this.store = new Ext.data.JsonStore({
				id: 'store_restore_sms',
				url: '/store/get_resotre_sms.php',
				remoteSort: true,
				sortInfo: {
					field: 'member_group_id',
					direction: 'DESC'
				},
				idProperty: 'member_group_id',
				root: 'datas',
				fields: [
					'id',
					'start_time',
					'end_time',
					'groups',
					'last_modify_user_id',
					'type',
					{name: 'last_modify_date', type: 'date', dateFormat: 'YmdHis'},
					'groups',
					'groups_nm'
				],
				listeners: {
					exception: function(self, type, action, opts, response, args){
						try {
							var r = Ext.decode(response.responseText, true);
							if(!r.success) {
								//>>Ext.Msg.alert('정보', response.responseText);
								Ext.Msg.alert(_text('MN00023'), response.responseText);
							}
						}
						catch(e) {
							//>>Ext.Msg.alert('정보', response.responseText);
							Ext.Msg.alert(_text('MN00023'), response.responseText);
						}
					}
				}
			});

			this.items = new Ext.grid.GridPanel({
				id: 'store_restore_sms_list',
				border: false,
				store: this.store,
				loadMask: true,
				listeners: {
					viewready: function(self){
						self.getStore().load();
					}
				},
				colModel: new Ext.grid.ColumnModel({
					defaults: {
						sortable: false
					},
					columns: [
						new Ext.grid.RowNumberer(),						
						{header: '모드', dataIndex: 'type',align:'center',renderer:this.method,width:70},
						{header: '장르', dataIndex: 'id',align:'center',menuDisabled: true,renderer:this.renderAuth,width:70},
						{header: '시작시간', dataIndex: 'start_time',menuDisabled: true,align:'center',width:80},
						{header: '종료시간', dataIndex: 'end_time',menuDisabled: true,align:'center',width:80},
						{header: '수정일시', dataIndex: 'last_modify_date',menuDisabled: true, renderer: Ext.util.Format.dateRenderer('Y-m-d H:i:s'), width: 150,align:'center'},
						{header: '수정자', dataIndex: 'last_modify_user_id',menuDisabled: true,align:'center',width:120},
						{header: '설정그룹', dataIndex: 'groups_nm',menuDisabled: true,align:'center',width:450}
					]
				}),
				tbar: [{
					icon: '/led-icons/accept.png',
					
					text: 'SMS 설정 변경',
					handler: function(btn, e){
						var sm = Ext.getCmp('store_restore_sms_list').getSelectionModel();
						if(sm.hasSelection()){
							this.request({select: sm.getSelected()});
						}else{							
							Ext.Msg.alert("알림","수정하실 장르를 선택해 주세요");
						}
					},
					scope: this
				},'-',{
					icon: '/led-icons/arrow_refresh.png',
					//>>text: '새로고침',
					text: _text('MN00139'),
					handler: function(btn, e){
						Ext.getCmp('store_restore_sms_list').getStore().reload();
					}
				}]
			});

			Ariel.config.restore_sms.superclass.initComponent.call(this);
		},

		request: function(p){
			
			var end_time    = p.select.get('end_time');
			var start_time  = p.select.get('start_time');
			var id          = p.select.get('id');
			var method_type = p.select.get('type');
			var groups      = p.select.get('groups');

			var sms_user_url = "/store/get_user_groups.php?id="+id;
			
			var request_form_win = new Ext.Window({
				title: '리스토어 요청 SMS 수신설정',
				 width : 385,
                  											
				modal: true,
				border: true,
				frame: true,
				padding: '0px',	
				buttonAlign: 'center',
				items:
				[
					{
                                itemId : 'setup_form',
								id:'request_sms_form',
                                xtype : 'form',
                                monitorValid : true,
                                border : false,
                                padding : '5',
                                freame : true,
								
                                items : [{
                                        xtype: 'hidden',
                                        itemId:'c_type',
                                        name:'type'
                                },{
                                        xtype: 'hidden',
                                        itemId:'c_category_id',
                                        name: 'category_id'
                                },{
                                        xtype:'combo',
                                        itemId: 'c_method',
                                        name : 'method_nm',
                                        anchor : '95%',
                                        mode: 'local',
                                        value: method_type,
                                        forceSelection: true,
                                        editable: false,
                                        allowBlank: false,
                                        fieldLabel: '사용방법',
                                        labelWidth : 70,
                                        displayField: 'method_nm',
                                        valueField: 'method_val',
                                        triggerAction : 'all',                     
                                        store: new Ext.data.ArrayStore({
                                            fields : ['method_nm', 'method_val'],
                                            data:[
                                                ['사용', 'A'],['중지', 'M']
                                            ]
                                        }),
                                        listeners : {
                                            select : function(combo, record, index) {
                                                var combo_val = combo.getValue();
												var form      = win.items.items[0].getForm();
											
                                                if(combo_val == 'M' || combo_val == 'N')
                                                {	
													form.findField("s_period").setDisabled(true);
													form.findField("e_period").setDisabled(true);													
                                                }
                                                else if(combo_val == 'A')
                                                {
													form.findField("s_period").setDisabled(false);
													form.findField("e_period").setDisabled(false);                                                  
                                                }
                                            }
                                        }                                        
                                },
								{
									xtype: 'compositefield',
									flex: 1,
									fieldLabel: '시작종료시간',									
									items: [{
												xtype : 'timefield',												
												name : 's_period',
												width: 110,
												format:'H:i',
												minValue: '00:00',
												maxValue: '23:00',
												increment: 60,
												allowBlank : false,
												value :start_time
											},
											{
												xtype: 'displayfield',
												value:'~'
											},
											
											{
												xtype : 'timefield',												
												name : 'e_period',
												width: 110,
												format:'H:i',
												minValue: '00:00',
												maxValue: '23:00',
												increment: 60,
												allowBlank : false,
												value :end_time,
												listeners:
												{
													select : function( combo, record, index )
													{
														var form       = win.items.items[0].getForm();
														var s_period_v = form.findField("s_period").getValue();													
													}
												}
											}
										  ]
								
								},
								{
									xtype: 'checkboxcombo',
									id:'restore_sms_grouop_list',
									hiddenName: 'check_flag',
									fieldLabel: 'group_nm',
									width:238,
									fieldLabel: '그룹설정',		
									store: new Ext.data.JsonStore({
										fields: ['group_id', 'group_nm','check_flag'],
										url: sms_user_url,										
										method: 'GET',
										root: 'datas',
										idProperty: 'group_id',
										autoLoad: true,
										listeners:
										{
											load: function( self, records, options){
												if(groups)
												{
													Ext.getCmp("restore_sms_grouop_list").setValue(groups);
												}												
											}
										}
									}),
									valueField: 'group_id',
									displayField: 'group_nm',			
									allowBlank: true,
									listeners:{
										
									}
								}
								]
                    }
				],
				buttons : [{
                        scale : 'medium',
                        text : '설정',
						icon: '/led-icons/accept.png',
						click_flag :false,
                        handler : function(){	
							if(!this.click_flag)
							{
								this.click_flag = true;
								var forms= Ext.getCmp('request_sms_form').getForm().getValues();
								Ext.Ajax.request({
									url: '/store/save_request_sms.php',
									params: {
										values: Ext.encode(forms),
										id :id
									},
									callback: function(self, success, response){
										try {
											var r = Ext.decode(response.responseText);
											if(r.success == true)
											{
												Ext.Msg.alert('알림', r.msg );												
											}
											else 
											{
												Ext.Msg.alert('알림', r.msg );
											}
											request_form_win.close();
											Ext.getCmp('store_restore_sms_list').getStore().load();
										}
										catch(e){
											//>>Ext.Msg.alert('오류', e);
											Ext.Msg.alert(_text('MN00022'), e);
										}
									}
								});
							}


						}
				},
				{
                        scale : 'medium',
                        text : '닫기',
					//	icon: '/led-icons/accept.png',
                        handler : function(){	
							request_form_win.close();

						}
				}
				]
			}).show();
			
		}
	});


	return new Ariel.config.restore_sms();
	
})()