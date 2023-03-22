<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
?>

{
	layout: 'border',

	items: [{
		region: 'center',
		xtype: 'grid',

		store: new Ext.data.JsonStore({
			url: '/pages/menu/config/code/php/get.php',
			root: 'data',
			fields: [
				'codeset_id', 'codeset_name'
			]
		}),

		columns: [
			{header: '코드 그룹'}
		]
	},{
		region: 'north'
	}]
}