<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');

$user_id = $_SESSION['user']['user_id'];

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