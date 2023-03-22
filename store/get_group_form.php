<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/lib/config.php');
session_start();
require_once($_SERVER['DOCUMENT_ROOT'] . '/lib/functions.php');

try
{
	$_user = $_SESSION['user'];
	$member_group_id = $_POST['member_group_id'];
	$action = $_POST['action'];

	if($action == 'del')
	{
		$r = $db->exec("delete from bc_member_group where member_group_id=$member_group_id");
		$r = $db->exec("delete from bc_member_group_member where member_group_id=$member_group_id");

		die(json_encode(array(
			'success' => true
		)));
	}
	
	

	$groups = $db->queryAll("select * from bc_member_group");

	$group_arr = '';
	foreach($groups as $group) {
		$group_arr.= "{group_name:'".$group['member_group_name']."',group_id:'".$group['member_group_id']."'},";
	}
	$group_arr = rtrim($group_arr,',');

	switch($action)
	{
		case 'add':
			//>>$window_title = "'그룹 추가'"; "'"._text('MN00126')."'";
			//>>$button_text = "'추가'";
			//>>$wait_msg = "'그룹 추가 중입니다...'";

			$window_title = _text('MN00118');
			$button_text = _text('MN00033');
			$wait_msg = _text('MSG00153');
		break;

		case 'edit':
			//>>$window_title = "'그룹 수정'";
			//>>$button_text = "'변경'";
			//>>$wait_msg = "'변경 중입니다...'";

			$window_title = _text('MN00116');
			$button_text = _text('MN00063');
			$wait_msg = _text('MN00118');


			$member_group = $db->queryRow("
				SELECT	A.*
						,(	SELECT	MEMBER_GROUP_NAME 
							FROM	BC_MEMBER_GROUP
							WHERE	MEMBER_GROUP_ID=A.PARENT_GROUP_ID
						) AS PARENT_GROUP_NAME
				FROM	BC_MEMBER_GROUP A
				WHERE	A.MEMBER_GROUP_ID=".$member_group_id."
			");
			if(empty($member_group))
				throw new Exception(_text('MSG00156'), ERROR_QUERY);
		break;

		default:
			throw new Exception(_text('MSG00148'), ERROR_QUERY);
		break;
	}
}
catch(Exception $e)
{
	switch($e->getCode())
	{
		case ERROR_QUERY:
			die(json_encode(array(
				'success' => false,
				'msg' => $e->getMessage().'('.$db->last_query.')'
			)));
		break;
	}
}
?>
(function(){
	var f = new Ext.Window({
		id: 'window_manager_member_group',
		layout: 'fit',
		width: 700,
		height: 240,
		modal: true,
		title: '<?=$window_title?>',
		style: {background : 'white'},
		iconCls: 'ariel_user_add',

		items: [{
			id: 'member_group_form',
			style: {background : 'white'},
			xtype: 'form',
			baseCls: 'x-plain',
			autoScroll: true,
			labelWidth: 120,
			padding: 5,
			defaultType: 'textfield',
			defaults: {
				anchor: '96%'
			},
			url: '/store/group.php',
			items: [{
				xtype: 'hidden',
				name: 'member_group_id',
				value: '<?=$member_group['member_group_id']?>'
			},{
				allowBlank: false,
				//>>fieldLabel: '그룹 이름',
				fieldLabel: _text('MN00117'),
				name: 'name'
				<?php
				if($action == 'edit' && !empty($member_group['member_group_name']))
				{
					echo ",value: '".$member_group['member_group_name']."'";
				}
				?>
			},{
				xtype: 'checkbox',
				//>>fieldLabel: '기본 사용',
				fieldLabel: _text('MN00153'),
				name: 'is_default',
				inputValue: 'Y'
				<?php
				if($action == 'edit' && $member_group['is_default'] == 'Y')
				{
					echo ", checked: true";
				}
				?>
			},{
				xtype: 'textarea',
				//>>fieldLabel: '설명',
				fieldLabel: _text('MN00049'),
				name: 'description'
				<?php
				if($action == 'edit' && !empty($member_group['description']))
				{
					echo ",value: '".esc3($member_group['description'])."'";
				}
				?>
			},{
				xtype : 'combo',
               			hidden: true,
				fieldLabel : _text('MN02380'),//Parent Group
				name: 'parent_group_name',
				displayField:'group_name',
				valueField: 'group_id',
				hiddenName: 'parent_group_id',
				mode: 'local',
				typeAhead: true,
				triggerAction: 'all',
				allowBlank: false,
				editable : false,
				store: new Ext.data.JsonStore({
						fields: ['group_name','group_id'],
						data: [
						<?=$group_arr?>
						]
				})
				<?php
				if($action == 'edit')
				{
					echo ",value: '".esc3($member_group['parent_group_id'])."'";
					echo ",hiddenValue: '".esc3($member_group['parent_group_id'])."'";
				} else if ($action == 'add'){
					echo ",value: '".esc3($groups[0]['member_group_id'])."'";
					echo ",hiddenValue: '".esc3($groups[0]['member_group_id'])."'";
				}
				?>
			}]
		}],

		buttonAlign: 'center',
		buttons: [{
			<?php if($button_text == _text('MN00033')){ ?>
				text : '<span style="position:relative;top:1px;"><i class="fa fa-plus" style="font-size:13px;"></i></span>&nbsp;'+_text('MN00033'),
			<?php }else if($button_text == _text('MN00063')){ ?>
				text : '<span style="position:relative;top:1px;"><i class="fa fa-edit" style="font-size:13px;"></i></span>&nbsp;'+_text('MN00043'),				
			<?php } ?>
			scale: 'medium',
			handler: function(btn, e){
				var f = Ext.getCmp('member_group_form');
				if(!f.getForm().isValid()){
					return;
				}

				var w = Ext.Msg.show({
					iconCls: 'ariel_wait',
					//>>title: '요청',
					title: _text('MN00066'),
					msg: '<?=$wait_msg?>',
					wait: true
				});

				f.getForm().submit({
					params: {
						action: '<?=$action?>'
					},
					success: function(form, action){
						w.hide();
						Ext.getCmp('group_list').getStore().reload();
						Ext.getCmp('window_manager_member_group').close();
					},
					failure: function(form, action){
						delete w;
						btn.enable();
						//>>Ext.Msg.alert('오류', action.result.msg);
						Ext.Msg.alert(_text('MN00022'), action.result.msg);
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
				Ext.getCmp('window_manager_member_group').close();
			}
		}]
	});

	return f;
})()