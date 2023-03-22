<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');

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
	'all' => _text('MN00008'),//'전체'
	'group' => _text('MN01001'),//'그룹'
	'user' => _text('MN02134')//'개별'
);
$type = $array_type[$n_info['notice_type']];

$labelwidth = 70;

$action = 'read_notice';
$user_read_id = $_SESSION['user']['user_id'];
$description = '';
insertLogNotice($action, $user_read_id, $uid, $description);
?>

(function(){

	var record = Ext.getCmp('main_notice_grid').getSelectionModel().getSelected();

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
			height : 600,
			width : 900,
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
					width : <?=$labelwidth?>,
					value: '<div align="left">'+_text('MN02135')+'</div>'//발신
				},{
					xtype: 'displayfield',
					value: '<div align="left"><?=$from_user_name?></div>'
				}]
			},{
				xtype: 'compositefield',
				items:[{
					xtype: 'displayfield',
					width : <?=$labelwidth?>,
					value: '<div align="left">'+_text('MN02136')+'</div>'//수신
				},{
					xtype: 'displayfield',
					value: '<div align="left"><?=$receipient?></div>'
				}]
			},{
				xtype: 'compositefield',
				items:[{
					xtype: 'displayfield',
					width : <?=$labelwidth?>,
					value: '<div align="left">'+_text('MN02137')+'</div>'//알림일자
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
					width : <?=$labelwidth?>,
					value: '<div align="left">'+_text('MN00249')+'</div>'//제목
				},{
					xtype: 'displayfield',
					value: '<div align="left"><?=addslashes($n_info['notice_title'])?></div>'
				}]
			},{
				xtype: 'htmleditor',
				readOnly: true,
			//	cls: 'readonly-class',
				value: record.get('notice_content'),
				height: 400,
				listeners: {
					render: function(self){
						self.getToolbar().hide();
					}
				}
			}],
			buttons:[{
				text: _text('MN00003'),//'확인'
				handler: function(btn, e){
					Ext.getCmp('main_notice_window').close();
					Ext.getCmp('main_notice_grid').getStore().load();
				}
			}]
		}]
	})

	return new Ext.Window({
		id: 'main_notice_window',
		title: _text('MN00144')+'<?='['.$type.']'?>',//공지사항[전체]
 		width: 900,
	//	height: 370,
		modal: true,
		resizable : false,
		//layout: 'fit',
		items: [
			hbox_panel
		],
		listeners: {
			close : function(self){
				Ext.getCmp('main_notice_grid').getStore().load();
			}
		}
	}).show();
})()