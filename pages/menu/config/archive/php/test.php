(function(){
	Ext.ns('Ariel.ArchiveConfig');                
        
	Ariel.ArchiveConfig = Ext.extend(Ext.Panel, {
		layout: 'border',
		border: false,

		initComponent: function(config){
			Ext.apply(this, config || {});

			Ext.apply(this, {
				items: [{
					region: 'center',
					xtype: 'grid',
                                        layout : 'fit',
                                        id: 'archive_config',
                                        autoScroll: true,
                                        store : new Ext.data.JsonStore({
                                            url : '/pages/menu/config/archive/php/get_archive_config.php',
                                            totalProperty: 'total',
                                            root: 'data',
                                            fields: [
                                                {name : 'category_id'},
                                                {name : 'category_title'},
                                                {name : 'arc_method'},
                                                {name : 'arc_period'},
                                                {name : 'del_period'},                
                                                {name : 'del_method'},
                                                {name : 'edit_date'}
                                            ]
                                        }),
                                        columns: [
                                            new Ext.grid.RowNumberer(),
                                            {header: '카테고리 ID', dataIndex: 'category_id', width: 70},
                                            {header: '<center>카테고리명</center>', dataIndex: 'category_title', width: 100},
                                            {header: '아카이브 방법', dataIndex: 'arc_method', width: 90},
                                            {header: '아카이브 기간', dataIndex: 'arc_period', width: 90},
                                            {header: '삭제 방법', dataIndex: 'del_method', width: 70},
                                            {header: '삭제 기간', dataIndex: 'del_period', width: 70},
                                            {header: '수정 일자', dataIndex: 'edit_date', width: 90}
                                        ]
                                }]
			});

			Ariel.ArchiveConfig.superclass.initComponent.call(this);
		}		
	});
 	return new Ariel.ArchiveConfig();
})()