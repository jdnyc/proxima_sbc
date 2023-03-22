<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/lang.php');

switch($_POST['action']){
	case 'add':
		//>>$window_title = "'사용자 추가'";
		//>>$button_text = "'추가'";
		//>>$wait_msg = "'사용자 추가 중입니다...'";
		$window_title = "'임시 사용자 추가'";
		$button_text = "'"._text('MN00033')."'";
		$wait_msg = "'"._text('MSG00152')."'";

		$node_id = $_POST['node_id'];
		if( !empty($node_id) )
		{
			$node_array = explode('-', $node_id);
			$category_id = $node_array[0];//상위 카테고리 아이디
		}
	break;

}
?>
(function(){
	var f = new Ext.Window({
		id: 'window_manager_member',
		width: 450,
		height: 300,
		resizable: false,
		modal: true,
		layout: 'fit',

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
			defaults: {
				msgTarget: 'under',
				anchor: '90%',
				width: '90%'
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
				vtype: 'alphanum',
				emptyText: '영문,숫자로만 입력해주세요',
				enableKeyEvents: true,
				value: 'w',
				listeners : {
					keyup : function(self, e){						
						var value = self.getValue();						
						if( Ext.isEmpty(value) || value == 'w' )
						{
							self.setValue('w');							
						}
						else if( value.substr(0,1) != 'w' )
						{							
							value = value.substr(1,value.length-1);
							self.setValue('w'+value);
						}						
					}
				}
				
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
				hidden: true,
				//>>fieldLabel: '직 위',
				fieldLabel: '<?=_text('MN00260')?>',
				name: 'job_position'
				<?php
				if($_POST['action'] == 'edit') echo ", value: '".$user['job_position']."'";
				?>
			},{
				//>>fieldLabel: '유효일시',
				fieldLabel: '<?=_text('MN00334')?>',
				xtype: 'datefield',
				name: 'expired_date',
				format: 'Y-m-d',
				listeners: {
					render: function(self){
						self.setValue(new Date().add(Date.MONTH, 6).format('Y-m-d'));
					}
				}
			}]
		}
		],
		buttonAlign: 'center',
		buttons: [{
			text: <?=$button_text?>,
			handler: function(self, e){

				//>>if(<?=$button_text?>=='추가'){
				if(<?=$button_text?>=='<?=_text('MN00033')?>'){
					self._addUser();
				}
				//>>if(<?=$button_text?>=='변경'){
				if(<?=$button_text?>=='<?=_text('MN00043')?>'){
					self._editUser(groups);
				}
			}
			,
			_addUser: function(groups){
				var sm = Ext.getCmp('userInfo');

				<?php
				if($_POST['action'] == 'add')
				{
				?>
					if(sm.get(2).getValue() != sm.get(3).getValue()){
						//>>Ext.Msg.alert('알림', '비밀번호가 일치하지 않습니다.');MSG00100
						Ext.Msg.alert('<?=_text('MN00021')?>', '<?=_text('MSG00100')?>');
						return;
					}
				<?php
				}
				?>
				if(!(sm.getForm().isValid())){
//					Ext.Msg.alert('알림', '모든 항목은 필수 입력입니다.');
					return;
				}
				//console.log(sm.getForm().isValid());

				var w = Ext.Msg.show({
					iconCls: 'ariel_wait',
					//>>title: '등록 요청',
					title: '<?=_text('MN00335')?>',
					msg: <?=$wait_msg?>,
					wait: true
				});

				//user = sm.getSelected();

				Ext.Ajax.request({
					url: '/store/user/temp_user_action.php',
					params: {
						action: 'add',
						type: 'temp',
						userId: sm.get(1).getValue(),
						pw: sm.get(2).getValue(),
						name: sm.get(4).getValue(),
						dept_nm: sm.get(5).getValue(),
						job_position: sm.get(6).getValue(),
						groups: '',
						category_id: '<?=$category_id?>',
						expired_date: sm.get(7).getValue().format('YmdHis')
					},
					callback: function(opts, success, resp){
						w.hide();
						if (success) {
							try {
								var r = Ext.decode(resp.responseText);
								if (r.success) {
									var tree = Ext.getCmp('nav_tab').get(1).get(0);
									var root = tree.getRootNode();
									tree.getLoader().load(root);

									Ext.getCmp('window_manager_member').close();
								} else {
									//>>Ext.Msg.alert('등록 오류', r.msg);
									Ext.Msg.alert(_text('MN00022'), r.msg);
								}
							} catch (e) {
								//>>Ext.Msg.alert('디코드 오류', e);
								Ext.Msg.alert(_text('MN00022'), e);
							}
						} else {
							//>>Ext.Msg.alert('서버 오류', resp.statusText);
							Ext.Msg.alert(_text('MN00022'), resp.statusText);
						}
					}
				});
			}

			,
			_editUser: function(groups){
				var sm = Ext.getCmp('userInfo');

				Ext.Ajax.request({
					url: '/store/user/user_oracle.php',
					params: {
						action: 'edit',
						member_id: <?=empty($_POST['member_id']) ? '0' : $_POST['member_id'];?>,
						userId: sm.get(1).getValue(),
						name: sm.get(2).getValue(),
						dept_nm: sm.get(3).getValue(),
						job_position: sm.get(4).getValue(),
						groups: groups.join(','),
						expired_date: sm.get(5).getValue().format('YmdHis')
					},
					callback: function(opts, success, resp){
						//w.hide();
						if (success) {
							try {
								var r = Ext.decode(resp.responseText);
								if (r.success) {
									Ext.getCmp('window_manager_member').close();
								} else {
									//>>Ext.Msg.alert('등록 오류', r.msg);
									Ext.Msg.alert('<?=_text('MN00022')?> ', r.msg);
								}
							} catch (e) {
								//>>Ext.Msg.alert('디코드 오류', e);
								Ext.Msg.alert('<?=_text('MN00022')?> ', e);
							}
						} else {
							//>>Ext.Msg.alert('서버 오류', resp.statusText);
							Ext.Msg.alert('<?=_text('MN00022')?> ', resp.statusText);
						}
					}
				});
			}
		},{
			//>>text: '취소',
			text: _text('MN00004'),
			handler: function(btn, e){
				Ext.getCmp('window_manager_member').close();
			}
		}]
	});

	return f;
})()