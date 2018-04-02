<h3 class="ttu fw5 f5 pb2">{{ \App\Helpers\DateHelper::getMonthAndYear($month) }}</h3>
<ul class="mb4">
  @if (count(auth()->user()->account->getRemindersForMonth($month)) > 0)
    @foreach (auth()->user()->account->getRemindersForMonth($month) as $reminder)
    <li class="pb2">
      <span class="ttu f6 mr2 black-60">{{ \App\Helpers\DateHelper::getShortDateWithoutYear($reminder->next_expected_date) }}</span>
      <span class="">

        @if ($reminder->contact->is_partial)

        <a href="/people/{{ $reminder->contact->getRelatedRealContact()->id }}">{{ $reminder->contact->getRelatedRealContact()->getIncompleteName() }}</a>

        @else

        <a href="/people/{{ $reminder->contact->id }}">{{ $reminder->contact->getIncompleteName() }}</a>

        @endif
      </span>
      {{ $reminder->title }}
    </li>
    @endforeach
  @else
  <p>{{ trans('dashboard.reminders_none') }}</p>
  @endif
</ul>
