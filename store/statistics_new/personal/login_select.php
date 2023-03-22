<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');

$year= date('Y');
$user_id = $_POST['user_id'];
if(empty($user_id)){
	$user_id =$_SESSION['user']['user_id'];
}
$select_user= $mdb->queryAll("select user_id, user_nm from bc_member");
$years = $db->queryAll("select substring(created_date, 1, 4) as year from bc_member group by substring(created_date, 1, 4)");

?>

{
	layout: 'fit',
	border: false,

	tbar: ['연도 선택:',{
		xtype: 'combo',
		id: 'login_chart_statistics_year',
		width: 65,
		mode: 'local',
		triggerAction: 'all',
		editable: false,
		displayField: 'd',
		valueField: 'v',
		value: <?=date('Y')?>,
		store: new Ext.data.ArrayStore({
			fields: [
				'd', 'v'
			],
			data: [
				<?php
				unset($t);
				foreach($years as $v){
					$t[] = "['".$v['year']."년', ".$v['year']."]";
				}
				echo implode(',', $t);	
				?>
			]
		})
	},
		'-','사용자 선택',{
		xtype: 'combo',
		id: 'login_chart_statistics_user',
		width: 70,
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
				unset($t);
				foreach($select_user as $choosen){
					$t[] = "['".$choosen['user_id']."', '".$choosen['user_nm']."']";
				}
				echo implode(', ', $t);
				?>
			]
		})
	},'-',{
		icon: '/led-icons/find.png',
		text: '조회',
		handler: function(btn, e){
			Ext.getCmp('line_chart').store.reload();
		}
	}],

	items: {
		xtype: 'linechart',
		id: 'line_chart',
		listeners:{
			render: function(self){
				self.store.load();
			}
		},
		store: new Ext.data.JsonStore({
			url: '/store/statistics/personal/login_select_return.php',
			root: 'login_return',
			fields:['month', 'value'],			
			listeners: {
				beforeload: function(slef, opts){
					opts.params = opts.params || {};

					Ext.apply(opts.params, {
						year: Ext.getCmp('login_chart_statistics_year').getValue(),
						user_id: Ext.getCmp('login_chart_statistics_user').getValue()
					});
				}
			}
		}),
		xField: 'month',
		yField: 'value'
	}
}