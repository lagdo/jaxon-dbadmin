(function(self) {
    const editor = {
        select: null,
        query: null,
        tabs: {},
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
     * @returns {string}
     */
    self.getQueryText = () => {
        // Try to get the selected text first.
        const selectedText = editor.query?.getSelectedText();
        return selectedText ? selectedText : editor.query?.getValue() ?? '';
    };

    /**
     * Set the SQL query value and reset the undo history.
     *
     * @param {string} query
     *
     * @returns {void}
     */
    self.setSqlQuery = (query) => editor.query?.session.setValue(query);

    /**
     * @param {string} tabId
     *
     * @returns {void}
     */
    self.onAppTabClick = (tabId) => {
        // Save the current app tab name.
        jaxon.bag.setEntry('dbadmin', 'tab.app', tabId);
    };

    /**
     * @param {string} tabId
     *
     * @returns {void}
     */
    self.onEditorTabClick = (tabId) => {
        editor.query = editor.tabs[tabId];
        // When the editor content is changed when it is in a hidden tab, the visible content
        // is not updated when the tab becomes visible. We need to force the refresh.
        editor.query?.session.setValue(self.getQueryText());
        // Save the current editor tab name.
        jaxon.bag.setEntry('dbadmin', 'tab.editor', tabId);
    };

    /**
     * @param {string} containerId
     * @param {string} driver
     *
     * @returns {void}
     */
    const createQueryEditor = function(containerId, driver) {
        editor.query = ace.edit(containerId, {
            mode: editor.modes[driver] ?? editor.modes.sql,
            selectionStyle: "text",
            dragEnabled: false,
            useWorker: false,
            enableBasicAutocompletion: true,
            enableSnippets: false,
            enableLiveAutocompletion: true,
            showPrintMargin: false,
        });
        editor.query.setTheme(editor.theme);
        editor.query.session.setUseWrapMode(true);
        document.getElementById(containerId).style.fontSize = editor.fontSize;
    };

    /**
     * @param {string} tabId
     * @param {string} containerId
     * @param {string} driver
     *
     * @returns {void}
     */
    self.createQueryEditor = function(tabId, containerId, driver) {
        createQueryEditor(containerId, driver);
        if (tabId === '') {
            return;
        }

        if (editor.tabs[tabId] !== undefined) {
            // Copy the query text of the previous editor instance in the tab.
            editor.query.session.setValue(editor.tabs[tabId].getValue());
            delete editor.tabs[tabId];
        }

        // Save the tab editor.
        editor.tabs[tabId] = editor.query;
        // Set the click handler on the tab nav.
        self.onEditorTabClick(tabId);
    };

    /**
     * @param {string} tabId 
     *
     * @returns {void}
     */
    self.deleteQueryEditor = (tabId) => {
        // Delete the deleted tab editor instance
        if (editor.tabs[tabId] !== undefined) {
            delete editor.tabs[tabId];
        }
    };

    /**
     * @param {string} containerId
     * @param {string} driver
     *
     * @returns {void}
     */
    self.createSelectEditor = (containerId, driver) => {
        editor.select = ace.edit(containerId, {
            mode: editor.modes[driver] ?? editor.modes.sql,
            selectionStyle: "text",
            dragEnabled: false,
            useWorker: false,
            showPrintMargin: false,
            showLineNumbers: false,
            showGutter: false, // Also hide the line number "column".
            readOnly: true,
        });
        editor.select.setTheme(editor.theme);
        editor.select.session.setUseWrapMode(true);
        editor.select.resize();
        document.getElementById(containerId).style.fontSize = editor.fontSize;
    };

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
        copyQueryText: (node, prefix) => self.setSqlQuery(getHistoryQuery(node, prefix)),

        /**
         * @param {Element} node
         * @param {string} prefix
         *
         * @returns {void}
         */
        insertQuerytext: (node, prefix) => editor.query.insert(getHistoryQuery(node, prefix)),
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
        getQueryText: (node, prefix) => getFavoriteQuery(node, prefix),

        /**
         * @param {Element} node
         * @param {string} prefix
         *
         * @returns {void}
         */
        copyQueryText: (node, prefix) => self.setSqlQuery(getFavoriteQuery(node, prefix)),

        /**
         * @param {Element} node
         * @param {string} prefix
         *
         * @returns {void}
         */
        insertQuerytext: (node, prefix) => editor.query.insert(getFavoriteQuery(node, prefix)),
    };
})(jaxon.dbadmin);
