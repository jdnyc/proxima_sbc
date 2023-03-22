<?php
require_once($_SERVER['DOCUMENT_ROOT']."/lib/config.php");
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');
//require_once($_SERVER['DOCUMENT_ROOT']."/lib/DBOracle.class.php");

$ud_content_id = $_POST['ud_content_id'];

//$query= "select type_code , date_code
  //             , (select name from (select * from bc_code where code_type_id in (select id from bc_code_type where code= 'FLDDDT')) z where z.code = de.date_code) as name
   //           from bc_ud_content_media_del de where de.ud_content_id = '$ud_content_id'";

$query= "select type_code , date_code, code_type from bc_ud_content_delete_info where ud_content_id = '$ud_content_id'";
$data = $db->queryAll($query);
$sel_data = array();
$i=0;

foreach($data as $d => $v)
{
	if($v['code_type']== 'UCSDDT'){
		$e_expire_date = $v['date_code'];
	}else if($v['code_type']== 'UCDDDT'){
		$e_con_expire_date = $v['date_code'];
	}else {
		$col = $v['type_code'];
		$sel_data[$col] = $v[date_code];
	}
}

$query = "select name from bc_code where code_type_id  in (select id from bc_code_type ct where ct.code = 'UCSDDT') and code = '$e_expire_date'";
$expire_date_value = $db->queryOne($query);

$query = "select name from bc_code where code_type_id  in (select id from bc_code_type ct where ct.code = 'UCDDDT') and code = '$e_con_expire_date'";
$con_expire_date_value = $db->queryOne($query);

$query= "select * from bc_code where code_type_id  in (select id from bc_code_type ct where ct.code = 'FLDLNM')";
$all = $db->queryAll($query);
foreach($all as $v)
{
	$id = '\'del_'.$v['code'].'_field\'';
	$combo_name =  'del_'.$v['code'].'_date';
	$checkboxName = '\'del_'.$v['code'].'_checkbox\' ';
	$title =  $v['name']." 삭제 기한";
	$file_del_str.= "
			,{
				//유동성있게 변하게 해야함
				id : $id,
				xtype:'fieldset',
				title: '$title',";
	$code =$v['code'];
	//echo(" $sel_data[$code] ");

	if($sel_data[$code])
	{
		$file_del_str.="checkboxToggle: true, collapsed: false,";
	}
	else {
		$file_del_str.="checkboxToggle: true, collapsed: true,";
	}
	$query="select name from (select * from bc_code where code_type_id in (select id from bc_code_type where code= 'FLDDDT')) where code='$sel_data[$code]'";
	$s_value = $db->queryOne($query);

	$file_del_str.= "
				checkboxName: $checkboxName,
				autowidth:true,
					items : [{
								 xtype : 'compositefield'
								 ,msgTarget: 'side'
								 ,fieldLabel: ' 파일 삭제 기한 '
								 ,layout: {
									align: 'middle'
									,pack: 'center'
									,type: 'hbox'
								  }
								 ,items : [
										{
										name : '$combo_name',
										xtype: 'combo',
										width: 100,
										autoWidth: true,
										store: new Ext.data.JsonStore({
											url: '/pages/menu/config/custom/user_metadata/php/get.php',
											baseParams: {
												action: 'file_del_date_list'
											},
											root: 'data',
											idProperty: 'id',
											fields: [
												{name: 'code', type: 'string'},
												{name: 'name', type: 'string'}
											]
										}),
										value: '$s_value',
										hiddenName: '$combo_name',
										hiddenValue : '$sel_data[$code]',
										valueField: 'code',
										displayField: 'name',
										fieldLabel: '삭제 예정일',
										typeAhead: true,
										triggerAction: 'all',
										forceSelection: true,
										editable: false
										}
									]
								}
							]
				}
		";

}

?>

