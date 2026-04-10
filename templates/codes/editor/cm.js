(function(self, types) {
    /**
     * @var {object} lib Filled with functions from the CodeMirror modules.
     */
    self.lib = {};

    /**
     * @param {string} containerId
     * @param {bool} readOnly
     * @param {string} driver
     * @param {object} schema
     *
     * @returns {object}
     */
    self.create = function(containerId, readOnly, driver, { tables } = {}) {
        const container = document.getElementById(containerId);
        if (!container) {
            return null;
        }

        // Save the query text and clear the editor container.
        const queryText = container.textContent;
        container.innerHTML = '';

        const schema = !types.isArray(tables) || tables.length === 0 ? null :
            tables.reduce((schema, { name: tableName, columns }) => {
                schema[tableName] = columns.map(({ name: columnName }) => columnName);
                return schema;
            }, {});
        return {
            driver,
            instance: self.lib.editor(container, queryText, readOnly, driver, schema),
        };
    };

    /**
     * @param {object} editor
     * @param {string} query
     *
     * @returns {void}
     */
    self.setQuery = ({ instance } = {}, query) => instance?.dispatch({
        changes: { from: 0, to: instance.state.doc.length, insert: query },
    });

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

        const { state: editorState } = instance;
        const { doc: queryText } = editorState;
        if (!takeSelectedText) {
            return queryText.toString() ?? '';
        }

        // Try to get the selected text first.
        const { selection: { main: { from, to } } } = editorState;
        const selectedText = editorState.sliceDoc(from, to);
        return selectedText ? selectedText : (queryText.toString() ?? '');
    };

    /**
     * Set the SQL query value and reset the undo history.
     *
     * @param {object} editor
     * @param {string} query
     *
     * @returns {void}
     */
    self.resetQuery = (editor, query) => self.setQuery(editor, query);
    // self.resetQuery = ({ instance } = {}, query) =>
    //     instance?.setState(EditorState.create({doc: query }));

    /**
     * Refresh the editor by resetting the SQL query to its current value.
     * (Nothing to do here, since the CodeMirror editor does not need to be refreshed.)
     *
     * @param {object} editor
     *
     * @returns {void}
     */
    self.refreshQuery = (editor) => false, // self.setQuery(editor, self.getQuery(editor, false));

    /**
     * @param {object} editor
     * @param {string} query
     *
     * @returns {void}
     */
    self.insertQuery = ({ instance } = {}, query) => {
        if (!instance) {
            return;
        }
        const { state: { selection: { main: { from, to } } } } = instance;
        instance.dispatch({ changes: { from, to, insert: query } });
    };
})(jaxon.dbadmin.editor, jaxon.utils.types);
