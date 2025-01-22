jaxon.dbadmin = {
    countTableCheckboxes: function(checkboxId) {
        $('#dbadmin-table-' + checkboxId + '-count').html($('.dbadmin-table-' + checkboxId + ':checked').length);
    },
    selectTableCheckboxes: function(checkboxId) {
        $('#dbadmin-table-' + checkboxId + '-all').change(function() {
            $('.dbadmin-table-' + checkboxId, '#jaxon-dbadmin').prop('checked', this.checked);
            jaxon.dbadmin.countTableCheckboxes(checkboxId);
        });
        $('.dbadmin-table-' + checkboxId).change(function() {
            jaxon.dbadmin.countTableCheckboxes(checkboxId);
        });
    },
    selectAllCheckboxes: function(checkboxId) {
        $('#' + checkboxId + '-all').change(function() {
            $('.' + checkboxId, '#jaxon-dbadmin').prop('checked', this.checked);
        });
    },
    setFileUpload: function(container, buttonId, fileInputId) {
        // Trigger a click on the hidden file select component when the user clicks on the button.
        $(buttonId).click(function() {
            $(fileInputId).trigger("click");
        });
        $(container).on('change', ':file', function() {
            let fileInput = $(this),
                numFiles = fileInput.get(0).files ? fileInput.get(0).files.length : 1,
                label = fileInput.val().replace(/\\/g, '/').replace(/.*\//, ''),
                textInput = $(container).find(':text'),
                text = numFiles > 1 ? numFiles + ' files selected' : label;

            if (textInput.length > 0) {
                textInput.val(text);
            }
        });
    },
    onColumnRenamed: function() {
        let column = $(this).parent();
        // The get() method returns the wrapped js object.
        while ((column) && !column.get().hasAttribute('data-index')) {
            column = column.parent();
        }
        if (!column) {
            return;
        }
        const index = parseInt(column.attr('data-index'), 10) + 1;
        $(this).attr('name', 'fields[' + index + '][' + $(this).attr('data-field') + ']');
    },
    insertSelectQueryItem: function(targetId, templateId) {
        const index = jaxon.dbadmin.newItemIndex++;
        const itemHtml = $('#' + templateId).html().replace(/__index__/g, index);
        const targetElt = jaxon.$(targetId);
        targetElt.insertAdjacentHTML('beforeend', itemHtml);
    },
    removeSelectQueryItems: function(containerId, checkboxClass) {
        $('.' + checkboxClass + ':checked', '#' + containerId).each(function() {
            const targetId = '#' + containerId + '-item-' + $(this).attr('data-index');
            $(targetId).remove();
        });
    },
    editor: {
        query: '',
        element: null,
        hintOptions: {},
        modes: {
            sql: 'text/x-sql',
            mysql: 'text/x-mysql',
            pgsql: 'text/x-pgsql',
        },
    },
    highlightSqlQuery: function(containerId, driver, query) {
        const mode = jaxon.dbadmin.editor.modes[driver] || jaxon.dbadmin.editor.modes.sql;
        const element = document.getElementById(containerId);
        jaxon.dbadmin.editor.query = query;
        CodeMirror(element, { value: query, mode, lineNumbers: false, readOnly: true });
    },
    highlightSqlEditor: function(containerId, driver) {
        const mode = jaxon.dbadmin.editor.modes[driver] || jaxon.dbadmin.editor.modes.sql;
        const element = document.getElementById(containerId);
        jaxon.dbadmin.editor.element = CodeMirror.fromTextArea(element, {
            mode,
            indentWithTabs: true,
            smartIndent: true,
            lineNumbers: true,
            matchBrackets : true,
            autofocus: true,
            extraKeys: {'Ctrl-Space': 'autocomplete'},
            hintOptions: jaxon.dbadmin.editor.hintOptions,
            /*hintOptions: {
                tables: {
                    users: ["name", "score", "birthDate"],
                    countries: ["name", "population", "size"]
                }
            }*/
        });
    },
    saveSqlEditorContent: function() {
        jaxon.dbadmin.editor.element.save();
    },
};

jaxon.dom.ready(function() {
    jaxon.register('dbadmin.hsqleditor', function({ id, server }) {
        jaxon.dbadmin.highlightSqlEditor(id, server);
        return true;
    });
    jaxon.register('dbadmin.window.open', function({ link }) {
        window.open(link, '_blank').focus();
        return true;
    });
    jaxon.register('dbadmin.row.ids.set', function({ ids }) {
        jaxon.dbadmin.rowIds = ids;
        return true;
    });
    jaxon.register('dbadmin.new.index.set', function({ count }) {
        jaxon.dbadmin.newItemIndex = count;
        return true;
    });
});
