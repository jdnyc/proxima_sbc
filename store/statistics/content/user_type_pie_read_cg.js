//사용자 정의 콘텐츠별 조회
(function() {
	var user_type_read = new Ext.data.JsonStore({
		root: 'data',
		url: '/store/statistics/content/user_type_pie_read_store_cg.php',
		autoLoad: true,
		fields: [{
				name: 'content'
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
						store: user_type_read,
						dataField: 'count',
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
						title: '사용자 정의 콘텐츠별 조회 횟수',
						store: user_type_read,
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
														window.location="/store/statistics/content/user_type_pie_read_excel.php?mode=cg";
													}
												}
									]
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
								header: '조회 횟수',
								sortable: true
								//css:'background-color: #EEFFAA;'
							}],
							viewConfig: {
								forceFit: true
							}
					}]
			};
})()