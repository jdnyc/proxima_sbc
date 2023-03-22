Ext.MessageBox = (function () {
  var dlg,
    opt,
    mask,
    waitTimer,
    bodyEl,
    msgEl,
    textboxEl,
    textareaEl,
    progressBar,
    pp,
    iconEl,
    spacerEl,
    buttons,
    activeTextEl,
    bwidth,
    bufferIcon = '',
    iconCls = '',
    buttonNames = ['ok', 'yes', 'no', 'cancel'];

  var handleButton = function (button) {
    buttons[button].blur();
    if (dlg.isVisible()) {
      dlg.hide();
      handleHide();
      Ext.callback(
        opt.fn,
        opt.scope || window,
        [button, activeTextEl.dom.value, opt],
        1
      );
    }
  };

  var handleHide = function () {
    if (opt && opt.cls) {
      dlg.el.removeClass(opt.cls);
    }
    progressBar.reset();
  };

  var handleEsc = function (d, k, e) {
    if (opt && opt.closable !== false) {
      dlg.hide();
      handleHide();
    }
    if (e) {
      e.stopEvent();
    }
  };

  var updateButtons = function (b) {
    var width = 0,
      cfg;
    if (!b) {
      Ext.each(buttonNames, function (name) {
        buttons[name].hide();
      });
      return width;
    }
    dlg.footer.dom.style.display = '';
    Ext.iterate(buttons, function (name, btn) {
      cfg = b[name];
      if (cfg) {
        btn.show();
        btn.setText(Ext.isString(cfg) ? cfg : Ext.MessageBox.buttonText[name]);
        width += btn.getEl().getWidth() + 15;
      } else {
        btn.hide();
      }
    });
    return width;
  };

  return {
    getDialog: function (titleText) {
      if (!dlg) {
        var btns = [];

        buttons = {};
        Ext.each(
          buttonNames,
          function (name) {
            btns.push(
              (buttons[name] = new Ext.Button({
                text: this.buttonText[name],
                handler: handleButton.createCallback(name),
                hideMode: 'offsets'
              }))
            );
          },
          this
        );
        dlg = new Ext.Window({
          autoCreate: true,
          title: titleText,
          resizable: false,
          constrain: true,
          constrainHeader: true,
          minimizable: false,
          maximizable: false,
          stateful: false,
          modal: true,
          shim: true,
          buttonAlign: 'center',
          width: 400,
          height: 100,
          minHeight: 80,
          plain: true,
          footer: true,
          closable: true,
          close: function () {
            if (opt && opt.buttons && opt.buttons.no && !opt.buttons.cancel) {
              handleButton('no');
            } else {
              handleButton('cancel');
            }
          },
          fbar: new Ext.Toolbar({
            items: btns,
            enableOverflow: false
          })
        });
        dlg.render(document.body);
        dlg.getEl().addClass('x-window-dlg');
        mask = dlg.mask;
        bodyEl = dlg.body.createChild({
          html:
            '<div class="ext-mb-icon"></div><div class="ext-mb-content"><span class="ext-mb-text"></span><br /><div class="ext-mb-fix-cursor"><input type="text" class="ext-mb-input" /><textarea class="ext-mb-textarea"></textarea></div></div>'
        });
        iconEl = Ext.get(bodyEl.dom.firstChild);
        var contentEl = bodyEl.dom.childNodes[1];
        msgEl = Ext.get(contentEl.firstChild);
        textboxEl = Ext.get(contentEl.childNodes[2].firstChild);
        textboxEl.enableDisplayMode();
        textboxEl.addKeyListener([10, 13], function () {
          if (dlg.isVisible() && opt && opt.buttons) {
            if (opt.buttons.ok) {
              handleButton('ok');
            } else if (opt.buttons.yes) {
              handleButton('yes');
            }
          }
        });
        textareaEl = Ext.get(contentEl.childNodes[2].childNodes[1]);
        textareaEl.enableDisplayMode();
        progressBar = new Ext.ProgressBar({
          renderTo: bodyEl
        });
        bodyEl.createChild({ cls: 'x-clear' });
      }
      return dlg;
    },

    updateText: function (text) {
      if (!dlg.isVisible() && !opt.width) {
        dlg.setSize(this.maxWidth, 100);
      }
      msgEl.update(text || '&#160;');

      var iw = iconCls != '' ? iconEl.getWidth() + iconEl.getMargins('lr') : 0,
        mw = msgEl.getWidth() + msgEl.getMargins('lr'),
        fw = dlg.getFrameWidth('lr'),
        bw = dlg.body.getFrameWidth('lr'),
        w;

      if (Ext.isIE && iw > 0) {
        iw += 3;
      }
      w = Math.max(
        Math.min(opt.width || iw + mw + fw + bw, opt.maxWidth || this.maxWidth),
        Math.max(opt.minWidth || this.minWidth, bwidth || 0)
      );

      if (opt.prompt === true) {
        activeTextEl.setWidth(w - iw - fw - bw);
      }
      if (opt.progress === true || opt.wait === true) {
        progressBar.setSize(w - iw - fw - bw);
      }
      if (Ext.isIE && w == bwidth) {
        w += 4;
      }

      if (Ext.isIE) {
        w += 20;
      }
      //메시지박스 강제로 width 길이 추가 2015-08-20 이성용
      w += 5;

      dlg.setSize(w, 'auto').center();
      return this;
    },

    updateProgress: function (value, progressText, msg) {
      progressBar.updateProgress(value, progressText);
      if (msg) {
        this.updateText(msg);
      }
      return this;
    },

    isVisible: function () {
      return dlg && dlg.isVisible();
    },

    hide: function () {
      var proxy = dlg ? dlg.activeGhost : null;
      if (this.isVisible() || proxy) {
        dlg.hide();
        handleHide();
        if (proxy) {
          dlg.unghost(false, false);
        }
      }
      return this;
    },

    show: function (options) {
      if (this.isVisible()) {
        this.hide();
      }
      opt = options;
      var d = this.getDialog(opt.title || '&#160;');

      d.setTitle(opt.title || '&#160;');
      var allowClose =
        opt.closable !== false && opt.progress !== true && opt.wait !== true;
      d.tools.close.setDisplayed(allowClose);
      activeTextEl = textboxEl;
      opt.prompt = opt.prompt || (opt.multiline ? true : false);
      if (opt.prompt) {
        if (opt.multiline) {
          textboxEl.hide();
          textareaEl.show();
          textareaEl.setHeight(
            Ext.isNumber(opt.multiline) ? opt.multiline : this.defaultTextHeight
          );
          activeTextEl = textareaEl;
        } else {
          textboxEl.show();
          textareaEl.hide();
        }
      } else {
        textboxEl.hide();
        textareaEl.hide();
      }
      activeTextEl.dom.value = opt.value || '';
      if (opt.prompt) {
        d.focusEl = activeTextEl;
      } else {
        var bs = opt.buttons;
        var db = null;
        if (bs && bs.ok) {
          db = buttons['ok'];
        } else if (bs && bs.yes) {
          db = buttons['yes'];
        }
        if (db) {
          d.focusEl = db;
        }
      }
      if (opt.iconCls) {
        d.setIconClass(opt.iconCls);
      }
      this.setIcon(Ext.isDefined(opt.icon) ? opt.icon : bufferIcon);
      bwidth = updateButtons(opt.buttons);
      progressBar.setVisible(opt.progress === true || opt.wait === true);
      this.updateProgress(0, opt.progressText);
      this.updateText(opt.msg);
      if (opt.cls) {
        d.el.addClass(opt.cls);
      }
      d.proxyDrag = opt.proxyDrag === true;
      d.modal = opt.modal !== false;
      d.mask = opt.modal !== false ? mask : false;
      if (!d.isVisible()) {
        document.body.appendChild(dlg.el.dom);
        d.setAnimateTarget(opt.animEl);

        d.on(
          'show',
          function () {
            if (allowClose === true) {
              d.keyMap.enable();
            } else {
              d.keyMap.disable();
            }
          },
          this,
          { single: true }
        );
        d.show(opt.animEl);
      }
      if (opt.wait === true) {
        progressBar.wait(opt.waitConfig);
      }
      return this;
    },

    setIcon: function (icon) {
      if (!dlg) {
        bufferIcon = icon;
        return;
      }
      bufferIcon = undefined;
      if (icon && icon != '') {
        iconEl.removeClass('x-hidden');
        iconEl.replaceClass(iconCls, icon);
        bodyEl.addClass('x-dlg-icon');
        iconCls = icon;
      } else {
        iconEl.replaceClass(iconCls, 'x-hidden');
        bodyEl.removeClass('x-dlg-icon');
        iconCls = '';
      }
      return this;
    },

    progress: function (title, msg, progressText) {
      this.show({
        title: title,
        msg: msg,
        buttons: false,
        progress: true,
        closable: false,
        minWidth: this.minProgressWidth,
        progressText: progressText
      });
      return this;
    },

    wait: function (msg, title, config) {
      this.show({
        title: title,
        msg: msg,
        buttons: false,
        closable: false,
        wait: true,
        modal: true,
        minWidth: this.minProgressWidth,
        waitConfig: config
      });
      return this;
    },

    alert: function (title, msg, fn, scope) {
      this.show({
        title: title,
        msg: msg,
        buttons: this.OK,
        fn: fn,
        scope: scope,
        minWidth: this.minWidth
      });
      return this;
    },

    confirm: function (title, msg, fn, scope) {
      this.show({
        title: title,
        msg: msg,
        buttons: this.YESNO,
        fn: fn,
        scope: scope,
        icon: this.QUESTION,
        minWidth: this.minWidth
      });
      return this;
    },

    prompt: function (title, msg, fn, scope, multiline, value) {
      this.show({
        title: title,
        msg: msg,
        buttons: this.OKCANCEL,
        fn: fn,
        minWidth: this.minPromptWidth,
        scope: scope,
        prompt: true,
        multiline: multiline,
        value: value
      });
      return this;
    },

    OK: { ok: true },

    CANCEL: { cancel: true },

    OKCANCEL: { ok: true, cancel: true },

    YESNO: { yes: true, no: true },

    YESNOCANCEL: { yes: true, no: true, cancel: true },

    INFO: 'ext-mb-info',

    WARNING: 'ext-mb-warning',

    QUESTION: 'ext-mb-question',

    ERROR: 'ext-mb-error',

    defaultTextHeight: 75,

    maxWidth: 600,

    minWidth: 100,

    minProgressWidth: 250,

    minPromptWidth: 250,

    buttonText: {
      ok: 'OK',
      cancel: 'Cancel',
      yes: 'Yes',
      no: 'No'
    }
  };
})();

