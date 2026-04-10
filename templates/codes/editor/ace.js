(function(self) {
    const options = {
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
     * @param {bool} readOnly
     * @param {string} driver
     * @param {object} schema
     *
     * @returns {object}
     */
    self.create = function(containerId, readOnly, driver, schema) {
        const editor = ace.edit(containerId, {
            mode: options.modes[driver] ?? options.modes.sql,
            selectionStyle: "text",
            dragEnabled: false,
            useWorker: false,
            enableSnippets: false,
            enableBasicAutocompletion: !readOnly,
            enableLiveAutocompletion: !readOnly,
            showPrintMargin: false,
            showLineNumbers: true,
            showGutter: true, // !readOnly, // Also hide the line number "column".
            readOnly: readOnly,
        });
        editor.setTheme(options.theme);
        editor.session.setUseWrapMode(true);
        editor.resize();
        document.getElementById(containerId).style.fontSize = options.fontSize;

        return { driver, instance: editor };
    };

    /**
     * @param {object} editor
     * @param {string} query
     *
     * @returns {void}
     */
    self.setQuery = ({ instance } = {}, query) => instance?.session.setValue(query);

    /**
     * Set the SQL query value and reset the undo history.
     *
     * @param {object} editor
     * @param {string} query
     *
     * @returns {void}
     */
    self.resetQuery = ({ instance } = {}, query) => instance?.session.setValue(query);

    /**
     * Refresh the editor by resetting the SQL query to its current value.
     *
     * @param {object} editor
     *
     * @returns {void}
     */
    self.refreshQuery = (editor) => self.setQuery(editor, self.getQuery(editor, false));

    /**
     * @param {object} editor
     * @param {bool} takeSelectedText
     *
     * @returns {string}
     */
    self.getQuery = ({ instance } = {}, takeSelectedText = false) => {
        if (!instance) {
            return '';
        }
        if (!takeSelectedText) {
            return instance.getValue() ?? '';
        }
        // Try to get the selected text first.
        const selectedText = instance.getSelectedText();
        return selectedText ? selectedText : (instance.getValue() ?? '');
    };

    /**
     * @param {object} editor
     * @param {string} query
     *
     * @returns {void}
     */
    self.insertQuery = ({ instance } = {}, query) => instance?.insert(query);
})(jaxon.dbadmin.editor);
