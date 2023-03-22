<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/lib.php');

$free_space = disk_free_space(ROOT);
$total_space = disk_total_space(ROOT);
$use_space = $total_space-$free_space;
echo "전체용량".formatBytes($total_space)."<br />";
echo "남은용량".formatBytes($free_space)."<br />";
echo "사용용량".formatBytes($use_space)."<br />";

$contents_type = $mdb->queryAll("select content_type_id as id, name from content_type");
?>
{
	layout: 'vbox',
	loadMask: true,
	border: false,
	layoutConfig: {
			align: 'stretch',
			pack: 'start'
	},

	items: [{
			store: new Ext.data.JsonStore({
			fields:['name', 'size', 'use'],
			data: [
				<?php

				foreach($contents_type as $type){
					$total_size = $mdb->queryOne("select sum(m.filesize) from content c, media m where c.content_type_id={$type['id']} and c.content_id=m.content_id");
					if(empty($total_size)) $total_size = 0;
					$t[] = "{name: '".$type['name']."', size: '".$total_size."', use: '".formatBytes($total_size)."'}";
				}
				echo implode(', ', $t);
				?>
			]
		}),
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
				$contents_type_g = $mdb->queryAll("select content_type_id as id, name from content_type");
				foreach($contents_type_g as $type_g){
					$total_size_g = $mdb->queryOne("select sum(m.filesize) from content c, media m where c.content_type_id={$type_g['id']} and c.content_id=m.content_id");
					if(empty($total_size_g)) $total_size_g = 0;
					$t_g[] = "{name: '".$type_g['name']."', size: '".$total_size_g."', use: '".formatBytes($total_size_g)."'}";
				}
				echo implode(', ', $t_g);
				?>
			]
		}),
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