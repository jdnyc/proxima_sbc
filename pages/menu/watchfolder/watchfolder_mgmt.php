<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
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
		url: '/store/watchfolder/get_watch_list.php',
		root: 'data',
		fields: [
			'content_id','title','category_full_path_name','ud_content_id','ud_content_title','created_date','reg_user_id','reg_user_nm'
		],
		listeners: {
			beforeload: function(self, opts){
				opts.params = opts.params || {};

				Ext.apply(opts.params, {
					keyword: Ext.getCmp('watch_search_keyword').getValue(),
					start_date: Ext.getCmp('watch_start_date').getValue().format('Ymd000000'),
					end_date: Ext.getCmp('watch_end_date').getValue().format('Ymd240000')
				});
			}
		}
	});

	return {
		layout:	'fit',
		border: false,
		title: '<span class="user_span"><span class="icon_title"><i class=""></i></span><span class="main_title_header">'+'와치폴더 메타관리'+'</span></span>',
		cls: 'grid_title_customize proxima_customize proxima_customize_progress',
		items: [{
			xtype: 'grid',
			id: 'watchfolder_mgmt_grid',
			border: false,
			cls: 'proxima_customize proxima_customize_progress',
			stripeRows: true,
			frame: false,
			defaults: {
				border: false,
				margins: '10 10 10 10'
			},
			frame: false,
			cls: 'proxima_customize',
			loadMask: true,
			store: store,
			viewConfig: {
				forceFit: true,
				emptyText: _text('MSG00148')//결과 값이 없습니다
			},
			listeners: {
				render: function(self) {
					store.load();
				},
				rowdblclick: function (self, row_index, e) {
					var rowRecord = self.getSelectionModel().getSelected();
					var content_id = rowRecord.get('content_id');
					
					//>>self.load = new Ext.LoadMask(Ext.getBody(), {msg: '상세 정보를 불러오는 중입니다...'});
					self.load = new Ext.LoadMask(Ext.getBody(), {msg: _text('MSG00143')});
					self.load.show();
					var that = self;

					if ( !Ext.Ajax.isLoading(self.isOpen) )
					{
						self.isOpen = Ext.Ajax.request({
							url: '/javascript/ext.ux/Ariel.DetailWindow.php',
							params: {
								content_id: content_id,
								win_type: 'zodiac',
								is_watch_meta: 'Y'
							},
							callback: function(self, success, response){
								if (success)
								{
									that.load.hide();
									try
									{
										var r = Ext.decode(response.responseText);

										if ( r !== undefined && !r.success)
										{
											Ext.Msg.show({
												title: '경고'
												,msg: r.msg
												,icon: Ext.Msg.WARNING
												,buttons: Ext.Msg.OK
											});
										}
									}
									catch (e)
									{
										//alert(response.responseText)
										//Ext.Msg.alert(e['name'], e['message'] );
									}
								}
								else
								{
									//>>Ext.Msg.alert('서버 오류', response.statusText+'('+response.status+')');
									Ext.Msg.alert(_text('MN00022'), response.statusText+'('+response.status+')');
								}
							}
						});
					} else {
                        that.load.hide();
                    }
				}
			},
			colModel: new Ext.grid.ColumnModel({
				defaults: {
					//menuDisabled: true,
					forceFit: true
				},
				columns: [//'content_id','title','category_full_path_name','ud_content_id','ud_content_title','created_date','reg_user_id','reg_user_nm'
					{header: '<center>'+_text('MN00287')+'</center>',dataIndex: 'content_id', width: 50, hidden: true},
					{header: '<center>'+_text('MN02046')+'</center>', dataIndex: 'ud_content_title', width: 70},
					{header: '<center>'+_text('MN02223')+'</center>', dataIndex: 'category_full_path_name', width: 150},
					{header: '<center>'+_text('MN00249')+'</center>', dataIndex: 'title', width: 300},
					{header: '<center>'+_text('MN00120')+'</center>', dataIndex: 'reg_user_nm', width: 70},//등록자
					{header: '<center>'+_text('MN00102')+'</center>', dataIndex: 'created_date', width: 140}//등록일시
				]
			}),
			sm: new Ext.grid.RowSelectionModel({
				singleSelect: true
			}),
			tbar: [_text('MN00102'),{
				xtype: 'datefield',
				id : 'watch_start_date',
				format: 'Y-m-d',
				width: 90,
				value: new Date().add(Date.DAY, -7).format('Y-m-d')
			}, _text('MN00183'),//' 부터 ' 
			{
				xtype: 'datefield',
				id : 'watch_end_date',
				format: 'Y-m-d',
				width: 90,
				value: new Date()
			},{
				width: 10
			},{
				id: 'watch_search_keyword',
				xtype: 'textfield',
				width: 300,
				emptyText: _text('MN00249'),
				listeners: {
					specialkey: function (field, e) {
						if (e.getKey() == e.ENTER) {
							store.reload();
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
				handler: function(b, e){
					store.reload();
				}
			}],
			bbar: {
				xtype: 'paging',
				pageSize: 20,
				displayInfo: true,
				store: store
			}
		}]
	}
})()