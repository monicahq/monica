<div class="ph2 ph5-ns cf w-100 mb4">
  <div class="mw9 center dt w-100 bg-white box-monica">

    {{-- COVER IMAGE --}}
    <div class="pt3 contact-header-cover" style="background: url(https://images.unsplash.com/photo-1540202404-d0c7fe46a087?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEyMDd9&auto=format&fit=crop&w=1790&q=80)">

      {{-- ONLY SHOW AVATAR ON MOBILE ABOVE THE COVER IMAGE --}}
      <div class="tc dn-ns">
        <img class="br-50 bw2 ba b--white mw3" src="https://pbs.twimg.com/profile_images/3171824697/ef75d90df2e65ce326acf30262df5918_400x400.jpeg" alt="avatar of {{ $contact->name }}">
      </div>

    </div>

    {{-- INFO --}}
    <div class="pa4-ns pa3 relative">
      <img class="br-50 absolute bw2 ba b--white dn db-m db-l contact-header-avatar" src="https://pbs.twimg.com/profile_images/3171824697/ef75d90df2e65ce326acf30262df5918_400x400.jpeg" alt="avatar of {{ $contact->name }}">

      <div class="relative contact-header-information">

        {{-- CONTACT NAME --}}
        <h1 class="ma0 f3 tc tl-ns">
          {{ $contact->name }}
        </h1>

        <ul class="list pa0 mt0 mb3 dn db-m db-l mb3">
          {{-- AGE --}}
          <li class="mb2 mb0-ns di-ns db tc">
            @if ($contact->birthday_special_date_id && !($contact->is_dead))
              @if ($contact->birthdate->getAge())
                <span class="{{ htmldir() == 'ltr' ? 'mr1' : 'ml1' }}">@include('partials.icons.header_birthday')</span>
                <span>{{ $contact->birthdate->getAge() }}</span>
              @endif
            @elseif ($contact->is_dead)
                @if (! is_null($contact->deceasedDate))
                  {{ trans('people.deceased_label_with_date', ['date' => $contact->deceasedDate->toShortString()]) }}
                  @if ($contact->deceasedDate->is_year_unknown == 0)
                    <span>({{ trans('people.deceased_age') }} {{ $contact->getAgeAtDeath() }})</span>
                  @endif
                @else
                  {{ trans('people.deceased_label') }}
                @endif
            @endif
          </li>

          {{-- LAST ACTIVITY --}}
          <li class="mb2 mb0-ns dn di-ns tc {{ htmldir() == 'ltr' ? 'mr3-ns' : 'ml3-ns' }}">
            <span class="{{ htmldir() == 'ltr' ? 'mr1' : 'ml1' }}">@include('partials.icons.header_people')</span>
            @if (is_null($contact->getLastActivityDate()))
              {{ trans('people.last_activity_date_empty') }}
            @else
              {{ trans('people.last_activity_date', ['date' => \App\Helpers\DateHelper::getShortDate($contact->getLastActivityDate())]) }}
            @endif
          </li>

          {{-- LAST CALLED --}}
          <li class="mb2 mb0-ns dn di-ns tc {{ htmldir() == 'ltr' ? 'mr3-ns' : 'ml3-ns' }}">
            <span class="{{ htmldir() == 'ltr' ? 'mr1' : 'ml1' }}">@include('partials.icons.header_call')</span>
            @if (is_null($contact->getLastCalled()))
              {{ trans('people.last_called_empty') }}
            @else
              {{ trans('people.last_called', ['date' => \App\Helpers\DateHelper::getShortDate($contact->getLastCalled())]) }}
            @endif
          </li>

          {{-- DESCRIPTION --}}
          @if ($contact->description)
          <li class="mb2 mb0-ns di-ns db tc {{ htmldir() == 'ltr' ? 'mr3-ns' : 'ml3-ns' }}">
            @include('partials.icons.header_description')
            {{ $contact->description }}
          </li>
          @endif

          {{-- STAY IN TOUCH --}}
          <li class="mb2 mb0-ns di-ns db tc {{ htmldir() == 'ltr' ? 'mr3-ns' : 'ml3-ns' }}">
            @include('partials.icons.header_stayintouch')
            <stay-in-touch :contact="{{ $contact }}" hash="{{ $contact->hashID() }}" limited="{{ auth()->user()->account->hasLimitations() }}"></stay-in-touch>
          </li>
        </ul>

        {{-- DESKTOP ACTIONS --}}
        <div class="absolute pt2 right-0 top-0 dn db-m db-l contact-header-actions">
          <a class="btn no-color no-underline pv1 ph2 {{ htmldir() == 'rtl' ? 'ml3' : 'mr3' }}" href="">
            <img class="relative" style="top: 2px;" src="/img/people/header/stay_in_touch_active.svg" alt="active state">
            Stay in touch
          </a>
          <a class="btn no-color no-underline pv1 ph2">
            <contact-favorite hash="{{ $contact->hashID() }}" :starred="{{ json_encode($contact->is_starred) }}"></contact-favorite>
          </a>
        </div>

        {{-- MOBILE ACTIONS --}}
        <ul class="list pa0 mh0 tc dn-ns">
          <li class="dib">
            <a class="btn no-color no-underline pv1 ph2 {{ htmldir() == 'rtl' ? 'ml3' : 'mr3' }}" href="">
              <img class="relative" style="top: 2px;" src="/img/people/header/stay_in_touch_active.svg" alt="active state">
              Stay in touch
            </a>
          </li>
          <li class="dib">
            <a class="btn no-color no-underline pv1 ph2">
              <contact-favorite hash="{{ $contact->hashID() }}" :starred="{{ json_encode($contact->is_starred) }}"></contact-favorite>
            </a>
          </li>
        </ul>
      </div>
    </div>
  </div>
</div>
