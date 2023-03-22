<?php

$usr_id =  $_REQUEST['user_id'];

if(!$usr_id)
{
	//없으면 로그인페이지나 리다이렉트로 가야함
}

?>

<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Regist Form</title>
<!--
<link rel="stylesheet" type="text/css" href="/lib/extjs/resources/css/ext-all.css" />
	
<script type="text/javascript" src="/lib/extjs/adapter/ext/ext-base.js"></script>
<script type="text/javascript" src="/lib/extjs/ext-all.js"></script>

-->
<link rel="stylesheet" type="text/css" href="/ext/resources/css/ext-all.css" />
	
<script type="text/javascript" src="/ext/adapter/ext/ext-base.js"></script>
<script type="text/javascript" src="/ext/ext-all.js"></script>

<script type="text/javascript">
var xx = false;
var form_data = null;
var vals = new Array();
var names = new Array();

Ext.onReady(function(){

    Ext.QuickTips.init();
    Ext.form.Field.prototype.msgTarget = 'side';

    var bd = Ext.getBody();

    bd.createChild({tag: 'h2', html: 'Form 1 - Very Simple'});
	var submit_url  = 'nle_save.php';

    var simple = new Ext.FormPanel({
			id:'form_id',		   
			url:'nle_save.php',
			frame:true,
			title: 'Simple Form',
			bodyStyle:'padding:5px 5px 0',
			width: 450,
			defaults: {width: 300},
			labelwidth:120,
			defaultType: 'textfield',

			items: [{
					fieldLabel: 'USER_ID',
					name: 'user_id',
					value:'<?=$usr_id?>'
				
			},{
					fieldLabel: 'SAMPLE META1',
					name: 'sample_meta1',
					allowBlank:false
				},{
					fieldLabel: 'SAMPLE META1',
					name: 'sample_meta2',
						id:'meta2'
				},{
					xtype:'textarea',
					fieldLabel: 'SAMPLE TEXTAREA',
					name: 'text'
				},{
					fieldLabel: 'EXPORT_URL',
					name: 'save_folder_url'
				}
			],

			buttons: [{
				text: 'Save',
				handler:function(e)
				{
					save();
				}
			},
				{
				text: 'getPostURL',
				handler:function(e)
				{
					GetPostURL();
				}
			},

				{
				text: 'GetFilePath',
				handler:function(e)
				{
					GetFilePath();
				}
			},
				
			{
				text: 'Cancel'
			}]
		});

		simple.render(document.body);
	});

	function GetFilePath()//get_export_url()
	{
		alert("GetFilePath");
		var set_export_url = "/Vol1/export/20150230121231.mov";
		return set_export_url;
	}



	function GetPostURL()
	{
		alert("call POST URL");
		var set_submit_url = "http://10.160.77.207:8033/nle_save.php";
		return set_submit_url;
	}

	function save() {		
		var submit_url  = 'nle_save.php';
		var file = "";
		var check_form =  Ext.getCmp("form_id").getForm();

		if(!check_form.isValid())
		{
			return "false";
		}
				
		check_form.submit({
			url: submit_url,
			params: {
				file: file
			},
			success: function(form, action) {

					return true;
			},
			failure: function(form, action) {

				 return false;
				 switch (action.failureType) {
										case Ext.form.Action.CLIENT_INVALID:
												Ext.Msg.alert('Failure', '누락된 메타정보 항목이 있습니다.');
												break;
										case Ext.form.Action.CONNECT_FAILURE:
												Ext.Msg.alert('Failure', 'Ajax communication failed');
												break;
										case Ext.form.Action.SERVER_INVALID:
												Ext.Msg.alert('Failure', action.result.msg);
												break;
								}
			}
		});

		
		return true;
	}


	function GetMetaData()
	{
		var form =  Ext.getCmp("form_id").getForm();		
		var arrMeta = [];	

		arrMeta.push(form.getValues());

		var x = eval(arrMeta);
		var t = Ext.getCmp("meta2");
		t.setValue( Ext.encode(arrMeta));
		
		 return Ext.encode(arrMeta);
		
	}

</script>


</head>
<body>

</body>
</html>