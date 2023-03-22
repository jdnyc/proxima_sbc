<?php
require_once($_SERVER['DOCUMENT_ROOT']."/lib/config.php");
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');
require_once($_SERVER['DOCUMENT_ROOT']."/lib/DBOracle.class.php");
?>

(function(){

var today = new Date();
var tomorrow = new Date( Date.parse( today ) + 1 * 1000 * 60 * 60 * 24 ); // 내일

function extend_request(date){
	Ext.Ajax.request({
		url : '/pages/menu/contents_management/content_delete_action.php',
		params : {
			ids : '<?=$_POST[ids]?>',
			action : 'extend',
			date : date
		},
		callback : function(opt, success, res){
			if(success)
			{
				 Ext.decode(res.responseText);
				 Ext.getCmp('delete_extend').close();
				 Ext.getCmp('delete_inform_id').getStore().reload();
			}
			else 
			{
				 Ext.Msg.alert('서버 오류', res.statusText);
			}
		}
	})
}

 	var new_win = new Ext.Window({
 		id : 'delete_extend',
 		border :false,
 		layout:'fit',
 		title : '사용기한 연장설정',
 		width :400,
 		modal : true,
 		height: 300,
 		items :[{
	 			xtype: 'form',	 		
	 			border: false,	 			
	 			defaultType: 'textfield',
				padding : 10,
				autoScroll : true,
				defaults: {
						anchor: '100%'
				},
	 			items : [{
	 					id:'startDateField',
	 					fieldLabel: '삭제 예정일',
	 					xtype:'datefield',
	 					name : 'extend_date',
	 					size : '120',
	 					editable: true,
	 					format: 'Y-m-d',
	 					minValue :tomorrow
	 					 
	 					}]
	 					
 				}],
 		buttons : [{
 			text: ' 확 인 ',
 			icon: '/led-icons/accept.png',
 			scale : 'large',
 			handler : function(btn,e){
 				var check_date_field = Ext.getCmp('startDateField').getValue();
 				if(!check_date_field){
 					Ext.Msg.alert(' 오류 ','연장 할 날짜를 선택해 주십시오'); 				
 				} 			
 				else {
 					Ext.Msg.show({
 						title: ' 경 고 ',
 						msg : '다음 선택하신 날짜로 기한이 연장됩니다.',
 						buttons : Ext.Msg.YESNO,
 						fn : function(button){
 							if(button == 'yes')
 							{
 								extend_request(check_date_field);
 								
 							}else {
 							
 							}
 						}
 					
 					}); 				
 				}
 			}			
 		}]
 		
 	});
 	
 	return new_win.show();
})()