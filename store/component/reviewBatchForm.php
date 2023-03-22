<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/libs/functions.php');

$user_name = $_SESSION['user']['KOR_NM'];
?>
{
	xtype: 'combo',
	id: 'review_status',
	allowBlank: false,
	mode: 'local',
	triggerAction: 'all',
	typeAhead: true,
	editable: false,
	fieldLabel: '심의상태',
	emptyText: '심의상태를 선택하세요',
	store:  [
		[2, '심의비대상'],
		[3, '심의대기중'],
		[4, '승인'],
		[5, '반려'],
		[6, '조건부승인']
	]
},{
	xtype: 'textarea',
	id: 'review_comment',
	fieldLabel: '심의결과 내용',
	height: 100
},{
	fieldLabel: '심의자',
	name: 'manager',
	disabled: true,
	value: '<?=$user_name?>'
}