<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/lib.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');

if ( empty( $_POST['flag'] ) )
{
	$flag = '프로그램명';
}
else
{
	$flag = $_POST['flag'];
}

if( $flag == 'broadymd' )
{
	$start_date = $_POST['start_date'];
	$end_date = $_POST['end_date'];
	$search = "방송일자";
	$status = "0";

	try
	{
		$query = "
		select
			 distinct SUBSTR(mv.value, 0, 4) as value
		from
			meta_value mv,
			meta_field mf,
			content c
		where
			mv.meta_field_id = mf.meta_field_id
		 and mf.name = '$search' and c.content_id = mv.content_id
		and c.status='$status'
		and c.user_id='admin'
		and c.created_time between $start_date and $end_date
		order by value asc
		";//검색 조건의 값들을 중복제거 해서 가져옴

		$columnlist = $db->queryAll( $query );

	//	print_r($columnlist );
		$columns = array();
		foreach($columnlist as $item)
		{
			if( !empty($item['value']) )
			{
				array_push($columns , "'".$item['value']."'" );
			}
		}
		$column =  '['.join(',', $columns).']';

//		echo $column;
//		exit;

	}
	catch (Exception $e)
	{
		echo $e->getMessage();
	}

}

if( empty($_POST['start_date']) )
{
	$start_date = date('Y-m-d');
}
else
{
	$start_date = date('Y-m-d', strtotime( substr($_POST['start_date'],0,8 ) ) );
}

if( empty($_POST['end_date'] ) )
{
	$end_date = date('Y-m-d');
}
else
{
	$end_date = date('Y-m-d', strtotime( substr($_POST['end_date'],0,8 ) ) );
}

if( empty($_POST['start_date']) )
{
	$start_date_p = date('Ymd000000');
}
else
{
	$start_date_p = $_POST['start_date'];
}

if( empty($_POST['end_date'] ) )
{
	$end_date_p = date('Ymd240000');
}
else
{
	$end_date_p = $_POST['end_date'];
}

