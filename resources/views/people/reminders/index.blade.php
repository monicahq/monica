<div class="col-12 section-title">
  <img src="img/people/reminders.svg" class="icon-section icon-reminders">
  <h3>
    {{ trans('people.section_personal_reminders') }}

    <span class="{{ htmldir() == 'ltr' ? 'fr' : 'fl' }}">
      <a href="{{ route('people.reminders.create', $contact) }}" class="btn">{{ trans('people.reminders_cta') }}</a>
    </span>
  </h3>
</div>


@if ($reminders->count() === 0)

  <div class="col-12">
    <div class="section-blank">
      <h3>{{ trans('people.reminders_blank_title', ['name' => $contact->first_name]) }}</h3>
      <a href="{{ route('people.reminders.create', $contact) }}">{{ trans('people.reminders_blank_add_activity') }}</a>
    </div>
  </div>

@else

  <div class="col-12 reminders-list">

    @if (! $accountHasLimitations)
    <p>{{ trans('people.reminders_description') }}</p>
    @else
    <p>{{ trans('people.reminders_free_plan_warning') }}</p>
    @endif

    <ul class="table">
      @foreach($reminders as $reminder)
      <li class="table-row">

        <div class="table-cell date">
          {{ $reminder->next_expected_date_human_readable }}
        </div>

        <div class="table-cell frequency-type">
          @if ($reminder->frequency_type != 'one_time')
            {{ trans_choice('people.reminder_frequency_'.$reminder->frequency_type, $reminder->frequency_number, ['number' => $reminder->frequency_number]) }}
          @else
            {{ trans('people.reminders_one_time') }}
          @endif
        </div>

        <div class="table-cell title">
          {{ $reminder->title }}
        </div>

        <div class="table-cell comment">
            @if (!is_null($reminder->description))
              {{ $reminder->description }}
            @endif
        </div>

        <div class="table-cell list-actions">
          {{-- Only display this if the reminder can be deleted - ie if it's not a reminder added automatically for birthdates --}}
          @if ($reminder->delible || ! $reminder->isBirthdayReminder())
            <a href="{{ route('people.reminders.edit', [$contact, $reminder]) }}" class="edit">
              <i class="fa fa-pencil-square-o" aria-hidden="true"></i>
            </a>
          @endif
          <form method="POST" action="{{ route('people.reminders.destroy', [$contact, $reminder]) }}" class="di">
            @method('DELETE')
            @csrf
            <confirm message="{{ trans('people.reminders_delete_confirmation') }}">
              <i class="fa fa-trash-o" aria-hidden="true"></i>
            </confirm>
          </form>
        </div>

      </li>
      @endforeach
    </ul>
  </div>

@endif
