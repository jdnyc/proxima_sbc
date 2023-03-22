<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/lang.php');
$select_user = $mdb->queryAll("select user_id, user_nm from bc_member");
?>
(function(){

	var myPageSize = 50;
	var store = new Ext.data.JsonStore({
		url: '/store/statistics/user/user_history.php',

		root: 'user_time',
		sortInfo:{
			field: 'date',
			desc: 'desc'
		},
		fields: [
			'user_id',
			'user_name',
			{name: 'date', type: 'date', dateFormat: 'YmdHis'}
		],
		listeners: {
			beforeload: function(self, opts){
				opts.params = opts.params || {};

				Ext.apply(opts.params, {
					user_id: Ext.getCmp('user_id').getValue(),
					start_date: Ext.getCmp('start_date').getValue().format('Ymd000000'),
					end_date: Ext.getCmp('end_date').getValue().format('Ymd240000')
				});
			}
		}
	});

	return {
		border: false,
		loadMask: true,
		layout: 'fit',


		//>>tbar: ['사용자 :',{ MN00189
		tbar: ['<?=_text('MN00195')?>: ',{ 
			xtype: 'combo',
			id: 'user_id',
			width: 100,
			triggerAction: 'all',
			editable: false,
			mode: 'local',
			displayField: 'd',
			valueField: 'v',
			value: '<?=$_SESSION['user']['user_id']?>',
			store: new Ext.data.ArrayStore({
				fields: [
					'v', 'd'
				],
				data: [
					<?php
					foreach($select_user as $choosen){
						$t[] = "['".$choosen['user_id']."', '".$choosen['name']." ".$choosen['user_id']."']";
					}
					echo implode(',', $t);
					?>

				]
			})
		},
		'-',
		//>>'기간',
		'<?=_text('MN00150')?>:',
		{
			xtype: 'datefield',
			id: 'start_date',
			editable: false,
			format: 'Y-m-d',
			listeners: {
				render: function(self){
					var d = new Date();

					self.setMaxValue(d.format('Y-m-d'));
					self.setValue(d.add(Date.MONTH, -1).format('Y-m-d'));
				}
			}
		},
		//>>'부터',
		'<?=_text('MN00183')?>',
		{
			xtype: 'datefield',
			id: 'end_date',
			editable: false,
			format: 'Y-m-d',
			listeners: {
				render: function(self){
					var d = new Date();

					self.setMaxValue(d.format('Y-m-d'));
					self.setValue(d.format('Y-m-d'));
				}
			}
		},'-',{
			icon: '/led-icons/find.png',
			//>>text: '조회',
			text: '<?=_text('MN00037')?>',
			handler: function(btn, e){
				Ext.getCmp('users_history').store.reload();
			}
		}],

		xtype: 'grid',
		id: 'users_history',
		border: false,
		loadMask: true,

		store: store,
		listeners: {
			viewready: function(self){
				self.store.load({
					params: {
						start: 0,
						limit: myPageSize,
						user_id: Ext.getCmp('user_id').getValue(),
						start_date: Ext.getCmp('start_date').getValue().format('Ymd000000'),
						end_date: Ext.getCmp('end_date').getValue().format('Ymd240000')
					}
				})
			}
		},
		cm: new Ext.grid.ColumnModel({
			defaults:{
				sortable: true
			},
			columns: [
				//>> {header: '사용자 아이디', dataIndex: 'user_id', width: 200},
				//>> {header: '사용자 이름', dataIndex: 'user_name', width: 200},
				//>> {header: '로그인 일시', dataIndex: 'date', renderer: Ext.util.Format.dateRenderer('Y-m-d'), width: 150}
				
				{header: '<?=_text('MN00195')?>', dataIndex: 'user_id', width: 200},
				{header: '<?=_text('MN00196')?>', dataIndex: 'user_name', width: 200},
				{header: '<?=_text('MN00103')?>', dataIndex: 'date', renderer: Ext.util.Format.dateRenderer('Y-m-d'), width: 150}
			]
		}),
		view: new Ext.ux.grid.BufferView({
			rowHeight: 18,
			scrollDelay: false
		}),

		bbar: new Ext.PagingToolbar({
			store: store,
			pageSize: myPageSize
		})

	}
})()