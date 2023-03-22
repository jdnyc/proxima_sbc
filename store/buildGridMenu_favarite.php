<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/lang.php');

$user_id		= $_SESSION['user']['user_id'];
$content_id		= $_POST['content_id'];

$type = $_POST['type'];

?>

[{ hidden:true }

,{
	icon: '/led-icons/delete.png',
	//>>text: '삭제',	
	text:'관심콘텐츠에서 제외',
	handler: function(btn, e){
		
		var sm = Ext.getCmp('east-menu').get(0).getSelectionModel();
		

		var rs = [];
		var _rs = sm.getSelections();
		Ext.each(_rs, function(r, i, a){
			rs.push({
				content_id: r.get('content_id')
			});
		});

		Ext.Msg.show({
			icon: Ext.Msg.QUESTION,
			//>> title: '확인',
			title: _text('MN00024'),			
			msg: '관심콘텐츠에서 제거하시겠습니까?',

			buttons: Ext.Msg.OKCANCEL,
			fn: function(btnId, text, opts){
				if(btnId == 'cancel') return;

				Ext.getCmp('east-menu').get(0).sendAction('delete', rs, Ext.getCmp('east-menu').get(0) );
			}
		});
	}
}
]