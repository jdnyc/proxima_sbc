(function () {
  Ext.ns('Ariel.archiveManagement');
  Ariel.archiveManagement.offlineTapeLocationList = Ext.extend(Ext.grid.GridPanel, {
    title: '<span class="user_span"><span class="icon_title"><i class=""></i></span><span class="main_title_header">' + '' + '</span></span>',
    loadMask: true,
    stripeRows: true,
    frame: false,
    viewConfig: {
      emptyText: '목록이 없습니다.'
    },
    cls: 'grid_title_customize proxima_customize',
    initComponent: function () {

      this._initialize();
      Ariel.archiveManagement.offlineTapeLocationList.superclass.initComponent.call(this);
    },
    _initialize: function () {
      var _this = this;
      var sm = new Ext.grid.RowSelectionModel({
        singleSelect: false,
        listeners: {
        }
      });
      this.store = new Ext.data.JsonStore({
        remoteSort: true,
        restful: true,
        proxy: new Ext.data.HttpProxy({
          method: 'GET',
          url: '/api/v1/tape/search',
          type: 'rest'
        }),
        remoteSort: true,
        totalProperty: 'total',
        root: 'data',
        fields: [
          'ta_id',
          'ta_barcode',
          'ta_acs',
          'ta_lsm',
          'ta_media_type_tp_id',
          'ta_set_id',
          'ta_is_online',
          'ta_protected',
          'ta_enable_for_writing',
          'ta_to_be_cleared',
          'ta_enable_for_repack',
          'ta_group_tg_id',
          'ta_remaining_size',
          'ta_filling_ratio',
          'ta_fragmentation_ratio',
          'ta_block_size',
          'ta_last_written_block',
          'ta_format',
          'ta_eject_comment',
          'ta_last_archive_date',
          'ta_first_mount_date',
          'ta_last_retention_date',
          'ta_first_insertion_date',
          'ta_export_tape',
          'created_at',
          'updated_at',
          'deleted_at',
          'id',
          'tape_se',
          'cstdy_lc',
          'disprs_at',
          'ta_total_size',
          'ta_version',
          'ta_group',
          'media_id'
        ]
      });
      this.sm = sm;

      this.cm = new Ext.grid.ColumnModel({
        defaults: {
          menuDisabled: true,
          sortable: false
        },
        columns: [
          new Ext.grid.RowNumberer({ width: 30 }),
          //sm,
          //{ header: '구분', dataIndex: 'tape_se' },
          { header: '바코드', align: 'center', dataIndex: 'ta_barcode', sortable: true },
          { header: '미디어ID', align: 'center', dataIndex: 'media_id', width: 130, sortable: true },
          { header: '소산여부', align: 'center', dataIndex: 'disprs_at', width: 100, sortable: true },
          { header: '그룹', align: 'center', dataIndex: 'ta_group' },
          { header: '보관위치', align: 'left', dataIndex: 'cstdy_lc', width: 400 }
        ]
      });

      this.bbar = {
        xtype: 'paging',
        pageSize: 30,
        displayInfo: true,
        store: this.store
      };

      this._search = function () {
        var searchParams = this._getParams();
        //console.log(searchParams);
        this.getStore().load({
          params: searchParams
        })
      };

      this._getParams = function () {
        var contentId = this._contentId;
        var returnVal = {};
        returnVal['tape_se'] = 'diva';
        returnVal['content_id'] = contentId;

        return returnVal;

      }

      _this.listeners = {
        afterrender: function (self) {
          this._search();
        }
      };

    }
  });
  return new Ariel.archiveManagement.offlineTapeLocationList();
})()