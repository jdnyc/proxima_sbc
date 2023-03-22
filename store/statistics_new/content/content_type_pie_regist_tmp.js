(function() {

	var store = new Ext.data.JsonStore({
		root: 'data',
		url: '/store/statistics/content/content_type_pie_regist_tmp.php',
		autoLoad: true,
		fields: [{
			name: 'name'
		},{
			name: 'count',
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
			store: store,
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
			//!!title: '콘텐츠 타입별 등록 횟수',
			title: _text('MN00284'),
			store: store,
			tbar:	[{
				xtype: 'toolbar',
				height: 35,
				items: [{
						xtype: 'button',
						icon: '/led-icons/disk.png',
						style: 'border-style:outset;',
						//!!text: '엑셀로 저장',
						text: _text('MN00212'),
						ref: '../button',
						id: 'btn',
						handler: function(btn, e){
							window.location="/store/statistics/content/content_type_pie_regist_excel.php";
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
				//!!header: '등록 수',
				header: _text('MN00284'),
				sortable: true
			}],
			viewConfig: {
				forceFit: true
			}
		}]
	};
})()