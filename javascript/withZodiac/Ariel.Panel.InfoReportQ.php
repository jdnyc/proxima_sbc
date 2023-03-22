/**
 * 2015-10-21 proxima_zodiac
 */
Ext.ns('Ariel.Panel.InfoReportQ');
(function() {

    function showTaskDetail(workflow_id, content_id) {

        Ext.Ajax.request({
            url: '/javascript/ext.ux/viewInterfaceWorkFlow.php',
            params: {
                workflow_id: workflow_id,
                content_id: content_id
            },
            callback: function (options, success, response) {
                if (success) {
                    try {
                        Ext.decode(response.responseText);
                    } catch (e) {
                        Ext.Msg.alert(e.name, e.message);
                    }
                } else {
                    Ext.Msg.alert( _text('MN01098'), response.statusText);//'서버 오류'
                }
            }
        });
    }

	function showDetail(artcl_id){
		Ext.Ajax.request({
            url: '/store/request_zodiac/get_article.php',
            params: {
                artcl_id : artcl_id,
				action : 'show_detail'
            },
            callback: function (options, success, response) {
                if (success) {
                    try {
                        r = Ext.decode(response.responseText);
						if(r.success){
							Ext.Ajax.request({
								url: '/javascript/withZodiac/viewDetailArticle.php',
								params: {
									artcl_id : artcl_id,
									data : Ext.encode(r)
								},
								callback: function (options, success, response) {
									if (success) {
										try {
											Ext.decode(response.responseText);
										} catch (e) {
											Ext.Msg.alert(e.name, e.message);
										}
									} else {
										Ext.Msg.alert( _text('MN01098'), response.statusText);//'서버 오류'
									}
								}
							});
						}
                    } catch (e) {
                        Ext.Msg.alert(e.name, e.message);
                    }
                } else {
                    Ext.Msg.alert( _text('MN01098'), response.statusText);//'서버 오류'
                }
            }
        });
	}

    Ariel.Panel.InfoReportQ = Ext.extend(Ext.grid.GridPanel, {

        initComponent: function (config) {
            var _this = this;

			if(this.gridtype == 'listArticle'){
				 var store_article = new Ext.data.JsonStore({
					url: '/store/request_zodiac/get_article.php',
					root: 'data',
					//autoLoad : true,
					fields: [
						'artcl_id', 'apprv_div_nm', 'artcl_div_nm', 'artcl_titl',  'prd_div_nm', 'inputr_nm', 'video_count', 'grphc_count',
						'issu_nm', 'artcl_fld_nm','artcl_frm_cd','artcl_frm_nm', 'rptr_nm'
					],
					listeners: {
						beforeload: function(self, opts){
							opts.params = opts.params || {};
							Ext.apply(opts.params, {
								action : 'list_article',
								search : Ext.encode(Ext.getCmp('search_form').getForm().getValues())
							});
						},
						load: function(store, records, opts){
						}
					}
				});
				Ext.apply(this, {
					defaults: {
						border: false,
						margins: '10 30 10 10'
					},
					frame: true,
					//title: '일반기사목록',
					//flex: 1,
					loadMask: true,
					store: store_article,
					viewConfig: {
						forceFit: true,
						emptyText: '데이터가 없습니다.'
					},
					listeners: {
						viewready: function (self) {

						},
						rowclick: function (self, row_index, e) {
							Ext.getCmp('grid_detail').getStore().load();
						},
						rowdblclick : function(self, row_index, e){
							if(Ext.isEmpty(Ext.getCmp('grid_article').getSelectionModel().getSelected().get('artcl_id'))){
								Ext.Msg.alert( _text('MN00023'),'기사 정보가 없습니다.');
							}else{
								showDetail(Ext.getCmp('grid_article').getSelectionModel().getSelected().get('artcl_id'));
							}
						}
					},
					colModel: new Ext.grid.ColumnModel({
						defaults: {
							//menuDisabled: true
						},
						columns: [
							new Ext.grid.RowNumberer(),
							{header: 'id', dataIndex: 'artcl_id', width: 120, hidden : true},
							{header: '상태', dataIndex: 'apprv_div_nm', width: 50},
							{header: '이슈', dataIndex: 'issu_nm', width: 50},
							{header: '대분류', dataIndex: 'artcl_fld_nm', width: 50},
							{header: '제목', dataIndex: 'artcl_titl'},
							{header: '형식', dataIndex: 'artcl_frm_nm', width: 50},
							{header: '기자', dataIndex: 'rptr_nm', align: 'center', width: 50},
							{header: '영상수', dataIndex: 'video_count', align: 'center', width: 50},
							{header: '그래픽수', dataIndex: 'grphc_count', align: 'center', width: 50}
						]
					}),
					sm: new Ext.grid.RowSelectionModel({
						singleSelect: true
					}),
					bbar: {
						xtype: 'paging',
						pageSize: 20,
						displayInfo: true,
						store: store_article
					}
				}, config || {});
			}else if(this.gridtype == 'listImage'){
				var store_detail = new Ext.data.JsonStore({
					url: '/store/request_zodiac/get_article.php',
					root: 'data',
					//autoLoad : true,
					fields: [
						'artcl_id', 'plyout_ord', 'media_nm', 'part',  'snd_st', 'plyout_yn', 'plyout_id'
					],
					listeners: {
						beforeload:	function(self, opts){
							var sel	= Ext.getCmp('grid_article').getSelectionModel().getSelected();
							if(sel){
								self.baseParams.action	= 'list_detail';
								self.baseParams.artcl_id = sel.get('artcl_id');
							}
						},
						load: function(store, records, opts){
							//myMask.hide();
						}
					}
				});

				Ext.apply(this, {
					defaults: {
						border: false,
						margins: '10 30 10 10'
					},
					frame: true,
					title: '영상 매칭 항목',
					//flex: 1,
					loadMask: true,
					store: store_detail,
					viewConfig: {
						forceFit: true,
						emptyText: '데이터가 없습니다.'
					},
					listeners: {
						viewready: function (self) {

						},

						rowcontextmenu: function (self, row_index, e) {
							e.stopEvent();

							self.getSelectionModel().selectRow(row_index);

							var rowRecord = self.getSelectionModel().getSelected();
							var workflow_id = rowRecord.get('workflow_id');
							var content_id = rowRecord.get('content_id');


							var menu = new Ext.menu.Menu({
								items: [{
									text: '작업흐름보기',
									icon: '/led-icons/chart_organisation.png',
									handler: function (btn, e) {
										showTaskDetail(workflow_id, content_id);
										menu.hide();
									}
								}, {
									hidden: true,
									text: '작업완료 시키기',
									icon: '/led-icons/chart_organisation.png',
									handler: function (btn, e) {

										// console.log(workflow_id, content_id);

										// manualTaskComplete(workflow_id, content_id);
										menu.hide();
									}
								}]
							});
							menu.showAt(e.getXY());
						},

						rowdblclick: function (self, row_index, e) {
							var rowRecord = self.getSelectionModel().getSelected();
							var workflow_id = rowRecord.get('workflow_id');
							var content_id = rowRecord.get('content_id');

							showTaskDetail(workflow_id, content_id);
						}
					},
					colModel: new Ext.grid.ColumnModel({
						defaults: {
							//menuDisabled: true
						},
						columns: [
							new Ext.grid.RowNumberer(),
							{header: 'id', dataIndex: 'artcl_id', width: 120, hidden : true},
							{header: '순서', dataIndex: 'plyout_ord', width: 50},
							{header: '제목', dataIndex: 'media_nm', width: 50},
							{header: '영상 종류', dataIndex: 'part', width: 50},
							{header: '영상 길이', dataIndex: 'snd_st'},
							{header: '전송여부', dataIndex: 'plyout_yn', width: 50},
							{header: '송출아이디', dataIndex: 'plyout_id', align: 'center', width: 50}
						]
					}),
					sm: new Ext.grid.RowSelectionModel({
						singleSelect: true
					}),
					bbar: {
						xtype: 'paging',
						pageSize: 5,
						displayInfo: true,
						store: store_detail
					}
				}, config || {});
			}else {
				//프로그램 grid store
				 var store_program = new Ext.data.JsonStore({
					url: '/store/request_zodiac/get_article.php',
					root: 'data',
					autoLoad : true,
					fields: [
						'rd_id', 'brdc_dt', 'brdc_start_clk', 'brdc_pgm_nm'
					],
					listeners: {
						beforeload: function(self, opts){
							opts.params = opts.params || {};
							var search_value;
							if(Ext.getCmp('search_form_q')){
								search_value = Ext.encode(Ext.getCmp('search_form_q').getForm().getValues());
							}else{
								search_value = '';
							}
							Ext.apply(opts.params, {
								action : 'list_program',
								search : Ext.encode(Ext.getCmp('search_form_q').getForm().getValues())
							});
						},
						load: function(store, records, opts){
						}
					}
				});

				Ext.apply(this, {
					defaults: {
						border: false,
						margins: '10 30 10 10'
					},
					frame: true,
					//title: '일반기사목록',
					//flex: 1,
					loadMask: true,
					store: store_program,
					viewConfig: {
						forceFit: true,
						emptyText: '데이터가 없습니다.'
					},
					listeners: {
						viewready: function (self) {

						},
						rowclick: function (self, row_index, e) {
							Ext.getCmp('grid_detail').getStore().load();
						},
						rowdblclick : function(self, row_index, e){
							if(Ext.isEmpty(Ext.getCmp('grid_article').getSelectionModel().getSelected().get('artcl_id'))){
								Ext.Msg.alert( _text('MN00023'),'기사 정보가 없습니다.');
							}else{
								showDetail(Ext.getCmp('grid_article').getSelectionModel().getSelected().get('artcl_id'));
							}
						}
					},
					colModel: new Ext.grid.ColumnModel({
						defaults: {
							//menuDisabled: true
						},
						columns: [
							new Ext.grid.RowNumberer(),
							{header: 'id', dataIndex: 'rd_id', width: 120, hidden : true},
							{header: '프로그램명', dataIndex: 'brdc_pgm_nm', width: 50},
							{header: '방송일', dataIndex: 'brdc_dt', width: 50},
							{header: '방송시각', dataIndex: 'brdc_start_clk', width: 50},
							{header: '기사수', dataIndex: 'rd_id', hidden : true}
						]
					}),
					sm: new Ext.grid.RowSelectionModel({
						singleSelect: true
					}),
					bbar: {
						xtype: 'paging',
						pageSize: 20,
						displayInfo: true,
						store: store_program
					}
				}, config || {});
			}

            Ariel.Panel.InfoReportQ.superclass.initComponent.call(this);

			this.on('render', this._init);
        },
		_init : function(){

		}
    });

    Ext.reg('tab_article_q', Ariel.Panel.InfoReportQ);
})();