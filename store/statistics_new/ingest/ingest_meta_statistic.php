<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/lib.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');

$user_id = $_SESSION['user']['user_id'];
$member_id=$db->queryOne("select member_id from member where user_id ='$user_id'");

$meta_tables = $db->queryAll("select * from meta_table where content_type_id='506' order by sort");

$meta_table_body=array();
foreach($meta_tables as $meta_table)
{
	array_push($meta_table_body,"{ id: '".$meta_table['meta_table_id']."', title: '".$meta_table['name']."' }");
}

?>



(function() {

	////////2011-01-24 by lsy
	Ext.ns('Ariel');
	Ariel.cur_page = 1;
	Ariel.total_page = 1;
	Ariel.start = 0;
	Ariel.limit = 20;

	//////////////////////
	var tabs = new Ext.TabPanel({
				id: 'tab_ingest',
				activeTab: 0,

				listeners: {
					tabchange: function(self, p) {
						Ariel.myMask = new Ext.LoadMask(Ext.getBody(), {
							msg:"로딩중입니다..."
						});
						Ariel.myMask.show();
						Ariel.cur_page=1;
						Ext.Ajax.request({
							url: '/store/statistics/ingest/ingest_statistic_treegrid_panel.php',
							params: {
								panel_id: p.id
							},
							callback: function(o, s, r){

								p.removeAll();
								p.add(Ext.decode(r.responseText));
								p.doLayout();
							}
						});

					}
				},
				defaults: {
					layout: 'fit'
				},

				items: [
					<?=join(',',$meta_table_body)?>
				]
			});

		ingest_panel = new Ext.Panel({
				flex: 1,
				layout: 'fit',

				bbar: [{
					//text: '첫페이지',
					icon: '/ext/resources/images/default/grid/page-first.gif',
					handler: function(b, e){
						Ariel.myMask.show();
						Ariel.cur_page = 1;
						var t = Ext.getCmp('ingest_list');
						t.getLoader().baseParams.start =0;
						t.getLoader().load(t.getRootNode());
					}
				},{
					//text: '이전',
					icon: '/ext/resources/images/default/grid/page-prev.gif',
					handler: function(b, e){
						Ariel.myMask.show();
						Ariel.cur_page--;
						var t = Ext.getCmp('ingest_list');
						t.getLoader().baseParams.start -= Ariel.limit;
						t.getLoader().load(t.getRootNode());
					}
				},{
					xtype: 'textfield',
					id: 'start_page',
					width: 40,
					value: 0,
					listeners: {
						specialKey: function(self, e){
							var k = e.getKey();
							var value = self.getValue();
							var start = value*Ariel.limit;
							if (k == e.ENTER)
							{
								Ariel.myMask.show();
								Ariel.cur_page = value;

								var loader = Ext.getCmp('ingest_list').getLoader();
								Ext.apply(loader.baseParams, {
									start_date: Ext.getCmp('start_date').getValue().format('Ymd000000'),
    								end_date: Ext.getCmp('end_date').getValue().format('Ymd240000'),
    								meta_table_id: tabs.getActiveTab().getId(),
    								start: start,
    								limit: Ariel.limit

    							});
								loader.load( Ext.getCmp('ingest_list').getRootNode() );
							}
						}
					}
				},'/','0',{
					//text: '다음',
					icon: '/ext/resources/images/default/grid/page-next.gif',
					handler: function(b, e){
						Ariel.myMask.show();
						var t = Ext.getCmp('ingest_list');
						Ariel.cur_page++;
						t.getLoader().baseParams.start += Ariel.limit;
						Ariel.start +=Ariel.limit;
						t.getLoader().load(t.getRootNode());
					}
				},{
					//text: '마지막페이지',
					icon: '/ext/resources/images/default/grid/page-last.gif',
					handler: function(b, e){
						Ariel.myMask.show();
						Ariel.cur_page = Ariel.total_page;
						var t = Ext.getCmp('ingest_list');
						t.getLoader().baseParams.start =Ariel.total_page*Ariel.limit;
						t.getLoader().load(t.getRootNode());
					}
				},
				{
					xtype: 'tbseparator',
					width: 20
				},
				{
					//text:'새로고침',
                    icon: '/led-icons/arrow_refresh.png',
					handler: function(){
						Ariel.myMask.show();
						Ariel.cur_page=1;

						var loader = Ext.getCmp('ingest_list').getLoader();

                        Ext.apply(loader.baseParams, {
								start_date: Ext.getCmp('start_date').getValue().format('Ymd000000'),
								end_date: Ext.getCmp('end_date').getValue().format('Ymd240000'),
								meta_table_id: tabs.getActiveTab().getId(),
								start: 0,
								limit: Ariel.limit

						});

                        loader.load( Ext.getCmp('ingest_list').getRootNode() );
					}
				}],

				tbar: [' ','기간:',
				{
					xtype: 'datefield',
					id: 'start_date',
					editable: false,
					format: 'Y-m-d',
					listeners: {
						render: function(self){
							var wait_date = new Date();
							self.setMaxValue(wait_date.format('Y-m-d'));
							self.setValue(wait_date.format('Y-m-d'));
							//self.setValue(wait_date.add(Date.DAY, -6).format('Y-m-d'));
						}
					}
				},
				'부터',
				{
					xtype: 'datefield',
					id: 'end_date',
					editable: false,
					format: 'Y-m-d',
					listeners: {
						render: function(self){
							var wait_date = new Date();
							self.setMaxValue(wait_date.format('Y-m-d'));
							self.setValue(wait_date.format('Y-m-d'));
						}
					}
				},'-',{
					icon: '/led-icons/find.png',
					text: '조회',
					handler: function(btn, e){//로더에 날짜 파라미터 셋팅
						Ariel.myMask.show();
						Ariel.cur_page=1;

						var loader = Ext.getCmp('ingest_list').getLoader();

                        Ext.apply(loader.baseParams, {
								start_date: Ext.getCmp('start_date').getValue().format('Ymd000000'),
								end_date: Ext.getCmp('end_date').getValue().format('Ymd240000'),
								meta_table_id: tabs.getActiveTab().getId(),
								start: 0,
								limit: Ariel.limit
						});
                        loader.load( Ext.getCmp('ingest_list').getRootNode() );
					}
				},
				{
					xtype: 'tbseparator',
					width: 20
				},
				{
					xtype: 'button',
					icon: '/led-icons/disk.png',
					text: '엑셀로 저장',
					handler: function(btn, e){
						var start_date = Ext.getCmp('start_date').getValue().format('Ymd000000');
						var end_date = Ext.getCmp('end_date').getValue().format('Ymd240000');
						var meta_table_id = tabs.getActiveTab().getId();
						window.location.href = '/store/statistics/ingest/ingest_meta_statistic_excel.php?meta_table_id='+meta_table_id+'&start_date='+start_date+'&end_date='+end_date+'&start='+Ariel.start+'&limit='+Ariel.limit;
					}
				}],
				items: [
					tabs
				]
			});

	return{
		layout: 'vbox',
		frame: false,
		border: false,
		layoutConfig: {
			align: 'stretch',
			pack: 'start'
		},
		items: [ingest_panel]

	};
})()