Ext.Msg = Ext.MessageBox;

Ext.override(Ext.Tip, {
  doAutoWidth: function (adjust) {
    adjust = adjust || 0;
    //퀵팁 강제로 width 길이 추가 2015-08-20 이성용
    var bw = this.body.getTextWidth() + 5;
    if (this.title) {
      bw = Math.max(bw, this.header.child('span').getTextWidth(this.title));
    }
    bw +=
      this.getFrameWidth() +
      (this.closable ? 20 : 0) +
      this.body.getPadding('lr') +
      adjust;
    this.setWidth(bw.constrain(this.minWidth, this.maxWidth));

    if (Ext.isIE7 && !this.repainted) {
      this.el.repaint();
      this.repainted = true;
    }
  }
});

Ext.override(Ext.grid.CheckboxSelectionModel, {
  handleMouseDown: function (g, rowIndex, e) {
    if (
      (g.enableDragDrop || g.enableDrag) &&
      e.getTarget().className == 'x-grid3-row-checker'
    ) {
      return;
    } else {
      Ext.grid.CheckboxSelectionModel.superclass.handleMouseDown.apply(
        this,
        arguments
      );
    }
  }
});
Ext.override(Ext.PagingToolbar, {
  doLoad: function (start) {
    var o = {},
      pn = this.getParams();
    o[pn.start] = start;
    o[pn.limit] = this.pageSize;
    if (this.fireEvent('beforechange', this, o) !== false) {
      var options = Ext.apply({}, this.store.lastOptions);
      options.params = Ext.applyIf(o, options.params);
      this.store.load(options);
    }
  }
});

