<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/lang.php');

$year = $_POST['year'];
$year = substr($year, 0, 4);
if(empty($year)){
	$year = date('Y');
}
$user_id = $_POST['user_id'];
if(empty($user_id)){
	$user_id =$_SESSION['user']['user_id'];
}
$user_name = $mdb->queryOne("select user_nm from bc_member where user_id = '$user_id'");

$actions = $mdb->queryAll("select action from bc_log group by action");
foreach($actions as $action){
	$kind .= "'".$action['action']."',";
}
$kind = substr($kind, 0, -1);

$f_year = $mdb->queryOne("select substr(created_date, 0, 4) from bc_media where media_type='original' order by created_date asc");		//시작 년도




$e_year = $mdb->queryOne("select substr(created_date, 0, 4) from bc_media where media_type='original' order by created_date desc");		//끝 년도


$select_user= $mdb->queryAll("select user_id, user_nm from bc_member");		//잠시 대기

?>
(function(){

	return {

		border: false,
		loadMask: true,
		layout: 'fit',

		//>> 연도 :', MN00213
		tbar: ['<?=_text('MN00213')?> :',{
			xtype: 'combo',
			id: 'select_year',
			width: 65,
			triggerAction: 'all',
			editable: false,
			mode: 'local',
			displayField: 'd',
			valueField: 'v',
			value: '<?=$e_year?>',
			store: new Ext.data.ArrayStore({
				fields: [
					'v', 'd'
				],
				data: [
					<?php
					unset($t);
					for($f_year; $f_year <= $e_year; $f_year++){
						//$t[] = "['".$f_year."', '".$f_year."년']";"._text('MN00254')."
						$t[] = "['".$f_year."', '".$f_year."']";
					}
					echo implode(', ', $t);
					?>
				]
			})
		},'-',{
				icon: '/led-icons/find.png',
				text: '조회',
				handler: function(btn, e){
										Ext.getCmp('jobs').store.reload({
					params: {
						
						year: Ext.getCmp('select_year').getValue()
						
					}
				});
						
					
				}
			}],
			items:{
					xtype: 'grid',
					id: 'jobs',
					loadMask: true,
					border: false,
					layout: 'fit',
					listeners: {
						render: function(self){
							self.store.load();
						}
					},
					store: new Ext.data.JsonStore({
						url: '/store/statistics/content/use_space.php',
						root: 'task',
						sortInfo:{
							field: 'month',
							desc: 'asc'
						},
						fields: [
							'month',
							'sum_space',
							'space',
							'sum_count',
							'count'
						],
						listeners: {
							beforeload: function(self, opts){
								opts.params = opts.params || {};
								//console.log(opts.params);
								//console.log(Ext.getCmp('select_year').getValue());

								Ext.apply(opts.params, {
								
						
										year: Ext.getCmp('select_year').getValue()
										
					
								});
							}
						}
					}),
					cm: new Ext.grid.ColumnModel({
						defaults:{
							sortable: true
						},
						columns: [
							//>>{header: '월', dataIndex: 'month'},
							{header: '<?=_text('MN00221')?>', dataIndex: 'month'},							
							{header: '누적용량', dataIndex: 'sum_space'},
							{header: '등록용량', dataIndex: 'space'},
							{header: '누적건수', dataIndex: 'sum_count'},
							{header: '등록건수', dataIndex: 'count'}
							
							
						]
					})
				}
	};

			
})()