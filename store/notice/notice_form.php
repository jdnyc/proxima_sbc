<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');

$user_id = $_SESSION['user']['KOR_NM'];

$uid = $_POST['n_id'];
$n_info = $db->queryRow("select * from bc_notice where notice_id = $uid");

$n_type = $_POST['n_type'];

$from_user_name = $db->queryOne("select user_nm from bc_member where user_id='{$n_info['from_user_id']}'");

$from_user_name = $from_user_name.'('.$n_info['from_user_id'].')';

if($n_type == 'all'){
	$receipient = '전체';
}
if($n_type == 'group'){
	$receipient = $db->queryOne("select member_group_name from bc_member_group where member_group_id='{$n_info['member_group_id']}'");
}
if($n_type == 'user'){
	$n_to_user_name = $db->queryOne("select user_nm from bc_member where user_id='{$n_info['to_user_id']}'");
	$receipient = $n_to_user_name."(".$n_info['to_user_id'].")";
}


$array_type = array(
	'all' => '전체',
	'group' => '그룹',
	'user' => '개별'
);
$type = $array_type[$n_info['notice_type']];

?>

(function(){

	var record = Ext.getCmp('notice_grid').getSelectionModel().getSelected();

	var hbox_panel = new Ext.Panel({
		layout : 'hbox',
		frame: true,
		//height: 400,
		border : false,
		layoutConfig: {

			pack:'center',
			align:'middle'
		},
		items : [{
			xtype: 'form',
			frame: false,
			height : 320,
			width : 380,
			border: false,
			buttonAlign : 'center',
			labelWidth: 1,
			defaults: {
				labelStyle: 'text-align:center;',
				anchor: '95%'
			},
			autoScroll: true,
			items:[{
				xtype: 'compositefield',
				items:[{
					xtype: 'displayfield',
					value: '<div align="left">발&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;신 : </div>'
				},{
					xtype: 'displayfield',
					value: '<div align="left"><?=$from_user_name?></div>'
				}]
			},{
				xtype: 'compositefield',
				items:[{
					xtype: 'displayfield',
					value: '<div align="left">수&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;신 : </div>'
				},{
					xtype: 'displayfield',
					value: '<div align="left"><?=$receipient?></div>'
				}]
			},{
				xtype: 'compositefield',
				items:[{
					xtype: 'displayfield',
					value: '<div align="left">알림일자 : </div>'
				},{
					xtype: 'displayfield',
					value: '<div align="left"><?=date('Y-m-d H:i:s', strtotime($n_info['created_date'])) ?></div>'
				}]
			},{
				xtype: 'compositefield',
			//	cls: 'readonly-class',
			//	width: 360,
				items:[{
					xtype: 'displayfield',
					value: '<div align="left">제&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;목 : </div>'
				},{
					xtype: 'displayfield',
					value: '<div align="left"><?=addslashes($n_info['notice_title'])?></div>'
				}]
			},{
				xtype: 'textarea',
				readOnly: true,
			//	cls: 'readonly-class',
				value: record.get('notice_content'),
				width: 330,
				height: 170
			}],
			buttons:[{
				text: '확인',
				handler: function(btn, e){
					Ext.getCmp('notice_window_1').close();
				}
			}]
		}]
	})

	return new Ext.Window({
		id: 'notice_window_1',
		title: '<?='공지 사항 ['.$type.']'?>',
 		width: 400,
	//	height: 370,
		modal: true,
		resizable : false,
		//layout: 'fit',
		items: [
			hbox_panel
		]
	}).show();
})()