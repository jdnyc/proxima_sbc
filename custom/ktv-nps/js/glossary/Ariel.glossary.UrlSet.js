Ext.ns('Ariel.glossary');
Ariel.glossary.UrlSet = new Ext.extend(Object, {
    word: '/api/v1/data-dic-words',
    field: '/api/v1/data-dic-fields',
    table: '/api/v1/data-dic-tables',
    column: '/api/v1/data-dic-columns',
    domain: '/api/v1/data-dic-domains',
    codeSet: '/api/v1/data-dic-code-sets',
    codeItem: '/api/v1/data-dic-code-items',
    wordIdParam: function (id) {
        return this.word + '/' + id;
    },
    wordNameParamSearch: function () {
        return this.word + '/search';
    },
    fieldNameParamSearch: function () {
        return this.field + '/search';
    },
    fieldIdParam: function (id) {
        return this.field + '/' + id;
    },
    fieldIdParamColumns: function (id) {
        return this.field + '/' + id + '/columns';
    },
    tableIdParamColumns: function (id) {
        return this.table + '/' + id + '/columns';
    },
    tableIdParam: function (id) {
        return this.table + '/' + id;
    },
    columnIdParam: function (id) {
        return this.column + '/' + id;
    },
    domainIdParam: function (id) {
        return this.domain + '/' + id;
    },
    domainIdParamCodeItems: function (id) {
        return this.domain + '/' + id + '/code-items';
    },
    codeSetIdParam: function (id) {
        return this.codeSet + '/' + id;
    },
    /**
     * 
     * @param id  code_set_id or code_set_code 
     */
    codeSetIdParamCodeItems: function (id) {
        return this.codeSet + '/' + id + '/code-items';
    },
    codeItemsByCodeSetId: function (id) {
        return this.codeItem + '/' + id + '/code-items-list';
    },
    codeSetIdParamCodes: function (id) {
        return this.codeSet + '/' + id + '/codes';
    },

    //codeItemIdParam
    codeItemIdParam: function (id) {
        return this.codeItem + '/' + id;
    }
});
Ariel.glossary.UrlSet = new Ariel.glossary.UrlSet();