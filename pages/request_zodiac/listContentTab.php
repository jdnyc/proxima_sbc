<?php
// 2019.12.08 hkkim 더이상 사용하지 않음
/*
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');

?>
{
	xtype : 'panel',
	layout : 'border',
	align:'stretch',
	title : '비디오',
	items : [{
		xtype: 'form',
		id : 'form_search',
		frame: true,
		border : false,
		region : 'north',
		width: 350,
		//flex : 1,
		height : 115,
		border: false,
		labelWidth: 1,
		defaults: {
			//labelStyle: 'text-align:center;',
			anchor: '100%'
		},
		autoScroll: true,
		items:[{
			xtype: 'compositefield',
			width: 350,
			items:[{
				xtype: 'displayfield',
				width : width_label,
				value: '<div align="right">제목</div>'
			},{
				xtype:'textfield',
				name : 'search_title',
				width : 205
			}]
		},{
			xtype: 'compositefield',
			labelWidth: 1,
			items:[{
				xtype: 'displayfield',
				width : width_label,
				value: '<div align="right">등록일</div>'
			},{
				xtype: 'datefield',
				width : width_combo,
				name : 'search_start',
				format: 'Y-m-d',
				listeners: {
					render: function(self){
						var d = new Date();
						self.setMaxValue(d.format('Y-m-d'));
						self.setValue(d.add(Date.DAY, -7).format('Y-m-d'));
					}
				}
			},{
				xtype: 'displayfield',
				width : 5,
				value: '~'
			},{
				xtype: 'datefield',
				width : width_combo,
				name : 'search_end',
				format: 'Y-m-d',
				listeners: {
					render: function(self){
						var d = new Date();
						self.setMaxValue(d.format('Y-m-d'));
						self.setValue(d.format('Y-m-d'));
					}
				}
			}]
		},{
			xtype: 'compositefield',
			labelWidth: 1,
			items:[{
				xtype: 'displayfield',
				width : width_label,
				value: '<div align="right">콘텐ㄴㄴ츠구분</div>'
			},{
				xtype: 'combo',
				width : 130,
				name : 'search_type',
				triggerAction: 'all',
				editable: false,
				mode: 'local',
				store: new Ext.data.JsonStore({
					autoLoad: true,
					url: '/store/request_zodiac/request_list.php',
					baseParams: {
						action : 'ud_content'
					},
					root: 'data',
					idProperty: 'module_info_id',
					fields: [
						{name: 'name', type: 'string'},
						{name: 'value', type: 'int'}
					]
				}),
				mode: 'remote',
				hiddenName: 'search_type',
				hiddenValue: 'value',
				valueField: 'value',
				displayField: 'name',
				typeAhead: true,
				triggerAction: 'all',
				forceSelection: true,
				editable: false,
				value : '전체',
				listeners: {
					select: function (cmb, record, index) {
					}
				}
			},{
				xtype: 'displayfield',
				width : 5,
				value: ''
			},{
				xtype: 'button',
				width : width_label,
				icon: '/led-icons/find.png',
				text : '조회',
				handler: function(self, e){
					Ext.getCmp('contentgrid_west').getStore().reload();
				}
			}]
		}]
	},{
		xtype: 'contentgrid',
		region : 'center',
		id : 'contentgrid_west',
		border: false,
		loadMask: true,
		 tools: [{
			id: 'detail',
			//>> qtip: '상세 보기',
			qtip: '리스트 보기',
			handler: function(e, toolEl, p, tc){
				p.mode = 'list';
				p.getView().changeTemplate();
			}
		},{
			id: 'tile',
			//>> qtip: '미리 보기',
			qtip: '섬네일 보기',
			handler: function(e, toolEl, p, tc){
				p.mode = 'thumb';
				p.getView().changeTemplate(p.template.tile);
				p.loadDDfile(p);
			}
		},{
			id: 'refresh20',
			//>> qtip: '새로고침',
			qtip: _text('MN00139'),
			handler: function(e, toolEl, p, tc){
				p.store.reload();
			}
		}],
		template: {
			//섬네일보기
			tile: new Ext.XTemplate(
				'<div class="x-grid3-row ux-explorerview-large-icon-row">',
					'<table class="x-grid3-row-table">',
						'<tbody>',
							'<tr colspan="2" align="center" height="<?=$thumb_height - 30?>">',
							'<td colspan="2" class="x-grid3-col x-grid3-cell ux-explorerview-icon" style=" vertical-align:middle" align="center">',
								'<tpl if="bs_content_id == <?=MOVIE?>   || bs_content_id == <?=SEQUENCE?> ">',
									'<tpl if="Ext.isEmpty(thumb)"><img id ="thumb-{content_id}" onload="resizeImg(this)" src="/img/incoming.jpg" alt="No IMAGE"></tpl>',
									'<tpl if="!Ext.isEmpty(thumb)">',
									'<tpl if="is_working==0">',
										'<img id ="thumb-{content_id}" align="center" style=" vertical-align:middle"  onload="resizeImg(this, {w:150, h:84})" ext:qtip="{qtip}" ext:qwidth=<?=CONFIG_QTIP_WIDTH?> src="{lowres_web_root}/{thumb}">',
									'</tpl>',
									'<tpl if="is_working==1"><img id ="thumb-{content_id}" onload="resizeImg(this)" src="/img/incoming.jpg" alt="No IMAGE"></tpl>',

									'</tpl>',
								'</tpl>',
								'<tpl if="bs_content_id == <?=SOUND?>">',
									'<img onload="resizeImg(this)" src="/img/audio_thumb.png" alt="No IMAGE">',
								'</tpl>',
								'<tpl if="bs_content_id == <?=DOCUMENT?>">',
									'<tpl if="!Ext.isEmpty(thumb)">',
									'<img align="center" style=" vertical-align:middle"  onload="resizeImgs(this, \'{lowres_web_root}/{thumb}\' , {w: 150, h: 84 } )" ext:qtip="{qtip}" ext:qwidth=<?=CONFIG_QTIP_WIDTH_DOCUMENT?> src="{lowres_web_root}/{thumb}">',
									'</tpl>',
									'<tpl if="Ext.isEmpty(thumb)">',
										'<img onload="resizeImg(this)" ext:qtip="{qtip}" ext:qwidth=<?=CONFIG_QTIP_WIDTH_DOCUMENT?>  src="/img/doc_thumb.png" alt="No IMAGE">',
									'</tpl>',
								'</tpl>',
								'<tpl if="bs_content_id == <?=IMAGE?>">',
									'<tpl if="!Ext.isEmpty(thumb)">',
									'<img align="center" style=" vertical-align:middle"  onload="resizeImg(this, {w:150, h:84})"  src="{lowres_web_root}/{thumb}">',
									'</tpl>',
									'<tpl if="Ext.isEmpty(thumb)">',
										'<img onload="resizeImg(this)" src="/img/incoming.jpg" alt="No IMAGE">',
									'</tpl>',
								'</tpl>',


							'</td>',
							'</tr>',
							'<div class="x-grid3-cell-inner" unselectable="on" ><tr colspan="2" >',
								'<td colspan="2" align="center" class="x-grid3-col x-grid3-cell"  ><div class="x-grid3-cell-inner" unselectable="on" >',

								'<tpl if=" ud_content_id == 4000305 ">',
									'{title}<br />{ori_size} / {sysf57055}',
								'</tpl>',

								'<tpl if=" ud_content_id == 4000308 ">',
									'{title}<br />{ori_size}',
								'</tpl>',

								'<tpl if=" ud_content_id == 4000306  ">',
									'{title}<br />{ori_size} / {sysf507}',
								'</tpl>',
								'<tpl if=" ud_content_id == 4000325 ">',
									'{title}<br />{ori_size}',
								'</tpl>',
								'<tpl if=" ud_content_id != 4000305 && ud_content_id != 4000308 && ud_content_id != 4000306 && ud_content_id != 4000325">',
									'<font  title="{register_type_text}" >{title}</font>',
								'</tpl>',
								'</div></td></tr>',
								'<tr colspan="2" ><td colspan="2" align="center" class="x-grid3-col x-grid3-cell" ><div class="x-grid3-cell-inner" unselectable="on" >',
																	'<tpl if=" ud_content_id != 4000406">',
																		'<font title="{register_type_text}" >{usr_materialid}</font>',
																	'</tpl>',
															'</div></td></tr>',
						'</tbody>',
					'</table>',
				'</div>',

				{
					gtWidth: function(url, lowres_web_root){
						var imgObj = new Image();
						imgObj.src = lowres_web_root+'/' + url;

						if (imgObj.width == 0 || imgObj.height == 0) return 0;
						if ( (imgObj.width/<?=$thumb_width?>) > (imgObj.height/<?=$thumb_height?>) )
						{
							return 1;
						}
						else
						{
							return -1;
						}
					}
				}
			)
		},
		loadDDfile: function(self){
			//섬네일이미지에 원본파일 경로로 변경하는 함수  by 이성용

			 if( Ext.isChrome ){
				//크롬일때

				self.getStore().each(function(r){
					//스토어의 각 콘텐츠 별로 경로 매핑

					var content_id = r.get('content_id');
					//원본경로 정보 찾기

					var ori_ext = r.json.ori_ext;

					if( self.ownerCt.title == 'DAS 요청' ){
						ori_ext = 'mov';
					}else{
						ori_ext = 'mxf';
					}

					var ori_path = r.get('ori_path');
					var highres_path = r.json.highres_path;
					//예) application/{확장자}:{파일명}:file:///{풀파일경로}
					var path = 'application/'+ori_ext+':'+content_id+'.'+ori_ext+':file:///Z:/highres/'+content_id+'.'+ori_ext;
					var path = 'application/'+ori_ext+':file:///' + highres_path + '/' + ori_path;

					// var path = 'application/mxf:file:///D:/1234.mxf';
					// path = 'application/1234_2.mxf:file:///C:/1234_2.mxf;'+path;

					//데이터뷰에서 각 이미지에 설정해놓은 ID로 객체를 찾는다
					var thumb_img = document.getElementById('thumb-'+content_id);
					var thumb_list_img= document.getElementById('thumb-list-'+content_id);

					if( !Ext.isEmpty(thumb_img) ){
						//객체가 있을경우 URL 셋팅 - 섬네일용
						thumb_img.addEventListener("dragstart",function(evt){
							evt.dataTransfer.setData("DownloadURL",path);
						},false);
					}

					if( !Ext.isEmpty(thumb_list_img) ){
						//객체가 있을경우 URL 셋팅 - 섬네일리스트용
						thumb_list_img.addEventListener("dragstart",function(evt){
							evt.dataTransfer.setData("DownloadURL",path);
						},false);
					}
				});
			} else {
				//console.log(self.ownerCt.title);
			}

			return true;
		},
		store: new Ext.data.JsonStore({
			url: '/store/request_zodiac/get_content.php',//
			idProperty: 'content_id',
			autoLoad : true,
			totalProperty: 'total',
			root: 'results',
			remoteSort: true,
			sortInfo: {
				field: 'cotnent_id',
				direction: 'asc'
			},
			listeners: {
				beforeload: function(self, opts){
					opts.params = opts.params || {};

					Ext.apply(opts.params, {
						search : Ext.encode(Ext.getCmp('form_search').getForm().getValues())

					});
				}
			},

			baseParams: {
				loc : 'inforeport',
				task: 'listing',
				start: 0,
				limit: 50
			},

			fields: [
				{name: 'content_id'},
				{name: 'title'},
				{name: 'bs_content_id'},
				{name: 'ud_content_id'},
				{name: 'ori_path'},
				{name: 'ori_size'},
				{name: 'ori_status'},
				{name: 'thumb'},
				{name: 'thumb_status'},
				{name: 'proxy_path'},
				{name: 'proxy_status'},
				{name: 'lowres_root'},
				{name: 'lowres_web_root'},
				{name: 'proxy_width'},
				{name: 'is_working'},
				{name: 'created_date', type:'date', dateFormat: 'YmdHis'}
			]
		}),
		cm: new Ext.grid.ColumnModel({
			defaults: {
				sortable: false
			},

			columns: [
				//new Ext.grid.CheckboxSelectionModel()
				new Ext.grid.RowNumberer(),
				{header: '제목', dataIndex: 'title', width: 200},
				{header: '등록일', dataIndex: 'created_date', width: 200, renderer: Ext.util.Format.dateRenderer('Y-m-d')},
				{header : '콘텐츠 구분', dataIndex : 'ud_content_name'},
				{header: 'content_id', dataIndex: 'content_id', width: 200,hidden : true}
			]
		})
	}]
}
*/