Ext.override(Ext.form.BasicForm, {
  getValues: function (asString) {
    var fs = Ext.lib.Ajax.serializeForm(this.el.dom);
    if (asString === true) {
      return fs;
    }
    //custom field 추가
    var returnVal = Ext.urlDecode(fs);
    Ext.each(this.items.items, function (r) {
      if (r.xtype == 'customfield') {
        returnVal[r.id] = r.value;
      }
    });
    return returnVal;
  },
  isValid: function () {
    var valid = true;
    this.items.each(function (f) {

      if (!f.hidden) {

        if (!f.isDestroyed && !f.validate()) {
          var f_ownerCt = f.ownerCt;
          var sub_valid = false;
          while (f_ownerCt !== undefined) {
            if (f_ownerCt.getXType() == 'fieldset' && f_ownerCt.checkboxName !== undefined) {
              if (f_ownerCt.collapsed === true) {
                sub_valid = true;
                break;
              }
            }
            if (f_ownerCt.initialConfig.ownerCt) {
              f_ownerCt = f_ownerCt.initialConfig.ownerCt;
            } else {
              f_ownerCt = f_ownerCt.ownerCt;
            }
          }
          if (!sub_valid) {
            valid = false;
          }
        }
      }
    });
    return valid;
  }
});

Ext.apply(Ext.form.VTypes, {
  phone: (function () {
    var re = /^[0-9]{2,3}[\-]{0,}[0-9]{3,4}[\-]{0,}[0-9]{4}$/;
    return function (v) {
      return re.test(v);
    };
  })(),
  phoneText:
    'The phone number format is wrong, ie: 123-456-7890 (dashes optional) ',
  phoneMask: /[0-9\-]/,
  fax: (function () {
    var re = /^[\(\)\.\- ]{0,}[0-9]{3}[\(\)\.\- ]{0,}[0-9]{3}[\(\)\.\- ]{0,}[0-9]{4}[\(\)\.\- ]{0,}$/;
    return function (v) {
      return re.test(v);
    };
  })(),
  faxText: 'The fax format is wrong',
  zipCode: (function () {
    var re = /^\d{5}(-\d{4})?$/;
    return function (v) {
      return re.test(v);
    };
  })(),
  zipCodeText: 'The zip code format is wrong, e.g., 94105-0011 or 94105',
  ssn: (function () {
    var re = /^\d{3}-\d{2}-\d{4}$/;
    return function (v) {
      return re.test(v);
    };
  })(),
  ssnText: 'The SSN format is wrong, e.g., 123-45-6789'
});

Ext.override(Ext.Component, {
  _initializeByPermission: function (permission) { },
  initComponent: function () {
    var _this = this;
    //컴포넌트에 권한 기능 로직 추가
    //권한 필요한 기능은 _initializeByPermission 에 정의
    if (Ext.isDefined(_this.permission_code)) {
      Ext.Ajax.request({
        method: 'POST',
        url: '/api/v1/permission/search-by-path',
        params: {
          code_path: _this.permission_code
        },
        callback: function (opts, success, resp) {
          var rtn = Ext.decode(resp.responseText);
          if (success) {
            if (_this._initializeByPermission != undefined) {
              if (rtn.success) {
                _this._initializeByPermission(rtn.data);
              }
            }
          } else {
            Ext.Msg.alert('Error', rtn.msg);
          }
        }
      });
    }
    Ext.Component.superclass.constructor.call(this);
  }
});