?>
(function() {

	<?php
	if( $flag == 'broadymd' )
	{
	?>
	var structure = {
        search: ['프로그램명'],
        ingest_source: ['broadymd']
    },// 테잎종류 컬럼
    broad_array = <?=$column?>,
	fields = [],
    columns = [],
    data = [],
    continentGroupRow = [],
    timeGroupRow = [];

	 function generateConfig(){

        Ext.iterate(structure, function(continent, times){

			if( continent == 'search' )
			{

				continentGroupRow.push({
					header: times,
					align: 'center',
					colspan: times.length
				});
				Ext.each(times, function(time){
					timeGroupRow.push({
						header: '',
						colspan: 1,
						align: 'center'
					});

					fields.push({
						type: 'string',
						name: 'field'
					});
					columns.push({
						dataIndex: 'field',
						header: '검색 목록',
						width: 400
					});
				});
			}
			else if( continent == 'ingest_source' )
			{
				continentGroupRow.push({
					header: continent,
					align: 'center',
					colspan: 13
				});
				Ext.each(times, function(time){
					if( time == 'broadymd' )
					{
						timeGroupRow.push({
							header: '방송연도',
							colspan: <?=count( $columns )?>,
							align: 'center'
						});
						Ext.each(broad_array, function(ana){
							fields.push({
								type: 'int',
								name: time + ana
							});
							columns.push({
								dataIndex: time + ana,
								header: ana+'년'
							});
						});
					}
				});

			}
        })
    }


	<?php
	}
	else
	{
	?>

	 var structure = {
        search: ['프로그램명'],
        ingest_source: ['Ana-Beta', 'Digi-Betacam', 'HD'],
		run_time: ['R/T 합계']
    },// 테잎종류 컬럼
    ana_array = ['10분', '20분', '30분', '60분', '90분'],
	digi_array = ['10분', '30분', '60분', '90분', '120분'],
	hd_array = ['40분', '60분', '90분', '120분'],
	fields = [],
    columns = [],
    data = [],
    continentGroupRow = [],
    timeGroupRow = [];

	function mapping(value)
	{
		var hour   = (parseInt(value) / 3600);
			hour	= parseInt(hour);
			hour = String.leftPad(hour, 2, '0');
		var	min = ((parseInt(value) % 3600) / 60);
			min = parseInt(min);
			min = String.leftPad(min, 2, '0');
		var	sec = ((parseInt(value) % 3600) % 60);
			sec = parseInt(sec);
			sec = String.leftPad(sec, 2, '0');
		return String(hour)+":"+String(min)+":"+String(sec);
	}

    function generateConfig(){

        Ext.iterate(structure, function(continent, times){

			if( continent == 'search' )
			{

				continentGroupRow.push({
					header: times,
					align: 'center',
					colspan: times.length
				});
				Ext.each(times, function(time){
					timeGroupRow.push({
						header: '',
						colspan: 1,
						align: 'center'
					});

					fields.push({
						type: 'string',
						name: 'field'
					});
					columns.push({
						dataIndex: 'field',
						header: '검색 목록',
						width: 400
					});


				});
			}
			else if( continent == 'ingest_source' )
			{
				continentGroupRow.push({
					header: continent,
					align: 'center',
					colspan: 13
				});
				Ext.each(times, function(time){
					if( time == 'Ana-Beta' )
					{
						timeGroupRow.push({
							header: time,
							colspan: 5,
							align: 'center'
						});
						Ext.each(ana_array, function(ana){
							fields.push({
								type: 'int',
								name: time + ana
							});
							columns.push({
								dataIndex: time + ana,
								header: ana
							});
						});
					}
					else if( time == 'Digi-Betacam' )
					{
						timeGroupRow.push({
							header: time,
							colspan: 5,
							align: 'center'
						});
						Ext.each(digi_array, function(digi){
							fields.push({
								type: 'int',
								name: time + digi
							});
							columns.push({
								dataIndex: time + digi,
								header: digi
							});
						});
					}
					else if( time == 'HD' )
					{
						timeGroupRow.push({
							header: time,
							colspan: 4,
							align: 'center'
						});
						Ext.each(hd_array, function(hd){
							fields.push({
								type: 'int',
								name: time + hd
							});
							columns.push({
								dataIndex: time + hd,
								header: hd
							});
						});
					}
				});

			}
			else if( continent == 'run_time' )
			{
				continentGroupRow.push({
					header: continent,
					align: 'center',
					colspan: times.length
				});
				Ext.each(times, function(time){
					timeGroupRow.push({
						header: '',
						colspan: 1,
						align: 'center'
					});

					fields.push({
						type: 'int',
						name: time
					});
					columns.push({
						dataIndex: time,
						header: time,
						width: 150,
						renderer: mapping
					});
				});
			}
        })
    }

<?php
}
?>
    generateConfig(); //필드 컬럼 만들기

    var group = new Ext.ux.grid.ColumnHeaderGroup({
        rows: [timeGroupRow]
    }); //상위 컬럼
	var s_date= new Date();
	var ingest_work_store = new Ext.data.JsonStore({
		proxy: new Ext.data.HttpProxy({
			url: '/store/statistics/ingest/ingest_work_statistic_store.php',
			timeout: 3600000
		}),
		root: 'rows',
	//	autoLoad: true,
		fields: fields,
		totalProperty: 'total',
		listeners: {
			beforeload: function(self, opts){

				opts.params = opts.params || {};

				Ext.apply(opts.params, {
					start_date: Ext.getCmp('ingest_start_date').getValue().format('Ymd000000'),
					end_date:  Ext.getCmp('ingest_end_date').getValue().format('Ymd240000'),
					search: '<?=$flag?>'
				});
			}
		}
	});

	return {
		xtype: 'grid',
		id: 'ingest_work',
		loadMask: true,
		store: ingest_work_store,
		columns: columns,
		viewConfig: {
			forceFit: true
		},
		plugins: group,
		tbar: [{
			xtype: 'combo',
			id: 'ingest_work_combo',
			width: 100,
			mode: 'local',
			triggerAction: 'all',
			editable: false,
			displayField: 'd',
			valueField: 'v',
			value: '<?=$flag?>',
			emptyText: '검색명을 선택하세요',
			store: new Ext.data.ArrayStore({
				fields: [
					'd', 'v'
				],
				data: [
					['프로그램명','프로그램명'],
					['영역','category'],
					['작업자','인코딩 작업자'],
					['방송연도','broadymd'],
					['등록일자','date']
				]
			}),
			listeners: {
				select: {
					fn: function(self, record, index){
						Ext.Ajax.request({
							url: '/store/statistics/ingest/ingest_work_statistic.php',
							params: {
								start_date: Ext.getCmp('ingest_start_date').getValue().format('Ymd000000'),
								end_date: Ext.getCmp('ingest_end_date').getValue().format('Ymd240000'),
								flag: self.getValue()
							},
							callback: function(o, s, r){
								Ext.getCmp('admin_contain').removeAll();
								Ext.getCmp('admin_contain').add(Ext.decode(r.responseText));
								Ext.getCmp('admin_contain').doLayout();
							}
						});
					}
				}
			}
		},'-',
		'기간: ',
		{
			xtype: 'datefield',
			id: 'ingest_start_date',
			editable: false,
			format: 'Y-m-d',
			value: '<?=$start_date?>'
		},
		'부터',
		{
			xtype: 'datefield',
			id: 'ingest_end_date',
			editable: false,
			format: 'Y-m-d',
			value: '<?=$end_date?>'
		},'-',{
			icon: '/led-icons/find.png',
			text: '조회',
			handler: function(btn, e){

				if(Ext.getCmp('ingest_work_combo').getValue() == 'broadymd')
				{
					Ext.Ajax.request({
						url: '/store/statistics/ingest/ingest_work_statistic.php',
						params: {
							start_date: Ext.getCmp('ingest_start_date').getValue().format('Ymd000000'),
							end_date: Ext.getCmp('ingest_end_date').getValue().format('Ymd240000'),
							flag: Ext.getCmp('ingest_work_combo').getValue()
						},
						callback: function(o, s, r){
							Ext.getCmp('admin_contain').removeAll();
							Ext.getCmp('admin_contain').add(Ext.decode(r.responseText));
							Ext.getCmp('admin_contain').doLayout();
							Ext.getCmp('ingest_work').getStore().load({
								params: {
									start_date: '<?=$start_date_p?>',
									end_date: '<?=$end_date_p?>',
									search: Ext.getCmp('ingest_work_combo').getValue()
								}
							});
						}
					});
				}
				else
				{
					Ext.getCmp('ingest_work').getStore().load({
						params: {
							start_date: Ext.getCmp('ingest_start_date').getValue().format('Ymd000000'),
							end_date: Ext.getCmp('ingest_end_date').getValue().format('Ymd240000'),
							search: Ext.getCmp('ingest_work_combo').getValue()
						}
					});
				}
			}
		},{
			xtype: 'tbseparator',
			width: 20
		},
		{
			xtype: 'button',
			icon: '/led-icons/disk.png',
			text: '엑셀로 저장',
			handler: function(btn, e){
				var start_date = Ext.getCmp('ingest_start_date').getValue().format('Ymd000000');
				var end_date = Ext.getCmp('ingest_end_date').getValue().format('Ymd240000');
				var search = Ext.getCmp('ingest_work_combo').getValue();
				window.location.href = '/store/statistics/ingest/ingest_work_statistic_excel.php?search='+search+'&start_date='+start_date+'&end_date='+end_date;
			}
		}]
    };
})()