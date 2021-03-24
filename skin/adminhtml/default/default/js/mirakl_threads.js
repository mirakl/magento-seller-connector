var AdminMiraklThreads = new Class.create();
AdminMiraklThreads.prototype = {
    initialize: function (options) {
        this.options = Object.extend({
            modalTarget: $('mirakl-thread-view-content'),
            overlay: $('popup-window-mask')
        }, options || {});

        this.registerEvents();

        this.options.overlay.observe('click', this.hideModal.bindAsEventListener(this));
    },

    registerEvents: function () {
        $$('.order-thread-view').invoke('observe', 'click', function (event) {
            event.stop();
            this.processView(event.element().href);
        }.bind(this));
    },

    processNew: function (url) {
        this.processView(url);
    },

    processView: function (url) {
        new Ajax.Request(url, {
            method: 'get',
            onSuccess: function (transport) {
                this.options.modalTarget.update(transport.responseText).setStyle({
                    'marginTop': - this.options.modalTarget.getDimensions().height / 2 + 'px'
                }).show();
                this.options.overlay.setStyle({
                    height: $('html-body').getHeight() + 'px'
                }).show();
            }.bind(this)
        });
    },

    hideModal: function () {
        $$('.backup-dialog').each(Element.hide);
        this.options.overlay.hide();
    }
};