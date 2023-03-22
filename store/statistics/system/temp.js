(function(){
	var ststore = new new Ext.data.JsonStore({
		root: 'data',
		url: '',
		autoLoad: true,
		fields:['name', 'size', 'use']

	});

	return{
		layout: 'vbox',
		loadMask: true,
		border: false,
		layoutConfig: {
				align: 'stretch',
				pack: 'start'
		},
		
		items: [{
			
			tipRenderer : function(chart, record, index, series) {
				var seriesData = record.data;
				var size = 0;
				Ext.each(series.data, function(obj) {
					size += parseInt(obj.size);
				});

				var slicePct = (seriesData.size/size) * 100;
				slicePct = ' (' + slicePct.toFixed(2) + '%)';

				var rangeMsg = '콘텐츠 타입 : ' + seriesData.name;
				var sizeMsg = 'Size : ' + seriesData.use + slicePct;		

				return rangeMsg + '\n' + sizeMsg;
			},
			xtype: 'piechart',
			flex: 2,
			dataField: 'size',
			categoryField: 'name',
			extraStyle:
			{
				legend:
				{
					display: 'bottom',
					padding: 5,
					font:
					{
						family: 'arial',
						size: 12
					}
				}
			}
		},{
			xtype: 'grid',
			flex: 1,
			title: '콘텐츠 타입별 사용 용량',
			store: new Ext.data.JsonStore({
				fields:['name', 'size', 'use'],
				data: [
					<?php
					
					foreach($contents_type as $type){
						$total_size = $mdb->queryOne("select sum(m.filesize) from content c, media m where c.content_type_id={$type['id']} and c.content_id=m.content_id");
						if(empty($total_size)) $total_size = 0;
						$t[] = "{name: '".$type['name']."', size: '".$total_size."', usecount: '".formatBytes($total_size)."'}";
					}
					echo implode(', ', $t);
					?>
				]
			})	
			border: false,
			ref: 'grid',
			columns: [{
				
				align: 'center',
				xtype: 'gridcolumn',							
				header: 'name',
				sortable: true
			},{
				align: 'center',
				xtype: 'gridcolumn',
				header: 'size',
				sortable: true	
			},{
				align: 'center',
				xtype: 'gridcolumn',
				header: 'use',
				sortable: true	
			}],
			viewConfig: {
				forceFit: true
			}
		}]
}