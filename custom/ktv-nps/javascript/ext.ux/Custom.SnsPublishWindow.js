(function() {
  Ext.ns("Custom");
  Custom.SnsPublishWindow = Ext.extend(Ext.Window, {
    // properties
    // SNS 게시물 아이디
    postId: null,
    // 콘텐츠 아이디
    contentId: null,
    data: null,

    // private variables
    _topPanel: null,
    _selectedChannel: null,
    _selectedPlatform: null,
    _selectedFile: null,
    _metadataForm: null,
    _imageUploadPanel: null,
    _components: ["Custom.ImageUploadPanel"],
    // SNS 게시물 객체
    _post: null,

    constructor: function(config) {
      this.addEvents("ok");

      this.title = "SNS 게시";
      this.width = 700;
      this.minWidth = 510;
      this.modal = true;
      this.top = 100;
      this.height = 620;
      this.minHeight = 620;
      //this.height = getSafeHeight(600);

      this.layout = "border";

      Ext.apply(this, {}, config || {});

      var _this = this;

      _this._initItems(config);

      Ext.apply(_this.listeners, {
        render: function(self) {},
        beforedestroy: function(self) {},
        show: function(self) {}
      });

      Ext.Loader.load(getComponentUrls(this._components), function() {
        _this._imageUploadPanel = new Custom.ImageUploadPanel();
        _this._metadataForm.add(_this._imageUploadPanel);
        _this._metadataForm.doLayout();
      });

      this._getPublishedPlatforms(this.contentId);

      Custom.SnsPublishWindow.superclass.constructor.call(_this);
    },

    initComponent: function(config) {
      Custom.SnsPublishWindow.superclass.initComponent.call(this);
    },

    isEditMode: function() {
      return this.postId !== null;
    },

    _makeTitle: function(title) {},

    _getPublishedPlatforms: function(contentId) {
      var _this = this;
      Ext.Ajax.request({
        url: requestApiPath("/contents/" + contentId + "/sns-posts"),
        method: "GET",
        success: function(response, opts) {
          try {
            var r = Ext.decode(response.responseText);
            if (r.success && r.data) {
              if (r.data.length <= 0) {
                return;
              }
              var posts = r.data;

              // SNS 게시된 플랫폼 추가
              Ext.each(posts, function(post, i) {
                _this._addPublishedPlatform(post.platform.slug);
                if (post.id == _this.postId) {
                  _this._post = post;
                }
              });

              // topPanel 보이기
              _this._topPanel.show();
              _this.doLayout();

              var currentPost = _this._post;

              // 채널과 플랫폼 정보 채워 넣기
              _this._channelNameField.setValue(currentPost.channel.name);
              _this._platformNameField.setValue(currentPost.platform.name);

              // 선택 채널 및 플랫폼 셋팅
              _this._selectedChannel = {
                id: currentPost.channel.id,
                name: currentPost.channel.name
              };

              _this._selectedPlatform = {
                id: currentPost.platform.id,
                name: currentPost.platform.name,
                slug: currentPost.platform.slug
              };
              // 폼 메타 채워넣기
              var form = _this._metadataForm.getForm();

              console.table(currentPost);

              var toDoLayout = false;
              // 유튜브면 카테고리 콤보 보이도록 설정
              if (currentPost.platform.slug === "yt") {
                var categoryCombo = _this._metadataForm.categoryCombo;
                categoryCombo.show();
                categoryCombo.getStore().load({
                  callback: function() {
                    categoryCombo.setValue(currentPost.category_id);
                  }
                });
                toDoLayout = true;
              }

              // 예약이면 예약 시간 보이도록 설정
              if (currentPost.privacy_status === "book") {
                _this._metadataForm.bookDateField.show();
                toDoLayout = true;
              }

              _this._imageUploadPanel.loadImage(currentPost.thumbnail.url);

              var data = {
                title: currentPost.title,
                description: currentPost.description,
                privacy_status: currentPost.privacy_status,
                booked_at: currentPost.booked_at
              };
              _this._loadData(form, data);

              // if (toDoLayout) {
              //   _this.doLayout();
              // }
            }else{
              Ext.Msg.alert('알림',r.msg);
            }
          } catch (error) {
            Ext.Msg.alert('알림',r.msg);
            // console.error(error);
          }
        }
      });
    },

    _loadData: function(form, data) {
      if (data === null) {
        return;
      }

      if (data.booked_at) {
        var bookedAt = new Date(data.booked_at);
        data.booked_date = bookedAt.format("Y-m-d");
        data.booked_time = bookedAt.format("H:i");
      }
      form.setValues(data);
    },

    _fireOkEvent: function() {
      this.fireEvent("ok", this, this._selected);
    },

    _addPublishedPlatform: function(platformSlug) {
      if (Ext.isEmpty(platformSlug)) {
        return;
      }
      var platform = new Ext.BoxComponent({
        width: 25,
        height: 25,
        autoEl: {
          tag: "img",
          src: "/css/icons/social/" + platformSlug + ".png"
        }
      });
      this._topPanel.add(platform);
      this._topPanel.doLayout();
    },

    _initItems: function(config) {
      var _this = this;

      this._topPanel = new Ext.Container({
        region: "north",
        hidden: true,
        height: 30,
        layout: "hbox",
        layoutConfig: {
          padding: "5",
          pack: "start",
          align: "middle"
        },
        defaults: { margins: "0 5 0 0" }
      });

      // 플랫폼 선택 그리드
      var platformSelectionGrid = new Ext.grid.GridPanel({
        title: "플랫폼",
        flex: 1,
        store: new Ext.data.JsonStore({
          proxy: makeHttpProxy("GET", "/api/v1"),
          root: "data",
          fields: [{ name: "id", type: "int" }, "name", "slug", "setting"]
        }),
        hideHeaders: true,
        columns: [
          {
            header: "플랫폼명",
            width: 130,
            dataIndex: "name"
          }
        ],
        sm: new Ext.grid.RowSelectionModel({
          listeners: {
            rowselect: function(self, rowIndex, record) {
              _this._selectedPlatform = {
                id: record.get("id"),
                name: record.get("name"),
                slug: record.get("slug")
              };
              // 유튜브 선택할 때만 카테고리가 나타나도록 수정
              var categoryCombo = _this._metadataForm.categoryCombo;
              if (record.get("slug") === "yt") {
                // 콤보가 보여 있으면 할 필요 없음
                if (!categoryCombo.isVisible()) {
                  categoryCombo.show();
                  // 스토어가 로드되어 있으면 할 필요 없음
                  if (!categoryCombo.getStore().getCount()) {
                    categoryCombo.getStore().load({
                      callback: function() {
                        // 최초 로드시만 설정
                        if (
                          Ext.isEmpty(categoryCombo.getValue()) &&
                          !_this.isEditMode()
                        ) {
                          categoryCombo.setValue(
                            record.get("setting").category_id
                          );
                        }
                      }
                    });
                  }
                }
              } else {
                categoryCombo.hide();
              }
            }
          }
        })
      });

      // 채널 선택 그리드
      var channelSelectionGrid = new Ext.grid.GridPanel({
        title: "채널",
        flex: 2,
        store: new Ext.data.JsonStore({
          proxy: makeHttpProxy("GET", "/api/v1/social/channels"),
          root: "data",
          fields: [{ name: "id", type: "int" }, "name"],
          autoLoad: true
        }),
        hideHeaders: true,
        columns: [
          {
            header: "채널명",
            width: 130,
            dataIndex: "name"
          }
        ],
        sm: new Ext.grid.RowSelectionModel({
          listeners: {
            rowselect: function(self, rowIndex, record) {
              _this._selectedChannel = {
                id: record.get("id"),
                name: record.get("name")
              };
              _this._selectedPlatform = null;
              // 채널 선택 시 유효한 플랫폼 로드
              var platformStore = platformSelectionGrid.getStore();
              var url =
                "/api/v1/social/channels/" + record.get("id") + "/platforms";
              platformStore.proxy.setUrl(url);
              platformStore.load();
            }
          }
        })
      });

      // 메타데이터 폼
      this._metadataForm = this._makeMetadataForm();

      this._channelNameField = new Ext.form.TextField({
        style: {
          backgroundColor: "lightgray"
        },
        readOnly: true
      });

      this._platformNameField = new Ext.form.TextField({
        style: {
          backgroundColor: "lightgray"
        },
        readOnly: true
      });

      this.items = [
        this._topPanel,
        {
          xtype: "container",
          hidden: this.isEditMode(),
          region: "west",
          width: 150,
          layout: {
            type: "vbox",
            padding: "5",
            align: "stretch"
          },
          items: [channelSelectionGrid, platformSelectionGrid]
        },
        {
          xtype: "container",
          region: "center",
          width: 150,
          layout: {
            type: "vbox",
            padding: "5",
            align: "stretch"
          },
          items: [
            {
              xtype: "container",
              hidden: !this.isEditMode(),
              height: 40,
              layout: "hbox",
              layoutConfig: {
                padding: "5",
                align: "middle"
              },
              defaults: { margins: "0 0 0 0" },
              items: [
                {
                  xtype: "label",
                  text: "채널:"
                },
                this._channelNameField,
                new Ext.Spacer({
                  width: 10
                }),
                {
                  xtype: "label",
                  text: "플랫폼:"
                },
                this._platformNameField
              ]
            },
            this._metadataForm
          ]
        }
      ];

      this.bbar = {
        items: [
          "->",
          {
            xtype: "a-iconbutton",
            text: "취소",
            handler: function(btn, e) {
              _this.close();
            }
          },
          "->",
          {
            xtype: "a-iconbutton",
            text: "확인",
            handler: function(btn, e) {
              var form = _this._metadataForm.getForm();
              var values = form.getValues();
              var params = {};
              params.content_id = _this.contentId;

              if (_this._selectedChannel) {
                params.channel_id = _this._selectedChannel.id;
                values.channel_id = params.channel_id;
              }

              if (_this._selectedPlatform) {
                params.platform_id = _this._selectedPlatform.id;
                values.platform_id = params.platform_id;
              }

              params.category_id = _this._metadataForm.categoryCombo.getValue();

              if (
                values.privacy_status === "book" &&
                !Ext.isEmpty(values.booked_date) &&
                !Ext.isEmpty(values.booked_time)
              ) {
                var bookedDate = new Date(
                  values.booked_date + " " + values.booked_time
                );
                params.booked_at = bookedDate.format("Y-m-d H:i:s");
                values.booked_at = params.booked_at;
                values.bookedDate = bookedDate;
              } else {
                values.booked_at = null;
                values.bookedDate = null;
              }

              if (!_this._validate(values)) {
                return;
              }

              var submitPath = "/contents/" + _this.contentId + "/sns-posts";
              Ext.MessageBox.show({
                msg: "등록중입니다. 잠시만 기다려주세요...",
                progressText: "Saving...",
                width: 300,
                wait: true,
                waitConfig: { interval: 200 }
              });
              form.submit({
                url: requestApiPath(submitPath),
                params: params,
                success: function(form, action) {
                  Ext.MessageBox.hide();
                  Ext.Msg.alert("확인", "SNS 게시가 등록되었습니다.");
                  _this.close();
                },
                failure: function(form, action) {
                  Ext.MessageBox.hide();
                  switch (action.failureType) {
                    case Ext.form.Action.CLIENT_INVALID:
                      Ext.Msg.alert(
                        "Failure",
                        "Form fields may not be submitted with invalid values"
                      );
                      break;
                    case Ext.form.Action.CONNECT_FAILURE:
                      Ext.Msg.alert("Failure", "Ajax communication failed");
                      break;
                    case Ext.form.Action.SERVER_INVALID:
                      Ext.Msg.alert("Failure", action.result.msg);
                  }
                }
              });
              // _this._fireOkEvent();
            }
          }
        ]
      };
    },

    /**
     * 메타데이터 정합성 체크
     *
     * @param {Object}} values
     */
    _validate: function(values) {
      if (Ext.isEmpty(values.channel_id)) {
        Ext.Msg.alert("알림", "채널을 선택해 주세요.");
        return false;
      }

      if (Ext.isEmpty(values.platform_id)) {
        Ext.Msg.alert("알림", "플랫폼을 선택해 주세요.");
        return false;
      }

      if (Ext.isEmpty(values.title)) {
        Ext.Msg.alert("알림", "제목을 입력해 주세요.");
        return false;
      }

      if (Ext.isEmpty(values.description)) {
        Ext.Msg.alert("알림", "내용을 입력해 주세요.");
        return false;
      }

      if (values.privacy_status === "book") {
        if (Ext.isEmpty(values.booked_at)) {
          Ext.Msg.alert("알림", "예약 일시를 입력해 주세요.");
          return false;
        }

        if (values.bookedDate <= new Date()) {
          Ext.Msg.alert("알림", "예약 일시는 현재보다 과거일 수 없습니다.");
          return false;
        }
      }

      return true;
    },

    _makeMetadataForm: function() {
      var bookDateField = new Ext.form.CompositeField({
        hidden: true,
        fieldLabel: "예약일시",
        items: [
          {
            xtype: "datefield",
            format: "Y-m-d",
            name: "booked_date",
            minValue: new Date(),
            value: new Date()
          },
          {
            xtype: "timefield",
            width: 90,
            name: "booked_time",
            value: new Date().add(Date.HOUR, 3)
          }
        ]
      });

      var categoryCombo = new Ext.form.ComboBox({
        hidden: true,
        fieldLabel: "카테고리",
        name: "category_id",
        editable: false,
        valueField: "id",
        displayField: "name",
        triggerAction: "all",
        store: new Ext.data.JsonStore({
          proxy: makeHttpProxy("GET", "/api/v1/social/categories"),
          root: "data",
          fields: [{ name: "id", type: "int" }, "name", "code"]
        })
      });

      var form = new Ext.form.FormPanel({
        bodyStyle: "padding:5px",
        fileUpload: true,
        defaults: {
          xtype: "textfield",
          anchor: "100%"
        },
        flex: 1,
        categoryCombo: categoryCombo,
        bookDateField: bookDateField,
        items: [
          {
            fieldLabel: "제목",
            name: "title"
          },
          {
            xtype: "textarea",
            fieldLabel: "내용",
            height: 250,
            name: "description"
          },
          categoryCombo,
          {
            xtype: "radiogroup",
            fieldLabel: "공개여부",
            items: [
              {
                boxLabel: "공개",
                name: "privacy_status",
                inputValue: "public",
                checked: true
              },
              {
                boxLabel: "비공개",
                name: "privacy_status",
                inputValue: "private"
              },
              {
                boxLabel: "예약",
                name: "privacy_status",
                inputValue: "book"
              }
            ],
            listeners: {
              change: function(self, radio) {
                /**
                 * 라디오 버튼 변경 시 예약일 경우만
                 * 예약 일시를 표출 한다.
                 */
                if (radio.inputValue === "book") {
                  bookDateField.show();
                } else {
                  bookDateField.hide();
                }
              }
            }
          },
          bookDateField
        ],
        hideLabels: false,
        labelAlign: "left", // or 'right' or 'top'
        labelWidth: 55, // defaults to 100
        labelPad: 8 // defaults to 5, must specify labelWidth to be hono
      });

      return form;
    }
  });
  Ext.reg("c-sns-publish-window", Custom.SnsPublishWindow);
})();
