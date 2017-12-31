<h3 class="ttu normal f5 bb b--black-20 pb2">{{ \App\Helpers\DateHelper::getMonthAndYear($month) }}</h3>
<ul class="mb3">
  @if (count(auth()->user()->account->getRemindersForMonth($month)) > 0)
    @foreach (auth()->user()->account->getRemindersForMonth($month) as $reminder)
    <li class="pb2">
      <span class="ttu f6 mr2 black-60">{{ \App\Helpers\DateHelper::getShortDateWithoutYear($reminder->next_expected_date) }}</span>
      <span class="">
        <a href="/people/{{ $reminder->contact->id }}">{{ $reminder->contact->getIncompleteName() }}</a>
      </span>
      {{ $reminder->title }}
    </li>
    @endforeach
  @else
  <p>No event planned for this month</p>
  @endif
</ul>
