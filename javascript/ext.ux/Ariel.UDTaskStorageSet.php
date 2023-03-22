(function(){
	Ext.ns('Ariel.config.custom');

	Ariel.config.custom.UDStorageSetPanel = Ext.extend(Ext.Panel, {
		layout: 'border',
		border: false,
		defaults: {
			split: true
		},

		initComponent: function(config){
			Ext.apply(this, config || {
				layout: 'fit'
			});

			var that = this;

			this.treeGrid =  new Ext.ux.tree.TreeGrid({
				enableDD: false,
				selModel: new Ext.tree.MultiSelectionModel({
				}),
				columns:[
					{ header: "NO", dataIndex: 'no' , width: 60, sortType: 'int' },
					{ header: "스토리지 그룹", dataIndex: 'storage_group_nm' , width: 300 },
					{ header: "기본 스토리지 패스", dataIndex: 'src_path' , width: 200 },
					{ header: "사용자 스토리지 패스", dataIndex: 'ud_path' , width: 200}
				],
				loader: new Ext.tree.TreeLoader({
					baseParams: {
						action: 'get_ud_storage_group'
					},
					listeners: {
						load: function( self,  node, response ){

						},
						loadException: function(self, node, response){
							Ext.Msg.alert('확인', response.responseText);
						}
					},
					dataUrl: '/pages/menu/config/workflow/workflow_list.php'
				}),

				tbar: [
					that.refresh_button(that),'-',
					that.addStorageGroup(that),'-',
					that.editStorageGroup(that),'-',
					that.delStorageGroup(that),'-',
					that.addSetStorage(that),'-',
					that.delSetStorage(that),'-'

				],
				contextMenu: new Ext.menu.Menu({
					items: [
					that.refresh_button(that),
					that.addStorageGroup(that),
					that.editStorageGroup(that),
					that.delStorageGroup(that),
					that.addSetStorage(that),
					that.delSetStorage(that)
					],
					listeners: {
						itemclick: {
							fn: function(item, e){
								var r = item.parentMenu.contextNode.getOwnerTree();
								switch (item.cmd) {
								}
							},
							scope: this
						}
					}
				}),
				listeners: {
					contextmenu: function(node, e) {
						if( Ext.isEmpty(node.ownerTree.getSelectionModel().getSelectedNodes()) ){
							node.select();
						}
						var c = node.getOwnerTree().contextMenu;
						c.contextNode = node;
						c.showAt(e.getXY());
					}
					,afterrender: function(self){
						var sort = new Ext.tree.TreeSorter(self, {
							//folderSort: true,
							dir: 'asc',
							sortType: function(node){
							//	return parseInt(node.no, 10);
							}
						});
					}
				}
			});



			this.items = [
				this.treeGrid
			];

			Ariel.config.custom.UDStorageSetPanel.superclass.initComponent.call(this);
		},
		StorageGroupRefresh : function(target){
			target.treeGrid.getLoader().on("beforeload", function(treeLoader, node){
			});


			target.treeGrid.getLoader().load( target.treeGrid.getRootNode() );
		},
		refresh_button : function(that){

			return {
				text: '새로고침',
				icon: '/led-icons/arrow_refresh.png',
				handler: function(btn, e){

					that.StorageGroupRefresh(that);
				}
			};
		},
		buildDeleteTable: function(e) {
			var rec = Ext.getCmp('storageList').getSelectionModel().getSelected();

			// 삭제 확인 창
			Ext.Msg.show({
				animEl: e.getTarget(),
				title: _text('MN00003'),
				icon: Ext.Msg.INFO,
				msg: _text('MN00381') +' '+ rec.get('name') +' '+ _text('MSG00140'),
				buttons: Ext.Msg.OKCANCEL,
				fn: function(btnID, text, opt) {
					if(btnID == 'ok') {
						Ext.Ajax.request({
							url: '/pages/menu/config/workflow/edit_workflow.php',
							params: {
								action: 'delete_storage',
								storage_id : rec.get('storage_id')
							},
							callback: function(opts, success, response) {
								try {
									var r = Ext.decode(response.responseText, true);
									if(r.success) {
										Ext.Msg.alert(_text('MN00023'), r.msg);
										Ext.getCmp('storageList').store.reload();
									}else{
										Ext.Msg.alert(_text('MN00022'), r.msg);
									}
								}catch(e) {
									alert(e.message + '(responseText: ' + response.responseText + ')');
								}
							}
						})
					}
				}
			})
		},

		addStorageGroup: function(target){
			return {
				cmd: 'add-storage-group',
				text: '그룹 추가',
				icon: '/led-icons/group.png',
				handler: function(){
					target.StorageGroupForm(target,'add_ud_storage_group','그룹 추가',{}).show();
				}
			};
		},StorageGroupForm: function(target,action,title,values){
			return	new Ext.Window({
				layout:'fit',
				title: title,
				width:400,
				height:200,
				modal:true,
				resizable:true,
				plian:true,
				items:[{
					xtype:'form',
					border:false,
					frame:true,
					url : '/pages/menu/config/workflow/workflow_list.php',
					//labelWidth:100,
					defaults:{
						anchor:'95%',
						xtype: 'textfield'
					},
					items: [{
						xtype: 'hidden',
						name: 'ud_storage_group_id',
						value: values.ud_storage_group_id
					},{
						name: 'ud_storage_group_nm',
						allowBlank : false,
						fieldLabel  : '스토리지 그룹 명',
						value: values.ud_storage_group_nm
					}],
					buttonAlign: 'center',
					buttons: [{
						text: '저장',
						handler: function(b,e){
							var form = b.ownerCt.ownerCt.getForm();
							form.submit({
								clientValidation: true,
								params: {
									action: action
								},
								success: function(form, action) {
								   Ext.Msg.alert('성공', action.result.msg);
								   b.ownerCt.ownerCt.ownerCt.close();
								   target.StorageGroupRefresh(target);
								},
								failure: function(form, action) {
									switch (action.failureType) {
										case Ext.form.Action.CLIENT_INVALID:
											Ext.Msg.alert('실패', '필수값을 입력해주세요.');
											break;
										case Ext.form.Action.CONNECT_FAILURE:
											Ext.Msg.alert('실패', 'Ajax communication failed');
											break;
										case Ext.form.Action.SERVER_INVALID:
										   Ext.Msg.alert('실패', action.result.msg);
								   }
								}
							});
						}
					},{
						text: '닫기',
						handler: function(b,e){
							b.ownerCt.ownerCt.ownerCt.close();
						}
					}]
				}]
			});
		}

		,editStorageGroup: function(target){
			return {
				cmd: 'edit-storage-group',
				text: '그룹 수정',
				icon: '/led-icons/group.png',
				handler: function(){
					var sel = target.treeGrid.getSelectionModel().getSelectedNodes();
					if( Ext.isEmpty(sel[0]) ){
						Ext.Msg.alert( _text('MN00023'), '목록을 선택해주세요.');
						return;
					}
					if( sel[0].getDepth() != 1 ){
						Ext.Msg.alert( _text('MN00023'), '스토리지 그룹을 선택해주세요.');
						return;
					}

					target.StorageGroupForm(target,'edit_ud_storage_group','그룹 수정',{
						ud_storage_group_id: sel[0].attributes.storage_group_id,
						ud_storage_group_nm: sel[0].attributes.storage_group_nm
					}).show();
				}
			};
		},delStorageGroup: function(target){
			return {
				cmd: 'del-storage-group',
				text: '그룹 삭제',
				icon: '/led-icons/group.png',
				handler: function(){
					var sel = target.treeGrid.getSelectionModel().getSelectedNodes();

					if( Ext.isEmpty(sel[0]) ){
						Ext.Msg.alert( _text('MN00023'), '목록을 선택해주세요.');
						return;
					}
					if( sel[0].getDepth() != 1 ){
						Ext.Msg.alert( _text('MN00023'), '스토리지 그룹을 선택해주세요.');
						return;
					}

					var ud_storage_group_ids = new Array();
					Ext.each(sel, function(node){
						if( node.getDepth() != 1 ){
							Ext.Msg.alert( _text('MN00023'), '스토리지 그룹을 선택해주세요.');
							return;
						}
						ud_storage_group_ids.push(node.attributes.storage_group_id);
					});

					Ext.Ajax.request({
						url:'/pages/menu/config/workflow/workflow_list.php',
						params:{
							action: 'del_ud_storage_group',
							ud_storage_group_ids : Ext.encode(ud_storage_group_ids)
						},
						callback: function(opts, suc, res){
							if(suc){
								try{
									var r = Ext.decode(res.responseText);
									if (!r.success){
										Ext.Msg.alert('확인', r.msg);
									}else{
										Ext.Msg.alert('확인',r.msg);
										target.StorageGroupRefresh(target);
									}
								}
								catch (e){
									Ext.Msg.alert('오류', res.responseText);
								}
							}
						}
					});
				}
			};
		},addSetStorage: function(target){
			return {
				cmd: 'add-set-storage',
				text: '설정 추가',
				icon: '/led-icons/cog.png',
				handler: function(){
					var sel = target.treeGrid.getSelectionModel().getSelectedNodes();
					if( Ext.isEmpty(sel[0]) ){
						Ext.Msg.alert( _text('MN00023'), '목록을 선택해주세요.');
						return;
					}
					if( sel[0].getDepth() != 1 ){
						Ext.Msg.alert( _text('MN00023'), '스토리지 그룹을 선택해주세요.');
						return;
					}

					target.SetStorageGroupForm(target,'add_set_storage_group','설정 추가',{
						ud_storage_group_id: sel[0].attributes.storage_group_id,
						ud_storage_group_nm: sel[0].attributes.storage_group_nm
					}).show();
				}
			};
		},SetStorageGroupForm: function(target,action,title,values){
			return	new Ext.Window({
				layout:'fit',
				title: title,
				width:400,
				height:200,
				modal:true,
				resizable:true,
				plian:true,
				items:[{
					xtype:'form',
					border:false,
					frame:true,
					labelWidth:100,
					url : '/pages/menu/config/workflow/workflow_list.php',
					defaults:{
						anchor:'95%',
						xtype: 'textfield'
					},
					items: [{
						xtype: 'hidden',
						name: 'ud_storage_group_id',
						value: values.ud_storage_group_id
					},{
						name: 'ud_storage_group_nm',
						readOnly: true,
						fieldLabel  : '스토리지 그룹 명',
						value: values.ud_storage_group_nm
					},{
						xtype: 'combo',
						fieldLabel: '기본 스토리지',
						store: new Ext.data.JsonStore({
							url: '/pages/menu/config/workflow/workflow_list.php',
							baseParams: {
								action: 'storage_list'
							},
							root: 'data',
							idProperty: 'storage_id',
							fields: [
								{name: 'name', type: 'string'},
								{name: 'storage_id', type: 'int'},
								{name: 'path', type: 'string'}
							]
						}),
						hiddenName: 'src_storage_id',
						valueField: 'storage_id',
						displayField: 'name',
						tpl: '<tpl for="."><div class="x-combo-list-item" >{name} [{path}]</div></tpl>',
						typeAhead: true,
						triggerAction: 'all',
						forceSelection: true,
						editable: false,
						emptyText: '기본 스토리지를 선택하세요'
					},{
						xtype: 'combo',
						fieldLabel: '사용자 스토리지',
						store: new Ext.data.JsonStore({
							url: '/pages/menu/config/workflow/workflow_list.php',
							baseParams: {
								action: 'storage_list'
							},
							root: 'data',
							idProperty: 'storage_id',
							fields: [
								{name: 'name', type: 'string'},
								{name: 'storage_id', type: 'int'},
								{name: 'path', type: 'string'}
							]
						}),
						hiddenName: 'trg_storage_id',
						valueField: 'storage_id',
						displayField: 'name',
						tpl: '<tpl for="."><div class="x-combo-list-item" >{name} [{path}]</div></tpl>',
						typeAhead: true,
						triggerAction: 'all',
						forceSelection: true,
						editable: false,
						emptyText: '변경될 사용자 스토리지를 선택하세요'
					}],
					buttonAlign: 'center',
					buttons: [{
						text: '저장',
						handler: function(b,e){
							var form = b.ownerCt.ownerCt.getForm();
							form.submit({
								clientValidation: true,
								params: {
									action: action
								},
								success: function(form, action) {
								   Ext.Msg.alert('성공', action.result.msg);
								   b.ownerCt.ownerCt.ownerCt.close();
								   target.StorageGroupRefresh(target);
								},
								failure: function(form, action) {
									switch (action.failureType) {
										case Ext.form.Action.CLIENT_INVALID:
											Ext.Msg.alert('실패', '필수값을 입력해주세요.');
											break;
										case Ext.form.Action.CONNECT_FAILURE:
											Ext.Msg.alert('실패', 'Ajax communication failed');
											break;
										case Ext.form.Action.SERVER_INVALID:
										   Ext.Msg.alert('실패', action.result.msg);
								   }
								}
							});
						}
					},{
						text: '닫기',
						handler: function(b,e){
							b.ownerCt.ownerCt.ownerCt.close();
						}
					}]
				}]
			});
		},delSetStorage: function(target){
			return {
				cmd: 'del-set-storage',
				text: '설정 삭제',
				icon: '/led-icons/cog.png',
				handler: function(){
					var sel = target.treeGrid.getSelectionModel().getSelectedNodes();

					if( Ext.isEmpty(sel[0]) ){
						Ext.Msg.alert( _text('MN00023'), '목록을 선택해주세요.');
						return;
					}
					if( sel[0].getDepth() != 2 ){
						Ext.Msg.alert( _text('MN00023'), '스토리지 목록을 선택해주세요.');
						return;
					}

					var ud_storage_group_ids = new Array();
					Ext.each(sel, function(node){
						if( node.getDepth() != 2 ){
							Ext.Msg.alert( _text('MN00023'), '스토리지 목록을 선택해주세요.');
							return;
						}
						ud_storage_group_ids.push({
							storage_group_id : node.attributes.storage_group_id,
							source_storage_id:  node.attributes.source_storage_id
						});
					});

					Ext.Ajax.request({
						url:'/pages/menu/config/workflow/workflow_list.php',
						params:{
							action: 'del_set_storage_group',
							ud_storage_group_ids : Ext.encode(ud_storage_group_ids)
						},
						callback: function(opts, suc, res){
							if(suc){
								try{
									var r = Ext.decode(res.responseText);
									if (!r.success){
										Ext.Msg.alert('확인', r.msg);
									}else{
										Ext.Msg.alert('확인',r.msg);
										target.StorageGroupRefresh(target);
									}
								}
								catch (e){
									Ext.Msg.alert('오류', res.responseText);
								}
							}
						}
					});
				}
			};
		}
	});

	return new Ariel.config.custom.UDStorageSetPanel();
})()