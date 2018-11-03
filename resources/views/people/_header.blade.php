<div class="ph3 ph5-ns pv2 cf w-100 mt4 mt0-ns">
    <div class="mw9 center dt w-100 box-shadow pa4">

      {{-- AVATAR --}}
      <div class="relative">
        {!! $avatar !!}
      </div>

      <h1 class="tc mb2 mt0">
        <span class="{{ htmldir() == 'ltr' ? 'mr1' : 'ml1' }}">{{ $contact->name }}</span>
        <contact-favorite hash="{{ $contact->hashID() }}" :starred="{{ json_encode($contact->is_starred) }}"></contact-favorite>
      </h1>

      <ul class="tc-ns mb3 {{ htmldir() == 'ltr' ? 'tl' : 'tr' }}">

        {{-- AGE --}}
        <li class="di-ns db tc {{ htmldir() == 'ltr' ? 'mr3-ns' : 'ml3-ns' }}">
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

        {{-- FAMILY --}}
        <li class="di-ns db tc {{ htmldir() == 'ltr' ? 'mr3-ns' : 'ml3-ns' }}">
          <span class="{{ htmldir() == 'ltr' ? 'mr1' : 'ml1' }}">@include('partials.icons.header_people')</span>
          3 family members
        </li>

        {{-- DESCRIPTION --}}
        @if ($contact->description)
        <li class="di-ns db tc {{ htmldir() == 'ltr' ? 'mr3-ns' : 'ml3-ns' }}">
          @include('partials.icons.header_description')
          {{ $contact->description }}
        </li>
        @endif

        {{-- STAY IN TOUCH --}}
        <li class="di-ns db tc {{ htmldir() == 'ltr' ? 'mr3-ns' : 'ml3-ns' }}">
          @include('partials.icons.header_stayintouch')
          <stay-in-touch :contact="{{ $contact }}" hash="{{ $contact->hashID() }}" limited="{{ auth()->user()->account->hasLimitations() }}"></stay-in-touch>
        </li>
      </ul>

      <tags hash="{{ $contact->hashID() }}"></tags>
    </div>
</div>

<div class="ph3 ph5-ns pv2 cf w-100">
    <div class="mw9 center dt w-100">
      @include ('partials.errors')
      @include ('partials.notification')
    </div>
</div>

<div class="pagehead">
  <div class="{{ Auth::user()->getFluidLayout() }}">
    <div class="row">
      <div class="col-xs-12">

        <div class="people-profile-information">

          <ul class="horizontal profile-detail-summary">
            @if ($contact->is_dead)
              <li>
                @if (! is_null($contact->deceasedDate))
                  {{ trans('people.deceased_label_with_date', ['date' => $contact->deceasedDate->toShortString()]) }}
                @else
                  {{ trans('people.deceased_label') }}
                @endif
              </li>
            @endif
            <li>
              {{ $contact->description }}
            </li>
          </ul>

          <ul class="horizontal quick-actions">
            <li>
              <a href="{{ route('people.edit', $contact) }}" class="btn edit-information" id="button-edit-contact">{{ trans('people.edit_contact_information') }}</a>
            </li>
          </ul>
        </div>
      </div>
    </div>
  </div>
</div>
