Validation.addAllThese([
    ['validate-api-url', 'Please enter a valid Mirakl URL. Protocol https:// is required (https://your_mirakl_env/api).', function(v) {
        var expression = /http(s):\/\/.(www\.)?[-a-zA-Z0-9@:%._\+~#=]{2,256}\.[a-z]{2,6}\b([-a-zA-Z0-9@:%_\+.~#?&//=]*)api/gi;
        var regex = new RegExp(expression);

        return v.match(regex);
    }]
]);