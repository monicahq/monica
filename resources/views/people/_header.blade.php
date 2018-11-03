<div class="ph3 ph5-ns pv2 cf w-100">
    <div class="mw9 center dt w-100 box-shadow pa4">

      {{-- AVATAR --}}
      <div class="">
        {{ $contact->getAvatar() }}
      </div>

      <h1 class="tc mb2 mt0">
        <span class="{{ htmldir() == 'ltr' ? 'mr1' : 'ml1' }}">{{ $contact->name }}</span>
      </h1>

      <ul class="tc-ns mb4 {{ htmldir() == 'ltr' ? 'tl' : 'tr' }}">

        {{-- AGE --}}
        <li class="di {{ htmldir() == 'ltr' ? 'mr3-ns' : 'ml3-ns' }}">
          @if ($contact->birthday_special_date_id && !($contact->is_dead))
            @if ($contact->birthdate->getAge())
              <span class="{{ htmldir() == 'ltr' ? 'mr1' : 'ml1' }}">@include('partials.icons.header_birthday')</span>
              <span>{{ $contact->birthdate->getAge() }}</span>
            @endif
          @elseif ($contact->is_dead)
            @if (! is_null($contact->deceasedDate))
              <span>({{ trans('people.deceased_age') }} {{ $contact->getAgeAtDeath() }})</span>
            @endif
          @endif
        </li>

        {{-- FAMILY --}}
        <li class="di {{ htmldir() == 'ltr' ? 'mr3-ns' : 'ml3-ns' }}">
          <span class="{{ htmldir() == 'ltr' ? 'mr1' : 'ml1' }}">@include('partials.icons.header_people')</span>
          3 family members
        </li>

        {{-- DESCRIPTION --}}
        @if ($contact->description)
        <li class="di {{ htmldir() == 'ltr' ? 'mr3-ns' : 'ml3-ns' }}">
          @include('partials.icons.header_description')
          {{ $contact->description }}
        </li>
        @endif
      </ul>

      <tags hash="{{ $contact->hashID() }}"></tags>
    </div>
</div>

<div class="pagehead">
  <div class="{{ Auth::user()->getFluidLayout() }}">
    <div class="row">
      <div class="col-xs-12">

        @include ('partials.errors')
        @include ('partials.notification')

        <div class="people-profile-information">

          @if ($contact->has_avatar)
            <img src="{{ $contact->getAvatarURL(110) }}" width="87">
          @else
            @if (! is_null($contact->gravatar_url))
              <img src="{{ $contact->gravatar_url }}" width="87">
            @else
              @if (strlen($contact->getInitials()) == 1)
              <div class="avatar one-letter" style="background-color: {{ $contact->getAvatarColor() }};">
                {{ $contact->getInitials() }}
              </div>
              @else
              <div class="avatar" style="background-color: {{ $contact->getAvatarColor() }};">
                {{ $contact->getInitials() }}
              </div>
              @endif
            @endif
          @endif

          <h3>
            <span class="{{ htmldir() == 'ltr' ? 'mr1' : 'ml1' }}">{{ $contact->name }}</span>

            <contact-favorite hash="{{ $contact->hashID() }}" :starred="{{ json_encode($contact->is_starred) }}"></contact-favorite>

            @if ($contact->birthday_special_date_id && !($contact->is_dead))
              @if ($contact->birthdate->getAge())
                <span class="ml3 f4">(<i class="fa fa-birthday-cake mr1"></i> {{ $contact->birthdate->getAge() }})</span>
              @endif
            @elseif ($contact->is_dead)
                @if (! is_null($contact->deceasedDate))
                  <span class="ml3 f4">({{ trans('people.deceased_age') }} {{ $contact->getAgeAtDeath() }})</span>
                @endif
            @endif
          </h3>

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

          <stay-in-touch :contact="{{ $contact }}" hash="{{ $contact->hashID() }}" limited="{{ auth()->user()->account->hasLimitations() }}"></stay-in-touch>

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
