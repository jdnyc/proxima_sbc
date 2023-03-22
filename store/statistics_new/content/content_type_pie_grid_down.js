(function() {
	var downstore = new Ext.data.JsonStore({
		root: 'data',
		url: '/store/statistics/content/content_type_pie_down_store.php',
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
		layoutConfig : {
			align: 'stretch',
			pack: 'start'
		},

		items : [{								
				xtype: 'piechart',
				flex: 2,
				store: downstore,	
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
				title: '콘텐츠 타입별 다운로드 횟수',
				store: downstore,
				
				tbar:	[{
					xtype: 'toolbar',
					height: 35,
					items: [
						
						{
							xtype: 'tbtext',
							text: '데이터 다운로드 (.xls)  :  '
						},
						{
							xtype: 'button',
							icon: '/led-icons/disk.png',
							style: 'border-style:outset;',
							text: 'Download',                   
							ref: '../button',
							id: 'btn',
							handler: function(btn, e){
								window.location="/store/statistics/content/content_type_pie_down_excel.php";
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
					header: '콘텐츠 타입',
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
