$('input[type=radio][name=is_birthdate_approximate]').change(function() {
    if (this.value == 'exact') {
        $('#specificDate').focus().select();
    }
    else if (this.value == 'approximate') {
        $('#age').focus().select();
    }
});