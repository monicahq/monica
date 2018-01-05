// Code to manage tags in both contacts view and profile view

$('#tagsDisplay').click(function (e) {
    $.each($('.people-list-item').nextAll(), function () {
        $(this).find('.tags').toggleClass("tags-inline");
    });
    e.preventDefault();
});

$('.tagsFormCancel').click(function (e) {
    var tagsForm = $(this.closest('.tagsForm'));
    tagsForm.hide();
    tagsForm.prev().toggle();
    e.preventDefault();
});

$('.showTagForm').click(function (e) {
    var tags = $(this.closest('.tags'));
    tags.next().toggle();
    tags.toggle();
    tags.next().find("input[name = 'tags']").tagsInput({
        'maxChars': 255,
        'height': 'auto',
        'width': 'auto',
    });
    e.preventDefault();
    return false;
});

$('.tagsForm').keyup(function (e) {
    if (e.keyCode == 27) {
        $(this).toggle();
        $(this).prev().toggle();
    }
});

$('.tagsForm').submit(function (e) {

    // gather the list of tags in the input and translating it into a comma
    // separated string
    var currentForm = $(this);
    var currentTags = currentForm.prev();

    var tags = $.map(
        currentForm.find('.tag span'), function (e, i) {
            return $(e).text().trim();
        });

    var tagsTring = tags.join(',');

    $.post(
        $(this).prop('action'),
        {
            "_token": $(this).find('input[name=_token]').val(),
            'tags': tagsTring
        },
        function (data) {
            // success
            $(currentForm).toggle();

            $(currentTags.find('.tags-list')).empty();

            // add the new tag
            for (var i = 0; i < data['tags'].length; i++) {
                $(currentTags.find('.tags-list')).append('<li class="pretty-tag"><a href="/people?tags=' + data.tags[i].slug + '">' + data.tags[i].slug + '</a></li>');
            }

            $(currentTags).toggle();
        },
        'json'
    );
    e.preventDefault();
});