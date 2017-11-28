<div class="col-xs-12 section-title">
  <img src="/img/people/reminders.svg" class="icon-section icon-reminders">
  <h3>
    {{ trans('people.section_personal_reminders') }}

    <span>
      <a href="/people/{{ $contact->id }}/reminders/add" class="btn">{{ trans('people.reminders_cta') }}</a>
    </span>
  </h3>
</div>


@if ($reminders->count() === 0)

  <div class="col-xs-12">
    <div class="section-blank">
      <h3>{{ trans('people.reminders_blank_title', ['name' => $contact->first_name]) }}</h3>
      <a href="/people/{{ $contact->id }}/reminders/add">{{ trans('people.reminders_blank_add_activity') }}</a>
    </div>
  </div>

@else

  <div class="col-xs-12 reminders-list">

    @if (! auth()->user()->account->hasLimitations())
    <p>{{ trans('people.reminders_description') }}</p>
    @else
    <p>{{ trans('people.reminders_free_plan_warning') }}</p>
    @endif

    <ul class="table">
      @foreach($reminders as $reminder)
      <li class="table-row">

        <div class="table-cell date">
          {{ \App\Helpers\DateHelper::getShortDate($reminder->getNextExpectedDate()) }}
        </div>

        <div class="table-cell frequency-type">
          @if ($reminder->frequency_type != 'one_time')
            {{ trans_choice('people.reminder_frequency_'.$reminder->frequency_type, $reminder->frequency_number, ['number' => $reminder->frequency_number]) }}
          @else
            {{ trans('people.reminders_one_time') }}
          @endif
        </div>

        <div class="table-cell title">
          {{ $reminder->getTitle() }}
        </div>

        <div class="table-cell comment">
            @if (!is_null($reminder->getDescription()))
              {{ $reminder->getDescription() }}
            @endif
        </div>

        <div class="table-cell list-actions">
          {{-- Only display this if the reminder can be deleted - ie if it's not a reminder added automatically for birthdates --}}
          @if ($reminder->is_birthday == false)
            <a href="#" onclick="if (confirm('{{ trans('people.reminders_delete_confirmation') }}')) { $(this).closest('.table-row').find('.entry-delete-form').submit(); } return false;">
              <i class="fa fa-trash-o" aria-hidden="true"></i>
            </a>
          @endif
        </div>

        <form method="POST" action="{{ action('Contacts\\RemindersController@destroy', compact('contact', 'reminder')) }}" class="entry-delete-form hidden">
          {{ method_field('DELETE') }}
          {{ csrf_field() }}
        </form>
      </li>
      @endforeach
    </ul>
  </div>

@endif
