Event.observe(window, 'load', function() {
    // Add a specific class name on some HTML elements in order to display the Mirakl logo
    $$('#nav li.level0 a span', '#product_info_tabs li a span').each(function(el) {
        if (el.innerHTML == 'Mirakl' || el.innerHTML == 'Mirakl Marketplace' || el.innerHTML == 'Seller') {
            el.up().addClassName('marketplace');
        }
    });

    // Catch listing tab click in order to register active tab
    $$('.adminhtml-mirakl-seller-listing-edit .tab-item-link').invoke('observe', 'click', function(event) {
        if (history) {
            var url = location.href;
            var el = event.element();
            if (el.tagName !== 'A') {
                el = el.up('a');
            }
            if (!el || !el.getAttribute('name')) {
                return;
            }
            if (/active_tab\/.+/.test(url)) {
                url = url.replace(/(.+active_tab\/)[^\/]+(.*)/, '$1' + el.name + '$2');
            } else {
                url = url.replace(/(.+edit\/id\/\d+)(.*)/, '$1/active_tab/' + el.name + '$2');
            }
            history.replaceState({}, '', url);
        }
    });
});