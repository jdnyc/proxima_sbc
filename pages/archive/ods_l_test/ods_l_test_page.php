<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
?>

(function(){
	Ext.ns('Ariel.ods_l');

	Ariel.ods_l.Test = Ext.extend(Ext.Panel, {
		id: 'ods_l_test_page',
		title: '<span class="user_span"><span class="icon_title"><i class=""></i></span><span class="main_title_header">ODS_L_Test_Page</span></span>',
		cls: 'grid_title_customize',
		border: false,
		layout: 'fit',
		initComponent: function(config) {
	        Ext.apply(this, config || {});
	        var that = this;

	        this.items=[{
	        	xtype: 'panel',
	        	items: [{
	        		xtype: 'button',
					scale: 'medium',
					text: 'Reset test data',
					handler: function(b, e){
						Ext.Ajax.request({
				            url: '/pages/archive/ods_l_test/ods_l_reset_data.php',
				            params: {
				            },
				            callback: function (options, success, response) {
				                if(success){
				                	alert('Reset data successfully');
				            	}else{
									alert('Reset data failed');
				            	}
				            }
				        });
					}
	        	}]
	    	}];
	    	Ariel.ods_l.Test.superclass.initComponent.call(this);
    	}
	});

	return new Ariel.ods_l.Test();
})()