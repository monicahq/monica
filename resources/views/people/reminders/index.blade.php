<div class="col-xs-12 section-title">
  <img src="{{ asset('/img/people/reminders.svg') }}" class="icon-section icon-reminders">
  <h3>
    {{ trans('people.section_personal_reminders') }}

    <span>
      <a href="{{ route('people.reminders.add', ['person' => $contact->id]) }}" class="btn">{{ trans('people.reminders_cta') }}</a>
    </span>
  </h3>
</div>


@if ($contact->getNumberOfReminders() == 0)

  <div class="col-xs-12">
    <div class="section-blank">
      <h3>{{ trans('people.reminders_blank_title', ['name' => $contact->getFirstName()]) }}</h3>
      <a href="{{ route('people.reminders.add', ['person' => $contact->id]) }}">{{ trans('people.reminders_blank_add_activity') }}</a>
    </div>
  </div>

@else

  <div class="col-xs-12 reminders-list">

    <p>{{ trans('people.reminders_description') }}</p>

    <table class="table table-sm table-hover">
      <thead>
        <tr>
          <th>{{ trans('people.reminders_date') }}</th>
          <th>{{ trans('people.reminders_frequency') }}</th>
          <th>{{ trans('people.reminders_content') }}</th>
          <th class="actions">{{ trans('people.reminders_actions') }}</th>
        </tr>
      </thead>
      <tbody>
        @foreach($contact->getReminders() as $reminder)
          <tr>
            <td class="date">{{ \App\Helpers\DateHelper::getShortDate($reminder->getNextExpectedDate()) }}</td>

            <td class="date">
              @if ($reminder->frequency_type != 'one_time')
                {{ trans_choice('people.reminder_frequency_'.$reminder->frequency_type, $reminder->frequency_number, ['number' => $reminder->frequency_number]) }}
              @else
                {{ trans('people.reminders_one_time') }}
              @endif
            </td>

            <td>
              {{ $reminder->getTitle() }}
            </td>

            <td class="actions">

              <div class="reminder-actions">
                <ul class="horizontal">
                  <li><a href="{{ route('people.reminders.delete', ['people' => $contact->id, 'reminderId' => $reminder->id]) }}" onclick="return confirm('{{ trans('people.reminders_delete_confirmation') }}')">{{ trans('people.reminders_delete_cta') }}</a></li>
                </ul>
              </div>

            </td>

            @if (!is_null($reminder->getDescription()))
            <td class="reminder-comment">
              {{ $reminder->getDescription() }}
            </td>
            @endif

          </tr>
        @endforeach
      </tbody>
    </table>
  </div>

@endif
