jaxon.adminer = {
    countTableCheckboxes: function(checkboxId) {
        $('#adminer-table-' + checkboxId + '-count').html($('.adminer-table-' + checkboxId + ':checked').length);
    },
    selectTableCheckboxes: function(checkboxId) {
        $('#adminer-table-' + checkboxId + '-all').change(function() {
            $('.adminer-table-' + checkboxId, '#<?php
                echo $this->containerId ?>').prop('checked', this.checked);
            jaxon.adminer.countTableCheckboxes(checkboxId);
        });
        $('.adminer-table-' + checkboxId).change(function() {
            jaxon.adminer.countTableCheckboxes(checkboxId);
        });
    },
    selectAllCheckboxes: function(checkboxId) {
        $('#' + checkboxId + '-all').change(function() {
            $('.' + checkboxId, '#<?php
                echo $this->containerId ?>').prop('checked', this.checked);
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
        const index = jaxon.adminer.newItemIndex++;
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
        const mode = jaxon.adminer.editor.modes[driver] || jaxon.adminer.editor.modes.sql;
        const element = document.getElementById(containerId);
        jaxon.adminer.editor.query = query;
        CodeMirror(element, { value: query, mode, lineNumbers: false, readOnly: true });
    },
    highlightSqlEditor: function(containerId, driver) {
        const mode = jaxon.adminer.editor.modes[driver] || jaxon.adminer.editor.modes.sql;
        const element = document.getElementById(containerId);
        jaxon.adminer.editor.element = CodeMirror.fromTextArea(element, {
            mode,
            indentWithTabs: true,
            smartIndent: true,
            lineNumbers: true,
            matchBrackets : true,
            autofocus: true,
            extraKeys: {'Ctrl-Space': 'autocomplete'},
            hintOptions: jaxon.adminer.editor.hintOptions,
            /*hintOptions: {
                tables: {
                    users: ["name", "score", "birthDate"],
                    countries: ["name", "population", "size"]
                }
            }*/
        });
    },
    saveSqlEditorContent: function() {
        jaxon.adminer.editor.element.save();
    },
};

jaxon.dom.ready(function() {
    jaxon.ajax.handler.register('dbadmin.hsql', function(command) {
        command.fullName = 'highlightSqlQuery';
        jaxon.adminer.highlightSqlQuery(command.id, command.driver, command.data);
        return true;
    });
});

