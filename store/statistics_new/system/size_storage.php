<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/lib.php');

$free_space = disk_free_space(ROOT);
$total_space = disk_total_space(ROOT);
$use_space = $total_space-$free_space;
//echo "전체용량".formatBytes($total_space)."<br />";
//echo "남은용량".formatBytes($free_space)."<br />";
//echo "사용용량".formatBytes($use_space)."<br />";

//Ext.chart.Chart.CHART_URL = '/ext/resources/charts.swf';
?>

  
{
	width: 400,
	height: 400,
	loadMask: true,
	border: false,
	items: {
		store: new Ext.data.JsonStore({
			fields:['name', 'size', 'use'],
			data: [
				{name:'남은용량', size: '<?=($free_space)?>', use: '<?=formatBytes($free_space)?>'},
				{name:'사용용량', size: '<?=($use_space)?>', use:'<?=formatBytes($use_space)?>'}
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

			var rangeMsg = '전체용량 : <?=formatBytes($total_space)?>'
			var sizeMsg = seriesData.name + ' : ' + seriesData.use + slicePct;		

			return rangeMsg + '\n' + sizeMsg;
		},
		xtype: 'piechart',
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
					family: 'Tahoma',
					size: 13
				}
			}
		}
	}
}

