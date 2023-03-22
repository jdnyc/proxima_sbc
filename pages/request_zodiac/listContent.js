// 서버사이드 php 파일에서 불러들여 {{}}안의 변수를 php변수로 치환한후 클라이언트사이드에 응답으로 주는 구조이다
{
	xtype : 'panel',
	layout : 'border',
	align:'stretch',
	border: false,

	items : [{
		xtype: 'form',
		id : 'form_search'+{{$tab_id}},
		frame: false,
		border : false,
		region : 'north',
		style: {
      paddingTop: '15px',
			background : '#f0f0f0'
    },
		flex : 1,
		height : 110,
		border: false,
		labelWidth: 1,
		bodyStyle:{"background-color":"#f0f0f0"},
		defaults: {
			//labelStyle: 'text-align:center;',
			anchor: '100%'
		},
		autoScroll: true,
		items:[{
			xtype: 'compositefield',
			items:[{
				xtype: 'displayfield',
				width : {{$labelwidth}},
				value: '<div align="right">'+_text('MN00249')+'&nbsp;</div>'//제목
			},{
				xtype:'textfield',
				name : 'search_title',
				width : 235,
				listeners: {
					specialkey: function (field, e) {
						if (e.getKey() == e.ENTER) {
							Ext.getCmp('contentgrid_west'+{{$tab_id}}).getStore().load();
						}
					}
				}
			}]
		},{
			xtype: 'compositefield',
			items:[{
				xtype: 'displayfield',
				width : {{$labelwidth}},
				value: '<div align="right">'+'미디어ID'+'&nbsp;</div>'//미디어아이디
			},{
				xtype:'textfield',
				name : 'search_media',
				width : 235,
				listeners: {
					specialkey: function (field, e) {
						if (e.getKey() == e.ENTER) {
							Ext.getCmp('contentgrid_west'+{{$tab_id}}).getStore().load();
						}
					}
				}
			}]
		},{
			xtype: 'compositefield',
			labelWidth: 1,
			items:[{
				xtype: 'displayfield',
				width : {{$labelwidth}},
				value: '<div align="right">'+_text('MN00109')+'&nbsp;</div>'//등록일
			},{
				xtype: 'datefield',
				width : 110,
				name : 'search_start',
				format: 'Y-m-d',
				listeners: {
					render: function(self){
						var d = new Date();
						self.setMaxValue(d.format('Y-m-d'));
						self.setValue(d.format('Y-m-d'));
					}
				}
			},{
				xtype: 'displayfield',
				width : 5,
				value: '~'
			},{
				xtype: 'datefield',
				width : 110,
				name : 'search_end',
				format: 'Y-m-d',
				listeners: {
					render: function(self){
						var d = new Date();
						self.setMaxValue(d.format('Y-m-d'));
						self.setValue(d.format('Y-m-d'));
					}
				}
			},{
				xtype: 'button',
				//width : 60,
				//icon: '/led-icons/find.png',
				id: 'news_video_graph_search_btn',
				//text :  '<span style="position:relative;top:1px;"><i class="fa fa-search" style="font-size:13px;"></i></span>&nbsp;'+_text('MN00059'),//조회
				cls: 'proxima_button_customize',
				width: 30,
				text: '<span style="position:relative;top:1px;" title="'+_text('MN00059')+'"><i class="fa fa-search" style="font-size:13px;color:white;"></i></span>',
				handler: function(self, e){
					Ext.getCmp('contentgrid_west'+{{$tab_id}}).getStore().load();
				}
			}]
		},{
			xtype: 'compositefield',
			labelWidth: 1,
			items:[{
				hidden:true,
				xtype: 'displayfield',
				width : {{$labelwidth}},
				value: '<div align="right">'+_text('MN00197')+'&nbsp;</div>'//사용자 정의 콘텐츠
			},{
				xtype: 'combo',
				width : 170,
				name : 'search_type',
				triggerAction: 'all',
				editable: false,
				mode: 'local',
				store: new Ext.data.JsonStore({
					autoLoad: true,
					url: '/store/request_zodiac/request_list.php',
					baseParams: {
						action : 'ud_content',
						tab_id : {{$tab_id}}
					},
					root: 'data',
					idProperty: 'module_info_id',
					fields: [
						{name: 'name', type: 'string'},
						{name: 'value', type: 'int'}
					]
				}),
				hidden:true,
				mode: 'remote',
				hiddenName: 'search_type',
				hiddenValue: 'value',
				valueField: 'value',
				displayField: 'name',
				typeAhead: true,
				triggerAction: 'all',
				forceSelection: true,
				editable: false,
				value : _text('MN00008'),//'전체'
				listeners: {
					select: function (cmb, record, index) {
					},
					afterrender: function(self){
						self.setValue('9');
					}
				}
			}]
		}]
	},{
		xtype: 'contentgrid2',
		region : 'center',
		id : 'contentgrid_west'+{{$tab_id}},
		cls: 'proxima_contentgrid_media2 proxima_grid_header2',
		border: false,
		loadMask: true,
		mode: 'list',
		tools: [{
			id: 'detail',
			//>> qtip: '상세 보기',
			qtip: _text('MN00061'),
			handler: function(e, toolEl, p, tc){
				p.mode = 'list';
				p.getView().changeTemplate();
				p.init_tools(p);
			}
		},{
			id: 'tile',
			//>> qtip: '썸네일 보기',
			qtip: _text('MN01037'),
			handler: function(e, toolEl, p, tc){
				p.mode = 'thumb';
				p.getView().changeTemplate(p.template.tile);
				p.loadDDfile(p);
				p.init_tools(p);
			}
		},{
			id: 'refresh20',
			//>> qtip: '새로고침',
			qtip: _text('MN00390'),
			handler: function(e, toolEl, p, tc){
				p.store.reload();
			}
		}],
		//2015-12-30 현재 상태 표시
		init_tools : function(target) {
			//console.log("init_tools");
			var self = this;
			var tools_els = self.tools;
			for (var i in tools_els) {

				if(!Ext.isEmpty(tools_els[i]))
				{
					var tar = Ext.get(tools_els[i]);
					tar.dom.style.color = "";
					tar.dom.style.color = "#000000";
				}

				var first_mode = self.mode;
				if(first_mode == 'thumb')
				{
					first_mode = 'tile';
				}
				else if(first_mode == 'mix')
				{
					first_mode = 'list';
				}
				else if(first_mode == 'list')
				{
					first_mode = 'detail';
				}

				var tar = Ext.get(tools_els[first_mode]);
				tar.dom.style.color = "#0099DA";
			}
		},
		template: {
			//섬네일보기
			tile: new Ext.XTemplate(
				'<div class="x-grid3-row ux-explorerview-large-icon-row2" style="height:115px !important; width: 150px; margin-left: 20px;">',
					'<table class="x-grid3-row-table">',
						'<tbody>',
							'<tr colspan="2"  height="{{$thumb_height - 30}}">',
							'<td colspan="2" class="x-grid3-col x-grid3-cell ux-explorerview-icon" style=" vertical-align:middle;" align="center">',
								'<tpl if="bs_content_id == {{MOVIE}}   || bs_content_id == {{SEQUENCE}} ">',
									'<tpl if="Ext.isEmpty(thumb)"><img id ="thumb-{content_id}" onload="resizeImg(this, {w:145, h:84})" src="/img/incoming_proxy.png" alt="No IMAGE"></tpl>',
									'<tpl if="!Ext.isEmpty(thumb)">',
									'<tpl if="is_working==0">',
										'<img id ="thumb-{content_id}" align="center" style=" vertical-align:middle"  onload="resizeImg(this, {w:145, h:84})" ext:qtip="{qtip}" onerror="fallbackImg(this)" ext:qwidth={{CONFIG_QTIP_WIDTH}} src="{thumb_web_root}/{thumb}">',
									'</tpl>',
									'<tpl if="is_working==1"><img id ="thumb-{content_id}" onload="resizeImg(this, {w:145, h:84})" src="/img/incoming_proxy.png" alt="No IMAGE"></tpl>',

									'</tpl>',
								'</tpl>',
								'<tpl if="bs_content_id == {{SOUND}}">',
									'<img onload="resizeImg(this)" src="/img/audio_thumb.png" alt="No IMAGE">',
								'</tpl>',
								'<tpl if="bs_content_id == {{DOCUMENT}}">',
									'<tpl if="!Ext.isEmpty(thumb)">',
									'<img align="center" style=" vertical-align:middle"  onload="resizeImgs(this, \'{thumb_web_root}/{thumb}\' , {w: 150, h: 84 } )" ext:qtip="{qtip}" ext:qwidth={{CONFIG_QTIP_WIDTH_DOCUMENT}} src="{thumb_web_root}/{thumb}">',
									'</tpl>',
									'<tpl if="Ext.isEmpty(thumb)">',
										'<img onload="resizeImg(this)" ext:qtip="{qtip}" ext:qwidth={{CONFIG_QTIP_WIDTH_DOCUMENT}}  src="/img/doc_thumb.png" alt="No IMAGE">',
									'</tpl>',
								'</tpl>',
								'<tpl if="bs_content_id == {{IMAGE}}">',
								'<tpl if="!Ext.isEmpty(thumb) && thumb != \'/img/incoming_proxy.png\'">',
								'<div   ext:qtip="{qtip}"  ext:qwidth= {proxy_width} draggable="true"  id ="thumb-{content_id}"  style="height:83px;width:150px;background-image:url(\'{thumb_web_root}/{thumb}\');background-position:center center;background-repeat:no-repeat;background-size:contain;text-align:center;" ></div>',
								'</tpl>',
								'<tpl if="Ext.isEmpty(thumb) || thumb === \'/img/incoming_proxy.png\'">',
									'<img onload="resizeImg(this, {w:145, h:84})" src="/img/incoming_proxy.png" alt="No IMAGE">',
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
					gtWidth: function(url, thumb_web_root){
						var imgObj = new Image();
						imgObj.src = thumb_web_root+'/' + url;

						if (imgObj.width == 0 || imgObj.height == 0) return 0;
						if ( (imgObj.width/{{$thumb_width}}) > (imgObj.height/{{$thumb_height}}) ) {
							return 1;
						} else {
							return -1;
						}
					}
				}
			)
		},
		 listeners: {
    		//2015-12-30 현재 상태 표시
			afterrender : function(self) {
				self.getView().changeTemplate();
				self.init_tools(self);
			},
			rowcontextmenu: function (self, row_index, e) {
				e.stopEvent();
	
				self.getSelectionModel().selectRow(row_index);
	
				var rowRecord = self.getSelectionModel().getSelected();
	
				var menu = new Ext.menu.Menu({
				  items: [{
					text: '뉴스부조 전송',
					handler: function (btn, e) {
						menu.hide();
						transmissionAction(rowRecord.get('content_id'),'transmission_zodiac_news');
					}},{
					text: 'AB부조 전송',
					handler: function (btn, e) {
						menu.hide();
						transmissionAction(rowRecord.get('content_id'),'transmission_zodiac_ab');
					}
				  }]
				});
				menu.showAt(e.getXY());
			  },
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
						search : Ext.encode(Ext.getCmp('form_search'+{{$tab_id}}).getForm().getValues())

					});
				}
			},

			baseParams: {
				tab_id : {{$tab_id}},
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
				{name: 'thumb_web_root'},
				{name: 'proxy_width'},
				{name: 'is_working'},
				{name: 'ud_content_title'},
				{name: 'sys_video_rt'},
				{name: 'created_date', type:'date', dateFormat: 'YmdHis'},
				{name: 'media_id'},
				{name: 'progrm_nm'},
				{name: 'content_status'},
				{name: 'scr_trnsmis_sttus'},
				{name: 'scr_news_trnsmis_sttus'},
				{name: 'scr_trnsmis_ty'}
			
			]
		}),
		cm: new Ext.grid.ColumnModel({
			defaults: {
				sortable: false
			},

			columns: [
				//new Ext.grid.CheckboxSelectionModel()
				new Ext.grid.RowNumberer(),
				/*'제목'*/{header: _text('MN00249'), dataIndex: 'title' , width: 250},
				/*'전송여부'*/
				{
					header: '뉴스부조', dataIndex: 'content_status', width: 80, align:'center',
					renderer: function (v) {
						var sttus = v.scr_news_trnsmis_sttus;
						var states = {
							'request': '요청중',
							'processing': '처리중',
							'assigning': '처리중',
							'queue': '대기중',
							'complete': '성공',
							'error': '실패',
							'canceled': '취소',
							'cancel': '취소',
							'retry': '재시도',
							'delete': '삭제'
						};
						if(v.scr_trnsmis_ty == 'news' || v.scr_trnsmis_ty == 'all' || !Ext.isEmpty(v.scr_news_trnsmis_sttus)){							
							if (states.hasOwnProperty(sttus)) {
								return states[sttus];
							};
						}else{
							return '';
						}
					}
				},{
					header: 'A/B부조', dataIndex: 'content_status', width: 80, align:'center',
					renderer: function (v) {
						var sttus = v.scr_trnsmis_sttus;
						var states = {
							'request': '요청중',
							'processing': '처리중',
							'assigning': '처리중',
							'queue': '대기중',
							'complete': '성공',
							'error': '실패',
							'canceled': '취소',
							'cancel': '취소',
							'retry': '재시도',
							'delete': '삭제'
						};
						if(v.scr_trnsmis_ty == 'ab' || Ext.isEmpty(v.scr_trnsmis_ty) || v.scr_trnsmis_ty == 'all' || !Ext.isEmpty(v.scr_trnsmis_sttus)) {							
							if (states.hasOwnProperty(sttus)) {
								return states[sttus];
							};
						}else{
							return '';
						}
					}
				},
				/*'미디어아이디'*/{header : '미디어ID', dataIndex : 'media_id', width: 115, align : 'center'},
				/*'등록일'*/{header: _text('MN00109'), dataIndex: 'created_date', width:80, renderer: Ext.util.Format.dateRenderer('Y-m-d'), align : 'center'},
				/*'프로그램명'*/{header : '프로그램명', dataIndex : 'progrm_nm', width: 135,hidden:true, align : 'left'},
				/*'콘텐츠 유형'*/{header : _text('MN00276'), dataIndex : 'ud_content_title', width: 95, align : 'center', hidden:true},
				{header: 'content_id', dataIndex: 'content_id', width: 50,hidden : true},

			]
		})
	}]
}
