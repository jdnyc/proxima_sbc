<?php

/////////////////////////////
// DIVA의 실제 Tape번호 조회용
/////////////////////////////

session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');

$combo = $db->queryAll("select distinct media from archive_info");
$combo_array = array("['전체','all']");
foreach($combo as $c)
{
	$combo_array[] = "['".strtoupper($c['media'])."','".$c['media']."']";
}
$combo_array = implode($combo_array, ',');

$diva_info_msg = getDivaStorageInfo();
?>		  

(function(){

	var total_list = 0;
	var delete_inform_size = 100;
	var tapeid_store = new Ext.data.JsonStore({
		url:'/pages/menu/archive_management/get_tapeid.php',
		root: 'data',
		totalProperty : 'total_list',		
		fields: [
			{name: 'appr_time',type:'date',dateFormat:'YmdHis'},
			{name: 'archive_id'},
			{name: 'category_id'},
			{name: 'category_title'},
			{name: 'content_id'},
			{name: 'filesize'},
			{name: 'media'},
			{name: 'tape'},
			{name: 'title'},
			{name: 'ud_content_id'},
			{name: 'ud_content_title'}
		],
		 
		listeners: {
			beforeload: function(self, opts){
				opts.params = opts.params || {};
				var genre = Ext.getCmp('tapeid_manage_genre_category').treePanel.getSelectionModel().getSelectedNode();
				if(Ext.isEmpty(genre)) {
					var genre_id = 0;
				} else {
					var genre_id = genre.attributes.id;
				}
				Ext.apply(opts.params, {
					arc_start_date: Ext.getCmp('arc_start_date').getValue().format('Ymd000000'),
					arc_end_date: Ext.getCmp('arc_end_date').getValue().format('Ymd240000'),
					genre_category: genre_id,
					tape_media: Ext.getCmp('tapeid_manage_tape_media').getValue(),
					search_value: Ext.getCmp('tapeid_manage_search_value').getValue()
				});
			},
			load: function(self, opts){
				total_list = self.getTotalCount();	
				var tooltext = "( 검색된 미디어 수 : <font color=blue><b>"+total_list +"</b></font> )";
				Ext.getCmp('tapeid_manage_toolbartext').setText(tooltext);
			}
		}
	});
	
	function upperCase(value){
		if(Ext.isEmpty(value)) value = '-';
		else value = value.toUpperCase();
		return value;
	}
	
	function convertFilesize (num) {
		if (num > 0 ) {
			if (num < 1024)				{ return (num).toFixed(2)+"Bytes" }
			if (num < 1048576)			{ return (num/1024).toFixed(2)+"KB" }
			if (num < 1073741824)		{ return (num/1024/1024).toFixed(2)+"MB" }
			if (num < 1099511600000)	{ return (num/1024/1024/1024).toFixed(2)+"GB" }

			return (num/1024/1024/1024/1024).toFixed(2)+"TB";
		}

		return num;
	}

	var tbar1 = new Ext.Toolbar({
		dock: 'top',
		items: [' Tape Media : ',{
			xtype:'combo',
			id:'tapeid_manage_tape_media',
			hiddenName: 'tapeid_manage_tape_media',
			mode:'local',
			width:80,
			triggerAction:'all',
			editable:false,
			displayField:'d',
			valueField:'v',
			value : 'all',
			store: new Ext.data.ArrayStore({
				fields:[
					'd','v'	
				],
				data:[					
					<?=$combo_array?>
				]
			}),
			listeners:{
				select:{
					fn:function(self,record,index){
						tape_manage_grid.getStore().reload();
					}
				}	
			}
		},'-','승인일자',{
			xtype: 'datefield',
			id: 'arc_start_date',
			editable: false,
			width: 95,
			format: 'Y-m-d',
			altFormats: 'Ymd|ymd|Y-m-d|y-m-d|Y/m/d|Y/m/d',
			listeners: {
				render: function(self){
					var d = new Date();

					self.setMaxValue(d.format('Y-m-d'));
					self.setValue(d.add(Date.MONTH, -1).format('Y-m-d'));
				}
			}
		},'~',{
			xtype: 'datefield',
			id: 'arc_end_date',
			editable: false,
			width: 95,
			format: 'Y-m-d',
			altFormats: 'Ymd|ymd|Y-m-d|y-m-d|Y/m/d|Y/m/d',
			listeners: {
				render: function(self){
					var d = new Date();

					self.setMaxValue(d.format('Y-m-d'));
					self.setValue(d.format('Y-m-d'));
				}
			}
		},
		/*'-','Tape완료일',
		{
			xtype: 'datefield',
			id: 'tape_start_date',
			//disabled : true,
			editable: true,
			width: 95,
			format: 'Y-m-d',
			altFormats: 'Ymd|ymd|Y-m-d|y-m-d|Y/m/d|Y/m/d',
			listeners: {
				render: function(self){
					var d = new Date();

					self.setMaxValue(d.format('Y-m-d'));
					self.setValue(d.add(Date.MONTH, -1).format('Y-m-d'));
				}
			}
		},'~' 
		,{
			xtype: 'datefield',
			id: 'tape_end_date',
			editable: true,
			width: 95,
			format: 'Y-m-d',
			altFormats: 'Ymd|ymd|Y-m-d|y-m-d|Y/m/d|Y/m/d',
			//disabled : true,
			listeners: {
				render: function(self){
					var d = new Date();

					self.setMaxValue(d.format('Y-m-d'));
					self.setValue(d.format('Y-m-d'));
				}
			}
		},*/
		'-','장르',{
			xtype: 'treecomboarchive',
			id: 'tapeid_manage_genre_category',
			hiddenName: 'tapeid_manage_genre_category',
			fieldLabel: '장르',
			width: 200,
			autoScroll: true,
			pathSeparator: ' > ',
			rootVisible: true,
			name: 'c_category_id',
			value: 0,
			listeners: {
				render: function(self) {
					var path = '/0';
					if(!Ext.isEmpty(path)){
						path = path.split('/');
						var catId = path[path.length-1];
						if(path.length <= 1) {
							self.setValue('');
							self.setRawValue('');
						} else {
							self.setValue(0);
							self.setRawValue('CPBCDAS');
						}
					}
				}
			},
			loader: new Ext.tree.TreeLoader({
				url: '/store/get_categories.php',
				baseParams: {
					action: 'get-folders',
					path: '0'
				}
			}),
			root: new Ext.tree.AsyncTreeNode({
				id: 0,
				text: 'CPBCDAS',
				expanded: true
			})
		},'-','제목',{
			xtype: 'textfield',
			id: 'tapeid_manage_search_value',
			listeners: {
				specialKey: function(self, e){
					if (e.getKey() == e.ENTER) {
						e.stopEvent();
						tape_manage_grid.getStore().reload({params:{start: 0}});
					}
				}
			}
		},'-',{
			icon: '/led-icons/find.png',
			//>>text: '조회',
			text: '<?=_text('MN00047')?>',
			handler: function(btn, e){
				tape_manage_grid.getStore().reload({params:{start: 0}});
			}
		},'->',{
			icon: '/led-icons/page_white_excel.png',
			text: 'Excel',
			handler: function(btn, e){
				var genre = Ext.getCmp('tapeid_manage_genre_category').treePanel.getSelectionModel().getSelectedNode();
				if(Ext.isEmpty(genre)) {
					var genre_id = 0;
				} else {
					var genre_id = genre.attributes.id;
				}
				
				window.location = '/pages/menu/archive_management/get_tapeid.php?is_excel=Y'+
					'&arc_start_date='+Ext.getCmp('arc_start_date').getValue().format('Ymd000000')+
					'&arc_end_date='+Ext.getCmp('arc_end_date').getValue().format('Ymd240000')+
					'&genre_category='+genre_id+
					'&tape_media='+Ext.getCmp('tapeid_manage_tape_media').getValue()+
					'&search_value='+Ext.getCmp('tapeid_manage_search_value').getValue();

				Ext.Msg.alert('알림','엑셀파일 다운로드가 곧 시작됩니다.');
			}
		}]
	});

	var selModel = new Ext.grid.RowSelectionModel({
		singleSelect : true
	});

	var tape_manage_grid = new Ext.grid.EditorGridPanel({
		border: false,
		region: 'center',
		frame:true,
		width:800,
		tbar: new Ext.Container({
			height: 29,
			layout: 'anchor',
			xtype: 'container',
			defaults: {
				anchor: '100%',
				height: 27
			},
			items: [
				tbar1
			]
		}),
		//xtype: 'editorgrid',
		clicksToEdit: 1,
		loadMask: true,
		columnWidth: 1,
		store: tapeid_store,
		disableSelection: true,

		listeners: {
			viewready: function(self){
				self.store.load();				
			},
			rowdblclick: function(self, rowIndex, e){
				var sm = self.getSelectionModel().getSelected();
				var content_id = sm.get('content_id');
				var mtrl_id = sm.get('mtrl_id');
				var that = self;

				if ( !Ext.Ajax.isLoading(self.isOpen) ) {
				
					//>>self.load = new Ext.LoadMask(Ext.getBody(), {msg: '상세 정보를 불러오는 중입니다...'});
					self.load = new Ext.LoadMask(Ext.getBody(), {msg: _text('MSG00143')});
					self.load.show();
					self.isOpen = Ext.Ajax.request({
								url: '/javascript/ext.ux/Ariel.DetailWindow.php',
								params: {
									content_id: content_id
								},
								callback: function(self, success, response){
									if (success) {
										that.load.hide();
										try {
											var r = Ext.decode(response.responseText);

											if ( r !== undefined && !r.success) {
												Ext.Msg.show({
													title: '경고'
													,msg: r.msg
													,icon: Ext.Msg.WARNING
													,buttons: Ext.Msg.OK
												});
											}
										} catch (e) {
											//alert(response.responseText)
											//Ext.Msg.alert(e['name'], e['message'] );
										}
									} else {
										//>>Ext.Msg.alert('서버 오류', response.statusText+'('+response.status+')');
										Ext.Msg.alert(_text('MN00022'), response.statusText+'('+response.status+')');
									}
								}
							});

							Ext.Ajax.request({
								url: '/interface/archive_interface/get_keyframe_one.php',
								params: {
									content_id: content_id
								},
								callback: function(self, success, response){
									
								}
							});
				} //endif
			}
		},		
		sm : selModel,
		cm: new Ext.grid.ColumnModel({
			defaults:{
				sortable: true
			},

			columns: [				
				new Ext.grid.RowNumberer(),
				{header: '콘텐츠ID', dataIndex:'content_id',align:'center',sortable:'true',hidden: true},
				{header: 'Tape Media', dataIndex:'media',align:'center',sortable:'true',width:100, renderer: upperCase},
				{header: 'Tape ID', dataIndex:'tape',align:'center',sortable:'true',width:100,
					editor: new Ext.form.TextField({
						allowBlank: true,
						readOnly: true
				})},
				{header: '크기', dataIndex:'filesize',align:'center',sortable:'true',width:100, renderer: convertFilesize},
				{header: '콘텐츠유형', dataIndex:'ud_content_title',align:'center',sortable:'true',width:100},
				{header: '장르', dataIndex:'category_title',align:'center',sortable:'true',width:100},
				{header: '<center>제목</center>', dataIndex:'title',align:'left',sortable:'true',width:350},
				{header: '승인일자', dataIndex:'appr_time', align:'center',sortable:'true',width:130,
					renderer: Ext.util.Format.dateRenderer('Y-m-d H:i:s')}				
			]
		}),

		view: new Ext.ux.grid.BufferView({
			rowHeight: 20,
			scrollDelay: false,
			emptyText: '결과 값이 없습니다.'
		}),
		viewConfig : {
			enableTextSelection : true
		},
	
		bbar: new Ext.PagingToolbar({
			store: tapeid_store,
			pageSize: delete_inform_size,
			items:[{
				id: 'tapeid_manage_toolbartext',
				xtype:'tbtext',
				pageX:'100',
				pageY:'100',				
				text : "리스트 수 : "+total_list
			},'->',{
				xtype: 'displayfield',				
				value: '<?=$diva_info_msg?>'
			}	]
		})

	});
	return tape_manage_grid;
})()
