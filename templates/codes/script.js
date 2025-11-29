jaxon.dbadmin = (function() {
    const countTableCheckboxes = (checkboxId) => $('#dbadmin-table-' + checkboxId + '-count')
        .html($('.dbadmin-table-' + checkboxId + ':checked').length);

    const selectTableCheckboxes = (checkboxId) => {
        $('#dbadmin-table-' + checkboxId + '-all').change(function() {
            $('.dbadmin-table-' + checkboxId, '#jaxon-dbadmin').prop('checked', this.checked);
            countTableCheckboxes(checkboxId);
        });
        $('.dbadmin-table-' + checkboxId).change(function() {
            countTableCheckboxes(checkboxId);
        });
    };

    const setExportEventHandlers = (checkboxId) => {
        // Select all
        $('#' + checkboxId + '-all').change(function() {
            $('.' + checkboxId, '#jaxon-dbadmin').prop('checked', this.checked);
        });
        // Select database or table
        const prefixLength = checkboxId.length - 5;
        if (checkboxId.substring(prefixLength, checkboxId.length) === '-name') {
            $('.' + checkboxId, '#jaxon-dbadmin').change(function() {
                const dataCheckboxId = checkboxId.substring(0, prefixLength) +
                    '-data-' + $(this).attr('data-pos');
                $('#' + dataCheckboxId, '#jaxon-dbadmin').prop('checked', this.checked);
            });
        }
    };

    const setFileUpload = (container) => {
        $(container).on('change', ':file', function() {
            const fileInput = $(this);
            const numFiles = fileInput.get(0).files ? fileInput.get(0).files.length : 1;
            const label = fileInput.val().replace(/\\/g, '/').replace(/.*\//, '');
            const textInput = $(container).find(':text');
            const text = numFiles > 1 ? numFiles + ' files selected' : label;
            textInput.length > 0 && textInput.val(text);
        });
    };

    const downloadFile = (url, filename) => {
        const downloadLink = document.createElement("a");
        downloadLink.href = url;
        downloadLink.download = filename;
        document.body.appendChild(downloadLink);
        downloadLink.click();
        document.body.removeChild(downloadLink);
    };

    const editor = {
        ace: null,
        page: '',
        fontSize: '13px',
        modes: {
            sql: 'ace/mode/sql',
            mysql: 'ace/mode/mysql',
            pgsql: 'ace/mode/pgsql',
        },
    };

    const createSqlQueryEditor = function(containerId, driver) {
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
        editor.ace.setTheme("ace/theme/textmate");
        editor.ace.session.setUseWrapMode(true);
        document.getElementById(containerId).style.fontSize = editor.fontSize;
    };

    const createSqlSelectEditor = (containerId, driver) => {
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
        editor.ace.setTheme("ace/theme/textmate");
        editor.ace.session.setUseWrapMode(true);
        editor.ace.resize();
        document.getElementById(containerId).style.fontSize = editor.fontSize;
    };

    const getSqlQuery = () => {
        // Try to get the selected text first.
        const selectedText = editor.ace.getSelectedText();
        return selectedText ? selectedText : editor.ace.getValue();
    };

    // Set the SQL query value and reset the undo history.
    const setSqlQuery = (query) => editor.ace.session.setValue(query);

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

    const getHistoryQuery = (node) =>
        $('#dbadmin-history-query-' + getQueryId(node, 'td')).text();

    const getFavoriteQuery = (node) =>
        $('#dbadmin-favorite-query-' + getQueryId(node, 'td')).text();

    return {
        countTableCheckboxes,
        selectTableCheckboxes,
        setExportEventHandlers,
        setFileUpload,
        downloadFile,
        createSqlQueryEditor,
        createSqlSelectEditor,
        getSqlQuery,
        setSqlQuery,
        history: {
            copySqlQuery: (node) => setSqlQuery(getHistoryQuery(node)),
            insertSqlQuery: (node) => editor.ace.insert(getHistoryQuery(node)),
        },
        favorite: {
            getQueryId: (node) => getQueryId(node, 'td'),
            copySqlQuery: (node) => setSqlQuery(getFavoriteQuery(node)),
            insertSqlQuery: (node) => editor.ace.insert(getFavoriteQuery(node)),
        },
    };
})();

jaxon.dom.ready(() => {
    const spin = {
        spinner: new Spin.Spinner({ position: 'fixed' }),
        count: 0, // To make sure that the spinner is started once.
        cursor: jaxon.config.cursor.update,
    };

    // Replace the default Jaxon defined cursor with our custom spinner.
    jaxon.config.cursor.update = {
        onRequest: function() {
            if(spin.count++ === 0)
            {
                spin.spinner.spin(document.body);
                spin.cursor.onRequest();
            }
        },
        onComplete: function() {
            if(--spin.count === 0)
            {
                spin.spinner.stop();
                spin.cursor.onComplete();
            }
        },
        onFailure: function() {
            if(--spin.count === 0)
            {
                spin.spinner.stop();
                spin.cursor.onFailure && spin.cursor.onFailure();
            }
        },
    };
});
