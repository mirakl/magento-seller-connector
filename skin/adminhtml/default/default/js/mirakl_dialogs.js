var AdminMiraklExport = new Class.create();
AdminMiraklExport.prototype = {
    initialize: function () {
        this.reset();
        this.exportUrl = '';
        this.exportValidator = new Validation($('export-form'));
    },

    reset: function () {
        $('export_mode').value = '';
        $$('.validation-advice').invoke('remove');
        $$('select').invoke('removeClassName', 'validation-failed');
        $$('select').invoke('removeClassName', 'validation-passed');
        $$('.export-messages').invoke('hide');
    },

    exportDialog: function () {
        this.reset();
        $('mirakl-export-dialog').show().setStyle({
            'marginTop': -$('mirakl-export-dialog').getDimensions().height / 2 + 'px'
        });
        $('popup-window-mask').setStyle({
            height: $('html-body').getHeight() + 'px'
        }).show();

        return false;
    },

    submitExport: function () {
        if (!!this.exportValidator && this.exportValidator.validate()) {
            this.hidePopup();

            setLocation(this.exportUrl + '?export_mode=' + $('export_mode').value);
        }

        return false;
    },

    hidePopup: function () {
        $('mirakl-export-dialog').hide();
        $('popup-window-mask').hide();
    }
};