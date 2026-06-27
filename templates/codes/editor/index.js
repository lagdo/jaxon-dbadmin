(function(self) {
    const editors = {
        select: null, // Editor for the current table SQL query.
        query: null, // Editor for the current tab SQL query.
        tabs: {},
    };

    /**
     * @var {object}
     */
    self.editor = {};

    /**
     * @param {string} appTabId
     * @param {string} appPage
     *
     * @returns {array}
     */
    self.getQueries = (appTabId, appPage) => {
        const queries = {};
        const tabEditors = editors.tabs[appTabId] ?? {};
        Object.values(tabEditors).filter(({ page } = {}) => page === appPage)
            .forEach(({ id, editor }) => queries[id] = self.editor.getQuery(editor));
        return queries;
    };

    /**
     * @returns {string}
     */
    self.getSelectQuery = () => !editors.select ? '' :
        self.editor.getQuery(editors.select, true);

    /**
     * @returns {string}
     */
    self.getQueryText = () => self.editor.getQuery(editors.query, true);

    /**
     * Set the SQL query value and reset the undo history.
     *
     * @param {string} query
     *
     * @returns {void}
     */
    self.setQueryText = (query) => self.editor.resetQuery(editors.query, query);

    /**
     * @param {string} appTabId
     *
     * @returns {void}
     */
    self.onAppTabClick = (appTabId) => jaxon.bag.setEntry('dbadmin.app', 'tab', appTabId);

    /**
     * @param {string} appTabId
     * @param {string} appPage
     * @param {string} editorTabId
     * @param {object} newEditor
     *
     * @returns {bool}
     */
    const addTabEditor = (appTabId, appPage, editorTabId, newEditor) => {
        const tabEditors = editors.tabs[appTabId] ?? {};
        editors.tabs[appTabId] = {
            ...tabEditors,
            [editorTabId]: {
                id: editorTabId,
                page: appPage,
                editor: newEditor,
            },
        };
    };

    /**
     * @param {string} appTabId
     * @param {string} editorTabId
     *
     * @returns {bool}
     */
    const hasTabEditor = (appTabId, editorTabId) => !editors.tabs[appTabId] ?
        false : editors.tabs[appTabId][editorTabId] !== undefined;

    /**
     * @param {string} appTabId
     * @param {string} editorTabId
     *
     * @returns {object|null}
     */
    const getTabEditor = (appTabId, editorTabId) => !editors.tabs[appTabId] ?
        null : (!editors.tabs[appTabId][editorTabId] ? null :
            editors.tabs[appTabId][editorTabId]['editor'] ?? null);

    /**
     * @param {string} appTabId
     * @param {string} editorTabId
     *
     * @returns {mixed}
     */
    const delTabEditor = (appTabId, editorTabId) => {
        delete editors.tabs[appTabId][editorTabId];
        editors.tabs[appTabId][editorTabId] = undefined;
    };

    /**
     * @param {string} appTabId
     *
     * @returns {mixed}
     */
    self.delAppEditors = (appTabId) => {
        const tabEditors = editors.tabs[appTabId] ?? null;
        if (tabEditors !== null) {
            Object.keys(tabEditors).forEach(editorTabId => delTabEditor(appTabId, editorTabId));
            delete editors.tabs[appTabId];
            editors.tabs[appTabId] = undefined;
        }
    };

    /**
     * @param {string} appTabId
     * @param {string} editorTabId
     *
     * @returns {void}
     */
    self.onEditorTabClick = (appTabId, editorTabId) => {
        editors.query = getTabEditor(appTabId, editorTabId);
        // When the editor content is changed when it is in a hidden tab, the visible content
        // is not updated when the tab becomes visible. We need to force the refresh.
        self.editor.refreshQuery(editors.query);
        // Save the current editor tab name.
        jaxon.bag.setEntry('dbadmin', 'tab.editor', editorTabId);
    };

    /**
     * @param {string} containerId
     * @param {string} driver
     * @param {object} schema
     * @param {string} appTabId
     * @param {string} appPage
     * @param {string} editorTabId
     *
     * @returns {void}
     */
    self.createQueryEditor = (containerId, driver, schema, appTabId, appPage, editorTabId) => {
        editors.query = self.editor.create(containerId, false, driver, schema);
        const prevEditor = getTabEditor(appTabId, editorTabId);
        if (prevEditor !== null) {
            // Copy the query text of the previous editor instance in the tab.
            self.editor.setQuery(editors.query, self.editor.getQuery(prevEditor));
            delTabEditor(appTabId, editorTabId);
        }

        // Save the current editor tab name.
        jaxon.bag.setEntry('dbadmin', 'tab.editor', editorTabId);
        // Save the tab editor.
        addTabEditor(appTabId, appPage, editorTabId, editors.query);
    };

    /**
     * @param {string} appTabId
     * @param {string} editorTabId
     *
     * @returns {void}
     */
    self.deleteQueryEditor = (appTabId, editorTabId) => {
        // Delete the deleted tab editor instance
        if (hasTabEditor(appTabId, editorTabId)) {
            delTabEditor(appTabId, editorTabId);
        }
    };

    /**
     * @param {string} appTabId
     * @param {string} sourceTabId
     *
     * @returns {void}
     */
    self.copyQueryText = (appTabId, sourceTabId) => {
        const sourceEditor = getTabEditor(appTabId, sourceTabId);
        if (sourceEditor !== null) {
            // Copy the query text from the source editor.
            self.editor.setQuery(editors.query, self.editor.getQuery(sourceEditor));
        }
    };

    /**
     * @param {string} containerId
     * @param {string} driver
     *
     * @returns {void}
     */
    self.createSelectEditor = (containerId, driver) =>
        editors.select = self.editor.create(containerId, true, driver);

    /**
     * @param {string} containerId
     * @param {string} driver
     *
     * @returns {void}
     */
    self.createViewEditor = (containerId, driver) =>
        self.editor.create(containerId, false, driver);

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

    /**
     * @var {object}
     */
    const toast = {
        lib: '',
        messages: {
            copied: 'Copied!',
            inserted: 'Inserted!',
        },
    };

    /**
     * @param {string}
     *
     * @returns {void}
     */
    self.setToastLib = (lib) => toast.lib = lib;

    /**
     * @param {string}
     *
     * @returns {void}
     */
    const showInfoMessage = (message) => {
        if (toast.lib !== '') {
            jaxon.dialog.alert(toast.lib, { type: 'info', text: message });
        }
    };

    self.history =  {
        /**
         * @param {Element} node
         * @param {string} prefix
         *
         * @returns {void}
         */
        copyQueryText: (node, prefix) => {
            self.setQueryText(getHistoryQuery(node, prefix));
            showInfoMessage(toast.messages.copied);
        },

        /**
         * @param {Element} node
         * @param {string} prefix
         *
         * @returns {void}
         */
        insertQuerytext: (node, prefix) => {
            self.editor.insertQuery(editors.query, getHistoryQuery(node, prefix));
            showInfoMessage(toast.messages.inserted);
        },
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
        copyQueryText: (node, prefix) => {
            self.setQueryText(getFavoriteQuery(node, prefix));
            showInfoMessage(toast.messages.copied);
        },

        /**
         * @param {Element} node
         * @param {string} prefix
         *
         * @returns {void}
         */
        insertQuerytext: (node, prefix) => {
            self.editor.insertQuery(editors.query, getFavoriteQuery(node, prefix));
            showInfoMessage(toast.messages.inserted);
        },
    };
})(jaxon.dbadmin);
