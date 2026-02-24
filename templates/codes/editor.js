(function(self) {
    const appEditors = {
        select: null, // Editor for the select SQL text
        query: null, // Editor for the query SQL text
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
     * @param {string} appTabId
     * @param {string} appPage
     *
     * @returns {array}
     */
    self.getQueries = (appTabId, appPage) => {
        const queries = {};
        const tabEditors = appEditors.tabs[appTabId] ?? {};
        Object.values(tabEditors).filter(({ page } = {}) => page === appPage)
            .forEach(({ id, editor }) => queries[id] = editor.getValue() ?? '');
        return queries;
    };

    /**
     * @returns {string}
     */
    self.getQueryText = () => {
        // Try to get the selected text first.
        const selectedText = appEditors.query?.getSelectedText();
        return selectedText ? selectedText : appEditors.query?.getValue() ?? '';
    };

    /**
     * Set the SQL query value and reset the undo history.
     *
     * @param {string} query
     *
     * @returns {void}
     */
    self.setQueryText = (query) => appEditors.query?.session.setValue(query);

    /**
     * @param {string} appTabId
     *
     * @returns {void}
     */
    self.onAppTabClick = (appTabId) => jaxon.bag.setEntry('dbadmin', 'tab.app', appTabId);

    /**
     * @param {string} appTabId
     * @param {string} appPage
     * @param {string} editorTabId
     * @param {object} newEditor
     *
     * @returns {bool}
     */
    const addTabEditor = (appTabId, appPage, editorTabId, newEditor) => {
        const tabEditors = appEditors.tabs[appTabId] ?? {};
        appEditors.tabs[appTabId] = {
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
    const hasTabEditor = (appTabId, editorTabId) => !appEditors.tabs[appTabId] ?
        false : appEditors.tabs[appTabId][editorTabId] !== undefined;

    /**
     * @param {string} appTabId
     * @param {string} editorTabId
     *
     * @returns {object|null}
     */
    const getTabEditor = (appTabId, editorTabId) => !appEditors.tabs[appTabId] ?
        null : (!appEditors.tabs[appTabId][editorTabId] ? null :
            appEditors.tabs[appTabId][editorTabId]['editor'] ?? null);

    /**
     * @param {string} appTabId
     * @param {string} editorTabId
     *
     * @returns {mixed}
     */
    const delTabEditor = (appTabId, editorTabId) => {
        delete appEditors.tabs[appTabId][editorTabId];
        appEditors.tabs[appTabId][editorTabId] = undefined;
    };

    /**
     * @param {string} appTabId
     *
     * @returns {mixed}
     */
    self.delAppEditors = (appTabId) => {
        const tabEditors = appEditors.tabs[appTabId] ?? null;
        if (tabEditors !== null) {
            Object.keys(tabEditors).forEach(editorTabId => delTabEditor(appTabId, editorTabId));
            delete appEditors.tabs[appTabId];
            appEditors.tabs[appTabId] = undefined;
        }
    };

    /**
     * @param {string} appTabId
     * @param {string} editorTabId
     *
     * @returns {void}
     */
    self.onEditorTabClick = (appTabId, editorTabId) => {
        appEditors.query = getTabEditor(appTabId, editorTabId);
        // When the editor content is changed when it is in a hidden tab, the visible content
        // is not updated when the tab becomes visible. We need to force the refresh.
        appEditors.query?.session.setValue(self.getQueryText());
        // Save the current editor tab name.
        jaxon.bag.setEntry('dbadmin', 'tab.editor', editorTabId);
    };

    /**
     * @param {string} containerId
     * @param {string} driver
     *
     * @returns {void}
     */
    const createQueryEditor = function(containerId, driver) {
        appEditors.query = ace.edit(containerId, {
            mode: appEditors.modes[driver] ?? appEditors.modes.sql,
            selectionStyle: "text",
            dragEnabled: false,
            useWorker: false,
            enableBasicAutocompletion: true,
            enableSnippets: false,
            enableLiveAutocompletion: true,
            showPrintMargin: false,
        });
        appEditors.query.setTheme(appEditors.theme);
        appEditors.query.session.setUseWrapMode(true);
        document.getElementById(containerId).style.fontSize = appEditors.fontSize;
    };

    /**
     * @param {string} containerId
     * @param {string} driver
     * @param {string} appTabId
     * @param {string} appPage
     * @param {string} editorTabId
     *
     * @returns {void}
     */
    self.createQueryEditor = function(containerId, driver, appTabId, appPage, editorTabId) {
        createQueryEditor(containerId, driver);
        if (!editorTabId || !appTabId) {
            return;
        }

        const prevEditor = getTabEditor(appTabId, editorTabId);
        if (prevEditor !== null) {
            // Copy the query text of the previous editor instance in the tab.
            appEditors.query.session.setValue(prevEditor.getValue());
            delTabEditor(appTabId, editorTabId);
        }

        // Save the current editor tab name.
        jaxon.bag.setEntry('dbadmin', 'tab.editor', editorTabId);
        // Save the tab editor.
        addTabEditor(appTabId, appPage, editorTabId, appEditors.query);
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
            appEditors.query.session.setValue(sourceEditor.getValue());
        }
    };

    /**
     * @param {string} containerId
     * @param {string} driver
     *
     * @returns {void}
     */
    self.createSelectEditor = (containerId, driver) => {
        appEditors.select = ace.edit(containerId, {
            mode: appEditors.modes[driver] ?? appEditors.modes.sql,
            selectionStyle: "text",
            dragEnabled: false,
            useWorker: false,
            showPrintMargin: false,
            showLineNumbers: false,
            showGutter: false, // Also hide the line number "column".
            readOnly: true,
        });
        appEditors.select.setTheme(appEditors.theme);
        appEditors.select.session.setUseWrapMode(true);
        appEditors.select.resize();
        document.getElementById(containerId).style.fontSize = appEditors.fontSize;
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
            appEditors.query.insert(getHistoryQuery(node, prefix));
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
            appEditors.query.insert(getFavoriteQuery(node, prefix));
            showInfoMessage(toast.messages.inserted);
        },
    };

    /**
     * Jaxon javascript callback for upload requests.
     */
    self.upload = {
        /**
         * @param {object} oRequest
         *
         * @returns {void}
         */
        onInitialize: (oRequest) => {
            // The upload field id must be associated to the current app tab id.
            const appTabId = jaxon.bag.getEntry('dbadmin', 'tab.app') ?? '';
            oRequest.upload = `${appTabId}_${oRequest.upload}`;
        },
    };
})(jaxon.dbadmin);
