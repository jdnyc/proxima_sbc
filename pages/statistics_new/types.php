<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/lang.php');

?>

(function(){

	var myMask = new Ext.LoadMask(Ext.getBody(), {msg:"loading..."});
	var myPageSize = 10;

	var test_d = new Date(),
	year = test_d.getFullYear(),
	years =  ['총계'],
    products = ['cnt', 'filesize'],
    fields = [],
    columns = [],
    data = [],
    continentGroupRow = [],
    cityGroupRow = [],
	check = 0;

	for(var i = year; i > year-5; --i){
		years.push(i+'년');
	}

	years.push(year-5+'년 이전');

	function map(value, type){
		switch(value)
		{
			case 'cnt':
				value_ = '등록<br>건수';
				width = 49;
			break;
			case 'filesize':
				value_ = '스토리지 용량<br>(MB)';
				width = 87;
			break;
			default:
				value_ = '';
				width = 40;
			break;
		}
		if(type == 'header')
		{
			return value_;
		}
		else if(type == 'width')
		{
			return width;
		}
		else
		{
			return '';
		}
	}

	 function generateConfig(){
        var arr,
            numProducts = products.length;

        Ext.iterate(years, function(continent, cities){
			check++;
			if(continent == '총계')
			{
				  continentGroupRow.push({
						header: '구분',
						align: 'center',
						rowspan: 2
					});
					fields.push({
						name: 'c_name'
					});
					columns.push({
						dataIndex:'c_name',
						width : 100,
						header: '',
						renderer : function(value, meta) {
							meta.attr = 'style="background-color:rgba(0, 0, 0, 0.2) !important;"';
							return value;
						}
					});
			}

            continentGroupRow.push({
                header: continent,
                align: 'center',
                colspan: 2
            });
            Ext.each(cities, function(city){
                cityGroupRow.push({
                    header: city,
                    colspan: numProducts,
                    align: 'center'
                });
                Ext.each(products, function(product){
					if(continent == '총계')
					{
						check = '';
					}
                    fields.push({
                        name: product + check
                    });
                    columns.push({
                        dataIndex:product + check,
                        header: map(product, 'header'),
						 align: 'right',
						width : map(product, 'width'),
						renderer : function(value, metaData, record, rowIndex, colIndex, store) {
							if(colIndex == 0 || colIndex == 1 || colIndex == 2)
							{
								metaData.attr = 'style="background-color:rgba(0, 0, 0, 0.2) !important;"';
							}
							else if(rowIndex == 0)
							{
								metaData.attr = 'style="background-color:rgba(0, 0, 0, 0.2) !important;"';
							}

							return value;
						}
                    });
                });
            });
        })
    }

    // Run method to generate columns, fields, row grouping
    generateConfig();

	var store1 = new Ext.data.JsonStore({
		url: '/pages/statistics_new/types_store.php',
		root: 'data',
		totalProperty: 'total',
		idProperty: 'user_id',
		fields : fields,
		baseParams : {
			type : 'first'
		},
		listeners: {
			beforeload: function(self, opts){
				opts.params = opts.params || {};
				Ext.apply(opts.params, {

				});
			},
			load: function(store, records, opts){
				myMask.hide();
				grid1.getSelectionModel().selectFirstRow();
			}
		}
	});

	var store2 = new Ext.data.JsonStore({
		url: '/pages/statistics_new/types_store.php',
		root: 'data',
		totalProperty: 'total',
		idProperty: 'user_id',
		fields : fields,
		baseParams : {
			type : 'second'
		},
		listeners: {
			beforeload: function(self, opts){
				opts.params = opts.params || {};

				Ext.apply(opts.params, {

				});
			},
			load: function(store, records, opts){
				myMask.hide();
				grid2.getSelectionModel().selectFirstRow();
			}
		}
	});

	var store3 = new Ext.data.JsonStore({
		url: '/pages/statistics_new/types_store.php',
		root: 'data',
		totalProperty: 'total',
		idProperty: 'user_id',
		fields : fields,
		baseParams : {
			type : 'third'
		},
		listeners: {
			beforeload: function(self, opts){
				opts.params = opts.params || {};
				Ext.apply(opts.params, {

				});
			},
			load: function(store, records, opts){
				myMask.hide();
				grid3.getSelectionModel().selectFirstRow();
			}
		}
	});

	var store4 = new Ext.data.JsonStore({
		url: '/pages/statistics_new/types_store.php',
		root: 'data',
		totalProperty: 'total',
		idProperty: 'user_id',
		fields : fields,
		baseParams : {
			type : 'fourth'
		},
		listeners: {
			beforeload: function(self, opts){
				opts.params = opts.params || {};
				Ext.apply(opts.params, {

				});
			},
			load: function(store, records, opts){
				myMask.hide();
				grid4.getSelectionModel().selectFirstRow();
			}
		}
	});

	var store5 = new Ext.data.JsonStore({
		url: '/pages/statistics_new/types_store.php',
		root: 'data',
		totalProperty: 'total',
		idProperty: 'user_id',
		fields : fields,
		baseParams : {
			type : 'fifth'
		},
		listeners: {
			beforeload: function(self, opts){
				opts.params = opts.params || {};
				Ext.apply(opts.params, {

				});
			},
			load: function(store, records, opts){
				myMask.hide();
				grid5.getSelectionModel().selectFirstRow();
			}
		}
	});

	var store6 = new Ext.data.JsonStore({
		url: '/pages/statistics_new/types_store.php',
		root: 'data',
		totalProperty: 'total',
		idProperty: 'user_id',
		fields : fields,
		baseParams : {
			type : 'sixth'
		},
		listeners: {
			beforeload: function(self, opts){
				opts.params = opts.params || {};
				Ext.apply(opts.params, {

				});
			},
			load: function(store, records, opts){
				myMask.hide();
				grid6.getSelectionModel().selectFirstRow();
			}
		}
	});


    var group = new Ext.ux.grid.ColumnHeaderGroup({
        rows: [continentGroupRow]
    });

	var height_grid = 450;
	var height_grid_m = 350;
	var height_grid_s = 250;

	var panel = {
		height : 20
	};

	var grid1 = new Ext.grid.GridPanel({
		id : 'type_info1',
        title : '장르분류',
		frame : true,
        height: height_grid,
		store : store1,
		cm: new Ext.grid.ColumnModel({
			defaults:{
				sortable: false
			},
			columns: columns
		}),
		view: new Ext.ux.grid.BufferView({
			scrollDelay: false
		}),
        viewConfig: {
            forceFit: true
        },
        plugins: group,
		listeners: {
			afterrender: function(self){
				self.getStore().load();
			}
		}
    });

	var grid2 = new Ext.grid.GridPanel({
		id : 'type_info2',
        title : '진로직업동영상분류',
		frame : true,
        height: height_grid,
		store : store2,
		cm: new Ext.grid.ColumnModel({
			defaults:{
				sortable: false
			},
			columns: columns
		}),
		view: new Ext.ux.grid.BufferView({
			scrollDelay: false
		}),
        viewConfig: {
            forceFit: true
        },
        plugins: group,
		listeners: {
			afterrender: function(self){
				self.getStore().load();
			}
		}
    });

    var grid3 = new Ext.grid.GridPanel({
		id : 'type_info3',
        title : '시청자분류',
		frame : true,
        height: height_grid_m,
		store : store3,
		cm: new Ext.grid.ColumnModel({
			defaults:{
				sortable: false
			},
			columns: columns
		}),
		view: new Ext.ux.grid.BufferView({
			scrollDelay: false
		}),
        viewConfig: {
            forceFit: true
        },
        plugins: group,
		listeners: {
			afterrender: function(self){
				self.getStore().load();
			}
		}
    });

	 var grid4 = new Ext.grid.GridPanel({
		id : 'type_info4',
        title : '진로교육영상분류',
		frame : true,
        height: height_grid_s,
		store : store4,
		//columns: columns,
		cm: new Ext.grid.ColumnModel({
			defaults:{
				sortable: false
			},
			columns: columns
		}),
		view: new Ext.ux.grid.BufferView({
			scrollDelay: false
		}),
        viewConfig: {
            forceFit: true
        },
        plugins: group,
		listeners: {
			afterrender: function(self){
				self.getStore().load();
			}
		}
    });

	 var grid5 = new Ext.grid.GridPanel({
		id : 'type_info5',
        title : '학과정보영상분류',
		frame : true,
        height: height_grid_m,
		store : store5,
		//columns: columns,
		cm: new Ext.grid.ColumnModel({
			defaults:{
				sortable: false
			},
			columns: columns
		}),
		view: new Ext.ux.grid.BufferView({
			scrollDelay: false
		}),
        viewConfig: {
            forceFit: true
        },
        plugins: group,
		listeners: {
			afterrender: function(self){
				self.getStore().load();
			}
		}
    });

	var grid6 = new Ext.grid.GridPanel({
		id : 'type_info6',
        title : '기타영상분류',
		frame : true,
        height: height_grid_s,
		store : store6,
		//columns: columns,
		cm: new Ext.grid.ColumnModel({
			defaults:{
				sortable: false
			},
			columns: columns
		}),
		view: new Ext.ux.grid.BufferView({
			scrollDelay: false
		}),
        viewConfig: {
            forceFit: true
        },
        plugins: group,
		listeners: {
			afterrender: function(self){
				self.getStore().load();
			}
		}
    });

	var w_blank = 20;
	var w_textfield = 150;
	var w_column = 210;
	var w_column_ = 280;

	var searchForm_ = new Ext.form.FormPanel({
		border: false,
		autoScroll: true,
		id:	'search_form',
		height: 80,
		width: '100%',
		frame: true,
		labelWidth: 45,
		defaults: {
			labelStyle: 'text-align:center;'
		},
		autoScroll: true,
		loadMask: true,
		items: [{
			layout:'hbox',
			items:[{
				 columnWidth:.3,
				 layout: 'form',
				 defaults: {
					labelStyle: 'text-align:center;',
					labelSeparator: ''
				 },
				 width: w_column,
				 items:[{
					xtype : 'textfield',
					fieldLabel : '제&nbsp;&nbsp;&nbsp;목',
					name : 'title',
					width: w_textfield
				 },{
					xtype : 'textfield',
					fieldLabel : '제작사',
					width: w_textfield
				 }]
			},{
				 columnWidth:.3,
				 layout: 'form',
				 defaults: {
					labelStyle: 'text-align:center;',
					labelSeparator: ''
				 },
				 width: w_column_,
				 items:[{
					xtype: 'textfield',
					fieldLabel : '프로그램',
					name : 'program',
					width: w_column
				},{
					xtype: 'compositefield',
					fieldLabel: '방송일자',
					items: [{
						xtype: 'datefield',
						width: 95,
						format: 'Y-m-d',
						altFormats: 'Y-m-d H:i:s|Y-m-d|Ymd|YmdHis',
						value: '',
						id : 's_date',
						name: 'start_date',
						maxValue: new Date(),
						parent_form_id: 'search_form_extract',
						next_field_name: 'e_date',
						enableKeyEvents: true,
						autoCreate: {tag: 'input', type: 'text', size: '10', autocomplete: 'off', maxlength: '10'},
						listeners :
						{
							keyup : function(self,e){
								self.nextDate(self, e);
							},
							select: function(self, e){
								Ext.getCmp('e_date').setMinValue(self.value);
							}
						}
					},{
						xtype: 'displayfield',
						width: 10,
						value: ' ~ '
					},{
						xtype: 'datefield',
						format: 'Y-m-d',
						width: 95,
						altFormats: 'Y-m-d H:i:s|Y-m-d|Ymd|YmdHis',
						value: '',
						id : 'e_date',
						name: 'end_date',
						maxValue: new Date(),
						autoCreate: {tag: 'input', type: 'text', size: '10', autocomplete: 'off', maxlength: '10'},
						listeners :
						{
							select: function(self, e){
								//Ext.getCmp('s_date').setMaxValue(self.value);
							}
						}
					}]
				}]
			},{
				 columnWidth:.3,
				 layout: 'form',
				 defaults: {
					labelStyle: 'text-align:center;',
					labelSeparator: ''
				 },
				 width: 240,
				 items:[{
					xtype : 'fieldset',
					columnWidth: 0.5,
					title: '진로직업동영상분류',
					layout:'column',
					collapsible: false,
					width: 220,
					autoHeight: true,
					items: [{
						id: 'category',
						xtype: 'radiogroup',
						width: 230,
						hideLabel: true,
						test : false,
						columns: [70, 70, 70],
						vertical: true,
						items: [
							{boxLabel: '중분류', name: 'category', inputValue: 'm', checked : true},
							{boxLabel: '소분류', name: 'category', inputValue: 's'},
							{boxLabel: '세분류', name: 'category', inputValue: 'n'}
						],
						listeners: {
							render: function (self) {
							},
							change: function (self, checked) {
							}
						}
					}]
				}]
			},{
				 columnWidth:.3,
				 layout: 'form',
				 defaults: {
					labelStyle: 'text-align:center;',
					labelSeparator: ''
				 },
				 width: w_column_,
				 items:[{
					xtype: 'panel',
					items : [{
						xtype : 'compositefield',
						fieldLabel: '버튼',
						items: [{
							xtype: 'button',
							icon: '/led-icons/magnifier.png',
							text: '조 회',
							width: 75,
							style: {
								marginRight: '12px'
							},
							handler: function(b, e){
								//var wait_m = Ext.Msg.wait('알림','처리중');
								searching_text('', '', 'search_form', 'type_info1');
								//wait_m.hide();
							}
						},{
								xtype: 'button',
								type: 'reset',
								width:75,
								text: '초기화',
								icon :'/led-icons/arrow_undo.png',
								style: {
									//marginLeft: '12px'
								},
								handler: function(b, e){
									Ext.getCmp('search_form').getForm().reset();
								}
						},{
							xtype: 'button',
							icon : '/led-icons/doc_excel_table.png',
							text: '엑셀출력',
							width: 75,
							style: {
								marginRight: '12px'
							},
							handler: function(b, e){
								var grid = Ext.getCmp('type_info1');
								var form = Ext.getCmp('search_form').getForm();
								var values = form.getValues();
								excelData( 'form', '/pages/statistics_new/types_store.php', grid.colModel, values, continentGroupRow, '');// '/pages/statistics_new/types_store.php'
							}
						}]
					}]
				}]
			}]
		}]
	});

	var searchForm = new Ext.FormPanel({
		frame: true,
		height: 70,
		id: 'search_forms',
		defaultType: 'textfield',
		labelSeparator: '',
		defaults : {
			anchor: '95%',
			labelSeparator : ''
		},
		border: false,
		items: [{
			xtype: 'compositefield',
			fieldLabel: '',
			hideLabel: true,
			items: [{
				xtype: 'displayfield',
				//width: 50,
				value: '<div >제&nbsp;&nbsp;&nbsp;목</div>'
			},{
				xtype: 'textfield',
				name : 'title',
				width: w_textfield
			},{
				xtype: 'displayfield',
				width: w_blank,
				value: '<div align="middle"></div>'
			},{
				xtype: 'displayfield',
				//width: 50,
				value: '<div>프로그램</div>'
			},{
				xtype: 'textfield',
				name : 'program',
				width: 210
			},{
				xtype: 'displayfield',
				width: 300,
				value: '<div align="middle"></div>'
			},{
				xtype: 'button',
				icon: '/led-icons/magnifier.png',
				text: '조 회',
				width: 75,
				style: {
					marginRight: '12px'
				},
				handler: function(b, e){
					//var wait_m = Ext.Msg.wait('알림','처리중');
					searching_text('', '', 'search_form', 'type_info1');
					//wait_m.hide();
				}
			},{
					xtype: 'button',
					type: 'reset',
					width:75,
					text: '초기화',
					icon :'/led-icons/arrow_undo.png',
					style: {
						//marginLeft: '12px'
					},
					handler: function(b, e){
						Ext.getCmp('search_form').getForm().reset();
					}
			},{
				xtype: 'button',
				icon : '/led-icons/doc_excel_table.png',
				text: '엑셀출력',
				width: 75,
				style: {
					marginRight: '12px'
				},
				handler: function(b, e){
					var grid = Ext.getCmp('type_info1');
					var form = Ext.getCmp('search_form').getForm();
					var values = form.getValues();
					excelData( 'form', '/pages/statistics_new/types_store.php', grid.colModel, values, continentGroupRow, '');// '/pages/statistics_new/types_store.php'
				}
			}]
		},{
			xtype: 'compositefield',
			fieldLabel: '',
			hideLabel: true,
			items: [{
				xtype: 'displayfield',
				//width: 20,
				value: '<div align="middle">제작사</div>'
			},{
				xtype: 'textfield',
				name : 'production',
				width : w_textfield,
				fieldLabel : '제작사'
			},{
				xtype: 'displayfield',
				width: w_blank,
				value: '<div align="middle"></div>'
			},{
				xtype: 'displayfield',
				//width: 20,
				value: '<div align="middle">방송일자</div>'
			},{
				xtype: 'datefield',
				width: 95,
				format: 'Y-m-d',
				altFormats: 'Y-m-d H:i:s|Y-m-d|Ymd|YmdHis',
				value: '',
				id : 's_date',
				name: 'start_date',
				maxValue: new Date(),
				parent_form_id: 'search_form_extract',
				next_field_name: 'e_date',
				enableKeyEvents: true,
				autoCreate: {tag: 'input', type: 'text', size: '10', autocomplete: 'off', maxlength: '10'},
				listeners :
				{
					keyup : function(self,e){
						self.nextDate(self, e);
					},
					select: function(self, e){
						Ext.getCmp('e_date').setMinValue(self.value);
					}
				}
			},{
				xtype: 'displayfield',
				width: 10,
				value: ' ~ '
			},{
				xtype: 'datefield',
				format: 'Y-m-d',
				width: 95,
				altFormats: 'Y-m-d H:i:s|Y-m-d|Ymd|YmdHis',
				value: '',
				id : 'e_date',
				name: 'end_date',
				maxValue: new Date(),
				autoCreate: {tag: 'input', type: 'text', size: '10', autocomplete: 'off', maxlength: '10'},
				listeners :
				{
					select: function(self, e){
						//Ext.getCmp('s_date').setMaxValue(self.value);
					}
				}
			},{
				xtype: 'displayfield',
				width: w_blank,
				value: '<div align="middle"></div>'
			},{
				xtype: 'displayfield',
				//width: 50,
				value: '<div  valign="bottom">진로직업동영상분류</div>'
			},{
				//id: 'category',
				xtype: 'radiogroup',
				width: 375,
				hideLabel: true,
				test : false,
				columns: [60, 60, 60],
				vertical: true,
				items: [
					{boxLabel: '중분류', name: 'category', inputValue: 'm', checked : true},
					{boxLabel: '소분류', name: 'category', inputValue: 's'},
					{boxLabel: '세분류', name: 'category', inputValue: 'n'}
				],
				listeners: {
					render: function (self) {
					},
					change: function (self, checked) {
					}
				}
			}]
		}],
		listeners: {
		},
		buttons: []
	});

	function searching_text(id_search_field, id_search_text, id_form, id_grid)
	{
		var form = Ext.getCmp('search_form').getForm();
		var values = form.getValues();

		Ext.getCmp('type_info1').getStore().reload({
			params: Ext.apply(values, {type: 'first'})
		});

		Ext.getCmp('type_info2').getStore().reload({
			params: Ext.apply(values, {type: 'second'})
		});
		Ext.getCmp('type_info3').getStore().reload({
			params: Ext.apply(values, {type: 'third'})
		});
		Ext.getCmp('type_info4').getStore().reload({
			params: Ext.apply(values, {type: 'fourth'})
		});
		Ext.getCmp('type_info5').getStore().reload({
			params: Ext.apply(values, {type: 'fifth'})
		});
		Ext.getCmp('type_info6').getStore().reload({
			params: Ext.apply(values, {type: 'sixth'})
		});
	}

	return {
		border: false,
		loadMask: true,
		//layout: 'vbox',
		activeItem: 0,
		autoScroll: true,
		items: [searchForm_,grid1,panel,grid2,panel,grid3,panel,grid4,panel,grid5,panel,grid6],
		listeners : {
			afterrender: function(self) {
			}
		}
	}
})()