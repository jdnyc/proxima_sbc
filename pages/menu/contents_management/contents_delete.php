<?php

/////////////////////////////
// 콘텐츠 삭제 관리 보여주는 페이지
// 2011.12.15
// by 허광회
/////////////////////////////

require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/lang.php');
fn_checkAuthPermission($_SESSION);

	$del_complete_code = 'DC'; // 삭제 완료
	$del_error_code = 'DO'; // 삭제 에러
	$del_admin_approve_code = 'DA';//관리자가 삭제 수락
	$del_request_code = 'DR'; // 삭제 요청
	$del_limit_code = 'DL'; //기한만료

// true 이면 사용자 요청은 안보이도록 한다
if(DELETE_USER_REQUEST_FLAG)
{

}

	//config

	$admin_auth_user_del_list = true;

//$total = $db->queryOne("select count(*) from bc_content c");
//$del_suc = $db->queryOne("select  count(*) from bc_content c where  c.IS_DELETED='N' ");
$total = $db->queryOne("select count(*) from bc_content");
$del_suc = $db->queryOne("select  count(*) from bc_media bm where bm.flag ='del_complete_code'");
$content_type = $db->queryAll("
								SELECT  UD_CONTENT_ID,
							        	UD_CONTENT_TITLE
								FROM BC_UD_CONTENT 
								ORDER BY SHOW_ORDER ASC
							");
?>

(function(){
    
	var sortChanges = function(grid,self){
                    var v = Ext.getCmp(deleteInformId).getView();
                    store = Ext.getCmp(deleteInformId).getStore();
                    store.each(function(r){
                        if(r.get('flag') == '삭제 완료')
                        {
                            v.fly(v.getRow(store.indexOf(r))).addClass('status-delete-complete');
                            v.fly(v.getRow(store.indexOf(r))).addClassOnSelect('status-delete-complete');
                            v.fly(v.getRow(store.indexOf(r))).addClassOnOver('status-delete-complete-over');
                        }

                        else if(r.get('flag') == '삭제승인')
                        {
                            v.fly(v.getRow(store.indexOf(r))).addClass('status-delete-approve');
                            v.fly(v.getRow(store.indexOf(r))).addClassOnClick('status-delete-complete');
                           v.fly(v.getRow(store.indexOf(r))).addClassOnOver('status-delete-approve-over');
                        }

                        else if(r.get('flag') == '기한만료')
                        {
                            v.fly(v.getRow(store.indexOf(r))).addClass('status-delete-limit');
                            v.fly(v.getRow(store.indexOf(r))).addClassOnClick('status-delete-complete');
                           v.fly(v.getRow(store.indexOf(r))).addClassOnOver('status-delete-limit-over');
                        }
                        else if(r.get('flag') == '사용자 요청')
                        {
                            v.fly(v.getRow(store.indexOf(r))).addClass('status-delete-request');
                            v.fly(v.getRow(store.indexOf(r))).addClassOnClick('status-delete-complete');
                            v.fly(v.getRow(store.indexOf(r))).addClassOnOver('status-delete-request-over');
                        }
                    });
                }


	var total_list = 0;
	var delete_inform_size = 200;

    var selModel = new Ext.grid.CheckboxSelectionModel();
	//var selModel = new Ext.grid.CheckboxSelectionModel({
	//     singleSelect : false,
	//     checkOnly : true
	// });

	var delete_store = new Ext.data.JsonStore({
		url:'/store/contents_deleted_info.php',
		root: 'data',
		totalProperty : 'total_list',
		idProperty: 'delete_id',
		fields: [
			{name: 'delete_id'},
			{name: 'delete_type'},
			{name: 'content_id'},
			{name: 'ud_content_title'},
			{name: 'bs_content_title'},
			{name: 'category'},
			{name: 'title'},
			{name: 'created_date',type:'date',dateFormat:'YmdHis'},
			{name: 'delete_date', type: 'date',dateFormat: 'YmdHis'},
			{name: 'reg_user_id'},
            {name: 'reg_user_nm'},
			{name: 'expired_date',type: 'date',dateFormat: 'YmdHis'},
			{name: 'file_size'},
			{name: 'flag'},
			{name: 'reason'},
			{name: 'delete_type_name'},
			{name: 'status'},
			'media_id',
            {name: 'del_req_date',type: 'date',dateFormat: 'YmdHis'},
            'del_req_user','del_req_user_nm',
			'path',
			'media_type'
		],

		listeners: {
			beforeload: function(self, opts){
				//opts.params = opts.params || {};

				//Ext.apply(opts.params, {
					//start_date: startDate.getValue().format('Ymd000000'),
					//end_date: endDate.getValue().format('Ymd240000')
				//});

			},
			load: function(self, opts){
				total_list = self.getTotalCount();
				var tooltext = "(  "+_text('MN02164')+" : <font color=blue><b>"+total_list +"</b></font> )";
				Ext.getCmp('toolbartext').setText(tooltext);
			}

			//load: sortChanges
		}
	});

	function note_qtip(value, metaData, record, rowIndex, colIndex, store)
	{
		if( value != '' && value != null)
		{
			metaData.attr = 'ext:qtip="'+value+'"';
		}
		return Ext.util.Format.htmlEncode(value);
	}
	var deleteCombo = new Ext.form.ComboBox({
			xtype:'combo',
			//id:'delete_combo',
			mode:'local',
			width:120,
			triggerAction:'all',
			editable:false,
			displayField:'d',
			valueField:'v',
			value : 'all',
			//>>emptyText:'검색',
			emptyText:'<?=_text('MN00037')?>',
			store: new Ext.data.ArrayStore({
				fields:[
					'd','v'
				],
				data:[
					['<?=_text('MN00244')?>','all'],//전체
					<?php if(!DELETE_USER_REQUEST_FLAG)
					  {
					     echo("["._text('MN02166').",'delete_request'],");//'사용자 요청'
					  }
					?>
					[ _text('MN02169'),'REQUEST'],//'삭제 요청'
					[ _text('MN02170'),'CONFIRM'],//'삭제 승인'
					[ _text('MN02171'),'SUCCESS'],//'삭제 완료'
					[ _text('MN00130'),'FAIL']//'삭제 실패'
				]
			}),
			listeners:{
				select:{
					fn:function(self,record,index){
						var search_val = deleteCombo.getValue();
						var content_type = contentTypeCombo.getValue();
							Ext.getCmp(deleteInformId).getStore().load({
							params: {
								start: 0,
								limit: delete_inform_size,
								date_mode : deleteDateCombo.getValue(),
								start_date: startDate.getValue().format('Ymd000000'),
								end_date: endDate.getValue().format('Ymd240000'),
								action : search_val,
								content_type: content_type
								}
							});
					}
				}
			}
	});
	var deleteDateCombo = new Ext.form.ComboBox({
		
			xtype:'combo',
			//id:'delete_date_combo',
			mode:'local',
			width:140,
			triggerAction:'all',
			editable:false,
			displayField:'d',
			valueField:'v',
			value : '',
			//>>emptyText:'검색',
			emptyText:_text('MSG00026'),//선택해주세요.
			store: new Ext.data.ArrayStore({
				fields:[
					'd','v'
				],
				data:[
					[ _text('MN00244'),'disable'],//전체
					//[ _text('MN01002'),'created_date'],//'등록일자'
					[ '삭제요청일','created_date'],//'삭제요청일'
					[ _text('MN02167'),'expired_date']//,//'만료일자'
					//[ _text('MN02168'),'delete_date']//'구분관련일자'
				]
			}),
			listeners:{
				select:{
					fn:function(self,record,index){
						var search_val = deleteDateCombo.getValue();
						if(search_val == 'disable')
						{
							startDate.disable();
							endDate.disable();

						}else{
								startDate.enable();
								endDate.enable();
								if(search_val == 'created_date' || search_val == 'deleted_date')
								{
									var d = new Date();
									startDate.setMaxValue(d.format('Y-m-d'));
									endDate.setMaxValue(d.format('Y-m-d'));
								}
								else
								{
									startDate.setMaxValue('');
									endDate.setMaxValue('');
								}
						}
					}
				}
			}
		
	});
	var startDate = new Ext.form.DateField({
		xtype: 'datefield',
		//	id: 'start_date',
			width:100,
			disabled : true,
			editable: true,
			format: 'Y-m-d',
			listeners: {
				render: function(self){
					var d = new Date();
					self.setValue(d.add(Date.DAY, -7).format('Y-m-d'));
				}
			}
	});
	var endDate = new Ext.form.DateField({
		xtype: 'datefield',
		//	id: 'end_date',
			width:100,
			editable: true,
			format: 'Y-m-d',
			disabled : true,
			listeners: {
				render: function(self){
					var d = new Date();
					self.setValue(d.format('Y-m-d'));
				}
			}
	});
	var contentTypeCombo = new Ext.form.ComboBox({
		
			xtype:'combo',
		//	id:'content_type_combo',
			mode:'local',
			width:120,
			triggerAction:'all',
			editable:false,
			displayField:'d',
			valueField:'v',
			value : 'all',
			//>>emptyText:'검색',
			emptyText:'<?=_text('MN00037')?>',
			store: new Ext.data.ArrayStore({
				fields:[
					'd','v'
				],
				data:[
					['<?=_text('MN00244')?>','all']
					<?php foreach ($content_type as $type) {
						echo ",[ '".$type['ud_content_title']."','".$type['ud_content_id']."']";
					} ?>
				]
			}),
			listeners:{
				select:{
					fn:function(self,record,index){
						var search_val = deleteCombo.getValue();
						var content_type = contentTypeCombo.getValue();
						Ext.getCmp(deleteInformId).getStore().load({
							params: {
									start: 0,
									limit: delete_inform_size,
									date_mode : deleteDateCombo.getValue(),
									start_date: startDate.getValue().format('Ymd000000'),
									end_date: endDate.getValue().format('Ymd240000'),
									action : search_val,
									content_type: content_type
								}
						});
					}
				}
			}
		
	});
	var deleteSearchTypeCombo = new Ext.form.ComboBox({
		xtype:'combo',
		//id:'delete_search_type_combo',
		mode:'local',
		width:80,
		triggerAction:'all',
		editable:false,
		displayField:'d',
		valueField:'v',
		value:'all',
		store: new Ext.data.ArrayStore({
			fields:[
				'd','v'
			],
			data:[
				['<?=_text('MN00244')?>','all'],//전체
				[ '제목','title'],
				[ '삭제승인자','reg_user_nm'],
				[ '삭제요청자','del_req_user_nm']
			]
		})
	});
	var deleteSearchTypeKeyword = new Ext.form.TextField({
		xtype:'textfield',
			width:100,
		//	id:'delete_search_type_keyword'
	});
	var deleteInformId = Ext.id();
	return {
		border: false,
		cls: 'proxima_customize',
		loadMask: true,
		frame:false,
		width:800,
		//>>tbar: [' 상태 ',{
		tbar: [ _text('MN00138')+' : ',deleteCombo,
			//>>},'-', _text('MN00106')+' : '{//삭제된 날짜
			
		'-', _text('MN00354'),//'날짜'
		deleteDateCombo
		,'-',startDate,
		//>>'부터'
		'<?=_text('MN00183')?>'
		,endDate,'-',
		_text('MN00276')+' : ',contentTypeCombo/*,'-',
		_text('MN00276')+'test : ',{
			xtype: 'treecombo',
			flex: 1,
			id: 'category_ud_content',
			disabled : true,
			fieldLabel: _text('MN00387'),
			name: 'c_category_ud_content',
			//value: title_category['category_full_path'],
			pathSeparator: ' > ',
			rootVisible: true/*,
			
			loader: new Ext.tree.TreeLoader({
				url: '/store/get_categories.php',
				baseParams: {
					action: 'get-folders',
					ud_content_tab: contentTypeCombo.getValue()
				},
				listeners: {
					load: function(self, node, response){
						var path = self.baseParams.path;
						
						if(!Ext.isEmpty(path) && path != '0'){
							path = path.split('/');
							self.baseParams.path = path.join('/');

							var caregory_id, id, n, i;
							caregory_id = path[path.length-1];
							
							//Find id to select. If path is long, many time run this part.
							for(i=1; i<path.length; i++) {
								id = path[path.length -i];
								n = node.findChild('id', id);
								if(!Ext.isEmpty(n)) {
									break;
								}
							}

							if(Ext.isEmpty(n) || node.id === caregory_id) {
								//For root category or find id
								node.select();
								Ext.getCmp('category_test').setValue(caregory_id);
							} else {
								//Expand and search again or select
								if(n && n.isExpandable()){
									n.expand(); //if not find id in this load, then expand(reload)
								}else{
									n.select();
									Ext.getCmp('category_test').setValue(n.id);
								}
							}
						}else{
							node.select();
							Ext.getCmp('category_test').setValue(node.id);
						}
					}
				}
			}),

			root: new Ext.tree.AsyncTreeNode({
				//id: '<?=$root_category_id?>',
				//text: '<?=$root_category_text?>',
				expanded: true
			}),
			listeners: {
				select: function(self, node) {
					Ext.getCmp('category_test').setValue(node.id);
				}
			}
			
		}*/
		// 검색 삭제승인자, 삭제요청자, 제목
		,'-',
		'검색 유형:',
			deleteSearchTypeCombo,
		deleteSearchTypeKeyword
		,'-',{
			//icon: '/led-icons/find.png',
			//>>text: '조회',
			cls: 'proxima_button_customize',
			width: 30,
			text: '<span style="position:relative;top:1px;" title="'+_text('MN00059')+'"><i class="fa fa-search" style="font-size:13px;color:white;"></i></span>',
			handler: function(btn, e){
				var search_val = deleteCombo.getValue();
				//>>if((search_val=='전체보기')||Ext.isEmpty(search_val))
					Ext.getCmp(deleteInformId).getStore().load({
						params: {
								start: 0,
								limit: delete_inform_size,
								date_mode : deleteDateCombo.getValue(),
								start_date: startDate.getValue().format('Ymd000000'),
								end_date: endDate.getValue().format('Ymd240000'),
								action : search_val,
								search_type: deleteSearchTypeCombo.getValue(),
								search_keyword: deleteSearchTypeKeyword.getValue(),
						}

					});

			}
		},{
			icon: '/led-icons/application_get.png',
			hidden : true,
			text: '기한연장',
			handler : function(btn,e){
				var sel = Ext.getCmp(deleteInformId).getSelectionModel().getSelections();
				var sel_id = new Array();
				if(sel.length>0)
				{
					for(var i=0;i<sel.length;i++)
					{
						//flag 값에 대해 처리
						if(sel[i].get('flag') == '삭제완료')
						{
							Ext.Msg.alert('오류','삭제완료 목록은 연장하실 수 없습니다.');
							return;
						}
						<?php
						 if(!DELETE_USER_REQUEST_FLAG){
						 echo("
							else if(sel[i].get('flag') == '사용자 요청')
							{
								Ext.Msg.alert('오류','사용자요청 목록은 연장하실 수 없습니다.');
								return;
							}
							");
						}
						?>
						sel_id.push(sel[i].get('media_id'));
					}

					Ext.Ajax.request({
						url: '/pages/menu/contents_management/contents_extend.php',
						params : {
							ids : Ext.encode(sel_id)
						},
						callback :function(opt, success, resp){
							if(success)
							{
								 Ext.decode(resp.responseText);
							}
							else
							{
								 Ext.Msg.alert( _text('MN01098'), resp.statusText);//'서버 오류'
							}
						}
					});
				}
				else {
					Ext.Msg.alert('오류', '선택 된 아이템이 없습니다.');
				}

			}
		},{
			//icon: '/led-icons/application_edit.png',
			cls: 'proxima_button_customize',
			width: 30,
			text: '<span style="position:relative;top:1px;" title="'+_text('MN02170')+'"><i class="fa fa-check" style="font-size:13px;color:white;"></i></span>',
			handler : function(btn, e){
				var sel = Ext.getCmp(deleteInformId).getSelectionModel().getSelections();
				var sel_id = new Array();
				var del_info = new Array();
				if(sel.length > 0)
				{
					Ext.each(sel, function(r){
						if( r.get('status') != 'REQUEST' ){
							Ext.Msg.alert(_text('MN00023'), _text('MSG02119'));
							return;
						}else{
							var data_r = new Object();
							data_r['delete_id'] = r.get('delete_id');
							data_r['content_id'] = r.get('content_id');
							data_r['media_id'] = r.get('media_id');
							data_r['delete_type'] = r.get('delete_type');
							data_r['delete_date'] = r.get('delete_date')
							sel_id.push(data_r);
						}
					});

					Ext.Msg.show({
 						title: _text('MN00021'),//' 경 고 ',
 						msg : _text('MSG02046'),//선택하신 목록을 삭제 승인하시겠습니까?
 						buttons : Ext.Msg.YESNO,
 						fn : function(button){
 							if(button == 'yes')
 							{
 								Ext.Ajax.request({
									url : '/pages/menu/contents_management/content_delete_action.php',
									params : {
										ids : Ext.encode(sel_id),
										action : 'approve'
									},
									callback : function(opt, success, res){
										if(success)
										{
											 var msg = Ext.decode(res.responseText);
											 if(msg.success)
											 {
											 	//Ext.Msg.alert(' 완 료',msg.msg);
											 	Ext.getCmp(deleteInformId).getStore().reload();
											 }
											 else {
												Ext.Msg.alert( _text('MN01039'), msg.msg);//'오류'
											 }
										}
										else
										{
											Ext.Msg.alert( _text('MN01098'), res.statusText);//'서버 오류'
										}
									}
								})
 							}
 						}
 					});

				}
				else {
					//Ext.Msg.alert('오류','먼저 대상을 선택 해 주시기 바랍니다.');
					Ext.Msg.alert( _text('MN01039'), _text('MSG01005'));
				}
			}
		},{
			//icon:'/led-icons/application_delete.png',
			//text: '삭제 취소',
			cls: 'proxima_button_customize',
			width: 30,
			text: '<span style="position:relative;top:1px;" title="'+_text('MN02172')+'"><i class="fa fa-ban" style="font-size:13px;color:white;"></i></span>',
			handler : function(btn, e){
				var sel = Ext.getCmp(deleteInformId).getSelectionModel().getSelections();
				if( sel.length < 1){
					Ext.Msg.alert(_text('MN00023'), _text('MSG01005'));//알림, 먼저 대상을 선택 해 주시기 바랍니다.
					return;
				}
				Ext.Msg.show({
					title : _text('MN00023'),
					msg : _text('MN02172')+' : '+_text('MSG02039'),
					buttons : Ext.Msg.OKCANCEL,
					fn : function(btn){
						if(btn == 'ok'){
							var sel_id = new Array();
							var now = new Date();
							var sel_expire_id = new Array();
							Ext.each(sel, function(r){
								if(r.get('status') != 'REQUEST'){
									Ext.Msg.alert( _text('MN00023'), _text('MSG02047'));//알림, 삭제된 목록은 삭제 취소할 수 없습니다.
									return;
								}else{
									var data_r = new Object();
									data_r['delete_id'] = r.get('delete_id');
									data_r['content_id'] = r.get('content_id');
									data_r['media_id'] = r.get('media_id');
									data_r['expired_date'] = r.get('expired_date');
									data_r['delete_type'] = r.get('delete_type');
									sel_id.push(data_r);
								}
							});

							Ext.Ajax.request({
								url : '/pages/menu/contents_management/content_delete_action.php',
								params : {
									ids : Ext.encode(sel_id),
									action : 'cancel'
								},
								callback : function(opt, success, res){
									if(success){
										 var msg = Ext.decode(res.responseText);
										 if(msg.success)
										 {
											Ext.getCmp(deleteInformId).getStore().reload();
										 }
										 else {
											Ext.Msg.alert(_text('MN00022'),msg.msg);
										 }
									}else{
										 Ext.Msg.alert(_text('MN00022'), res.statusText);
									}
								}
							})
						}
					}
				});
			}
		},{
			//icon:'/led-icons/arrow_refresh.png',
			//text: '초기화',
			cls: 'proxima_button_customize',
			width: 30,
			text: '<span style="position:relative;top:1px;" title="'+_text('MN02096')+'"><i class="fa fa-refresh" style="font-size:13px;color:white;"></i></span>',
			handler : function(btn, e){
				var sm = Ext.getCmp(deleteInformId).getSelectionModel();
				sm.clearSelections();
			}
		}],
		xtype: 'grid',
		layout: 'fit',
		//id: 'delete_inform_id',
		id:deleteInformId,
		title: '<span class="user_span"><span class="icon_title"><i class=""></i></span><span class="main_title_header">'+_text('MN02044')+'</span></span>',
		cls: 'grid_title_customize proxima_customize',
		stripeRows: true,
		border: false,
		loadMask: true,
		columnWidth: 1,
		store: delete_store,
		disableSelection: true,
		listeners: {
			viewready: function(self){
				self.store.load({
					params: {
						start: 0,
						limit: delete_inform_size,
						start_date: startDate.getValue().format('Ymd000000'),
						end_date: endDate.getValue().format('Ymd240000')
					}

				})
			},
			rowdblclick: {
						fn: function(self, rowIndex, e){
							var index =0;
							var sm = self.getSelectionModel();
							var sel = Ext.getCmp(deleteInformId).getSelectionModel().getSelections();
							if(sel.length>0)
							{
								for(var i=0;i<sel.length;i++)
								{
									index = Ext.getCmp(deleteInformId).getStore().indexOf(sel[i]);
									sm.selectRow(index,true);
								}
							}
							if(sm.isSelected(rowIndex))
							{
								sm.deselectRow(rowIndex);
							}
							else  sm.selectRow(rowIndex,true);
						}
					}
			//sortchange : sortChanges

		},

		sm : selModel,

		cm: new Ext.grid.ColumnModel({
			defaults:{
				sortable: true
			},

			columns: [
				new Ext.grid.RowNumberer({width: 30}),
				selModel,
				{header: _text('MN00138'),dataIndex:'flag',align:'center',sortable:'true',width:80},//상태
                {header: _text('MN01998'), dataIndex:'del_req_date',align:'center',sortable:'true',width:145,renderer: Ext.util.Format.dateRenderer('Y-m-d  H:i:s')},//삭제요청일
                {header: _text('MN01999'), dataIndex:'del_req_user_nm',align:'center',sortable:'true',width:100},//삭제요청자
				{header: '<center>'+_text('MN00128')+'</center>', dataIndex: 'reason',align:'left',sortable:'true',width:160, renderer : function(value, metadata) {
					if(!Ext.isEmpty(value))
					{
						metadata.attr = 'ext:qtip="' + value + '"';
					}
					return value;
				}},//삭제 사유
				{header: 'delete_id', dataIndex: 'delete_id',hidden:true},
				{header: 'content_id', dataIndex: 'content_id',hidden:true},
				{header: _text('MN02084'),dataIndex:'delete_type_name',align:'center',sortable:'true',width:80},//구분
				{header: _text('MN00300'),dataIndex:'media_type',align:'center',sortable:'true',width:100, hidden : true},//파일 용도
				{header: _text('MN00279'),dataIndex:'bs_content_title',align:'center',sortable:'true',width:60},//콘텐츠 종류
				{header: _text('MN00197'), dataIndex: 'ud_content_title', align:'center',sortable:'true',width:100},//사용자 정의 콘텐츠
				{header: _text('MN00387'), dataIndex: 'category', align:'left',sortable:'true',width:200, hidden : true},//카테고리
				{header: '미디어ID', dataIndex: 'media_id', align:'center',sortable:'true',width:125},
                {header:'<center>'+ _text('MN00249')+'</center>', dataIndex:'title',align:'left',sortable:'true',width:250},//제목
				{header: _text('MN00301'), dataIndex:'file_size',align:'center',sortable:'true',width:80},//파일 용량
				{header: _text('MN00172'), dataIndex: 'path',align:'left',sortable:'true',width:120},//미디어경로
				{header: _text('MN00120'), dataIndex:'reg_user_nm',align:'center',sortable:'true',width:100, hidden:true},//등록자
				{header: '승인자', dataIndex:'del_req_user_nm',align:'center',sortable:'true',width:100},//승인자
				{header: _text('MN01002'), dataIndex:'created_date',align:'center',sortable:'true',width:120 ,renderer: Ext.util.Format.dateRenderer('Y-m-d H:i:s'),sortable:'true',width:100, hidden:true},//등록일자
				{header: '삭제일자', dataIndex:'delete_date',align:'center',sortable:'true',width:120 ,renderer: Ext.util.Format.dateRenderer('Y-m-d H:i:s'),sortable:'true',width:100, hidden:true},//삭제일자
				{header: _text('MN01007'), dataIndex:'expired_date',align:'center',sortable:'true',width:120 ,renderer: Ext.util.Format.dateRenderer('Y-m-d'),sortable:'true',width:100, hidden: true}//만료기한
			]
		}),

		viewConfig: {
			rowHeight: 20,
            templates: {
                cell: new Ext.Template(
                    '<td class="x-grid3-col x-grid3-cell x-grid3-td-{id} x-selectable {css}" style="{style}" tabIndex="0" {cellAttr}>',
                    '<div class="x-grid3-cell-inner x-grid3-col-{id}" {attr}>{value}</div>',
                    '</td>'
                )
            },
			forceFit: false,
			emptyText : _text('MSG00148')//'결과 값이 없습니다.'
		},

		bbar: new Ext.PagingToolbar({
			store: delete_store,
			pageSize: delete_inform_size,
			items:[{
				id : 'toolbartext',
				xtype:'tbtext',
				pageX:'100',
				pageY:'100',
				//>>text: '(총 미디어 수: <?=$total?>  |  삭제된 미디어 수: <?="<font color=red>".$del_suc."</font>"?>  |  존재하는 미디어의 수: <?="<font color=blue>".($total-$del_suc)."</font>"?>)'
				text : _text('MN02165')+" : "+total_list
			}]
		})

	}
})()