(function(){
		var sm = Ext.getCmp('bc_ud_content').getSelectionModel();
		var sel = sm.getSelected();

		new Ext.Window({
				id: 'edit_table_win',
				layout: 'border',
				title: _text('MN00147'),
				width: 650,
				height: 350,
				padding: 10,
				modal: true,
				border : false,
				split : true,
				items: [{
					title: '콘텐츠 주요사항 설정',
					region : 'west',
					id: 'edit_table_form',
					padding : 10,
					xtype: 'form',
					defaultType: 'textfield',
					defaults: {
						anchor: '100%'
					},
					width:350,
					items: [{
						xtype: 'hidden',
						name: 'ud_content_id'
					},{
						xtype: 'hidden',
						name: 'show_order'
					},{
						name: 'ud_content_title',
						fieldLabel: _text('MN00273'),
						msgTarget: 'under',
						allowBlank: false,
						listeners: {
							render: function(self){
								self.focus(true, 500);
							}
						}
					},{
						xtype: 'combo',
						id: 'content_type_list',
						store: new Ext.data.JsonStore({
							url: '/pages/menu/config/custom/user_metadata/php/get.php',
							baseParams: {
								action: 'content_type_list'
							},
							root: 'data',
							idProperty: 'bs_content_id',
							fields: [
								{name: 'bs_content_title', type: 'string'},
								{name: 'bs_content_id', type: 'int'}
							]
						}),
						allowBlank: false,
						hiddenName: 'ori_content_idx',
						hiddenValue: sel.get('bs_content_id'),
						valueField: 'bs_content_id',
						displayField: 'bs_content_title',
						fieldLabel: _text('MN00279'),
						typeAhead: true,
						triggerAction: 'all',
						forceSelection: true,
						editable: false,
						emptyText: _text('MSG00111')
					},{
						name: 'allowed_extension',
						fieldLabel: _text('MN00309')
					},/*{
						xtype: 'radiogroup',
						fieldLabel: _text('MN00395'),
						name: 'use_common_category',
						items: [
							{boxLabel: _text('MN00393'), name: 'use_common_category', inputValue: 'Y'},
							{boxLabel: _text('MN00394'), name: 'use_common_category', inputValue: 'N'}
						]
					},*/{
						xtype: 'textarea',
						name: 'description',
						fieldLabel: _text('MN00049')
					},{
						name : 'content_expire_date',
						xtype: 'combo',
						store: new Ext.data.JsonStore({
							url: '/pages/menu/config/custom/user_metadata/php/get.php',
							baseParams: {
								action: 'content_del_date_list'
							},
							root: 'data',
							idProperty: 'id',
							fields: [
								{name: 'code', type: 'string'},
								{name: 'name', type: 'string'}
							]
						}),
						allowBlank: false,
						value : '<?=$expire_date_value?>',
						hiddenName: 'expired_date',
						hiddenValue :'<?=$e_expire_date?>',
						valueField: 'code',
						displayField: 'name',
						fieldLabel: '만료 기한',
						typeAhead: true,
						triggerAction: 'all',
						forceSelection: true,
						editable: false
					}],
					listeners: {
						afterrender: function(self) {
							var sm = Ext.getCmp('bc_ud_content').getSelectionModel();
							var rec = sm.getSelected();
							self.getForm().loadRecord(rec);

//							if (rec.get('use_common_category') == 'N')
//							{
//								self.getForm().setValues({
//									use_common_category: 'N'
//								});
//							}
//							else
//							{
//								self.getForm().setValues({
//									use_common_category: 'Y'
//								});
//							}
						}
					}
				},{
					region: 'center',
					id: 'edit_table_form2',
					title: '콘텐츠 폐기/삭제 기한 설정',
					xtype: 'form',
					defaultType: 'textfield',
					padding : 10,
					autoScroll : true,
					anchor: '100%',
					listeners: {
						afterrender: function(self) {
							var sm = Ext.getCmp('bc_ud_content').getSelectionModel();
							var rec = sm.getSelected();
							self.getForm().loadRecord(rec);

//							if (rec.get('use_common_category') == 'N')
//							{
//								self.getForm().setValues({
//									use_common_category: 'N'
//								});
//							}
//							else
//							{
//								self.getForm().setValues({
//									use_common_category: 'Y'
//								});
//							}
						}
					},
					items: [{
							xtype:'fieldset',
							title: '콘텐츠 삭제 기한',
							collapsible: false,
							items : [{
								 xtype : 'compositefield'
								 ,msgTarget: 'side'
								 ,fieldLabel: '콘텐츠 삭제 기한'
								 ,layout: {
									align: 'middle'
									,pack: 'center'
									,type: 'hbox'
								  }
								 ,items : [
										{
										name : 'contents_expire_date',
										width: 100,
										autoWidth: true,
										xtype: 'combo',
										store: new Ext.data.JsonStore({
											url: '/pages/menu/config/custom/user_metadata/php/get.php',
											baseParams: {
												action: 'contents_del_date_list'
											},
											root: 'data',
											idProperty: 'id',
											fields: [
												{name: 'code', type: 'string'},
												{name: 'name', type: 'string'}
											]
										}),
										allowBlank: false,
										value : '<?=$con_expire_date_value?>',
										hiddenName: 'con_expired_date',
										hiddenValue :'<?=$e_con_expire_date?>',
										valueField: 'code',
										displayField: 'name',
										fieldLabel: '삭제예정일',
										typeAhead: true,
										triggerAction: 'all',
										forceSelection: true,
										editable: false
									}
									]
								}
								]

							}

							<?=$file_del_str?>
						]
				}],
				buttons: [{
					text: _text('MN00043'),
					scale : 'large',
					handler: function(btn, e) {

					var form2_params = Ext.encode(Ext.getCmp('edit_table_form2').getForm().getValues());
						Ext.getCmp('edit_table_form').getForm().submit({
							url: '/pages/menu/config/custom/user_metadata/php/edit.php',
							params: {
								action: 'edit_table',
								expire : form2_params
							},
							success: function(form, action) {
								try {
									var result = Ext.decode(action.response.responseText, true);
									if(result.success) {
										Ext.getCmp('edit_table_win').close();
										Ext.getCmp('bc_ud_content').store.reload();
										//Ext.getCmp('table_combo').store.reload();
									}else{
										Ext.Msg.show({
											title: _text('MN00022'),
											icon: Ext.Msg.ERROR,
											msg: result.msg,
											buttons: Ext.Msg.OK
										})
									}
								}catch(e){
									Ext.Msg.show({
										title: _text('MN00022'),
										icon: Ext.Msg.ERROR,
										msg: e.message,
										buttons: Ext.Msg.OK
									})
								}
							},
							failure: function(form, action) {
								Ext.Msg.show({
									icon: Ext.Msg.ERROR,
									title: _text('MN00022'),
									msg: action.result.msg,
									buttons: Ext.Msg.OK
								});
							}
						});

					}
				},{
					text: _text('MN00004'),
					scale : 'large',
					handler: function(btn, e) {
						this.ownerCt.ownerCt.close();
					}
				}]
			}).show();
})()


