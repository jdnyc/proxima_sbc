
function excelDataProgram(types, url, grid_column, search_action, search_type, search_text) {
	var columns = new Array();
	var coloumn_length, columnInfo;
	if (search_type == 'program') {
		coloumn_length = grid_column.length;
		columnInfo = grid_column;
	} else if (search_type == 'form') {
		coloumn_length = grid_column.getColumnCount();
		columnInfo = grid_column.columns;
	} else {
		coloumn_length = grid_column.getColumnCount();
		columnInfo = grid_column.columns;
	}

	for (var i = 0; i < coloumn_length; i++) {
		if (columnInfo[i].dataIndex == 'content_id') continue;
		if (columnInfo[i].hidden == true) {
			var hidden_text = 'hidden';
		} else {
			var hidden_text = 'show';
			var column_data = new Array();
			column_data[0] = columnInfo[i].dataIndex;
			column_data[1] = columnInfo[i].header;
			column_data[2] = columnInfo[i].width;
			column_data[3] = columnInfo[i].align;
			column_data[4] = hidden_text;//grid_column.columns[i].hidden
			columns[i] = column_data;
		}
	}

	var form = document.createElement("form");
	form.setAttribute("method", "post"); // POST
	form.setAttribute("target", "_blank"); // 새창

	form.setAttribute("action", url);

	//조회값 종류
	var types = document.createElement("input");
	types.setAttribute("name", "type");
	types.setAttribute("value", types);
	form.appendChild(types);


	//컬럼
	var grid_column = document.createElement("input");
	grid_column.setAttribute("name", "columns");
	grid_column.setAttribute("value", Ext.encode(columns));
	form.appendChild(grid_column);

	//조회 값
	//action
	var search_values_action = document.createElement("input");
	search_values_action.setAttribute("name", "action");
	search_values_action.setAttribute("value", search_action);
	form.appendChild(search_values_action);

	//action
	var search_value_type = document.createElement("input");
	search_value_type.setAttribute("name", "pgm_type");
	search_value_type.setAttribute("value", search_type);
	form.appendChild(search_value_type);

	//value
	var search_values_text = document.createElement("input");
	search_values_text.setAttribute("name", "pgm_value");
	search_values_text.setAttribute("value", search_text);
	form.appendChild(search_values_text);

	//excel 구분값
	var is_excel = document.createElement("input");
	is_excel.setAttribute("name", "is_excel");
	is_excel.setAttribute("value", 1);
	form.appendChild(is_excel);

	document.body.appendChild(form);
	form.submit();

	return;
}

Ariel.Nps.Program = Ext.extend(Ext.Panel, {
	layout: 'border',
	autoScroll: true,
	isLoading: false,
	initialized: false,

	// sub menu
	_subMenu: null,

	setLoading: function (isLoading) {
		this.isLoading = isLoading;
		return true;
	},
	getLoading: function () {
		return this.isLoading;
	},

	listeners: {
		show: function (self) {
			if (!self.initialized) {
				var node = self._subMenu.getRootNode().findChild('id', self._subMenu.getRootNode().childNodes[0].id);
				if (node) {
					node.fireEvent('click', node);
					self.initialized = true;
				}
			}
		}
	},

	initComponent: function (config) {
		Ext.apply(this, config || {});

		this._subMenu = this._makeSubMenu();
		this.items = [
			this._subMenu,
			{
				xtype: 'panel',
				region: 'center',
				title: '&nbsp;',
				layout: 'fit'
			}
		]

		Ariel.Nps.Program.superclass.initComponent.call(this);
	},

	_makeSubMenu: function () {
		var _this = this;
		return new Ext.tree.TreePanel({
			xtype: 'treepanel',
			region: 'west',
			//>>title: '프로그램관리',
			title: _text('MN05004'),
			width: 280,
			boxMinWidth: 280,
			split: true,
			collapsible: true,
			autoScroll: true,
			rootVisible: false,
			cls: 'tree_menu',
			lines: false,
			listeners: {
				click: function (node, e) {
					var url = node.attributes.url;

					if (_this.getLoading()) {
						return;
					}
					_this.setLoading(true);

					if (!url) return;

					var components = [
						'/custom/ktv-nps/js/custom/Custom.RadioDay.js',
						'/custom/ktv-nps/javascript/common.js',
						'/custom/ktv-nps/javascript/api/Custom.Store.js',
						'/javascript/ext.ux/Ariel.Nps.BISProgram.js',
						'/custom/ktv-nps/javascript/ext.ux/Custom.UserSelectWindow.js',
						'/custom/ktv-nps/javascript/ext.ux/components/Custom.UserListGrid.js',
						'/custom/ktv-nps/js/folder/Ariel.System.UserMapWindow.js'
					];
					Ext.Loader.load(components, function (r) {
						Ext.Ajax.request({
							url: url,
							timeout: 0,
							callback: function (opts, success, response) {
								try {
									_this.get(1).removeAll(true);
									_this.get(1).add(Ext.decode(response.responseText));
									_this.get(1).doLayout();

									_this.setLoading(false);

								} catch (e) {
									Ext.Msg.alert(e['name'], opts.url + '<br />' + e['message']);
								}
							}
						});
					}, this, true);
				}
			},
			root: {
				//id:'admin',
				//>> text: '시스템관리',
				text: _text('MN00207'),
				expanded: true,
				children: [{
					text: '<span style="position:relative;top:1px;"><i class="fa fa-list-alt" style="font-size:13px;"></i></span>&nbsp;제작폴더 신청관리',
					title: '제작폴더 신청관리',
					url: '/custom/ktv-nps/js/folder/Ariel.System.ProgramRequestManagement.js',
					leaf: true,
				}, {
					text: '<span style="position:relative;top:1px;"><i class="fa fa-list-alt" style="font-size:13px;"></i></span>&nbsp;제작폴더 관리',
					title: '제작폴더 관리',
					url: '/custom/ktv-nps/js/folder/Ariel.System.ProductAuthMng.js',
					leaf: true
				}, {
					text: '<span style="position:relative;top:1px;"><i class="fa fa-list-alt" style="font-size:13px;"></i></span>&nbsp;스크래치 폴더 관리',
					title: '스크래치 폴더 관리',
					url: '/custom/ktv-nps/js/folder/Ariel.System.ScratchAuthMng.js',
					leaf: true
				}]
			}
		});
	}
});