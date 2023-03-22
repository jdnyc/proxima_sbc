<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/lang.php');
// require_once($_SERVER['DOCUMENT_ROOT'].'/lib/Zodiac.class.php');

if($_POST['action'] == 'del'){
	//보도정보에 사용자 삭제 정보 전달
	$update_user_id = $_SESSION['user']['user_id'];
	$del_user_id = $db->queryOne("
						SELECT	USER_ID
						FROM	BC_MEMBER
						WHERE	MEMBER_ID = ".$_POST['member_id']
					);
	// $zodiac = new Zodiac();

	// $zodiac->userManage(array(
	// 		'action'			=>	'del',
	// 		'user_id'			=>	$del_user_id,
	// 		'user_nm'			=>	'',
	// 		'password'			=>	'',
	// 		'interPhone'		=>	'',
	// 		'homePhone'			=>	'',
	// 		'handPhone'			=>	'',
	// 		'email'				=>	'',
	// 		'rmk'				=>	'',
	// 		'update_user_id'	=>	$update_user_id
	// ));

	// $r = $db->exec("delete from bc_member where member_id=".$_POST['member_id']);
	$r = $db->exec("delete from bc_member_option where member_id=".$_POST['member_id']);
	$r = $db->exec("delete from bc_member_group_member where member_id=".$_POST['member_id']);
	$result = $db->exec("
				update	bc_member
				set		del_yn='Y'
				where	user_id='$del_user_id' 
			");
	die(json_encode(array(
		'success' => true
	)));
}

$groups = $db->queryAll("select * from bc_member_group order by member_group_name");
switch($_POST['action']){
	case 'add':
		//>>$window_title = "'사용자 추가'";
		//>>$button_text = "'추가'";
		//>>$wait_msg = "'사용자 추가 중입니다...'";
		$window_title = "'"._text('MN00126')."'";
		$button_text = "'"._text('MN00033')."'";
		$wait_msg = "'"._text('MSG00152')."'";
		$user['lang'] = 'ko';
	break;

	case 'edit':
		//>>$window_title = "'사용자 수정'";MN00193
		//>>$button_text = "'변경'";
		//>>$wait_msg = "'변경 중입니다...'";
		$window_title = "'"._text('MN00193')."'";
		$button_text = "'"._text('MN00043')."'";
		$wait_msg = "'"._text('MSG00153')."'";
		$user = $db->queryRow("
											SELECT a.*, b.top_menu_mode, b.action_icon_slide_yn
											FROM bc_member a
											LEFT OUTER JOIN bc_member_option b ON a.member_id = b.member_id
											WHERE a.member_id='{$_POST['member_id']}'
										");
		if(empty($user)){
			//>>die("'" . $_POST['user_id'] . "'을 찾을수 없습니다");MSG00154
			die("'" . $_POST['user_id'] . "'을 찾을수 없습니다");
		}
		//2016-10-26 전화번호 입력방식 변경
		$phone = $user['phone'];
		if( preg_match('/-/', $phone) ){
			$phone0 = explode('-', $phone);
			$phone1 = $phone0[0];
			$phone2 = $phone0[1];
			$phone3 = $phone0[2];
		}else{
			if( $phone != '' ){
				if( strlen($phone) <= 10  ){
					$phone1 = substr($phone, 0, 3);
					$phone2 = substr($phone, 3, 3);
					$phone3 = substr($phone, 6, 4);
				}else{
					$phone1 = substr($phone, 0, 3);
					$phone2 = substr($phone, 3, 4);
					$phone3 = substr($phone, 7, 4);
				}
			}
		}

		$in_groups = $db->queryall("select g.member_group_name from bc_member m, bc_member_group g, bc_member_group_member gm " .
										"where m.member_id={$_POST['member_id']} " .
										"and m.member_id=gm.member_id " .
										"and gm.member_group_id=g.member_group_id");
		$group_member = array();
		foreach($in_groups as $in_group){
			array_push($group_member, $in_group['member_group_name']);
		}
	break;


}
?>
(function(){
	var f = new Ext.Window({
		id: 'window_manager_member',
		iconCls: 'ariel_user_add',
		width: 500,
		<?php if($_POST['action'] == 'add'){?>
		height: 590,
		<?php }else{?>
		height: 530,
		<?php }?>
		resizable: true,
		modal: true,
		layout: 'vbox',
		layoutConfig: {
			align: 'stretch'
		},
		style: {background : 'white'},
		title: <?=$window_title?>,

		items: [{
			<?php
			//echo ($_POST['action'] == 'edit') ? 'hidden: true,' : '';
			?>
			xtype: 'form',
			border: false,
			frame: true,
			padding: '5px',
			defaultType: 'textfield',
			flex: 1,
            autoScroll: true,
			layoutConfig: {
				autoScroll: true
			},
			defaults: {
				msgTarget: 'under',
				autoScroll: true,
				anchor: '95%'
			},
			style: {
				background: 'white'
			},
			id: 'userInfo',

			items: [{
				xtype: 'hidden',
				name: 'member_id',
				value: '<?=$user['member_id']?>'
			},{
				allowBlank: false,
				//>>fieldLabel: '아이디',MN00195
				fieldLabel: '<?=_text('MN00195')?>',
				name: 'user_id',
				maskRe: /[a-zA-Z0-9_]/,
				vtype: 'alphanum'
				<?php
				if($_POST['action'] == 'edit')
				{
					echo ", value: '".$user['user_id']."', disabled: true";
				}
				?>

			},
			<?php
			if($_POST['action'] == 'add')
			{
			?>
				{
					allowBlank: false,
					inputType: 'password',
					//>>fieldLabel: '비밀번호',
					fieldLabel: '<?=_text('MN00185')?>',
					name: 'password'
				},{
					allowBlank: false,
					inputType: 'password',
					//>>fieldLabel: '비밀번호 확인',
					fieldLabel: '<?=_text('MN00187')?>',
					name: 'password_valid'
				},
			<?php
			}
			?>
			{
				allowBlank: false,
				//>>fieldLabel: '이 름',
				fieldLabel: '<?=_text('MN00196')?>',
				name: 'name'
				<?php
				if($_POST['action'] == 'edit') echo ", value: '".$user['user_nm']."'";
				?>
			},{
				//>>fieldLabel: '부 서',
				fieldLabel: '<?=_text('MN00181')?>',
				name: 'dept_nm'
				<?php
				if($_POST['action'] == 'edit') echo ", value: '".$user['dept_nm']."'";
				?>
			},{
				//>>fieldLabel: '직 위',
				fieldLabel: '<?=_text('MN00260')?>',
				hidden: true,
				name: 'job_position'
				<?php
				if($_POST['action'] == 'edit') echo ", value: '".$user['job_position']."'";
				?>
			},{
				//>>fieldLabel: '이메일',
				fieldLabel: _text('MN02127'),
				name: 'email'
				<?php
				if($_POST['action'] == 'edit') echo ", value: '".$user['email']."'";
				?>
			},{
				//>>fieldLabel: '전화번호',
				fieldLabel: _text('MN00333'),
				name: 'phone',
				xtype : 'compositefield',
				items : [{
					xtype : 'combo',
					triggerAction: 'all',
					editable: false,
					store : ['010','011','016','017','018','019'],
					//xtype : 'textfield',
					width : 100,
					name : 'phone1'
					<?php
					if($_POST['action'] == 'edit') echo ", value: '".$phone1."'";
					?>
				},{
					xtype : 'displayfield',
					width : 7,
					value : '-'
				},{
					xtype : 'textfield',
					width : 100,
					name : 'phone2'
					<?php
					if($_POST['action'] == 'edit') echo ", value: '".$phone2."'";
					?>
				},{
					xtype : 'displayfield',
					width : 7,
					value : '-'
				},{
					xtype : 'textfield',
					width : 100,
					name : 'phone3'
					<?php
					if($_POST['action'] == 'edit') echo ", value: '".$phone3."'";
					?>
				}]
			},{
				xtype : 'combo',
				fieldLabel : _text('MN02189'),//'언어 선택'
				hiddenName: 'lang',
				hiddenValue: 'value',
				displayField:'name',
				valueField: 'value',
				typeAhead: true,
				triggerAction: 'all',
				lazyRender:true,
				mode: 'local',
				value: '<?=$user['lang']?>',
				editable : false,
				store: new Ext.data.ArrayStore({
						fields: ['name','value'],
						//data: [['한국어', 'ko'], ['English', 'en'], ['日本語', 'ja']]
						data: [['한국어', 'ko'], ['English', 'en']]
				})
			},{
					xtype: 'radiogroup',
					fieldLabel: _text('MN02319'),
					name: 'top_menu_mode_user',
					id: 'top_menu_mode_id_user',
					allowBlank: false,
					items: [
						{boxLabel: _text('MN02320'), id:'top_menu_mode_b_user', name: 'top_menu_mode_user', inputValue: 'B'},
						{boxLabel: _text('MN02321'),id:'top_menu_mode_s_user', name: 'top_menu_mode_user', inputValue: 'S'}
					],
					listeners: {
						render: function(self){
							<?php if($_POST['action'] == 'add'){?>
								self.onSetValue('top_menu_mode_b_user', true);
							<?php }else{?>
								if('<?=$user['top_menu_mode']?>' == 'S'){
								self.onSetValue('top_menu_mode_s_user', true);
								}else{
									self.onSetValue('top_menu_mode_b_user', true);
								}
							<?php }?>


						}

					}
			},{
					xtype: 'radiogroup',
					hidden: true,
					fieldLabel: _text('MN02379'),
					name: 'action_icon_slide_user',
					id: 'action_icon_slide_user',
					allowBlank: false,
					items: [
						{boxLabel: _text('MN00001'), id:'action_icon_slide_yes_user', name: 'action_icon_slide', inputValue: 'Y'},
						{boxLabel: _text('MN00002'),id:'action_icon_slide_no_user', name: 'action_icon_slide', inputValue: 'N'}
					],
					listeners: {
						render: function(self){
							<?php if($_POST['action'] == 'add'){?>
								self.onSetValue('action_icon_slide_yes_user', true);
							<?php }else{?>
								if('<?=$user['action_icon_slide_yn'] ?>' == 'Y'){
									self.onSetValue('action_icon_slide_yes_user', true);
								}else{
									self.onSetValue('action_icon_slide_no_user', true);
								}
							<?php }?>

						}

					}
			}
			/*{
				//>>fieldLabel: '유효일시',
				fieldLabel: '<?=_text('MN00334')?>',
				xtype: 'datefield',
				name: 'expired_date',
				format: 'Y-m-d',
				listeners: {
					render: function(self){
						self.setValue(new Date().add(Date.YEAR, 5).format('Y-m-d'));
					}
				}
			}*/]
		}
		<?php
		if (in_array(ADMIN_GROUP, $_SESSION['user']['groups']) || 
			in_array(CHANNEL_GROUP, $_SESSION['user']['groups']) || 
			$_SESSION['user']['is_admin'] == 'Y') {
		?>
		,{
			xtype: 'fieldset',
			id:'user_group_info',
			//title: '그룹',
			title: '<?=_text('MN00111')?>',
			style: {
				background: 'white'
			},
			//layout: 'fit',
			//bodyStyle: 'background-color:white;',
			autoScroll: true,
			flex: 1,
			labelWidth: 30,
			height: 200,


			items: {
				xtype: 'checkboxgroup',
				id: 'grant_list',
				name: 'group',
				columns:

				items: [
				<?php
				$checkboxs = array();
				foreach($groups as $group){
					if($_POST['action'] == 'add' && $group['is_default'] == 'Y'){
						$checked = ', checked: true';
					}
					else if($_POST['action'] == 'edit' && in_array($group['member_group_name'], $group_member)){
						$checked = ', checked: true';
					}
					array_push($checkboxs, "{boxLabel: '{$group['member_group_name']}', name: 'g_".str_replace(' ', '_', $group['member_group_name'])."', inputValue: '{$group['member_group_id']}' $checked}");
					$checked = '';
				}
				echo implode(", \n", $checkboxs);
				?>
				]
			}
		}
		<?php
		};
		?>
		],
		buttonAlign: 'center',
		buttons: [{
			<?php
				$add_text = "'"._text('MN00033')."'";
				$edit_text = "'"._text('MN00043')."'";
				if($button_text == $add_text){
			?>
				text : '<span style="position:relative;top:1px;"><i class="fa fa-plus" style="font-size:13px;"></i></span>&nbsp;'+_text('MN00033'),
			<?php }else if($button_text == $edit_text){?>
				text : '<span style="position:relative;top:1px;"><i class="fa fa-edit" style="font-size:13px;"></i></span>&nbsp;'+_text('MN00043'),
			<?php } ?>
			scale: 'medium',
			handler: function(self, e){
				var _groups = Ext.getCmp('grant_list').getValue(),
					groups = [];

				if (_groups.length == 0) {
					//>>Ext.Msg.alert('정보', '그룹을 선택하여 주세요');MN00023, MSG00098
					Ext.Msg.alert('<?=_text('MN00023')?>', '<?=_text('MSG00098')?>');
					return;
				}
				Ext.each(_groups, function(i){
					groups.push(i.getRawValue());
				});

				//>>if(<?=$button_text?>=='추가'){
				if(<?=$button_text?>=='<?=_text('MN00033')?>'){
					var sm = Ext.getCmp('userInfo');
		
	
					self._addUser(groups);	

					Ext.getCmp('user_list').getStore().reload();
				}
				//>>if(<?=$button_text?>=='변경'){
				if(<?=$button_text?>=='<?=_text('MN00043')?>'){
					self._editUser(groups);
					
					Ext.getCmp('user_list').getStore().reload();
				}
			}/* 2010-12-17 매일 EBS 종합정보시스템에서 이용자 정보를 가져오기 때문에 MAM에서는 cj에서 사용된 사용자 선택 리스트 제거*/
			,
			_addUser: function(groups){
				var sm = Ext.getCmp('userInfo');
				var user_form = sm.getForm();

				var add_user_id = sm.get(1).getValue();
				var password_1 = sm.get(2).getValue();
				var password_2 = sm.get(3).getValue();
				var email = sm.get(7).getValue();
				//var phone = sm.get(8).getValue();
				var phone_number = '';
				if( !Ext.isEmpty(user_form.findField('phone1')) ){
					phone_number = user_form.findField('phone1').getValue()+'-'+user_form.findField('phone2').getValue()+'-'+user_form.findField('phone3').getValue();
				}

				var lang = sm.get(9).getValue();
				var dept = sm.get(6).getValue();
				var user_top_menu_mode = Ext.getCmp('top_menu_mode_id_user').getValue().inputValue;
				var action_icon_slide_yn = Ext.getCmp('action_icon_slide_user').getValue().inputValue;

				changeInfo('add',add_user_id, password_1, password_2, email, phone_number, Ext.getCmp('window_manager_member'), sm, groups, lang,user_top_menu_mode, action_icon_slide_yn);
				if(Ext.getCmp('user_list')){
					Ext.getCmp('user_list').getStore().reload();
				}
			},
			_editUser: function(groups){
				var sm = Ext.getCmp('userInfo');
				var user_form = sm.getForm();
				var user_top_menu_mode = Ext.getCmp('top_menu_mode_id_user').getValue().inputValue;
				var action_icon_slide_yn = Ext.getCmp('action_icon_slide_user').getValue().inputValue;
				var phone_number = '';
				if( !Ext.isEmpty(user_form.findField('phone1')) ){
					phone_number = user_form.findField('phone1').getValue()+'-'+user_form.findField('phone2').getValue()+'-'+user_form.findField('phone3').getValue();
				}

				Ext.Ajax.request({
					url: '/store/user/user_oracle.php',
					params: {
						action: 'edit_1',
						member_id: <?=empty($_POST['member_id']) ? '0' : $_POST['member_id'];?>,
						userId: sm.get(1).getValue(),
						pw: sm.get(2).getValue(),
						name: sm.get(2).getValue(),
						dept_nm: sm.get(3).getValue(),
						job_position: sm.get(4).getValue(),
						groups: groups.join(','),
						//expired_date: sm.get(5).getValue().format('YmdHis'),
						email: sm.get(5).getValue(),
						//phone: sm.get(6).getValue(),
						phone: phone_number,
						lang : sm.get(7).getValue(),
						user_top_menu_mode: user_top_menu_mode,
						action_icon_slide_yn: action_icon_slide_yn
					},
					callback: function(opts, success, resp){
						//w.hide();
						if (success) {
							try {
								var r = Ext.decode(resp.responseText);
								if (r.success) {
									Ext.getCmp('user_list').getStore().reload();
									Ext.getCmp('window_manager_member').close();
								} else {
									//>>Ext.Msg.alert('등록 오류', r.msg);
									Ext.Msg.alert('<?=_text('MN00022')?> ', r.msg);
								}
							} catch (e) {
								//>>Ext.Msg.alert('디코드 오류', e);
								Ext.Msg.alert('<?=_text('MN00022')?> ', e);//에러
							}
						} else {
							//>>Ext.Msg.alert('서버 오류', resp.statusText);
							Ext.Msg.alert('<?=_text('MN00022')?> ', resp.statusText);
						}
					}
				});
			}
		},{
			text : '<span style="position:relative;top:1px;"><i class="fa fa-close" style="font-size:13px;"></i></span>&nbsp;'+_text('MN00004'),
			scale: 'medium',
			handler: function(btn, e){
//				var w = btn.ownerCt.ownerCt;
//				if(w.get(0).getForm().isDirty()){
//					/*
//					Ext.Msg.show({
//						buttons: Ext.Msg.OKCANEL,
//						msg: '변경사항이 있습니다. 그래도 취소시겠습니까?'
//
//					})
//					*/
//				}
				Ext.getCmp('window_manager_member').close();
			}
		}]
	});

	return f;
})()