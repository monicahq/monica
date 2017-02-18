<div class="pagehead">
  <div class="{{ Auth::user()->getFluidLayout() }}">
    <div class="row">
      <div class="col-xs-12">

        @include ('partials.notification')

        <div class="people-profile-information">

          @if ($contact->has_avatar == 'true')
            <img src="{{ $contact->getAvatarURL(110) }}" width="87">
          @else
            @if (count($contact->getInitials()) == 1)
            <div class="avatar one-letter" style="background-color: {{ $contact->getAvatarColor() }};">
              {{ $contact->getInitials() }}
            </div>
            @else
            <div class="avatar" style="background-color: {{ $contact->getAvatarColor() }};">
              {{ $contact->getInitials() }}
            </div>
            @endif
          @endif

          <h2>
            {{ $contact->getCompleteName() }}
          </h2>

          <ul class="horizontal profile-detail-summary">

            {{-- Last called information --}}
            <li>
              @if (is_null($contact->getLastCalled(Auth::user()->timezone)))
                {{ trans('people.last_called_empty') }}
              @else
                {{ trans('people.last_called', ['date' => $contact->getLastCalled(Auth::user()->timezone)]) }}
              @endif
            </li>

            {{-- Last activity information --}}
            <li>
              @if (is_null($contact->getLastActivityDate(Auth::user()->timezone)))
                {{ trans('people.last_activity_date_empty') }}
              @else
                {{ trans('people.last_activity_date', ['date' => $contact->getLastActivityDate(Auth::user()->timezone)]) }}
              @endif
            </li>
          </ul>
        </div>
      </div>
    </div>
  </div>
</div>
