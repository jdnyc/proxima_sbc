(function() {

	var storeApproveGrid = new Ext.data.JsonStore({
		storeId: 'storeApproveGrid',
		root: 'data',
		url: '/store/statistics/content/user_type_approve.php',
		baseParams: {
			userContentType: 'all'
		},
		autoLoad: true,
		fields: [{
				name: 'type'
			},{
				name: 'total',
				type: 'int'
			},{
				name: 'approve',
				type: 'int'
			},{
				name: 'refuse',
				type: 'int'
			},{
				name: 'wait',
				type: 'int'
			}]
	});

	return{

			xtype: 'grid',
			store: storeApproveGrid,
//			tbar:	[{
//				xtype: 'toolbar',
//				height: 35,
//				items: [{
//						xtype: 'button',
//						text: '엑셀로 다운로드',
//						icon: '/led-icons/disk.png',
//						style: 'border-style:outset;',
//						ref: '../button',
//						id: 'btn',
//						handler: function(btn, e){
//							window.location="/store/statistics/content/user_type_approve_excel.php";
//						}
//				}]
//			}],
			flex: 1,
			border: false,
			autoShow: true,
			ref: 'grid',
			id: 'gridApprove',
			columns: [
				{ header: '유형', dataIndex: 'type', align: 'center', sortable: true },
				{ header: '전체', dataIndex: 'total', align: 'center', sortable: true },
				{ header: '승인', dataIndex: 'approve', align: 'center', sortable: true },
				{ header: '반려', dataIndex: 'refuse', align: 'center', sortable: true },
				{ header: '대기', dataIndex: 'wait', align: 'center', sortable: true }
			],
			viewConfig: {
				forceFit: true
			}
	};
})()