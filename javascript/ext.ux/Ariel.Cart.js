Ext.ns('Ariel');
(function(){
	Ariel.Cart = Ext.extend(Ext.Panel, {
		title: 'Work Panel',
		layout: 'fit',
		split: true,
		collapsible: true,
		autoScroll: true,
		height: 150,

		tbar: [{
			text: '작업 실행',
			handler: function(btn, e){

			}
		}],

		initComponent: function(config){
			Ext.apply(this, config || {});

			var items = [{
				text: '카탈로깅'
			},{
				text: '트랜스코드'
			}];

			this.menu = new Ext.menu.Menu({
				items: items,
				listeners: {
				}
			});

			this.items = {
				parent: this,
				id: 'images-view',
				xtype: 'dataview',
				multiSelect: true,
				overClass:'x-view-over',
				itemSelector:'div.thumb-wrap',
				emptyText: 'No images to display',
				
				plugins: [
					new Ext.DataView.DragSelector()
				],
				store: new Ext.data.ArrayStore({
					fields: [
						'title'
					]
				}),
				tpl: new Ext.XTemplate(
					'<tpl for=".">',
						'<div class="thumb-wrap" id="{name}">',
						'<div class="thumb"><img src="{url}" title="{name}"></div>',
						'<span class="x-editable">{shortName}</span></div>',
					'</tpl>',
					'<div class="x-clear"></div>'
				),
				listeners: {
					afterrender: function(self){
						var dd = new Ext.dd.DropTarget(self.el, {
							ddGroup:'ContentDD',
							notifyDrop: function(dd, e, node){
								var r = Ext.data.Record.create([
									'name',
									'shortName'
								]);
								for(var i=0; i<node.selections.length; i++){
									self.getStore().add(new r({name: node.selections[i].get('content_id'), shortName: node.selections[i].get('title')}));
								}

								var m = self.parent.menu;

								var i = m.find('text', '삭제');
								if(i.length > 0){
									m.remove(i[0]);
								}

								e.stopEvent();

								m.showAt(e.getXY());

								return true;
							}
						});
					},
					contextmenu: function(self, index, node, e){
						var m = self.parent.menu;

						var d = m.find('text', '삭제');
						if(d.length == 0){
							m.addItem({
								text: '삭제',
								handler: function(b, e){
									var s = self.getStore();
									var rs = self.getSelectedRecords();

									for(var i=0; i<rs.length; i++){
										s.remove(rs[i]);
									}
								}
							});
						}

						e.stopEvent();
						m.showAt(e.getXY());
					}						
				}
			}

			Ariel.Cart.superclass.initComponent.call(this);
		}
	});

	new Ariel.Cart().show();
})();