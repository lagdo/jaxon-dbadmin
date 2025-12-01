jaxon.dbadmin = {};

(function(self) {
    /**
     * @param {string} checkboxId
     *
     * @returns {void}
     */
    const countTableCheckboxes = (checkboxId) => $('#dbadmin-table-' + checkboxId + '-count')
        .html($('.dbadmin-table-' + checkboxId + ':checked').length);

    /**
     * @param {string} checkboxId
     *
     * @returns {void}
     */
    self.selectTableCheckboxes = (checkboxId) => {
        $('#dbadmin-table-' + checkboxId + '-all').change(function() {
            $('.dbadmin-table-' + checkboxId, '#jaxon-dbadmin').prop('checked', this.checked);
            countTableCheckboxes(checkboxId);
        });
        $('.dbadmin-table-' + checkboxId).change(function() {
            countTableCheckboxes(checkboxId);
        });
    };

    /**
     * @param {string} checkboxId
     *
     * @returns {void}
     */
    self.setExportEventHandlers = (checkboxId) => {
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

    /**
     * @param {string} container
     *
     * @returns {void}
     */
    self.setFileUpload = (container) => {
        $(container).on('change', ':file', function() {
            const fileInput = $(this);
            const numFiles = fileInput.get(0).files ? fileInput.get(0).files.length : 1;
            const label = fileInput.val().replace(/\\/g, '/').replace(/.*\//, '');
            const textInput = $(container).find(':text');
            const text = numFiles > 1 ? numFiles + ' files selected' : label;
            textInput.length > 0 && textInput.val(text);
        });
    };

    /**
     * @param {string} url
     * @param {string} filename
     *
     * @returns {void}
     */
    self.downloadFile = (url, filename) => {
        const downloadLink = document.createElement("a");
        downloadLink.href = url;
        downloadLink.download = filename;
        document.body.appendChild(downloadLink);
        downloadLink.click();
        document.body.removeChild(downloadLink);
    };
})(jaxon.dbadmin);

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
