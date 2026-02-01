(function(self) {
    const editor = {
        ace: null,
        page: '',
        fontSize: '13px',
        modes: {
            sql: 'ace/mode/sql',
            mysql: 'ace/mode/mysql',
            pgsql: 'ace/mode/pgsql',
        },
        theme: 'ace/theme/textmate',
    };

    /**
     * @param {string} containerId
     * @param {string} driver
     *
     * @returns {void}
     */
    self.createSqlQueryEditor = function(containerId, driver) {
        editor.ace = ace.edit(containerId, {
            mode: editor.modes[driver] ?? editor.modes.sql,
            selectionStyle: "text",
            dragEnabled: false,
            useWorker: false,
            enableBasicAutocompletion: true,
            enableSnippets: false,
            enableLiveAutocompletion: true,
            showPrintMargin: false,
        });
        editor.ace.setTheme(editor.theme);
        editor.ace.session.setUseWrapMode(true);
        document.getElementById(containerId).style.fontSize = editor.fontSize;
    };

    /**
     * @param {string} containerId
     * @param {string} driver
     *
     * @returns {void}
     */
    self.createSqlSelectEditor = (containerId, driver) => {
        editor.ace = ace.edit(containerId, {
            mode: editor.modes[driver] ?? editor.modes.sql,
            selectionStyle: "text",
            dragEnabled: false,
            useWorker: false,
            showPrintMargin: false,
            showLineNumbers: false,
            showGutter: false, // Also hide the line number "column".
            readOnly: true,
        });
        editor.ace.setTheme(editor.theme);
        editor.ace.session.setUseWrapMode(true);
        editor.ace.resize();
        document.getElementById(containerId).style.fontSize = editor.fontSize;
    };

    /**
     * @returns {string}
     */
    self.getSqlQuery = () => {
        // Try to get the selected text first.
        const selectedText = editor.ace.getSelectedText();
        return selectedText ? selectedText : editor.ace.getValue();
    };

    /**
     * Set the SQL query value and reset the undo history.
     *
     * @param {string} query
     *
     * @returns {void}
     */
    self.setSqlQuery = (query) => editor.ace.session.setValue(query);

    /**
     * When the editor content is changed when it is in a hidden tab, the visible content
     * is not updated when the tab becomes visible. We need to force the refresh.
     *
     * @returns {void}
     */
    self.refreshContent = () => editor.ace.session.setValue(self.getSqlQuery());

    /**
     * Read the data-query-id attribute in the parent with the given tag name
     *
     * @param {Element} node
     * @param {string} tag
     *
     * @returns {string}
     */
    const getQueryId = (node, tag) => {
        while ((parent = node?.parent())) {
            if (parent.prop('tagName')?.toLowerCase() === tag) {
                return parent.attr('data-query-id') ?? '';
            }
            node = parent;
        }
        return '';
    };

    /**
     * @param {Element} node
     * @param {string} prefix
     *
     * @returns {string}
     */
    const getHistoryQuery = (node, prefix) => $(`#${prefix}` + getQueryId(node, 'td')).text();

    /**
     * @param {Element} node
     * @param {string} prefix
     *
     * @returns {string}
     */
    const getFavoriteQuery = (node, prefix) => $(`#${prefix}` + getQueryId(node, 'td')).text();

    self.history =  {
        /**
         * @param {Element} node
         * @param {string} prefix
         *
         * @returns {void}
         */
        copySqlQuery: (node, prefix) => self.setSqlQuery(getHistoryQuery(node, prefix)),

        /**
         * @param {Element} node
         * @param {string} prefix
         *
         * @returns {void}
         */
        insertSqlQuery: (node, prefix) => editor.ace.insert(getHistoryQuery(node, prefix)),
    };

    self.favorite = {
        /**
         * @param {Element} node
         *
         * @returns {string}
         */
        getQueryId: (node) => getQueryId(node, 'td'),

        /**
         * @param {Element} node
         * @param {string} prefix
         *
         * @returns {string}
         */
        getSqlQuery: (node, prefix) => getFavoriteQuery(node, prefix),

        /**
         * @param {Element} node
         * @param {string} prefix
         *
         * @returns {void}
         */
        copySqlQuery: (node, prefix) => self.setSqlQuery(getFavoriteQuery(node, prefix)),

        /**
         * @param {Element} node
         * @param {string} prefix
         *
         * @returns {void}
         */
        insertSqlQuery: (node, prefix) => editor.ace.insert(getFavoriteQuery(node, prefix)),
    };
})(jaxon.dbadmin);
