<?php
$fieldProgramCode = "{
	xtype: 'compositefield',
	fieldLabel: '프로그램코드',
	items: [{
		xtype: 'textfield',
		name: '{$v['meta_value_id']}',
		anchor: '100%',
		value: '{$v['value']}'
	}]
}";
?>