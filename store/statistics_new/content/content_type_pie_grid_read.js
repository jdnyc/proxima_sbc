(function() {

	var content_type_pie_read_sql = new Ext.data.JsonStore({
		root: 'data',
		url: '/store/statistics/content/content_type_pie_read_sql.php',
		autoLoad: true,
		fields: [{
			name: 'name'
		},{
			name: 'count',
			type: 'int'
		}]
	});
	return {
		//   title: '콘텐츠 타입별 다운로드 현황',
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
			store: content_type_pie_read_sql,
			dataField: 'count',
			categoryField: 'name',

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
			title: _text('MN00286'),
			//!!title: '콘텐츠 타입별 조회 횟수',
			store: content_type_pie_read_sql,
			tbar:	[{
				xtype: 'toolbar',
				height: 35,
				items: [{
						xtype: 'button',
						style: 'border-style:outset;',
						icon: '/led-icons/disk.png',
						//!!text: '엑셀로 저장',
						text: _text('MN00212'),
						ref: '../button',
						id: 'btn',
						handler: function(btn, e){
							window.location="/store/statistics/content/content_type_pie_read_excel.php";
						}
					}]
			}],
			flex: 1,
			border: false,
			autoShow: true,
			ref: 'grid',
			id: 'grid1',
			columns: [{
				align: 'center',
				xtype: 'gridcolumn',
				//!!header: '콘텐츠 타입',
				header: _text('MN00276'),
				sortable: true
			},{
				align: 'center',
				xtype: 'gridcolumn',
				//!!header: '조회 횟수',
				header: _text('MN00251'),
				sortable: true
			}],
			viewConfig: {
				forceFit: true
			}
		}]
	};
})()
