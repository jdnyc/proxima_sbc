<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/lang.php');

$total = $db->queryOne("select  count(*)
						from bc_media m, bc_content c
						where  c.content_id=m.content_id
						and m.media_type='nearline'
						and c.bs_content_id='506'");

$del_suc = $db->queryOne("select  count(*)
						  from bc_media m, bc_content c
						  where  c.content_id=m.content_id
						  and m.media_type='nearline'
						  and m.status=1
						  and c.bs_content_id='506'");
?>
(function(){

	var delete_inform_size = 100;
	var delete_store = new Ext.data.JsonStore({
		url:'/store/delete_informDB_original.php',
		root: 'data',
		totalProperty: 'total',
		fields: [
			{name: 'title'},
			{name: 'contentsType'},
			{name: 'contentsID'},
			{name:'mediaType'},
			{name:'path'},
			{name:'created_time',type:'date',dateFormat:'YmdHis'},
			{name: 'delete_date', type: 'date',dateFormat: 'YmdHis'},
			{name: 'status',type:'text'},
			{name: 'delete_result'},
			//{name:'IsDeleted'},
			{name: 'id'}
		],
		listeners: {
			beforeload: function(self, opts){
				opts.params = opts.params || {};

				Ext.apply(opts.params, {
					start_date: Ext.getCmp('start_date').getValue().format('Ymd000000'),
					end_date: Ext.getCmp('end_date').getValue().format('Ymd240000')
				});
			}
		}
	});

	return {
		border: false,
		loadMask: true,
		frame:true,
		width:800,
		//>>tbar: [' 삭제여부: ',{
		tbar: [' <?=_text('MN00133')?>: ',{
			xtype:'combo',
			id:'delete_combo',
			mode:'local',
			width:80,
			triggerAction:'all',
			editable:false,
			displayField:'d',
			valueField:'v',
			//>>emptyText:'검색',
			emptyText:'<?=_text('MN00037')?>',
			store: new Ext.data.ArrayStore({
				fields:[
					'd','v'
				],
				data:[
				//>>['전체보기','전체보기'],
				//>>['삭제성공','삭제성공'],
				//>>['삭제실패','삭제실패']
					['<?=_text('MN00246')?>','<?=_text('MN00246')?>'],
					['<?=_text('MN00129')?>','<?=_text('MN00129')?>'],
					['<?=_text('MN00130')?>','<?=_text('MN00130')?>']
				]
			}),
			listeners:{
				select:{
					fn:function(self,record,index){
						var search_val = Ext.getCmp('delete_combo').getValue();
					}
				}
			}
		//>>},'-','삭제된 날짜 : ',{
		},'-','<?=_text('MN00106')?> : ',{
			xtype: 'datefield',
			id: 'start_date',
			editable: true,
			format: 'Y-m-d',
			listeners: {
				render: function(self){
					var d = new Date();

					self.setMaxValue(d.format('Y-m-d'));
					self.setValue(d.add(Date.MONTH, -12).format('Y-m-d'));
				}
			}
		},
		//>>'부터'
		'<?=_text('MN00183')?>'
		,{
			xtype: 'datefield',
			id: 'end_date',
			editable: true,
			format: 'Y-m-d',
			listeners: {
				render: function(self){
					var d = new Date();

					self.setMaxValue(d.format('Y-m-d'));
					self.setValue(d.format('Y-m-d'));
				}
			}
		},'-',{
			icon: '/led-icons/find.png',
			//>>text: '조회',
			text: '<?=_text('MN00059')?>',
			handler: function(btn, e){
				var search_val = Ext.getCmp('delete_combo').getValue();
				//>>if((search_val=='전체보기')||Ext.isEmpty(search_val))
				if((search_val=='<?=_text('MN00246')?>')||Ext.isEmpty(search_val))
				{
					Ext.getCmp('delete_inform_id').getStore().load({
						params:{
							start:0,
							limit:delete_inform_size
						}

					});
				}
				else
				{
					Ext.getCmp('delete_inform_id').getStore().load({
						params:{
							index:1,
							start:0,
							limit:delete_inform_size,
							search_val:search_val
						}
					});
				}
			}
		}],
		xtype: 'grid',
		id: 'delete_inform_id',
		loadMask: true,
		columnWidth: 1,
		store: delete_store,

		listeners: {
			viewready: function(self){
				self.store.load({
					params: {
						start: 0,
						limit: delete_inform_size,
						start_date: Ext.getCmp('start_date').getValue().format('Ymd000000'),
						end_date: Ext.getCmp('end_date').getValue().format('Ymd240000')
					}
				})
			}
		},
		cm: new Ext.grid.ColumnModel({
			defaults:{
				sortable: true
			},

			columns: [
				new Ext.grid.RowNumberer(),
//>>				{header: '제목', dataIndex: 'title', align:'center',sortable:'true',width:200},
//>>				{header: '종류', dataIndex: 'contentsType', align:'center',sortable:'true',width:100},
//>>				{header: '미디어 경로', dataIndex: 'path', align:'center', sortable:'true',width:250},
//>>				//{header: '콘텐츠 ID', dataIndex: 'contentsID', align:'center',sortable:'true',width:70},
//>>				//{header: '저장경로', dataIndex: 'mediaType', align:'center',sortable:'true',width:70},
//>>				//{header: '존재유무', dataIndex: 'IsDeleted', align:'center',sortable:'true'},
//>>				{header: '생성일자', dataIndex: 'created_time', align:'center', renderer: Ext.util.Format.dateRenderer('Y-m-d'),sortable:'true',width:100},
//>>				{header: '삭제일자', dataIndex: 'delete_date', align:'center', renderer: Ext.util.Format.dateRenderer('Y-m-d h:m:i'),sortable:'true',width:150},
//>>				{header: '삭제결과', dataIndex: 'delete_result', align:'center',sortable:'true',width:70},
//>>				{header: '실패사유', dataIndex: 'status', align:'center',sortable:'true',width:150}
				{header: 'id', dataIndex: 'id',hidden:true},
				{header: '<?=_text('MN00249')?>', dataIndex: 'title', align:'center',sortable:'true',width:200},
				{header: '<?=_text('MN00255')?>', dataIndex: 'contentsType', align:'center',sortable:'true',width:100},
				{header: '<?=_text('MN00172')?>', dataIndex: 'path', align:'center', sortable:'true',width:250},
				//{header: '<?=_text('MN00287')?>', dataIndex: 'contentsID', align:'center',sortable:'true',width:70},
				//{header: '<?=_text('MN00242')?>', dataIndex: 'mediaType', align:'center',sortable:'true',width:70},
				//{header: '<?=_text('MN00064')?>', dataIndex: 'IsDeleted', align:'center',sortable:'true'},
				{header: '<?=_text('MN00108')?>', dataIndex: 'created_time', align:'center', renderer: Ext.util.Format.dateRenderer('Y-m-d'),sortable:'true',width:100},
				{header: '<?=_text('MN00105')?>', dataIndex: 'delete_date', align:'center', renderer: Ext.util.Format.dateRenderer('Y-m-d h:m:i'),sortable:'true',width:150},
				{header: '<?=_text('MN00127')?>', dataIndex: 'delete_result', align:'center',sortable:'true',width:70},
				{header: '<?=_text('MN00209')?>', dataIndex: 'status', align:'center',sortable:'true',width:150}
			]
		}),

		view: new Ext.ux.grid.BufferView({
			rowHeight: 18,
			scrollDelay: false
		}),

		bbar: new Ext.PagingToolbar({
			store: delete_store,
			pageSize: delete_inform_size,
			items:[{
				xtype:'tbtext',
				pageX:'100',
				pageY:'100',
				//>>text: '(총 미디어 수: <?=$total?>  |  삭제된 미디어 수: <?="<font color=red>".$del_suc."</font>"?>  |  존재하는 미디어의 수: <?="<font color=blue>".($total-$del_suc)."</font>"?>)'
				text: '(<?=_text('MN00244')?>: <?=$total?>  |  <?=_text('MN00133')?>: <?="<font color=red>".$del_suc."</font>"?>  |  <?=_text('MN00254')?>: <?="<font color=blue>".($total-$del_suc)."</font>"?>)'
			}]
		})

	}
})()