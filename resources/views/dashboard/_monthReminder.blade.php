@foreach($remindersList as $month => $reminders)
    <h3 class="ttu fw5 f5 pb2">{{ \App\Helpers\DateHelper::getMonthAndYear($month) }}</h3>
    <ul class="mb4">
        @if(count($reminders) > 0)
            @foreach($reminders as $reminder)
            <li class="pb2">
                <span class="ttu f6 mr2 black-60">{{ \App\Helpers\DateHelper::getShortDateWithoutYear($reminder->next_expected_date) }}</span>
                <span>
                    @if ($reminder->contact->is_partial)

                        @php($relatedRealContact = $reminder->contact->getRelatedRealContact())
                        <a href="{{ route('people.show', $relatedRealContact) }}">{{ $relatedRealContact->getIncompleteName() }}</a>

                    @else

                        <a href="{{ route('people.show', $reminder->contact) }}">{{ $reminder->contact->getIncompleteName() }}</a>

                    @endif
                </span>
                {{ $reminder->title }}
            </li>
            @endforeach
        @else
            <p>{{ trans('dashboard.reminders_none') }}</p>
        @endif
    </ul>
@endforeach