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

    const selectAllCheckboxes = (checkboxId) => $('#' + checkboxId + '-all').change(function() {
        $('.' + checkboxId, '#jaxon-dbadmin').prop('checked', this.checked);
    });

    const setFileUpload = (container, buttonId, fileInputId) => {
        // Trigger a click on the hidden file select component when the user clicks on the button.
        $(buttonId).click(() => $(fileInputId).trigger("click"));
        $(container).on('change', ':file', function() {
            const fileInput = $(this);
            const numFiles = fileInput.get(0).files ? fileInput.get(0).files.length : 1;
            const label = fileInput.val().replace(/\\/g, '/').replace(/.*\//, '');
            const textInput = $(container).find(':text');
            const text = numFiles > 1 ? numFiles + ' files selected' : label;
            textInput.length > 0 && textInput.val(text);
        });
    };

    const editor = {
        container: null,
        hintOptions: {},
        modes: {
            sql: 'text/x-sql',
            mysql: 'text/x-mysql',
            pgsql: 'text/x-pgsql',
        },
    };

    const createSqlEditor = function(containerId, driver) {
        const container = document.getElementById(containerId);
        editor.container = CodeMirror.fromTextArea(container, {
            mode: editor.modes[driver] || editor.modes.sql,
            indentWithTabs: true,
            smartIndent: true,
            lineNumbers: true,
            matchBrackets : true,
            autofocus: true,
            extraKeys: {'Ctrl-Space': 'autocomplete'},
            hintOptions: editor.hintOptions,
            /*hintOptions: {
                tables: {
                    users: ["name", "score", "birthDate"],
                    countries: ["name", "population", "size"]
                }
            }*/
        });
    };

    const refreshSqlQuery = (txtQueryId, driver) => {
        // The query is replaced by the string formatted with CodeMirror.
        const container = document.getElementById(txtQueryId);
        const query = container.innerText || container.textContent;
        // Erase the initial SQL text.
        container.innerHTML = '';
        CodeMirror(container, {
            value: query,
            mode: editor.modes[driver] || editor.modes.sql,
            lineNumbers: false,
            readOnly: true,
        });
    };

    const getSqlQuery = () => {
        // Try to get the selected text first.
        const selectedText = editor.container.getSelection();
        return selectedText ? selectedText : editor.container.getValue();
    };

    const saveSqlEditorContent = () => editor.container.save();

    return {
        countTableCheckboxes,
        selectTableCheckboxes,
        selectAllCheckboxes,
        setFileUpload,
        createSqlEditor,
        refreshSqlQuery,
        getSqlQuery,
        saveSqlEditorContent,
    };
})();
