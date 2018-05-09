// Code to manage tags in the contact view

var vue = $('#app')[0].__vue__;

$('#tags').tagsInput({
   maxChars : 255,
   defaultText : vue.$t('people.tag_add')
});

$('#tagsForm').hide();

$('#tagsFormCancel').click( function(e) {
  $('#tagsForm').hide();
  $('.tags').toggle();
});

$('#showTagForm').click(function(e) {
  $('#tagsForm').toggle();
  $('.tags').toggle();
  e.preventDefault();
  return false;
});

$('#tags_tagsinput').keyup(function(e) {
  if (e.keyCode == 27) {
    $('#tagsForm').toggle();
    $('.tags').toggle();
  }
});

$('#tagsForm').submit(function(e) {

  // gather the list of tags in the input and translating it into a comma
  // separated string
  var tags = $.map(
    $('#tagsForm .tag span'), function(e,i) {
      return $(e).text().trim();
  });

  var tagsTring = tags.join(',');

  $.post(
    $( this ).prop( 'action' ),
    {
      "_token": $(this).find( 'input[name=_token]' ).val(),
      'tags': tagsTring
    },
    function( data ) {
      // success
      $('#tagsForm').toggle();

      $('.tags-list').empty();

      // add the new tag
      for (var i = 0; i < data['tags'].length ; i++) {
        $('.tags-list').append('<li class="pretty-tag"><a href="/people?tag1=' + data.tags[i].slug + '">' + data.tags[i].name + '</a></li>');
      }

      $('.tags').toggle();
    },
    'json'
  );
  e.preventDefault();
});
