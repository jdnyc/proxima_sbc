<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">

	<link rel="stylesheet" type="text/css" href="/ext/resources/css/ext-all.css" />
	<script type="text/javascript" src="/ext/adapter/ext/ext-base.js"></script>
	<script type="text/javascript" src="/ext/ext-all.js"></script>

	<script type="text/javascript" src="js/Ext.ux.TreeCombo2.js"></script>
	<script type="text/javascript" src="js/Ext.ux.ContentListWin.js"></script>
	<script type="text/javascript" src="js/Ext.ux.MetadataLoader.js"></script>

	<script type="text/javascript">
	function renderDeleted(v) {

		if( v == 'Y' )
		{
			return 'Y';
		}		
		else
		{			
			return '<span style="color: red">N</span>';
		}		
	}	

	function getChecked(id)
	{
		var status_checkbox,
			tmp,
			status_group = new Array();
			
			Ext.each(id.items.items, function(checkbox){
				if(checkbox.checked)
				{
					status_group.push(checkbox.status);
				}
				//console.log(checkbox);
			});

			return Ext.encode(status_group);
	}

	Ext.onReady(function(){
		var myPageSize = 30

		var store = new Ext.data.JsonStore({
			url: 'php/getData.php',
			root: 'data',
			totalProperty: 'total',
			fields: [
				'content_id',
				'ud_content_title',
				'title',
				'content_deleted',
				{name: 'created_date', type: 'date', dateFormat: 'YmdHis'},
				{name: 'archive', mapping: 'media_info.archive' },
				{name: 'original' , mapping: 'media_info.original' },
				{name: 'proxy' , mapping: 'media_info.proxy'},
				{name: 'thumb' , mapping: 'media_info.thumb'}
			],
			listeners: {
				exception: function (self, type, action, options, response, arg) {
					if (type == 'response') {
						Ext.Msg.alert('Info', response.responseText);
					}
				},
				load : function (self,record,option)
				{

				},
				beforeload : function (self,opts)
				{				
					var id = Ext.getCmp('checkbox_group_composit_field');
					
					var combo_val = Ext.getCmp('top_form').get(2).getValue();


					if(combo_val == 'created_date')
					{
						self.baseParams = {
							content_type : Ext.getCmp('content_type').getValue(),
							start_date: Ext.getCmp('start_date').getValue().format('Ymd000000'),
							end_date: Ext.getCmp('end_date').getValue().format('Ymd240000'),
							status : getChecked(id),
							limit : myPageSize,
							start :0
						}
					}
					else if(combo_val == 'title')
					{
						self.baseParams = {
							content_type : Ext.getCmp('content_type').getValue(),
							title: Ext.getCmp('title_search_field').getValue(),							
							status : getChecked(id),
							limit : myPageSize,
							start :0
						}
					}					
				}
			}
		});	

		var content_list_grid =  new Ext.grid.GridPanel({			
				flex: 1,
				padding: '15px',				
				xtype: 'grid',
				id: 'content_list_grid',
				loadMask: true,
				store: store,
				viewConfig: {
					emptyText: '데이터가 없습니다.' 
				},

				columns: [
					new Ext.grid.RowNumberer({width:23}),
					{header: 'content_id', dataIndex: 'content_id', hidden: true},
					{header: '제목', dataIndex: 'title', width: 350},
					{header: '콘텐츠 구분', dataIndex: 'ud_content_title'},
					{header: '콘텐츠 삭제', dataIndex: 'content_deleted', align: 'center', renderer: renderDeleted},
					{header: '아카이브', dataIndex: 'archive', align: 'center', renderer: renderDeleted},
					{header: '온라인 스토리지', dataIndex: 'original', align: 'center', renderer: renderDeleted},
					{header: '프록시', dataIndex: 'proxy', align: 'center', renderer: renderDeleted},
					{header: '썸네일', dataIndex: 'thumb', align: 'center', renderer: renderDeleted},
					{header: '등록일', dataIndex: 'created_date', width: 130, renderer: Ext.util.Format.dateRenderer('Y-m-d H:i:s'), align: 'center'}
				],

				sm: new Ext.grid.RowSelectionModel({
					singleSelect: true
				}),

				listeners: {
					viewready : function(grid)
					{
						grid.store.baseParams ={
								start: 0,
								limit: myPageSize
						};
						grid.store.load();
					}
				},
				bbar: new Ext.PagingToolbar({
					store: store,
					displayInfo: true,
					pageSize: myPageSize
				})
			

		});

		var select_form = new Ext.form.FormPanel({	
				id : 'top_form',
				height: 70,
				layout: 'absolute',
				defaultType: 'textfield',
				defaults: {
					//hidden: true
				},

				items: [{
					x: 5,
					y: 5,
					width: 100,
					xtype: 'label',
					text: '콘텐츠 구분'
				},{
					x: 105,
					y: 5,
					id : 'content_type',
					xtype: 'combo',
					mode: 'remote',
					triggerAction: 'all',
					editable: false,
					displayField: 'ud_content_title',
					store: new Ext.data.JsonStore({
						url: 'php/ud_content_list.php',
						root: 'data',						
						fields: [
							'ud_content_id', 'ud_content_title'
						]
					}),
					displayField : 'ud_content_title',
					hiddenName : 'ud_content_id',
					value: '전체',
					valueField : 'ud_content_id'
				},{
					x: 300,
					y: 5,
					xtype: 'combo',
					mode: 'local',						
					triggerAction: 'all',
					typeAhead: true,
					editable: false,
					displayField: 'name',
					valueField: 'value',
					value: 'created_date',
					store: new Ext.data.ArrayStore({													
						fields: [
							'value', 'name'
						],
						data: [['created_date', '등록일'],['title', '제목']]
					}),
					listeners: {
						select: function(self){
							

							if( self.getValue() == 'title')
							{
								self.ownerCt.get(3).setVisible(false);
								self.ownerCt.get(4).setVisible(true);
							}
							else if( self.getValue() == 'created_date' )
							{
								self.ownerCt.get(3).setVisible(true);
								self.ownerCt.get(4).setVisible(false);
							}
						}
					}
					
				},{
					x: 490,
					y: 5,
					xtype: 'compositefield',
					items: [{
						xtype: 'datefield',
						editable: false,
						format: 'Y-m-d',
						id : 'start_date',
						listeners: {
							render: function(self){
								var d = new Date();

								self.setMaxValue(d.format('Y-m-d'));
								self.setValue(d.add(Date.MONTH, -12).format('Y-m-d'));
							}
						}
					},{
						xtype: 'label',
						text: '~'
					},{
						xtype: 'datefield',
						editable: false,
						format: 'Y-m-d',
						id: 'end_date',
						listeners: {
							render: function(self){
								var d = new Date();

								self.setMaxValue(d.format('Y-m-d'));
								self.setValue(d.format('Y-m-d'));
							}
						}
					}]
				},{
					x: 490,
					y: 5,
					id: 'title_search_field',
					width:	'215',
					hidden: true,					
					xtype: 'textfield',
					listeners: {
						
					}
					
				},{	
					x: 715,
					y: 5,
					xtype: 'button',
					text: '검색',
					icon: '/led-icons/find.png',
					handler: function(){
						var id = Ext.getCmp('checkbox_group_composit_field');
					
						var combo_val = Ext.getCmp('top_form').get(2).getValue();


						if(combo_val == 'created_date')
						{
							var baseParams = {
								content_type : Ext.getCmp('content_type').getValue(),
								start_date: Ext.getCmp('start_date').getValue().format('Ymd000000'),
								end_date: Ext.getCmp('end_date').getValue().format('Ymd240000'),
								status : getChecked(id),
								limit : myPageSize,
								start :0
							}
						}
						else if(combo_val == 'title')
						{
							var baseParams = {
								content_type : Ext.getCmp('content_type').getValue(),
								title: Ext.getCmp('title_search_field').getValue(),							
								status : getChecked(id),
								limit : myPageSize,
								start :0
							}
						}
						Ext.getCmp('content_list_grid').getStore().reload({ params: baseParams });
					}
				},{
					x: 5,
					y: 30,
					xtype: 'compositefield',
					id : 'checkbox_group_composit_field',
					items: [{
						xtype: 'checkbox',
						boxLabel: '콘텐츠 삭제',						
						status:'is_content_delete_info'
					},{
						xtype: 'checkbox',
						boxLabel: '아카이브 X',
						status:'is_archive_info'
					},{
						xtype: 'checkbox',
						boxLabel: '온라인 스토리지 X',
						status:'is_online_storage_info'
					},{
						xtype: 'checkbox',
						boxLabel: '프록시 X',
						status:'is_proxy_info'
					},{
						xtype: 'checkbox',
						boxLabel: '썸네일 X',
						status: 'is_thumbnail_info'
					}]
				}]
			});

		new Ext.Viewport({
//			renderTo: Ext.getBody(),
			//region: 'center',
			baseCls: 'x-plain',
			layout:'vbox',
			layoutConfig: 
			{
				align : 'stretch',
				pack  : 'start'
			},

			items: [select_form,content_list_grid]
		});

	});
	</script>
</head>

<body>


</body>
</html>