(function(){
	//Ext.chart.Chart.CHART_URL = '/ext/resources/charts.swf';
	var session_id;
	//메인 정보
	var main_store = new Ext.data.JsonStore({
        url: '/store/sys_monitor/get_images.php',
        root: 'images',
        fields: ['id', 'url', 'name']
    });
    main_store.load();
    main_store.on('load', function(){
        //console.log('main store load');
        Ext.getCmp('svr-list').select(session_id);
    });

    var data_store = new Ext.data.JsonStore({
    	url: '/store/sys_monitor/more_info_data.php',
    	root: 'details',
    	fields: ['key','value']
    });
    data_store.load();
    
    var chart_store = new Ext.data.JsonStore({
    	url: '/store/sys_monitor/more_info_chart.php',
    	root: 'details',
    	fields: ['xfield', 'cpu', 'memory', 'date', 'memory_mb']
    });
    
    var hdd_store = new Ext.data.JsonStore({
    	url: '/store/sys_monitor/more_info_hdd.php',
    	root: 'details',
    	fields: ['drive','total','remain','percent']
    });
    
	var task_store = new Ext.data.JsonStore({
    	url: '/store/sys_monitor/more_info_task.php',
    	root: 'details',
    	fields: ['num','task','pid','date','during']
    });
    
    var timeset_store = new Ext.data.SimpleStore ({
		fields: ['id', 'time'],
		data : [['0','5'],['1','10'],['2','30'],['3','60'],['4','300']]
	});
	
	var log_store_tab1 = new Ext.data.JsonStore({
    	url: '/store/sys_monitor/more_info_log.php',
    	root: 'details',
    	fields: ['time','cpu','memory']
    });
    var log_store_tab2 = new Ext.data.JsonStore({
    	url: '/store/sys_monitor/more_info_log.php',
    	root: 'details',
    	fields: ['name','pid','start','end']
    });
		
    
    //메인정보 쪽 템플릿
    var tpl = new Ext.XTemplate(
    	'<tpl for=".">',
            '<div class="thumb-wrap" id="{id}" title="{id}">',
		    '<div class="thumb"><img src="{url}"></div>',
		    '<span>{Name}</span></div>',
        '</tpl>',
        '<div class="x-clear"></div>'
	);
		
    var panel = new Ext.Panel({	
        id:'images-view',
        frame:true,              
   		//boxMaxHeight: true,
        layout:'fit',
        autoScroll: true,
        bbar: [
			{
				xtype: 'tbtext',
				text: '갱신 주기(초) : '
			},{
				xtype: 'combo',
				width: 30,
				name: 'timeset',
				id: 'timeset',
				//fieldLabel: '시간설정',
				mode: 'local',
				store: timeset_store,
				displayField:'time',
				width: 130,
				listeners: {
					select: function(field, rec, selIndex){
						if (selIndex == 0){
							//Ext.Msg.prompt('새로운 장르', '장르명', Ext.emptyFn);
						}
					}
				}				
			},{
				xtype: 'button',
				
					text: '저장',
					handler: function(){
						Ext.Ajax.request({
							url: '/store/sys_monitor/config_input.php',
							params: {
								timeset: Ext.getCmp('timeset').value
							}
							
						});
						Ext.Msg.alert(
            				'',
            				'반영되었습니다.'
        				);
					}
			},'-','->',{
	            text: '선택항목 삭제',
	            align: 'right',
	            handler: function(){
	            	Ext.Msg.show({
	            		title: '선택항목 삭제',
	            		msg: '정말 삭제하시겠습니까?',
	            		buttons: {
	            			yes: true,
	            			no: true	            			
	            		},
	            		fn: function(btn) {
	            			switch(btn){
	            				case 'yes':
		            				Ext.Ajax.request({
			            				url: '/store/sys_monitor/delete_contents.php',
			            				params: { id: session_id },
			            				callback: function(o, r, c){
				            				if(c.responseText == 3)
				            				{
					            				Ext.Msg.alert(
						            				'삭제 오류',
						            				'모니터링중인 서버 입니다. 연결 종료 후 다시 시도해 주세요.'						            				
					            				);
				            				}
				            				else if(c.responseText == 2)
				            				{
					            				Ext.Msg.alert(
						            				'삭제 오류',
						            				'삭제할 서버를 선택해주세요.'
					            				);
				            				}
				            				else if(c.responseText == 11111)
				            				{
					            				Ext.Msg.alert(
						            				'삭제 완료',
						            				'삭제되었습니다.'						            				
					            				);
				            				}
				            				else if(c.responseText == 0)
				            				{
					            				Ext.Msg.alert(
						            				'삭제 오류',
						            				'서버시간이 현재시간보다 빠릅니다.'						            				
					            				);
				            				}
				            				else
				            				{
					            				Ext.Msg.alert(
						            				'삭제 오류'
						            			);
				            				}
			            				}
		            				});		            					
	            					break;
	            				case 'no':	            				
	            					break;
	            			}
	            		}
	            	}); 
	            }
        	},'-',{
        		text: '로그 보기',
        		//handler: function(){
        		handler: function(){
        			log_store_tab1.reload({
        				url: '/store/sys_monitor/more_info_log.php',
        				params: {
        					id: session_id,
        					tab: 1
        				}
        			});
        			log_store_tab2.reload({
        				url: '/store/sys_monitor/more_info_log.php',
        				params: {
        					id: session_id,
        					tab: 2
        				}
        			});
        			new Ext.Window({
        				title: '모니터링 로그 정보',
        				width: 700,
        				height: 500,
        				layout: 'fit',
        				modal: true,
        				items: [{
        					xtype: 'tabpanel',
        					activeTab: 0,
        					items:[{
	    						xtype: 'grid',
	        					title: 'CPU, Memory 사용량 과다',
						        store: log_store_tab1,
						        height: 400,
						        autoWidth: true,
						        autoScroll: true,
						        viewConfig: {
									forceFit: true
								},
								bbar: ['->',{
									text: '엑셀로 저장',
									handler: function(){										
										window.location = '/store/sys_monitor/more_info_log_excel.php?id='+session_id+'&tab=1';
									}
								}],
						        colModel: new Ext.grid.ColumnModel({
							        defaultSortable: true,
									defaults: {
								        sortable: true,
								        menuDisabled: true
								        //width: 100
								    },
							        columns: [
							            {
							                header   : '시간', 
							                //width    : 80, 
							                sortable : true, 
							                dataIndex: 'time'
							            },{
							                header   : 'CPU 사용량', 
							                //width    : 80, 
							                sortable : true, 
							                dataIndex: 'cpu'
							            },{
							                header   : 'Memory 사용량', 
							                //width    : 80, 
							                sortable : true, 
							                dataIndex: 'memory'
						            }]
	        					})
	    					},{
	        					xtype: 'grid',
	        					title: 'Process별 시작/종료 시간',
						        store: log_store_tab2,						        
						        height: 400,
						        autoWidth: true,
						        autoScroll: true,
						        viewConfig: {
									forceFit: true
								},
								bbar: ['->',{
									text: '엑셀로 저장',
									handler: function(){										
										window.location = '/store/sys_monitor/more_info_log_excel.php?id='+session_id+'&tab=2';
									}								}],
						        colModel: new Ext.grid.ColumnModel({
							        defaultSortable: true,
									defaults: {
								        sortable: true,
								        menuDisabled: true
								        //width: 100
								    },
							        columns: [
							            {//fields: ['name','pid','start','end']
							                header   : '이름', 
							                //width    : 80, 
							                sortable : true, 
							                dataIndex: 'name'
							            },{
							                header   : 'PID', 
							                //width    : 80, 
							                sortable : true, 
							                dataIndex: 'pid'
							            },{
							                header   : '시작시간', 
							                //width    : 80, 
							                sortable : true, 
							                dataIndex: 'start'
							            },{
							                header   : '종료시간', 
							                //width    : 80, 
							                sortable : true, 
							                dataIndex: 'end'
							            }]
	        					})
        					}]
        				}]
        			}).show();        			
        		}
        	}
        ],
       items: new Ext.DataView({
            id: 'svr-list',
            store: main_store,
            tpl: tpl,
            title: '시스템 모니터링',            
            singleSelect: true,
            overClass:'x-view-over',
        	itemSelector:'div.thumb-wrap',
        	//blockRefresh: true,
            emptyText: 'DB에 등록된 서버가 없습니다.',
            plugins: [
                //new Ext.DataView.DragSelector()
            ],            
            prepareData: function(data){
                data.Name = Ext.util.Format.ellipsis(data.name, 15);
                //data.sizeString = Ext.util.Format.fileSize(data.size);
                //data.dateString = data.lastmod.format("m/d/Y g:i a");
                return data;
            },            
            listeners: {
            	click: function(self, idx, node, e){
                	//console.log(panel.items.items[0].itemSelector);     
            		if(node.title)
            		{   
                		Ext.Ajax.request({
                    		url: '/store/sys_monitor/session_handling.php',
                    		params: { 
                        		value: node.title 
                        	},
                    		callback: function(o, r, c){
                        		session_id = c.responseText;
                    		}
                		});
            		}
            		if(session_id = node.title)
            		{     			
            			data_store.reload({
	            			params: {
	            				id: node.title
	            			}
	            		});
	            		chart_store.reload({
	            			params: {
	            				id: node.title
	            			}
	            		});
	            		hdd_store.reload({
	            			params: {
	            				id: node.title
	            			}
	            		});
	            		task_store.reload({
	            			params: {
	            				id: node.title
	            			}
	            		});
            		}            		
            	}            
            }
        })    	
    });
        
    var data_list = new Ext.Panel({
        id:'data-view',
        title: '서버 정보',
        collapsible: true,
        frame: true,        
        //layout:'fit', 이것 하면 IE8에서 잘 안나옴
        autoScroll: true,
        autoHeight: true,
        items: new Ext.list.ListView({
	        store: data_store,
	        hideHeaders: true,
	        viewConfig: {
	            forceFit: true
	        },
	        
	        columns: [
				{
				    header   : '1',
				    width    : .2, 
				    sortable : true, 
				    dataIndex: 'key'
				},	
				{
				    header   : '2', 
				    width    : .8, 
				    sortable : true, 
				    dataIndex: 'value'
				}
			]        
    	})
    });
   
    var hdd_grid = new Ext.grid.GridPanel({
        title: 'HDD 정보',
        collapsible: true,
        store: hdd_store,
        autoHeight: true,
        autoWidth: true,
        viewConfig: {
			forceFit: true
		},
        colModel: new Ext.grid.ColumnModel({
	        defaultSortable: true,
			defaults: {
		        sortable: true,
		        menuDisabled: true
		        //width: 100
		    },
	        columns: [
	            {
	                header   : '이름', 
	                //width    : 80, 
	                sortable : true, 
	                dataIndex: 'drive'
	            },{
	                header   : '전체크기', 
	                //width    : 80, 
	                sortable : true, 
	                dataIndex: 'total'
	            },{
	                header   : '사용 가능한 공간', 
	                //width    : 80, 
	                sortable : true, 
	                dataIndex: 'remain'
	            },{
	                header   : '사용률', 
	                //width    : 80, 
	                sortable : true, 
	                dataIndex: 'percent'
	            }]
        })
    });
    
    var task_grid = new Ext.grid.GridPanel({
        //renderTo: Viewport의 오른쪽 부분 
        title: '프로세스 목록',
        collapsible: true,
        //collapsed: true,
        store: task_store,
        autoHeight: true,
        autoWidth: true,
        columns: [
            {
                header   : '', 
                width    : 20, 
                sortable : true, 
                dataIndex: 'num'
            },{
                header   : '이름', 
                width    : 100, 
                sortable : true, 
                dataIndex: 'task'
            },
            {
                header   : 'PID', 
                width    : 50, 
                sortable : true,
                hidden	 : true,
                dataIndex: 'pid'
            },
            {
                header   : '날짜', 
                width    : 120,
                sortable : true, 
                dataIndex: 'date'
            },
            {
                header   : '실행된 총 시간', 
                width    : 100,
                sortable : true, 
                dataIndex: 'during'
            },
            {
                header   : '기타', 
                width    : 100,
                sortable : true, 
                dataIndex: ''
            }],
        view: new Ext.ux.grid.BufferView({
        	getRowClass : function (r, rowIndex, rp, ds)
        	{
        		if(r.get('task') == 'PCMS.exe')
        		{
        			return 'task';
        		}
        	},
        	forceFit: true
        }),
        stateful: true
        
        //stateId: 'grid'
    });
    task_grid.on('contextmenu', gridContextHandler);

    var contextMenu = new Ext.menu.Menu({
		items: [
			{ text: '강조', handler: setFirst }			
		]
	});
    
	function gridContextHandler(e){
		//console.log(e);
		//node.select();
		contextMenu.show();
	}
    
    function setFirst()
    {
        //console.log("옮기는 작업");
    }
    
    var chart = new Ext.Panel({
        iconCls:'chart',
        title: 'CPU, Memory 사용률',
        collapsible: true,
        frame:true,
        autoWidth: true,
        height:170,
        //layout:'fit',

        items: [{
            xtype: 'linechart',
            store: chart_store,
            width: '49.9%',
            url:'/ext/resources/charts.swf',
            xField: 'xfield',
            yAxis: new Ext.chart.NumericAxis({
                displayName: 'Values',
                maximum: 100,
                minimum: 0
                //labelRenderer : Ext.util.Format.numberRenderer('0,0')
            }),
            tipRenderer : function(chart, record, index, series){
                if(series.yField == 'cpu'){
                	//console.log(record);
                    return '점유률 : '+record.data.cpu + ' %\n' +
                    		'시간: '+record.data.date;
                }
            },
            chartStyle: {
                padding: 10,
                legend: {
                	display: 'right',
                	padding: '-68'
                	                	
                },
                animationEnabled: true,
                font: {
                    name: 'Tahoma',
                    color: 0xffffff,
                    size: 11
                },
                dataTip: {
                    padding: 5,
                    border: {
                        color: 0x99bbe8,
                        size:1
                    },
                    background: {
                        color: 0xDAE7F6,
                        alpha: .9
                    },
                    font: {
                        name: 'Tahoma',
                        color: 0x15428B,
                        size: 10,
                        bold: true
                    }
                },
                xAxis: {
                    color: 0x69aBc8
//                    majorTicks: {color: 0x69aBc8, length: 4},
//                    minorTicks: {color: 0x69aBc8, length: 2},
//                    majorGridLines: {size: 1, color: 0xeeeeee}
                    
                },
                yAxis: {
                    color: 0x69aBc8
//                    majorTicks: {color: 0x69aBc8, length: 4},
//                    minorTicks: {color: 0x69aBc8, length: 2},
//                    majorGridLines: {size: 1, color: 0xdfe8f6}
                }
            },
            series: [{
                type: 'line',
                displayName: 'CPU',
                yField: 'cpu',
                style: {
                    //image:'bar.png',
                    //mode: 'stretch',
                    color:0x99BBE8
                }
            }]
        },
        {
            xtype: 'linechart',
            store: chart_store,
            width: '49.9%',
            url:'/ext/resources/charts.swf',
            xField: 'xfield',
            yAxis: new Ext.chart.NumericAxis({
                displayName: 'Values',
                maximum: 100,
                minimum: 0
               //labelRenderer : Ext.util.Format.numberRenderer('0,0')
            }),
            tipRenderer : function(chart, record, index, series){
                if(series.yField == 'memory'){
                	//console.log(data_grid);
                    return '점유률 : '+record.data.memory + '% ('+record.data.memory_mb+')MB\n' +
                    		'시간 : '+record.data.date;
                }
            },
            chartStyle: {
                padding: 10,
                legend: {
                	display: 'right',
                	padding: '-68'                	
                },
                animationEnabled: true,
                font: {
                    name: 'Tahoma',
                    color: 0xffffff,
                    size: 10
                },
                dataTip: {
                    padding: 5,
                    border: {
                        color: 0x99bbe8,
                        size:1
                    },
                    background: {
                        color: 0xDAE7F6,
                        alpha: .9
                    },
                    font: {
                        name: 'Tahoma',
                        color: 0x15428B,
                        size: 10,
                        bold: true
                    }
                },
                xAxis: {
                    color: 0x69aBc8
//                    majorTicks: {color: 0x69aBc8, length: 4},
//                    minorTicks: {color: 0x69aBc8, length: 2},
//                    majorGridLines: {size: 1, color: 0xeeeeee}
                    
                },
                yAxis: {
                    color: 0x69aBc8
//                    majorTicks: {color: 0x69aBc8, length: 4},
//                    minorTicks: {color: 0x69aBc8, length: 2},
//                    majorGridLines: {size: 1, color: 0xdfe8f6}
                }
            },
            series: [{
                type: 'line',
                displayName: 'Memory',
                yField: 'memory',
                style: {
                    //image:'bar.png',
                    //mode: 'stretch',
                    color:0x99BBE8
                }
            }]
        }]
    });
    
    var settime;
    
	var main_panel = new Ext.Panel({
	  //  title: "시스템 모니터링",
	    layout: "fit",
	    listeners: {
	    	render: function(self){
	    		settime = setInterval(reloadstore, 5000);
	    	},
	    	destroy: function(self){
	    		clearInterval(settime);
	    	}	    	
	    },
	    items: [
	    	new Ext.Panel({
		    	//title: "시스템 모니터링",
		    	layout: "border",
		    	items: [{	
					region: "center",
					minWidth: 400,					
					//width: 600,					
					//minSize: 400,
					autoScroll: true,
					html: '',
					layout: 'fit',
					margins: '0 0 0 0',
					items: [
						panel								
					]
			    }, {	
			    	region: 'east',
			    	flex: 4,
			    	split: true,
			    	autoScroll: true,
			    	width: '63%',
			    	//minWidth: 350,
			    	//maxWidth: '90%',
			    	layout: 'fit',			    			    			
			    	margins: '0 0 0 0',
			    	items:[	
			    		data_list,	
			    		hdd_grid,
			    		chart,
						task_grid
			    	]
		  		}]
	    })]
	});

//	5초마다 화면 재전송
	var reloadstore = function(){		
		data_store.reload({
			params: {
				id: session_id
			}
		});
		chart_store.reload({
			params: {
				id: session_id
			}
		});
		hdd_store.reload({
			params: {
				id: session_id
			}
		});
		task_store.reload({
			params: {
				id: session_id
			}
		});	
	};

//	setInterval(reloadstore, 5000);
	
//	var server_monitoring_interval = setInterval(reloadstore, 5000);
//	console.log(server_monitoring_interval);
	//window.clearInterval(server_monitoring_interval);
	return main_panel;
})()