/*
 * Focus the input field next to the radio button.
 *
 */
$('input[type=radio][name=is_birthdate_approximate]').change(function () {
  if (this.value == 'exact') {
    $('#specificDate').focus().select();
  }
  else if (this.value == 'approximate') {
    $('#age').focus().select();
  }
});

/*
 * Submit the form in a modal window
 */
$('.log-call .modal-cta').click(function() {
  $('.log-call form').submit();
});

// When clicking on the Change date in the Log a call modal, we display the
// date picker
$('.log-call .change-date-happened').click(function (e) {
  $('.log-call .date-it-happened').hide();
  $('.log-call .exact-date').show();
  e.preventDefault();
});

$('a[href^="#logCallModal"]').click(function (e) {
  $('.log-call .date-it-happened').show();
  $('.log-call .exact-date').hide();
  e.preventDefault();
});

// On the contact sheet of a person, for the Deceased section
$('#is_deceased').click(function() {
  $('#datePersonDeceased').toggle(this.checked);

  if(! document.getElementById('markPersonDeceased').checked) {
    $('#is_deceased_date_known').prop('checked', false);
    $('#add_reminder_deceased').prop('checked', false);
    $('#datesSelector').prop('checked', false);
    $('#datesSelector').hide();
    $('#reminderDeceased').hide();
  }
});


$('#is_deceased_date_known').click(function () {
  $('#reminderDeceased').toggle(this.checked);
  $('#datesSelector').toggle(this.checked);
});
