@foreach($reminderOutboxesList as $month => $reminderOutboxes)
    <h3 class="ttu fw5 f5 pb2">{{ \App\Helpers\DateHelper::getMonthAndYear($month) }}</h3>
    <ul class="mb4">
        @if(count($reminderOutboxes) > 0)
            @foreach($reminderOutboxes as $reminderOutbox)
            <li class="pb2">
                <span class="ttu f6 mr2 black-60">{{ \App\Helpers\DateHelper::getShortDateWithoutYear($reminderOutbox->planned_date) }}</span>
                <span>
                    @if ($reminderOutbox->reminder->contact->is_partial)

                        @php($relatedRealContact = $reminderOutbox->reminder->contact->getRelatedRealContact())
                        <a href="{{ route('people.show', $relatedRealContact) }}">{{ $relatedRealContact->getIncompleteName() }}</a>

                    @else

                        <a href="{{ route('people.show', $reminderOutbox->reminder->contact) }}">{{ $reminderOutbox->reminder->contact->getIncompleteName() }}</a>

                    @endif
                </span>
                {{ $reminderOutbox->reminder->title }}
            </li>
            @endforeach
        @else
            <p>{{ trans('dashboard.reminders_none') }}</p>
        @endif
    </ul>
@endforeach