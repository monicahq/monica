<div class="col-xs-12 section-title">
  <img src="/img/people/reminders.svg" class="icon-section icon-reminders">
  <h3>
    {{ trans('people.section_personal_reminders') }}

    <span>
      <a href="/people/{{ $contact->id }}/reminders/add" class="btn">{{ trans('people.reminders_cta') }}</a>
    </span>
  </h3>
</div>


@if ($contact->reminders->count() === 0)

  <div class="col-xs-12">
    <div class="section-blank">
      <h3>{{ trans('people.reminders_blank_title', ['name' => $contact->first_name]) }}</h3>
      <a href="/people/{{ $contact->id }}/reminders/add">{{ trans('people.reminders_blank_add_activity') }}</a>
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
        @foreach($contact->reminders as $reminder)
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

              {{-- Only display this if the reminder can be deleted - ie if it's not a reminder added automatically for birthdates --}}
              @if ($reminder->is_birthday == 'false')
              <div class="reminder-actions">
                <ul class="horizontal">
                  <li><a href="/people/{{ $contact->id }}/reminders/{{ $reminder->id }}/delete" onclick="return confirm('{{ trans('people.reminders_delete_confirmation') }}')">{{ trans('people.reminders_delete_cta') }}</a></li>
                </ul>
              </div>
              @endif

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
