(function() {

	var user_type_pie_regist_sql = new Ext.data.JsonStore({
		storeId: 'user_type_pie_regist_sql',
		root: 'data',
		url: '/store/statistics/content/user_type_pie_regist_sql_cg.php',
		params : {
			action : 'statistics_cg'
		},
		autoLoad: true,
		fields: [{
				name: 'name'
			},{
				name: 'count',
				type: 'int'
			}]
	});

	return{
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
						store: user_type_pie_regist_sql,
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
						title: '사용자 정의 콘텐츠별 등록 횟수',
						store: user_type_pie_regist_sql,
						tbar:	[{
							xtype: 'toolbar',
							height: 35,
							items: [{

									xtype: 'button',
									icon: '/led-icons/disk.png',
									text: '엑셀로 저장',
									style: 'border-style:outset;',
									ref: '../button',
									id: 'btn',
									handler: function(btn, e){
										window.location="/store/statistics/content/user_type_pie_regist_excel.php?mode=cg";
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
								header: '사용자 정의 콘텐츠',
								sortable: true
							},{
								align: 'center',
								xtype: 'gridcolumn',
								header: '등록 횟수',
								sortable: true
							}],
						viewConfig: {
							forceFit: true
						}
					}]
			};
})()
