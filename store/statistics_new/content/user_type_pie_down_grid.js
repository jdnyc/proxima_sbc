(function() {
	var user_type_pie_down_store = new Ext.data.JsonStore({
		root: 'data',
		url: '/store/statistics/content/user_type_pie_down_store.php',
		autoLoad: true,
		fields: [{
				name: 'content'
			},{
				name: 'down',
				type: 'int'
			}]
	});

	
	
	return {
		layout: 'vbox',
		frame: false,
		border: false,
		layoutConfig: {
			align: 'stretch',
			pack: 'start'
		},
		items: [{								
			xtype: 'piechart',
			flex: 2,
			store: user_type_pie_down_store,	
			dataField: 'down',
			categoryField: 'content',
			
			extraStyle:
			{
				legend:
				{
					display: 'bottom',
					padding: 5,
					font:
					{
						family: 'Tahoma',
						size: 13
					}
				}
			}
		},{
			xtype: 'grid',
			title: '사용자 정의 콘텐츠별 다운로드 횟수',
			store: user_type_pie_down_store,
			tbar:	[{
				xtype: 'toolbar',
				
				height: 35,
				items: [{
						xtype: 'tbtext',
						text: '데이터 다운로드 (.xls)  :  '
					},{
						xtype: 'button',
						icon: '/led-icons/disk.png',
						text: 'Download', 
						style: 'border-style:outset;',
						ref: '../button',
						id: 'btn',
						handler: function(btn, e){
							window.location="/store/statistics/content/user_type_pie_down_excel.php";
						}
					}]
			}],
			flex: 1,
			border: false,
			autoShow: true,
			ref: 'grid',
			id: 'grid1',
			columns: [{
				xtype: 'gridcolumn',
				header: '콘텐츠 종류',
				sortable: true,
				align: 'center'
			},{
				xtype: 'gridcolumn',
				header: '다운로드 횟수',
				sortable: true,	
				align: 'center'
			}],
			viewConfig: {
				forceFit: true
			}
		}]
	};
})()
