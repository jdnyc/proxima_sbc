(function () {
    Ext.ns("Ariel.archiveManagement");

    Ariel.archiveManagement.originalListGrid = Ext.extend(Ext.grid.GridPanel, {
        title: '<span class="user_span"><span class="icon_title"><i class=""></i></span><span class="main_title_header">' + '방송원본 관리' + '</span></span>',
        loadMask: true, //데이터 로드하는 동안 요소를 일반적으로 마스킹
        stripeRows: true,
        frame: false,
        autowidth: true,
        viewConfig: {
            emptyText: '목록이 없습니다.',
            // forceFit: true,
            border: false
        },
        cls: 'grid_title_customize proxima_customize',
        listeners: {
            rowdblclick: function (self, rowIndex, e) {
               /*  fn: function(self, rowIndex, e){
                    this.buildEditTableWin(e);
                },
                scope: this */

                var _this = this;
                var sm = _this.getSelectionModel();
                var selectRecord = sm.getSelected();
                _this.buildEditTableWin(_this, selectRecord);
            }
        },
        initComponent: function () {
            this._initialize();
            Ariel.archiveManagement.originalListGrid.superclass.initComponent.call(this);
        },
        _initialize: function () {
            var _this = this;

            this.store = new Ext.data.SimpleStore({
                fields: [
                    'idx',
                    'tape_no',
                    'times',
                    'category',
                    'media',
                    'size',
                    'id',
                    'program_nm',
                    'length',
                    { name: "date", type: "date" },
                ],
                data: [
                    ['0', '213A07034', '1', '방송일반', '디지베타', '소', 'PG110782D', '국민안전기동대', '10', '2007.08.01'],
                    ['1', '213B05223', '1', '방송일반', '디지베타', '중', 'PG110755D', '이슈추적', '100', '2007.08.02']
                    
                ]
            });

            this.cm = new Ext.grid.ColumnModel({
                defaults: {
                    align: 'center',
                    menuDisabled: true,
                    sortable: false
                },
                columns: [
                    {
                        header: 'NO',
                        renderer: function (v, p, record, rowIndex) {
                            return rowIndex + 1;
                        }
                    },
                    { header: 'NO', dataIndex: 'idx', sortable: true, width: 50, hidden: true },
                    { header: '테입번호', dataIndex: 'tape_no', sortable: true, width: 130 },
                    { header: '회차', dataIndex: 'times', sortable: true, width: 100 },
                    { header: '분류', dataIndex: 'category', sortable: true,  width: 130 },
                    { header: '매체', dataIndex: 'media', sortable: true,width: 130 },
                    { header: '크기', dataIndex: 'size', sortable: true, width: 100 },
                    { header: '프로그램ID', dataIndex: 'id', sortable: true, width: 130 },
                    { header: '프로그램명', dataIndex: 'program_nm', sortable: true, width: 130 },
                    { header: '길이', dataIndex: 'length', sortable: true, width: 100 },
                    { header: '인수일', dataIndex: 'date', sortable: true, renderer: Ext.util.Format.dateRenderer("Y-m-d"), width: 130 }
                ]
            });
            this.tbar = [
                {
                    xtype: 'tbtext',
                    text: '프로그램코드',
                },{
                    xtype: 'textfield',
                    width: 100
                },' ', {
                    xtype: 'textfield',
                    width: 180
                },' ', {
                    xtype: 'tbtext',
                    text: '인수일',
                }, {
                    xtype: 'datefield',
                    format: 'Y-m-d'
                },'~',{
                    xtype: 'datefield',
                    format: 'Y-m-d'
                }
            ]

        },
        buildEditTableWin: function(_this, originRecord){//추후 데이터로 상세 팝업창 띄울 예정
            var win = new Ext.Window({
                cls: 'change_background_panel',
                title: '방송원본등록',
                width: 1200,
                height: 700,
                modal: true,
                layout: 'border',
                border: false,
                items: [
                    {
                        region: 'north',
                        xtype: 'fieldset',
                        width: '100%',
                        frame: false,
                        height: 300,
                        split: true,
                        layout:	'border',
                        flex:1,
                        items: [{
                            xtype:'form',
                            id: 'serchForm',
                            region: 'center',
                            border: false,
                            width: '60%',
                            labelWidth: 1,
                            defaults: {
                                anchor: '100%',
                                width: 115,
                                style: {
                                    'padding-top': '5px',
                                }
                            },
                            items: [{
                                xtype: "compositefield",
                                style: {
                                    'padding-top': '15px',
                                },
                                items: [
                                {
                                    xtype: "displayfield",
                                    value: "일련번호",
                                    width: 95,
                                },
                                {
                                    xtype: "textfield",
                                    name: "serial_num",
                                    id: "serial_num",
                                    width: 115
                                },
                                {
                                    xtype: 'displayfield',
                                    value: '구분',
                                    width: 95
                                },
                                {
                                    xtype : 'combo',
                                    width: 115,
                                    id : 'category',
                                    hiddenName: 'category',
                                    hiddenValue: 'value',
                                    displayField:'name',
                                    valueField: 'value',
                                    typeAhead: true,
                                    triggerAction: 'all',
                                    lazyRender:true,
                                    mode: 'local',
                                    editable : false,
                                    store: new Ext.data.ArrayStore({
                                            fields: ['name','value'],
                                            data: [['방송일반', 'general'], ['국회', 'congress'], ['구매', 'buy'], ['방송DCT', 'DCT'], ['기타', 'etc']]
                                    }),
                                },
                                {
                                    xtype: "displayfield",
                                    value: '테입번호',
                                    width: 95
                                },
                                {
                                    xtype: "textfield",
                                    name: "tape_no",
                                    width: 115
                                }]
                            },{
                                xtype: "compositefield",
                                items: [
                                {
                                    xtype: "displayfield",
                                    value: "매체",
                                    width: 95,
                                },
                                {
                                    xtype : 'combo',
                                    width: 115,
                                    id : 'media',
                                    hiddenName: 'media',
                                    hiddenValue: 'value',
                                    displayField:'name',
                                    valueField: 'value',
                                    typeAhead: true,
                                    triggerAction: 'all',
                                    lazyRender:true,
                                    mode: 'local',
                                    editable : false,
                                    store: new Ext.data.ArrayStore({
                                            fields: ['name','value'],
                                            data: [['베타', 'beta'], ['DCT', 'DCT'], ['디지베타', 'digibeta'], ['DVD', 'DCT'], ['HD', 'HD'], ['Blu-Ray', 'blulay']]
                                    })
                                },
                                {
                                    xtype: 'displayfield',
                                    value: '규격',
                                    width: 95,
                                },
                                {
                                    xtype : 'combo',
                                    width: 115,
                                    id : 'size',
                                    hiddenName: 'size',
                                    hiddenValue: 'value',
                                    displayField:'name',
                                    valueField: 'value',
                                    typeAhead: true,
                                    triggerAction: 'all',
                                    lazyRender:true,
                                    mode: 'local',
                                    editable : false,
                                    store: new Ext.data.ArrayStore({
                                            fields: ['name','value'],
                                            data: [['소', 'small'], ['중', 'medium'], ['대', 'large']]
                                    })
                                }]
                            },{
                                xtype: "compositefield",
                                items: [
                                {
                                    xtype: "displayfield",
                                    value: "길이",
                                    width: 95,
                                },
                                {
                                    xtype: "textfield",
                                    name: "length",
                                    id: "length",
                                    width: 50
                                },{
                                    xtype: 'displayfield',
                                    width: 60
                                },
                                {
                                    xtype: 'displayfield',
                                    value: '인수일',
                                    width: 95,
                                },
                                {
                                    xtype: "textfield",
                                    name: "acquisition_date",
                                    id: "acquisition_date",
                                    width: 115
                                }]
                            },
                            {
                                xtype: "compositefield",
                                items: [
                                {
                                    xtype: "displayfield",
                                    value: "프로그램",
                                    width: 95,
                                },
                                {
                                    xtype: "textfield",
                                    name: "program",
                                    id: "program",
                                    width: 115
                                },
                                {
                                    xtype: "textfield",
                                    name: "subject",
                                    id: "subject",
                                    width: 215
                                },
                                {
                                    xtype: "a-iconbutton",
                                    text: "VOD 검색",
                                    handler: function (btn, e) {
                                      
                                    }
                                }]
                            }]
                        },{
                            xtype: "fieldset",
                            region: 'east',
                            border: false,
                            width: '40%',
                            id: 'included_num',
                            items: [{
                                xtype: 'editorgrid',
                                height: 200,
                                frame: false,
                                clicksToEdit: 2,//더블클릭으로 변경
                                viewConfig: {
                                    emptyText: "목록이 없습니다.",
                                    border: false
                                },
                                store: new Ext.data.SimpleStore({
                                    fields: [
                                      'times',
                                      'sub_title',
                                      'broadcast_date',
                                      'PD',
                                      'category',
                                      'length'
                                    ],
                                    data: [
                                        ['230', '금융빅뱅! 자본시장의 세계화 - 임승태 재정경제부 금융', '2007.07.25', '최운석', '자체', '3624']
                                    ]
                                }),
                                cm: new Ext.grid.ColumnModel({
                                    defaults: {
                                        align: "center",
                                        menuDisabled: true,
                                        sortable: false
                                    },
                                    columns: [
                                        { header: '회차', dataIndex: 'times', sortable: true, width: 50, editor: { xtype: 'textfield' }, hidden: true },
                                        { header: '부제', dataIndex: 'sub_title', sortable: true, editor: { xtype: 'textfield' }, width: 130 },
                                        { header: '방송일', dataIndex: 'broadcast_date', sortable: true , editor: { xtype: 'textfield' }, width: 100 },
                                        { header: 'PD', dataIndex: 'PD', sortable: true , editor: { xtype: 'textfield' }, width: 100 },
                                        { header: '제작구분', dataIndex: 'category', sortable: true , editor: { xtype: 'textfield' }, width: 100 },
                                        { header: '길이', dataIndex: 'length', editor: { xtype: 'textfield' }, width: 50 }
                                    ]
                                }),
                                sm : new Ext.grid.RowSelectionModel({
                                    singleSelction: true
                                }),
                                tbar: [
                                    {
                                        xtype: "a-iconbutton",
                                        text: "추가",
                                        handler: function (self) {
                                            
                                        }
                                    },
                                    {
                                        xtype: "a-iconbutton",
                                        text: "수정",
                                        handler: function (self) {
                                           
                                        }
                                    }, {
                                        xtype: "a-iconbutton",
                                        text: "삭제",
                                        handler: function(grid, rowIndex, colIdex, item, e)  {
                                            var grid2 = Ext.getCmp('included_num');
                                            console.log(grid2);
                                            console.log(rowIndex);
                                            //deleteMovie(grid2.getStore().getAt(rowIndex));
                                            var sm = grid.getSelectionModel();
                                            deleteMovie(sm.getSelected());
                                            
                                        }
                                    }
                                ]
                            }]
                        }] 
                    },{
                        xtype: "fieldset",
                        region: "center",
                        flex: 1,
                        border: false,
                        height: 200,
                        items: [{
                            xtype: 'editorgrid',
                            height: 200,
                            frame: false,
                            clicksToEdit: 2,
                            viewConfig: {
                                emptyText: "목록이 없습니다.",
                                border: false
                            },
                            store: new Ext.data.SimpleStore({
                                fields: [
                                  'times',
                                  'content',
                                  'start_time',
                                  'end_time',
                                  'preview'
                                ],
                                data: [
                                    ['230', '전체', '00:00', '00:00', 'kangdate/kangdate_20070725_2200_00.wmv'],
                                    ['230', '주제:금융빅뱅!자본시장의 세계화', '00:00', '00:00', ' ']
                                ]
                            }),
                            cm: new Ext.grid.ColumnModel({
                                defaults: {
                                    align: "center",
                                    menuDisabled: true,
                                    sortable: false
                                },
                                columns: [
                                    { header: '회차', dataIndex: 'times', sortable: true , editor: { xtype: 'textfield' }, width: 50 },
                                    { header: '장면내용', dataIndex: 'content', sortable: true , editor: { xtype: 'textfield' },width: 350, align: "left" },
                                    { header: '시작시간', dataIndex: 'start_time', sortable: true ,editor: { xtype: 'textfield' }, width: 130 },
                                    { header: '종료시간', dataIndex: 'end_time', sortable: true,editor: { xtype: 'textfield' }, width: 130 },
                                    { header: '미리보기', dataIndex: 'preview', sortable: true ,editor: { xtype: 'textfield' }, width: 350, align: "left" }
                                ]
                            }),
                            sm : new Ext.grid.RowSelectionModel({
                                singleSelction: true
                            }),
                            tbar: [
                                {
                                    xtype: "a-iconbutton",
                                    text: "추가",
                                    handler: function (self) {
                                       
                                    }
                                },
                                {
                                    xtype: "a-iconbutton",
                                    text: "수정",
                                    handler: function (self) {
                                       
                                    }
                                }, {
                                    xtype: "a-iconbutton",
                                    text: "삭제",
                                    handler: function (self) {
                        
                                    }
                                }
                            ]
                        }] 
                    }
                ]
            }).show(); 
            
           
            function deleteMovie(record) {
                Ext.Msg.show({
                    title: 'Remove Movie', 
                    buttons: Ext.MessageBox.YESNOCANCEL,
                    msg: 'Remove ?',
                    fn: function(btn){
                        if (btn == 'yes'){
                            grid.getStore().remove(record);
                        }
                    }
                });
            }
            /* Ext.Ajax.request({
                url: '/custom/ktv-nps/js/archiveManagement/originalInput.js',
                /*   params: {
                    
                }, 
                callback: function(option,success,response){
                    if(success){
                        var result = Ext.decode(response.responseText);
                    }
                    else{
                        Ext.Msg.alert(_text('MN00022'), response.statusText+'('+response.status+')');
                    }
                }
            }); */

            

           
        } 
    });
    return new Ariel.archiveManagement.originalListGrid();
})()
