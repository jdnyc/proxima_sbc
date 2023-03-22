<?php

require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/lang.php');
fn_checkAuthPermission($_SESSION);
?>

(function(){
	var total_list = 0;
	var v_page_size = 30;

	var selModel = new Ext.grid.CheckboxSelectionModel({
	     singleSelect : false,
	     checkOnly : true
	});

	var list_store = new Ext.data.JsonStore({
		url:'/store/content_archive_target_info.php',
		root: 'data',
		totalProperty : 'total_list',
		fields: [
			{name: 'content_id'},
			{name: 'bs_content_id'},
			{name: 'category_full_path'},
			{name: 'title'},
			{name: 'media_type'},
			{name: 'file_size'},
			{name: 'path'},
			{name: 'ud_content_title'},
			{name: 'bs_content_title'},
			{name: 'category'},
            {name: 'created_date',type:'date',dateFormat:'YmdHis'},
		],
		listeners: {
			beforeload: function(self, opts){},
			load: function(self, opts){
				total_list = self.getTotalCount();
				var tooltext = "(  "+_text('MN02165')+" : <font color=blue><b>"+total_list +"</b></font> )";
				Ext.getCmp('toolbartext').setText(tooltext);
			}
		}
	});

	return {
		border: false,
		cls: 'proxima_customize',
		loadMask: true,
		frame:false,
		width:'100%',
		tbar: [ _text('MN02527')+' : ',{
			xtype: 'datefield',
			id: 'archive_start_date',
			width:100,
			editable: true,
			format: 'Y-m-d',
			listeners: {
				render: function(self){
					var d = new Date();
					self.setValue(d.add(Date.MONTH, -1).format('Y-m-d'));
				}
			}
		},
		'<?=_text('MN00183')?>'
		,{
			xtype: 'datefield',
			id: 'archive_end_date',
			width:100,
			editable: true,
			format: 'Y-m-d',
			listeners: {
				render: function(self){
					var d = new Date();
					self.setValue(d.format('Y-m-d'));
				}
			}
		},'-',{
			cls: 'proxima_button_customize',
			width: 30,
			text: '<span style="position:relative;top:1px;" title="'+_text('MN00059')+'"><i class="fa fa-search" style="font-size:13px;color:white;"></i></span>',
			handler: function(btn, e){
				Ext.getCmp('archive_target_inform_id').getStore().load({
					params: {
							start: 0,
							limit: v_page_size,
							start_date: Ext.getCmp('archive_start_date').getValue().format('Ymd000000'),
							end_date: Ext.getCmp('archive_end_date').getValue().format('Ymd240000')
					}
				});
			}
		}
		<?php if($arr_sys_code['interwork_archive_confirm']['use_yn'] == 'Y'){?>
		,{
			cls: 'proxima_button_customize',
			width: 30,
			text: '<span style="position:relative;top:1px;" title="'+_text('MN02528')+'"><i class="fa fa-check" style="font-size:13px;color:white;"></i></span>',
			handler : function(btn, e){
				var sel = Ext.getCmp('archive_target_inform_id').getSelectionModel().getSelections();
				if(sel.length > 0){
					var win = new Ext.Window({
							layout:'fit',
							title: _text('MN02382'),
							modal: true,
							width:500,
							height:170,
							buttonAlign: 'center',
							items:[{
								id:'reason_approve_inform',
								xtype:'form',
								border: false,
								frame: true,
								padding: 5,
								labelWidth: 70,
								cls: 'change_background_panel',
								defaults: {
									anchor: '95%'
								},
								items: [{
									id:'archive_reason',
									xtype: 'textarea',
									height: 50,
									fieldLabel:_text('MN02382'),
									allowBlank: false,
									blankText: '<?=_text('MSG02183')?>',
									msgTarget: 'under'
								}]
							}],
							buttons:[{
								text : '<span style="position:relative;top:1px;"><i class="fa fa-paper-plane-o" style="font-size:13px;"></i></span>&nbsp;'+_text('MN02197'),
								scale: 'medium',
								handler: function(btn,e){
									var isValid = Ext.getCmp('archive_reason').isValid();
									if (!isValid) {
										Ext.Msg.show({
											icon: Ext.Msg.INFO,
											title: _text('MN00024'),
											msg: '<?=_text('MSG02183')?>',
											buttons: Ext.Msg.OK
										});
										return;
									}

									var archive_reason = Ext.getCmp('archive_reason').getValue();

									var sel = Ext.getCmp('archive_target_inform_id').getSelectionModel().getSelections();
									var sel_content_id = [];									
									if(sel.length > 0){
										Ext.each(sel, function(r){
											sel_content_id.push(r.get('content_id'));
										});

										Ext.Ajax.request({
											<?php if( $arr_sys_code['interwork_archive_confirm']['use_yn'] == 'Y'){ ?>
												url: '/store/archive/insert_archive_request.php',
												params: {
													case_management: 'request',
											<?php }else{?>
												url: '/store/archive/insert_archive_request_not_confirm.php',
												params: {
											<?php }?>
												job_type : 'archive',
												contents : Ext.encode(sel_content_id),
												comment: archive_reason
											},
											callback : function(opt, success, res){
												if(success){
													 var msg = Ext.decode(res.responseText);
													 if(msg.success){
													 	Ext.getCmp('archive_target_inform_id').getStore().reload();
													 }else {
														Ext.Msg.alert( _text('MN01039'), msg.msg);
													 }
												}else{
													Ext.Msg.alert( _text('MN01098'), res.statusText);
												}
											}
										});

									}else{
										Ext.Msg.alert( _text('MN01039'), _text('MSG01005'));
									}
									win.destroy();
								}
							},{
								text : '<span style="position:relative;top:1px;"><i class="fa fa-close style="font-size:13px;"></i></span>&nbsp;'+_text('MN00004'),
								scale: 'medium',
								handler: function(btn,e){
									win.destroy();
								}
							}]
						});
						win.show();
				}else{
					Ext.Msg.alert( _text('MN01039'), _text('MSG01005'));
				}
			}
		}
		<?php } ?>
		,{
			cls: 'proxima_button_customize',
			width: 30,
			text: '<span style="position:relative;top:1px;" title="'+_text('MN00390')+'"><i class="fa fa-refresh" style="font-size:13px;color:white;"></i></span>',
			handler : function(btn, e){
				var sm = Ext.getCmp('archive_target_inform_id').getSelectionModel();
				sm.clearSelections();
				Ext.getCmp('archive_target_inform_id').getStore().reload();
			}
		}],
		xtype: 'grid',
		layout: 'fit',
		id: 'archive_target_inform_id',
		title: '<span class="user_span"><span class="icon_title"><i class=""></i></span><span class="main_title_header">'+_text('MN02526')+'</span></span>',
		cls: 'grid_title_customize proxima_customize',
		stripeRows: true,
		border: false,
		loadMask: true,
		columnWidth: 1,
		store: list_store,
		disableSelection: true,
		listeners: {
			viewready: function(self){
				self.store.load({
					params: {
						start: 0,
						limit: v_page_size,
						start_date: Ext.getCmp('archive_start_date').getValue().format('Ymd000000'),
						end_date: Ext.getCmp('archive_end_date').getValue().format('Ymd240000')
					}

				})
			},
			rowdblclick: {
				fn: function(self, rowIndex, e){
					var index =0;
					var sm = self.getSelectionModel();
					var sel = Ext.getCmp('archive_target_inform_id').getSelectionModel().getSelections();
					if(sel.length>0)
					{
						for(var i=0;i<sel.length;i++)
						{
							index = Ext.getCmp('archive_target_inform_id').getStore().indexOf(sel[i]);
							sm.selectRow(index,true);
						}
					}
					if(sm.isSelected(rowIndex))
					{
						sm.deselectRow(rowIndex);
					}
					else  sm.selectRow(rowIndex,true);
				}
			}
		},

		sm : selModel,

		cm: new Ext.grid.ColumnModel({
			defaults:{
				sortable: true
			},

			columns: [
				new Ext.grid.RowNumberer(),
				selModel,
				{header: 'content_id', dataIndex: 'content_id',hidden:true},
				{header: _text('MN00300'),dataIndex:'media_type',align:'center',sortable:'true',width:80, hidden : true},
				{header: _text('MN00279'),dataIndex:'bs_content_title',align:'center',sortable:'true',width:100},
				{header: _text('MN00197'), dataIndex: 'ud_content_title', align:'center',sortable:'true',width:120},
				{header: _text('MN00387'), dataIndex: 'category', align:'left',sortable:'true',width:120},
				{header: _text('MN00249'), dataIndex:'title',align:'left',sortable:'true',width:200},
				{header: _text('MN00301'), dataIndex:'file_size',align:'right',sortable:'true',width:80},
				{header: _text('MN00172'), dataIndex: 'path',align:'left',sortable:'true',width:250},
				{header: _text('MN02137'), dataIndex:'created_date',align:'center',sortable:'true',width:170 ,renderer: Ext.util.Format.dateRenderer('Y-m-d H:i:s'),sortable:'true',width:150, hidden:true},
			]
		}),

		view: new Ext.ux.grid.BufferView({
			rowHeight: 20,
			forceFit: true,
			scrollDelay: false,
			emptyText : _text('MSG00148')//'결과 값이 없습니다.'
		}),

		bbar: new Ext.PagingToolbar({
			store: list_store,
			pageSize: v_page_size,
			items:[{
				id : 'toolbartext',
				xtype:'tbtext',
				pageX:'100',
				pageY:'100',
				text : _text('MN02165')+" : "+total_list
			}]
		})

	}
})()