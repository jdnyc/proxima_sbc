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

$f_year = $mdb->queryOne("select substr(created_date, 0, 4) from bc_log order by created_date asc");
//$f_year = substr($first_year, 0, 4);
//$f_year= 2010;
$e_year = $mdb->queryOne("select substr(created_date, 0, 4) from bc_log order by created_date desc");
//$e_year = substr($end_year, 0, 4);
//$e_year=2011;
$select_user= $mdb->queryAll("select user_id, user_nm from bc_member");

?>
{

	border: false,
	loadMask: true,
	layout: 'fit',

	//>> tbar: ['연도 :',{ MN00213
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
		icon: '/led-icons/magnifier_zoom_in.png',
        //>>text: '사용자 조회',
        text: '<?=_text('MN00125')?> ',
		handler: function(btn, e){

			var user_search_store = new Ext.data.JsonStore({
				url: '/pages/menu/config/user/php/get.php',
				remoteSort: true,
				sortInfo: {
					field: 'user_id',
					direction: 'ASC'
				},
				idProperty: 'member_id',
				root: 'data',
				fields: [
					'member_id',
					'user_id',
					'user_nm',
					'group',
					'job_position',
					'dept_nm',
					{name: 'created_date', type: 'date', dateFormat: 'YmdHis'},
					{name: 'last_login_date', type: 'date', dateFormat: 'YmdHis'}
				],
				listeners: {
					exception: function(self, type, action, opts, response, args){
						try {
							var r = Ext.decode(response.responseText);
							if(!r.success) {
								//>>Ext.Msg.alert('정보', r.msg);
								Ext.Msg.alert('<?=_text('MN00023')?>', r.msg);
							}
						}
						catch(e) {
							//>> Ext.Msg.alert('디코드 오류', e);MN00022
							Ext.Msg.alert('<?=_text('MN00022')?>', e);
						}
					}
				}
			});

			var search=new Ext.Window({
									//>>title: '사용자 검색',MN00190
									title: '<?=_text('MN00190')?>',
									modal:true,
									width: 400,
									height: 400,
									layout: 'fit',
									items: [{
										xtype: 'form',
										layout: 'fit',
										border:false,

										buttons:[
										{
											icon: '/led-icons/magnifier_zoom_in.png',
											//>>text: '통계보기',MN00296
											text: '<?=_text('MN00296')?>',
											handler:function(){
												var del = Ext.getCmp('user_list').getSelectionModel().getSelected();

												Ext.getCmp('jobs').store.reload({
													params: {
														year:	Ext.getCmp('select_year').getValue(),
														user:   del.get('user_id')
													}
												});
												search.destroy();
											}
										},{
											icon:'/led-icons/cancel.png',
											//>>text: '닫기',MN00031
											text: '<?=_text('MN00031')?>',
											handler: function(self){
												self.ownerCt.ownerCt.ownerCt.destroy();
											}
										}],
										items: [
											new Ext.grid.GridPanel({
												id: 'user_list',
												border: false,
												store: user_search_store,
												loadMask: true,
												listeners: {
													viewready: function(self){
														self.getStore().load({
															params: {
																start: 0,
																limit: 20
															}
														});
													}
												},
												colModel: new Ext.grid.ColumnModel({
													defaults: {
														sortable: true
													},
													columns: [
														new Ext.grid.RowNumberer(),
														//>>{header: 'number', dataIndex:'member_id',hidden:'true'},
														//>>{header: '아이디', dataIndex: 'user_id',	align:'center' },MN00195
														//>>{header: '이름',   dataIndex: 'user_nm',		align:'center'},
														//>>{header: '부서',   dataIndex: 'dept_nm',	align:'center'}

														{header: 'number', dataIndex:'member_id',hidden:'true'},
														{header: '<?=_text('MN00195')?>', dataIndex: 'user_id',	align:'center' },
														{header: '<?=_text('MN00223')?>',   dataIndex: 'user_nm',		align:'center'},
														{header: '<?=_text('MN00181')?>',   dataIndex: 'dept_nm',	align:'center'}
													]
												}),
												viewConfig: {
													forceFit: true
												},
												tbar: [{
													xtype: 'combo',
													id: 'search_f',
													width: 70,
													triggerAction: 'all',
													editable: false,
													mode: 'local',
													store: [
														//>>['s_created_time', '등록일자'],
														//>>['s_user_id', '아이디'],
														//>>['s_name', '이름'],
														//>>['s_dept_nm','부서']

														['s_created_time', '<?=_text('MN00102')?>'],
														['s_user_id', '<?=_text('MN00195')?>'],
														['s_name', '<?=_text('MN00223')?>'],
														['s_dept_nm','<?=_text('MN00181')?>']
													],
													value: 's_created_time',
													listeners: {
														select: function(self, r, i){
															if (i == 0)
															{
																self.ownerCt.get(2).setVisible(true);
																self.ownerCt.get(3).setVisible(false);
															}
															else
															{
																self.ownerCt.get(3).setVisible(true);
																self.ownerCt.get(2).setVisible(false);
															}
														}
													}
												},' ',{
													xtype: 'datefield',
													id: 'search_v1',
													format: 'Y-m-d',
													listeners: {
														render: function(self){
															self.setValue(new Date());
														}
													}
												},{
													hidden: true,
													allowBlank: false,
													xtype: 'textfield',
													id: 'search_v2',
													listeners: {
														specialKey: function(self, e){
															var w = self.ownerCt.ownerCt;
															if (e.getKey() == e.ENTER && self.isValid())
															{
																e.stopEvent();
																w.doSearch(w.getTopToolbar(), user_search_store);
															}
														}
													}
												},' ',{
													icon:'/led-icons/magnifier_zoom_in.png',
													xtype: 'button',
													//>>text: '조회',
													text: '<?=_text('MN00037')?>',
													handler: function(b, e){
														var w = b.ownerCt.ownerCt;
														w.doSearch(w.getTopToolbar(), user_search_store);
													}
												},'-',{
													icon: '/led-icons/arrow_refresh.png',
													//>>text: '새로고침',
													text: '<?=_text('MN00139')?>',
													handler: function(btn, e){
														Ext.getCmp('user_list').getStore().load({
																params:{
																	start:0,
																	limit:20
																}
														});
													}
												}],
												bbar: new Ext.PagingToolbar({
													store: user_search_store,
													pageSize: 20
												}),
												doSearch: function(tbar, store){
													var combo_value = tbar.get(0).getValue(),
														params = {};
														params.start = 0;
														params.limit = 20;

													if (combo_value == 's_created_time')
													{
														params.search_field = combo_value;
														params.search_value = tbar.get(2).getValue().format('Y-m-d');
													}
													else
													{
														params.search_field = combo_value;
														params.search_value = tbar.get(3).getValue();
													}
													if(Ext.isEmpty(params.search_field) || Ext.isEmpty(params.search_value)){
														//>>Ext.Msg.alert('정보', '검색어를 입력해주세요.');
														Ext.Msg.alert('<?=_text('MN00023')?>', '<?=_text('MSG00007')?>');
													}else{
														Ext.getCmp('user_list').getStore().load({
															params: params
														});
													}
												}
											})
										]}
									]
								})	.show();
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
			url: '/store/statistics/content/job_kinds.php',
			root: 'task',
			sortInfo:{
				field: 'month',
				desc: 'asc'
			},
			fields: [
				'month',
				<?=$kind?>
			],
			listeners: {
				beforeload: function(self, opts){
					opts.params = opts.params || {};

					Ext.apply(opts.params, {
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
				<?php
				unset($t);
				foreach($actions as $v){
					$t[] = "{header:'".$v['action']."', dataIndex: '".$v['action']."'}";
				}
				echo implode(', ', $t);
				?>
			]
		})
	}
}