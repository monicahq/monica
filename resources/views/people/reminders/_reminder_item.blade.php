<div class="reminder-item">

  <div class="reminder-icon">
    @if ($reminder->getReminderType() == 'birthday')
    <img src="/img/people/reminders/birthday.svg">
    @endif

    @if ($reminder->getReminderType() == 'birthday_kid')
    <img src="/img/people/reminders/baby.svg">
    @endif

    @if ($reminder->getReminderType() == 'phone_call')
    <img src="/img/people/reminders/phone.svg">
    @endif

    @if ($reminder->getReminderType() == 'lunch')
    <img src="/img/people/reminders/lunch.svg">
    @endif

    @if ($reminder->getReminderType() == 'hangout')
    <img src="/img/people/reminders/hangout.svg">
    @endif

    @if ($reminder->getReminderType() == 'email')
    <img src="/img/people/reminders/email.svg">
    @endif

    @if ($reminder->getReminderType() == null)
    <img src="/img/people/reminders/clock.svg">
    @endif
  </div>

  <div class="reminder-description">
    {{ $reminder->getTitle() }}
  </div>

  <div class="reminder-info">

    @if ($reminder->frequency_type != 'one_time')
    <div class="reminder-frequency">
      {{ trans_choice('people.reminder_frequency_'.$reminder->frequency_type, $reminder->frequency_number, ['number' => $reminder->frequency_number]) }}
    </div>
    @endif

    <span class="next-expected-date">{{ trans('people.reminders_next_expected_date') }} {{ $reminder->getNextExpectedDate() }}</span>

    @if ($reminder->getReminderType() != 'birthday' and $reminder->getReminderType() != 'birthday_kid')

    <div class="reminder-actions">
      <ul class="horizontal">
        <li><a href="/people/{{ $contact->id }}/reminders/{{ $reminder->id }}/delete" onclick="return confirm('{{ trans('people.reminders_delete_confirmation') }}')">{{ trans('people.reminders_delete_cta') }}</a></li>
      </ul>
    </div>

    @endif

  </div>

  @if (!is_null($reminder->getDescription()))
  <div class="reminder-comment">
    {{ $reminder->getDescription() }}
  </div>
  @endif

</div>
