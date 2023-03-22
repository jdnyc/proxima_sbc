(function() {
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
					'job_position',
					'dept_nm',
					{name: 'created_date', type: 'date', dateFormat: 'YmdHis'},
					{name: 'last_login_date', type: 'date', dateFormat: 'YmdHis'}
				],
				listeners: {
					exception: function(self, type, action, opts, response, args){
						try {
							var r = Ext.decode(response.responseText);
							if(!r.success) {
								//!!Ext.Msg.alert('알림', r.msg);
								Ext.Msg.alert(_text('MN00023'), r.msg);
							}
						}
						catch(e) {
							//!!Ext.Msg.alert('오류', r.msg);
							Ext.Msg.alert(_text('MN00022'), e);
						}
					}
				}
		});

		var adstore = new Ext.data.JsonStore({
		    root: 'data',
            url: '/store/statistics/personal/adstore.php',
            autoLoad: true,
            fields: [
                {name: 'd'},
                {name: 'v'}
            ]
		});
		var ystore = new Ext.data.JsonStore({
			root: 'data',
            url: '/store/statistics/personal/ystore.php',
            fields: [
                {name: 'd'},
                {name: 'v'}
            ]

		});
		var chartstore = new Ext.data.JsonStore({
			root: 'data',
			autoLoad: true,
            url: '/store/statistics/personal/chartstore.php',           
            fields: [
                {name: 'name'},
                {name: 'visit',type: 'int'}
            ]

		});
		var gridstore = new Ext.data.JsonStore({
		    url: '/store/statistics/personal/gridstore.php',
            root: 'data',
            autoLoad: true,
            fields: [
                {name: 'jan',type: 'int'},
                {name: 'feb',type: 'int'},
                {name: 'mar',type: 'int'},
                {name: 'apr',type: 'int'},
                {name: 'may',type: 'int'},
                {name: 'jun',type: 'int'},
                {name: 'jul',type: 'int'},
                {name: 'aug',type: 'int'},
                {name: 'seb',type: 'int'},
                {name: 'oct',type: 'int'},
                {name: 'nov',type: 'int'},
                {name: 'dec',type: 'int'},
				{name: 'user_id', type: 'char'}
            ]

		});


	return{
		layout: 'vbox',
		frame: false,
		border: false,
		layoutConfig : {
            align: 'stretch',
			pack: 'start'
        },
		tbar : [
			{
            xtype: 'toolbar',
            height: 30,
            items: [{
                    xtype: 'tbtext',
					//!!연도선택
					text: _text('MN00213')
                },{
                    xtype: 'combo',
                    width: 70,
                    store: ystore,
                    value: new Date().format('Y'),
                    displayField: 'd',
                    valueField: 'v',
                    editable: false,
                    triggerAction: 'all',
                    id: 'login_chart_statistics_year'
                },{
                    xtype: 'tbseparator',
					width:15

                },{
                    xtype: 'button',
					icon: '/led-icons/magnifier_zoom_in.png',
                    text: _text('MN00125'),
                    width: 47,
                    ref: '../button',
                    id: 'btn',
					handler: function(btn, e){
						var search=new Ext.Window({
									title: _text('MN00190'),
									modal:true,
									width: 400,
									height: 400,
									layout: 'fit',
									items: [
										{
											xtype: 'form',
											layout: 'fit',
											border:false,

											buttons:[
											{
												icon: '/led-icons/magnifier_zoom_in.png',
												text: _text('MN00296'),
												handler:function(){
													var del = Ext.getCmp('user_list').getSelectionModel().getSelected();
													
													Ext.getCmp('chart').store.reload({
														params: {
															year:	Ext.getCmp('login_chart_statistics_year').getValue(),
															user:   del.get('user_id')
														}
													});
													Ext.getCmp('grid1').store.reload({
														params: {
															year:	Ext.getCmp('login_chart_statistics_year').getValue(),
															user:   del.get('user_id')
														}
													});
													search.destroy();
												}
											},{
												icon:'/led-icons/cancel.png',
												text: _text('MN00031'),
												handler: function(self){
													self.ownerCt.ownerCt.ownerCt.destroy();
												}
											}],
											items: [
												new Ext.grid.GridPanel({
													id: 'user_list',
													border: false,
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
														}
													},
													colModel: new Ext.grid.ColumnModel({
														defaults: {
															sortable: true
														},
														columns: [
															new Ext.grid.RowNumberer(),
															//{header: 'number', dataIndex:'member_id',hidden:'true'},
															/*!!
															{header: '아이디', dataIndex: 'user_id',	align:'center' },
															{header: '이름',   dataIndex: 'user_nm',		align:'center'},
															{header: '부서',   dataIndex: 'dept_nm',	align:'center'}
															*/
															{header: _text('MN00195'), dataIndex: 'user_id',	align:'center' },
															{header: _text('MN00196'),   dataIndex: 'user_nm',		align:'center'},
															{header: _text('MN00181'),   dataIndex: 'dept_nm',	align:'center'}
														]
													}),
													viewConfig: {
														forceFit: true
													},
													tbar: [{
														xtype: 'combo',
														id: 'search_f',
														width: 70,
														triggerAction: 'all',
														editable: false,
														mode: 'local',
														store: [
															/*!!
															['s_created_time', '등록일자'],
															['s_user_id', '아이디'],
															['s_name', '이름'],
															['s_dept_nm','부서']
															*/
															['s_created_time', _text('MN00102')],
															['s_user_id', _text('MN00195')],
															['s_name', _text('MN00196')],
															['s_dept_nm',_text('MN00181')]
														],
														value: 's_created_time',
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
														xtype: 'datefield',
														id: 'search_v1',
														format: 'Y-m-d',
														listeners: {
															render: function(self){
																self.setValue(new Date());
															}
														}
													},{
														hidden: true,
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
														icon:'/led-icons/magnifier_zoom_in.png',
														xtype: 'button',
														//!!text: '조회',
														text: _text('MN00059'),
														handler: function(b, e){
															var w = b.ownerCt.ownerCt;
															w.doSearch(w.getTopToolbar(), user_search_store);
														}
													},'-',{
														icon: '/led-icons/arrow_refresh.png',
														//!!text: '새로고침',														
														text: _text('MN00139'),
														handler: function(btn, e){
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
															//!!Ext.Msg.alert('정보', '검색어를 입력해주세요.');
															Ext.Msg.alert(_text('MN00023'), _text('MSG00007'));
														}else{
															Ext.getCmp('user_list').getStore().load({
																params: params
															});
														}
													}
												})
											]
										}
									]
								})	.show();

					}
                }]
		}],
       items : [{
                xtype: 'linechart',
                layout: 'fit',
                id: 'chart',
				store : chartstore,
				xField:'name',
				flex: 2,
				listners:
				{
					itemclick:function(o){
						var rec = chartstore.getAt(o.index);
						Ext.example.msg('Item Selected','You chose{0}.',rec.get('name'));
					}
				},
				extraStyle:
				{
					padding:10,
					animationEnabled:true
				},
				series:
				[
					{
						type:'line',
						//!!displayName:'로그인 횟수',
						displayName:_text('MN00122'),
						yField:'visit',
						style:
						{
							size:7,
							connectPoints:true
						}
					}
				]

            },
            {
                xtype: 'grid',
                //!!title: '기간별 로그인 횟수 그리드',
                title: _text('MN00152'),
				store: gridstore,	
				tbar:	[{
					xtype: 'toolbar',
					height: 35,
					items: [

						{
							xtype: 'button',
							icon: '/led-icons/disk.png',
							style: 'border-style:outset;',
							//!!text: '엑셀로 저장',							
							text: _text('MN00212'),
							ref: '../button',
							id: 'btn1',
							handler: function(btn1, e){
								//console.log(gridstore.get('jan'));

								var	year=	Ext.getCmp('login_chart_statistics_year').getValue();
								var	user=   gridstore.reader.jsonData.data[0].user_id;
								//console.log(gridstore.reader.jsonData.data[0].user_id);
								//Ext.Msg.alert('dd',user);
								window.location.href = "/store/statistics/personal/login_select_excel.php?user="+user+"&year="+year;
							}
						}]
				}],
                border: false,
                autoShow: true,
                ref: 'grid',
                id: 'grid1',
				flex: 1,
                columns: [
                    {
                        xtype: 'gridcolumn',                        
                        //!!header: '1월',
						header: _text('MN00071'),
						sortable: true,
                        id: 'jan'
                    },
                    {
                        xtype: 'gridcolumn',
                        //!!header: '2월',
						header: _text('MN00072'),
                        sortable: true,
                        id: 'feb'
                    },
                    {
                        xtype: 'gridcolumn',
                        //!! header: '3월',
						header: _text('MN00073'),
                        sortable: true,
                        id: 'mar'
                    },
                    {
                        xtype: 'gridcolumn',
                        //!!header: '4월',
						header: _text('MN00074'),
                        sortable: true,
                        id: 'apr'
                    },
                    {
                        xtype: 'gridcolumn',
                        //!!header: '5월',
						header: _text('MN00075'),
                        sortable: true,
                        id: 'may'
                    },
                    {
                        xtype: 'gridcolumn',
                        //!!header: '6월',                        
						header: _text('MN00076'),
                        sortable: true,
                        id: 'jun'
                    },
                    {
                        xtype: 'gridcolumn',
                        //!!header: '7월',
						header: _text('MN00077'),
                        sortable: true,
                        id: 'jul'
                    },
                    {
                        xtype: 'gridcolumn',
                        //!!header: '8월',
						header: _text('MN00078'),
                        sortable: true,
                        id: 'aug'
                    },
                    {
                        xtype: 'gridcolumn',
                        //!!header: '9월',
						header: _text('MN00079'),
                        sortable: true,
                        id: 'seb'
                    },
                    {
                        xtype: 'gridcolumn',
                        //!!header: '10월',
						header: _text('MN00080'),
                        sortable: true,
                        id: 'oct'
                    },
                    {
                        xtype: 'gridcolumn',
                        //!!header: '11월',
						header: _text('MN00081'),
                        sortable: true,
                        id: 'nov'
                    },
                    {
                        xtype: 'gridcolumn',
                        //!!header: '12월',
						header: _text('MN00082'),
                        sortable: true,
                        id: 'dec'
                    }
                ],
					viewConfig: {
					forceFit: true
				}
            }]

	};
})()
