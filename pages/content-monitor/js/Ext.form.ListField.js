/*****************************************************************************
Ext.form.ListField
*****************************************************************************/
Ext.form.ListField = function(config){
    Ext.form.ListField.superclass.constructor.call(this, config);
    this.value = this.value||'';
	this.inputValues = this.inputValues||this.value;
    this.hiddenName = this.hiddenName || this.name || this.id;
    this.inputType = this.inputType||'radio';
};
Ext.extend(Ext.form.ListField, Ext.form.Field,  {
    // private
    onRender : function(ct, position){
        if(this.el){
            this.el = Ext.get(this.el);
            if(!this.target){
                ct.dom.appendChild(this.el.dom);
            }
        }else {
            var cfg = {tag: 'div'};
            cfg = {};
            this.el = ct.createChild(cfg, position);
        }
        this.tree = new Ext.tree.TreePanel(this.el.id, {
        	inputName: this.hiddenName,
        	inputType: this.inputType,
            animate:false, 
            enableDD:false,
            containerScroll: true,
            rootVisible: false,
            lines: false
        });
        var root = new Ext.tree.TreeNode({
            id:Ext.id(),
            text: 'Root'
        });
        this.tree.setRootNode(root);
        this.tree.render();
        root.expand();

        if (this.inputValues) {
			var values = this.inputValues.split(',');
			for (i=0;i<values.length;i++) {	    	
				if (values[i]!='') {
					var pair = values[i].split('=');
                    var path = pair[0];
                    var keys = path.split('/');
                    var key = keys.pop();
					var value = pair.length>1?pair[1]:key;
                    var k = keys.shift();
                    var n = this.tree.getNodeById(k);
                    while (n&&keys.length>0) {
                        k = keys.shift();
                        n = this.tree.getNodeById(k);
                    }
                    if (!n) n = this.tree.root;
                    n.appendChild(
                        new Ext.tree.TreeNode({
                            id: key,
                            checked: false,
                            removeInput: pair.length<2,
                            inputValue: key,
                            uiProvider: Ext.tree.InputTreeNodeUI,
                            text: value
                        })
                    );
				}
			}
			this.tree.root.expand(true);
		}
        this.initValue();
    },
    checkNode: function(node,check) {
	    if (node&&node.ui&&node.ui.input) {
	    	c = check!=null;
    		node.checked = c;
			node.ui.checked = c;
			node.ui.input.checked = c;
		}
	},
    setValue : function(v){
    	this.tree.root.eachChild(this.checkNode);
		var values = v.split(',');
		for (i=0;i<values.length;i++) 	    	
    		this.checkNode(this.tree.getNodeById(values[i]),true);
    },
    getRawValue : function(){
        return this.getValue();
    },
    getValue : function(){
        var r = [];
        var fn =function(node) {
            if (node.checked)
                r[r.length] = node.id;
        }
        this.tree.root.eachChild(fn);
        return r.join(',');
    }
});