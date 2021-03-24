if (typeof(varienGridMassaction) !== 'undefined') {
    // Need to remove the default count check at the beginning of the apply() method in grid.js file
    // in order to be able to refuse all Mirakl order lines without selecting any checkboxes.
    varienGridMassaction.prototype.apply = function () {
        var item = this.getSelectedItem();

        if (item.id !== 'accept' && varienStringArray.count(this.checkedString) === 0) {
            alert(this.errorText);
            return;
        }

        if (!item) {
            this.validator.validate();
            return;
        }

        this.currentItem = item;
        var fieldName = (item.field ? item.field : this.formFieldName);

        if (this.currentItem.confirm && !window.confirm(this.currentItem.confirm)) {
            return;
        }

        this.formHiddens.update('');

        new Insertion.Bottom(this.formHiddens, this.fieldTemplate.evaluate({
            name: fieldName,
            value: this.checkedString
        }));

        new Insertion.Bottom(this.formHiddens, this.fieldTemplate.evaluate({
            name: 'massaction_prepare_key',
            value: fieldName
        }));

        if (!this.validator.validate()) {
            return;
        }

        if (this.useAjax && item.url) {
            new Ajax.Request(item.url, {
                'method': 'post',
                'parameters': this.form.serialize(true),
                'onComplete': this.onMassactionComplete.bind(this)
            });
        } else if (item.url) {
            this.form.action = item.url;
            this.form.submit();
        }
    }
}