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
			//title: _text('MN00286'),
			title: '<span class="user_span"><span class="icon_title"><i class=""></i></span><span class="main_title_header">'+_text('MN00286')+'</span></span>',
			cls: 'grid_title_customize proxima_customize',
			stripeRows: true,
			//!!title: '콘텐츠 타입별 조회 횟수',
			store: content_type_pie_read_sql,
			tbar:	[{
				//xtype: 'toolbar',
				//height: 35,
				//items: [{
						xtype: 'button',
						//style: 'border-style:outset;',
						//icon: '/led-icons/doc_excel_table.png',
						//!!text: '엑셀로 저장',
						//text: _text('MN00212'),
						cls: 'proxima_button_customize',
						width: 30,
						text: '<span style="position:relative;top:1px;" title="'+_text('MN00212')+'"><i class="fa fa-file-excel-o" style="font-size:13px;color:white;"></i></span>',
						ref: '../button',
						id: 'btn',
						handler: function(btn, e){
							window.location="/store/statistics/content/content_type_pie_read_excel.php";
						}
					//}]
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
