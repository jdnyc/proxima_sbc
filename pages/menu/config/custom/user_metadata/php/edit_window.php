<?php
require_once($_SERVER['DOCUMENT_ROOT']."/lib/config.php");
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');
//require_once($_SERVER['DOCUMENT_ROOT']."/lib/DBOracle.class.php");

if(empty($_SESSION['user']['lang'])){
	//throw new Exception(_text('MSG02041'));//'세션이 만료되어 로그인이 필요합니다.'
	HandleError(_text('MSG02041'));
}else{
	$user_lang = $_SESSION['user']['lang'];
}

$ud_content_id = $_POST['ud_content_id'];

//$query= "select type_code , date_code
  //             , (select name from (select * from bc_code where code_type_id in (select id from bc_code_type where code= 'FLDDDT')) z where z.code = de.date_code) as name
   //           from bc_ud_content_media_del de where de.ud_content_id = '$ud_content_id'";

$query= "select type_code , date_code, code_type from bc_ud_content_delete_info where ud_content_id = '".$ud_content_id."'";
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
		$sel_data[$col] = $v['date_code'];
	}
}

$query = "
	SELECT	CT.CODE	 AS CODE_TYPE, C.ID, C.CODE, C.CODE_TYPE_ID, C.SORT, C.HIDDEN, C.REF1,
				C.".get_code_name_field()." AS NAME
	FROM		BC_CODE_TYPE CT
					LEFT JOIN	BC_CODE C
					ON				C.CODE_TYPE_ID = CT.ID
";
$codes = $db->queryAll($query);

foreach( $codes  as $code ){
	if( $code['code_type'] == 'UCSDDT' && $code['code'] == $e_expire_date ){
		$expire_date_value = $code['name'];
	}

	if( $code['code_type'] == 'UCDDDT' && $code['code'] == $e_con_expire_date ){
		$con_expire_date_value = $code['name'];
	}
}


//$query = "select name from bc_code where code_type_id  in (select id from bc_code_type ct where ct.code = 'UCSDDT') and code = '$e_expire_date'";
//$expire_date_value = $db->queryOne($query);

//$query = "select name from bc_code where code_type_id  in (select id from bc_code_type ct where ct.code = 'UCDDDT') and code = '$e_con_expire_date'";
//$con_expire_date_value = $db->queryOne($query);

//$query= "select * from bc_code where code_type_id  in (select id from bc_code_type ct where ct.code = 'FLDLNM')";
//$all = $db->queryAll($query);
//foreach($all as $v)
//{
	//$id = '\'del_'.$v['code'].'_field\'';
	//$combo_name =  'del_'.$v['code'].'_date';
	//$checkboxName = '\'del_'.$v['code'].'_checkbox\' ';
	//$title =  $v['name']." 삭제 기한";
	//$file_del_str.= "
			//,{
				////유동성있게 변하게 해야함
				//id : $id,
				//xtype:'fieldset',
				//title: '$title',";
	//$code =$v['code'];
	////echo(" $sel_data[$code] ");

	//if($sel_data[$code])
	//{
		//$file_del_str.="checkboxToggle: true, collapsed: false,";
	//}
	//else {
		//$file_del_str.="checkboxToggle: true, collapsed: true,";
	//}
	//$query="select t.name from (select * from bc_code where code_type_id in (select id from bc_code_type where code= 'FLDDDT')) t where t.code='$sel_data[$code]'";
	//$s_value = $db->queryOne($query);

	//$file_del_str.= "
			//checkboxName: $checkboxName,
			//autowidth:true,
				//items : [{
							 //xtype : 'compositefield'
							 //,msgTarget: 'side'
							 //,fieldLabel: ' 파일 삭제 기한 '
							 //,layout: {
								//align: 'middle'
								//,pack: 'center'
								//,type: 'hbox'
							  //}
							 //,items : [
									//{
									//name : '$combo_name',
									//xtype: 'combo',
									//width: 100,
									//autoWidth: true,
									//store: new Ext.data.JsonStore({
										//url: '/pages/menu/config/custom/user_metadata/php/get.php',
										//baseParams: {
											//action: 'file_del_date_list'
										//},
										//root: 'data',
										//idProperty: 'id',
										//fields: [
											//{name: 'code', type: 'string'},
											//{name: 'name', type: 'string'}
										//]
									//}),
									//value: '$s_value',
									//hiddenName: '$combo_name',
									//hiddenValue : '$sel_data[$code]',
									//valueField: 'code',
									//displayField: 'name',
									//fieldLabel: '삭제 예정일',
									//typeAhead: true,
									//triggerAction: 'all',
									//forceSelection: true,
									//editable: false
									//}
								//]
							//}
						//]
			//}
	//";

//}

?>

