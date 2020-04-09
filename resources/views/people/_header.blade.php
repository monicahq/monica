<div class="ph3 ph5-ns pv2 cf w-100 mt4 mt0-ns">

    @if ($contact->isMe())
    <div class="alert alert-success tc">
      {{ trans('people.me') }}
    </div>
    @endif

    <div class="mw9 center tc w-100 avatar-header relative">
      {{-- AVATAR --}}
      <div class="relative center dib z-3">
        <div class="relative hide-child">
          <div class="image-header top-0 left-0">
            <img class="cover br3 bb b--gray-monica"
                 alt="{{ $contact->initials }}"
                 src="{{ $contact->getAvatarURL() }}"
                 style="height: 115px; width: 115px;"
                 v-on:error="fixAvatarDisplay"
            />
            <div class="hidden br3 dib white tc f1"
                 style="padding-top: 21px; height: 115px; width: 115px; background-color: {{ $contact->default_avatar_color }}"
            >
              {{ $contact->initials }}
            </div>
          </div>
          <div class="child absolute top-0 left-0 h-100 w-100 br3">
            <div class="db w-100 h-100 center tc pt5">
              <a class="no-underline white" href="{{ route('people.avatar.edit', $contact) }}">
                📷 {{ trans('app.update' )}}
              </a>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="mw9 center dt w-100 box-shadow pa4 relative">

      <h1 class="tc mb2 mt4">
        <span class="{{ htmldir() == 'ltr' ? 'mr1' : 'ml1' }}">{{ $contact->name }}</span>
        <contact-favorite hash="{{ $contact->hashID() }}" :starred="{{ \Safe\json_encode($contact->is_starred) }}"></contact-favorite>
        @if ($contact->job)
        <span class="db f5 normal">{{ $contact->job }}
          @if ($contact->company)
            ({{ $contact->company }})
          @endif
        </span>
        @endif
      </h1>

      <ul class="tc-ns mb3 {{ htmldir() == 'ltr' ? 'tl' : 'tr' }}">

        {{-- AGE --}}
        <li class="mb2 mb0-ns di-ns db tc {{ htmldir() == 'ltr' ? 'mr3-ns' : 'ml3-ns' }}">
          @if ($contact->birthdate && !($contact->is_dead))
            @if ($contact->getBirthdayState() !== 'unknown')
              <span class="{{ htmldir() == 'ltr' ? 'mr1' : 'ml1' }}">@include('partials.icons.header_birthday')</span>
              @if($contact->getBirthdayState() === 'approximate')
                <span>{{ trans('people.age_approximate_in_years', ['age' => $contact->birthdate->getAge()]) }}</span>
              @elseif($contact->getBirthdayState() === 'almost')
                <span>{{$contact->birthdate->toShortString()}}</span>
              @else
                <span>{{$contact->birthdate->toShortString()}} ({{ $contact->birthdate->getAge() }})</span>
              @endif
            @endif
          @elseif ($contact->is_dead)
              @if (! is_null($contact->deceasedDate))
                {{ trans('people.deceased_label_with_date', ['date' => $contact->deceasedDate->toShortString()]) }}
                @if ($contact->deceasedDate->is_year_unknown == 0 && $contact->getBirthdayState() !== 'almost')
                  <span>({{ trans('people.deceased_age') }} {{ $contact->getAgeAtDeath() }})</span>
                @endif
              @else
                {{ trans('people.deceased_label') }}
              @endif
          @endif
        </li>

        {{-- LAST ACTIVITY --}}
        @if (! $contact->isMe())
        <li class="mb2 mb0-ns dn di-ns tc {{ htmldir() == 'ltr' ? 'mr3-ns' : 'ml3-ns' }}">
          <span class="{{ htmldir() == 'ltr' ? 'mr1' : 'ml1' }}">@include('partials.icons.header_people')</span>
          @if (is_null($contact->getLastActivityDate()))
            {{ trans('people.last_activity_date_empty') }}
          @else
            {{ trans('people.last_activity_date', ['date' => \App\Helpers\DateHelper::getShortDate($contact->getLastActivityDate())]) }}
          @endif
        </li>
        @endif

        {{-- LAST CALLED --}}
        @if (! $contact->isMe())
        <li class="mb2 mb0-ns dn di-ns tc {{ htmldir() == 'ltr' ? 'mr3-ns' : 'ml3-ns' }}">
          <span class="{{ htmldir() == 'ltr' ? 'mr1' : 'ml1' }}">@include('partials.icons.header_call')</span>
          @if (is_null($contact->last_talked_to))
            {{ trans('people.last_called_empty') }}
          @else
            {{ trans('people.last_called', ['date' => \App\Helpers\DateHelper::getShortDate($contact->last_talked_to)]) }}
          @endif
        </li>
        @endif

        {{-- DESCRIPTION --}}
        @if ($contact->description)
        <li class="mb2 mb0-ns di-ns db tc {{ htmldir() == 'ltr' ? 'mr3-ns' : 'ml3-ns' }}">
          @include('partials.icons.header_description')
          {{ $contact->description }}
        </li>
        @endif

        {{-- STAY IN TOUCH --}}
        @if(!$contact->is_dead && ! $contact->isMe())
          <li class="mb2 mb0-ns di-ns db tc {{ htmldir() == 'ltr' ? 'mr3-ns' : 'ml3-ns' }}">
            @include('partials.icons.header_stayintouch')
            <stay-in-touch :contact="{{ $contact }}" hash="{{ $contact->hashID() }}" :limited="{{ \Safe\json_encode($accountHasLimitations) }}"></stay-in-touch>
          </li>
        @endif
      </ul>

      <tags hash="{{ $contact->hashID() }}" class="mb3 mb0-ns"></tags>

      <div class="absolute-ns tc profile-edit-contact-button">
        <a href="{{ route('people.edit', $contact) }}" class="btn" id="button-edit-contact">{{ trans('people.edit_contact_information') }}</a>
      </div>
    </div>
</div>

<div class="ph3 ph5-ns pv2 cf w-100">
    <div class="mw9 center dt w-100">
      @include ('partials.errors')
      @include ('partials.notification')
    </div>
</div>
