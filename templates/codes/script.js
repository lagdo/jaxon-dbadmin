jaxon.dbadmin = {};

(function(self) {
    /**
     * @param {string} checkboxClass
     * @param {string} checkboxId
     *
     * @returns {void}
     */
    const countTableCheckboxes = (checkboxClass, checkboxId) => $(`#${checkboxId}-count`)
        .html($(`.${checkboxClass}` + ':checked').length);

    /**
     * @param {string} checkboxClass
     * @param {string} checkboxId
     * @param {string} wrapperId
     *
     * @returns {void}
     */
    self.selectTableCheckboxes = (checkboxClass, checkboxId, wrapperId) => {
        $(`#${checkboxId}-all`).change(function() {
            $(`.${checkboxClass}`, `#${wrapperId}`).prop('checked', this.checked);
            countTableCheckboxes(checkboxClass, checkboxId);
        });
        $(`.${checkboxClass}`, `#${wrapperId}`).change(function() {
            countTableCheckboxes(checkboxClass, checkboxId);
        });
    };

    /**
     * @param {string} itemNameClass
     * @param {string} itemNameId
     * @param {string} itemDataClass
     * @param {string} itemDataId
     * @param {string} wrapperId
     *
     * @returns {void}
     */
    self.setExportEventHandlers = (itemNameClass, itemNameId, itemDataClass, itemDataId, wrapperId) => {
        $(`#${itemNameId}-all`).change(function() {
            $(`.${itemNameClass}`, `#${wrapperId}`).prop('checked', this.checked);
        });
        $(`#${itemDataId}-all`).change(function() {
            $(`.${itemDataClass}`, `#${wrapperId}`).prop('checked', this.checked);
        });
        // Check or uncheck the data checkbox when the name is changed.
        $(`.${itemNameClass}`, `#${wrapperId}`).change(function() {
            const itemDataPos = $(this).attr('data-pos');
            $(`#${itemDataId}-${itemDataPos}`, `#${wrapperId}`).prop('checked', this.checked);
        });
    };

    /**
     * @param {string} wrapperId
     *
     * @returns {void}
     */
    self.setFileUpload = (wrapperId) => {
        $(`#${wrapperId}`).on('change', ':file', function() {
            const fileInput = $(this);
            const numFiles = fileInput.get(0).files ? fileInput.get(0).files.length : 1;
            const label = fileInput.val().replace(/\\/g, '/').replace(/.*\//, '');
            const textInput = $(`#${wrapperId}`).find(':text');
            const text = numFiles > 1 ? numFiles + ' files selected' : label;
            if (textInput.length > 0) {
                textInput.val(text);
            }
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

    /**
     * Convert an HTML text to a DOM node. Only the first child is returned.
     *
     * @param {string} htmlText
     *
     * @returns {string}
     */
    const makeHtmlNode = (htmlText) => {
        const node = document.createElement('div');
        node.innerHTML = htmlText;
        // Parse the custom Jaxon attributes in the node.
        // Todo: automate this with the Jaxon library.
        if (node.firstChild !== null) {
            jaxon.parser.attr.process(node.firstChild);
        }
        return node.firstChild;
    };

    /**
     * @param {string} tabId
     *
     * @returns {void}
     */
    self.setCurrentTab = (tabId) => {
        // Todo: merge this value into the "dbadmin" databag?
        jaxon.ajax.parameters.setBag('dbadmin.tab', { current: tabId });
    };

    /**
     * @param {string} titleId 
     *
     * @returns {void}
     */
    const activateTab = (titleId) => document.getElementById(titleId)?.click(new Event('click'));

    /**
     * @param {string} tabNavHtml
     * @param {string} tabContentHtml
     * @param {string} titleId 
     *
     * @returns {void}
     */
    self.addTab = (tabNavHtml, tabContentHtml, titleId) => {
        const tabNav = document.getElementById('dbadmin-server-tab-nav');
        tabNav.appendChild(makeHtmlNode(tabNavHtml));
        const tabContent = document.getElementById('dbadmin-server-tab-content');
        tabContent.appendChild(makeHtmlNode(tabContentHtml));
        // Activate the new tab.
        activateTab(titleId);
    };

    /**
     * @param {string} titleId 
     * @param {string} wrapperId 
     * @param {string} activeTab 
     *
     * @returns {void}
     */
    self.deleteTab = (titleId, wrapperId, activeTab) => {
        // The title is the child of a parent element.
        document.getElementById(titleId)?.parentElement?.remove();
        document.getElementById(wrapperId)?.remove();
        // Activate another tab, so the screen is not left blank.
        activateTab(activeTab);
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
