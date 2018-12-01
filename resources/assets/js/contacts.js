/**
 *  This file is part of Monica.
 *
 *  Monica is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU Affero General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  Monica is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU Affero General Public License for more details.
 *
 *  You should have received a copy of the GNU Affero General Public License
 *  along with Monica.  If not, see <https://www.gnu.org/licenses/>.
 **/

 /*
 * Focus the input field next to the radio button.
 *
 */
$('input[type=radio][name=is_birthdate_approximate]').change(function() {
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
$('.log-call .change-date-happened').click(function(e) {
  $('.log-call .date-it-happened').hide();
  $('.log-call .exact-date').show();
  e.preventDefault();
});

$('a[href^="#logCallModal"]').click(function(e) {
  $('.log-call .date-it-happened').show();
  $('.log-call .exact-date').hide();
  e.preventDefault();
});

// On the contact sheet of a person, for the Deceased section
$('#markPersonDeceased').click(function() {
  $('#datePersonDeceased').toggle(this.checked);

  if(! document.getElementById('markPersonDeceased').checked) {
    $('#checkboxDatePersonDeceased').prop('checked', false);
    $('#addReminderDeceased').prop('checked', false);
    $('#datesSelector').prop('checked', false);
    $('#datesSelector').hide();
    $('#reminderDeceased').hide();
  }
});

$('#checkboxDatePersonDeceased').click(function() {
  $('#reminderDeceased').toggle(this.checked);
  $('#datesSelector').toggle(this.checked);
});
