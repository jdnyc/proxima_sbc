/*****************************************************************************
Ext.tree.InputTreeNodeUI
*****************************************************************************/
Ext.tree.InputTreeNodeUI = function(){
    Ext.tree.InputTreeNodeUI.superclass.constructor.apply(this, arguments);
};
Ext.extend(Ext.tree.InputTreeNodeUI,Ext.tree.TreeNodeUI, {
	oninputclick:function() {
		var n = this.node;
		n.checked = this.input.checked;
		n.cascade(
			function(){
				this.checked = n.checked;
				this.ui.input.checked = n.checked;
			}
		);
	},
    render : function(bulkRender){
        var n = this.node;
        var targetNode = n.parentNode ? 
              n.parentNode.ui.getContainer() : n.ownerTree.container.dom;
        if(!this.rendered){
            this.rendered = true;
            var a = n.attributes;
        
            // add some indent caching, this helps performance when rendering a large tree
            this.indentMarkup = "";
            if(n.parentNode){
                this.indentMarkup = n.parentNode.ui.getChildIndent();
            }
            var inputEl = '';
            if (!(a.removeInput||n.removeInput)) {
	            var ic = {};
	            ic.tree = n.getOwnerTree();
	            ic.inputTag = ic.tree.inputTag||a.inputTag||n.inputTag||'input';
	            ic.inputName = ic.tree.inputName||a.inputName||n.inputName||ic.tree.id+'-input';
	            ic.inputType = ic.tree.inputType||a.inputType||n.inputType||'radio';
	            ic.inputClass = ic.tree.inputCls||a.inputCls||n.inputCls||'x-tree-node-input';
	            ic.inputClass = ' class="'+ic.inputClass+'"';
	            ic.inputStyle = ic.tree.inputStyle||a.inputStyle||n.inputStyle||'margin-left:1px;vertical-align: middle;';
	            if (ic.inputStyle) ic.inputStyle = ' style="'+ic.inputStyle+'"';
	            ic.checked = a.checked||n.checked||'';
	            ic.value = a.inputValue||n.inputValue||(ic.checked?'on':'');
	            ic.id = Ext.id();
	            inputEl = '<'+ic.inputTag+' id="'+ic.id+'"'+(ic.inputClass?ic.inputClass:'')+(ic.inputStyle?ic.inputStyle:'')+' name="'+ic.inputName+'[]" type="'+ic.inputType+'" value="'+ic.value+'"'+(ic.checked?' checked>':'>');
			} else
	            inputEl = '<span></span>';
	        inputEl = inputEl+'<span unselectable="on">'+n.text+'</span>';
            
            var buf = ['<li class="x-tree-node"><div class="x-tree-node-el ', n.attributes.cls,'">',
                '<span class="x-tree-node-indent">',this.indentMarkup,"</span>",
                '<img src="', this.emptyIcon, '" class="x-tree-ec-icon">',
                '<img src="', this.emptyIcon, '" class=""','" unselectable="on">',//(a.icon ? " x-tree-node-inline-icon" : ""),(a.iconCls ? " "+a.iconCls : "")
                inputEl,
                '</div>',
                '<ul class="x-tree-node-ct" style="display:none;"></ul>',
                "</li>"];
                
            if(bulkRender !== true && n.nextSibling && n.nextSibling.ui.getEl()){
                this.wrap = Ext.DomHelper.insertHtml("beforeBegin",
                                    n.nextSibling.ui.getEl(), buf.join(""));
            }else{
                this.wrap = Ext.DomHelper.insertHtml("beforeEnd", targetNode, buf.join(""));
            }
            this.elNode = this.wrap.childNodes[0];
            this.ctNode = this.wrap.childNodes[1];
            var cs = this.elNode.childNodes;
            this.indentNode = cs[0];
            this.ecNode = cs[1];
            this.iconNode = cs[2];
            this.input = cs[3];
			if (ic&&ic.inputType=='checkbox') Ext.get(ic.id).addListener('click',this.oninputclick,this);
	        this.anchor = cs[4];
	        this.textNode = cs[4].firstChild;
            if(a.qtip){
               if(this.textNode.setAttributeNS){
                   this.textNode.setAttributeNS("ext", "qtip", a.qtip);
                   if(a.qtipTitle){
                       this.textNode.setAttributeNS("ext", "qtitle", a.qtipTitle);
                   }
               }else{
                   this.textNode.setAttribute("ext:qtip", a.qtip);
                   if(a.qtipTitle){
                       this.textNode.setAttribute("ext:qtitle", a.qtipTitle);
                   }
               } 
            }
            this.initEvents();
            if(!this.node.expanded){
                this.updateExpandIcon();
            }
        }else{
            if(bulkRender === true) {
                targetNode.appendChild(this.wrap);
            }
        }
    }
});