(function(){
		var sm = Ext.getCmp('bc_ud_content').getSelectionModel();
		var sel = sm.getSelected();

		new Ext.Window({
				id: 'edit_table_win',
				layout: 'border',
				title: _text('MN00147'),
				width: 800,
				height: 490,
				padding: 10,
				modal: true,
				border : false,
				split : true,
				buttonAlign: 'center',
				items: [{
					//title: _text('MN01006'),//'콘텐츠 주요사항 설정',
					region : 'center',
					id: 'edit_table_form',
					cls: 'change_background_panel',
					padding : 10,
					xtype: 'form',
					defaultType: 'textfield',
					defaults: {
						anchor: '100%',
						labelSeparator: ''
					},
					labelWidth : 170,
					width:550,
					items: [{
						xtype: 'hidden',
						name: 'ud_content_id'
					},{
						xtype: 'hidden',
						name: 'show_order'
					},{
						name: 'ud_content_title',
						fieldLabel: _text('MN00273'),//콘텐츠 명
						msgTarget: 'under',
						allowBlank: false,
						//2015-12-16 콘텐츠 명 20자 이내로 수정
						autoCreate: {tag: 'input', type: 'text', autocomplete: 'off', maxlength: '20'},
						listeners: {
							render: function(self){
								self.focus(true, 500);
							}
						}
					},{
						name: 'ud_content_code',
						fieldLabel: _text('MN02153'),//'테이블명'
						msgTarget: 'under',
						regex: /^[A-Za-z0-9+]*$/,
						regexText: _text('MSG02074'),//Only number, alphabet can allow here.
						autoCreate: {tag: 'input', type: 'text', size: 4, autocomplete: 'off', maxlength: 16},
						allowBlank: false,
						listeners: {
							render: function(self){
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
						fieldLabel: _text('MN02013'),//'만료 기한'
						typeAhead: true,
						triggerAction: 'all',
						forceSelection: true,
						editable: false
					},{
						fieldLabel : _text('MN02014'),//고해상도 스토리지
						id: 'highres_storage',
						hiddenName : 'highres',
						valueField: 'storage_id',
						displayField: 'name',
						xtype: 'combo',
						store: new Ext.data.JsonStore({
							url: '/pages/menu/config/custom/user_metadata/php/get.php',
							baseParams: {
								action: 'ud_storage_list'
							},
							autoLoad: true,
							root: 'data',
							idProperty: 'storage_id',
							fields: [
								{name: 'storage_id'},
								{name: 'name'}
							],
							listeners: {
								load: function(self){
									Ext.getCmp('highres_storage').setValue( sel.get('storage').highres );
								}
							}
						}),
						allowBlank: false,
						typeAhead: true,
						triggerAction: 'all',
						forceSelection: true,
						editable: false
					},{
						fieldLabel : _text('MN02015'),//저해상도 스토리지
						id: 'lowres_storage',
						hiddenName : 'lowres',
						valueField: 'storage_id',
						displayField: 'name',
						xtype: 'combo',
						store: new Ext.data.JsonStore({
							url: '/pages/menu/config/custom/user_metadata/php/get.php',
							baseParams: {
								action: 'ud_storage_list'
							},
							autoLoad: true,
							root: 'data',
							idProperty: 'storage_id',
							fields: [
								{name: 'storage_id'},
								{name: 'name'}
							],
							listeners: {
								load: function(self){
									Ext.getCmp('lowres_storage').setValue( sel.get('storage').lowres );
								}
							}
						}),
						allowBlank: false,
						typeAhead: true,
						triggerAction: 'all',
						forceSelection: true,
						editable: false

					},{
						fieldLabel : _text('MN02016'),//업로드 스토리지
						id: 'upload_storage',
						hiddenName : 'upload',
						valueField: 'storage_id',
						displayField: 'name',
						xtype: 'combo',
						store: new Ext.data.JsonStore({
							url: '/pages/menu/config/custom/user_metadata/php/get.php',
							baseParams: {
								action: 'ud_storage_list'
							},
							autoLoad: true,
							root: 'data',
							idProperty: 'storage_id',
							fields: [
								{name: 'storage_id'},
								{name: 'name'}
							],
							listeners: {
								load: function(self){
									Ext.getCmp('upload_storage').setValue( sel.get('storage').upload );
								}
							}
						}),
						allowBlank: false,
						typeAhead: true,
						triggerAction: 'all',
						forceSelection: true,
						editable: false
					},{
						fieldLabel : _text('MN01013'),//기본 카테고리
						readOnly: true,
						id: 'category',
						hiddenName : 'category',
						valueField: 'category_id',
						displayField: 'category_title',
						xtype: 'combo',
						store: new Ext.data.JsonStore({
							url: '/pages/menu/config/custom/user_metadata/php/get.php',
							baseParams: {
								action: 'ud_category_list'
							},
							autoLoad: true,
							root: 'data',
							idProperty: 'category_id',
							fields: [
								{name: 'category_id'},
								{name: 'category_title'}
							],
							listeners: {
								load: function(self){
									Ext.getCmp('category').setValue( sel.get('category'));
								}
							}
						}),
						allowBlank: false,
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
					region: 'east',
					id: 'edit_table_form2',
					hidden : true,
					title: '콘텐츠 폐기/삭제 기한 설정',
					xtype: 'form',
					defaultType: 'textfield',
					padding : 10,
					autoScroll : true,
					width: 1,
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

							/*<?=$file_del_str?>*/
						]
				}],
				buttons: [{
					text : '<span style="position:relative;top:1px;"><i class="fa fa-edit" style="font-size:13px;"></i></span>&nbsp;'+_text('MN00043'),
					scale: 'medium',
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
					text : '<span style="position:relative;top:1px;"><i class="fa fa-close" style="font-size:13px;"></i></span>&nbsp;'+_text('MN00004'),
					scale: 'medium',
					handler: function(btn, e) {
						this.ownerCt.ownerCt.close();
					}
				}]
			}).show();
})()


