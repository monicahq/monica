<div class="sidebar-box">

  <p class="sidebar-box-title">
    <strong>{{ trans('people.section_personal_information') }}</strong>
  </p>

  <div class="people-information">
    <ul>

      {{-- Birthdate --}}
      <li>
        <i class="fa fa-birthday-cake"></i>
        @if (is_null($contact->birthdate))
          {{ trans('people.birthdate_not_set') }}
        @else
          {{ $contact->getAge() }}
        @endif
      </li>

      {{-- City --}}
      <li>
        <i class="fa fa-globe"></i>
        @if (is_null($contact->getPartialAddress()))
        {{ trans('people.information_no_address_defined') }}
        @else
        {{ $contact->getPartialAddress() }}
        @endif
      </li>

      {{-- Email address --}}
      <li>
        <i class="fa fa-envelope-open-o"></i>
        @if (is_null($contact->email))
        {{ trans('people.information_no_email_defined') }}
        @else
        <a href="mailto:{{ $contact->email }}">
          {{ $contact->email }}
        </a>
        @endif
      </li>

      {{-- Phone --}}
      <li>
        <i class="fa fa-volume-control-phone"></i>
        @if (is_null($contact->phone_number))
        {{ trans('people.information_no_phone_defined') }}
        @else
        <a href="tel:{{ $contact->phone_number }}">
          {{ $contact->phone_number }}
        </a>
        @endif
      </li>

      {{-- Facebook --}}
      <li>
        <i class="fa fa-facebook-official"></i>
        @if (is_null($contact->facebook_profile_url))
        {{ trans('people.information_no_facebook_defined') }}
        @else
        <a href="{{ $contact->facebook_profile_url }}">Facebook</a>
        @endif
      </li>

      {{-- Twitter --}}
      <li>
        <i class="fa fa-twitter-square"></i>
        @if (is_null($contact->twitter_profile_url))
        {{ trans('people.information_no_twitter_defined') }}
        @else
        <a href="{{ $contact->twitter_profile_url }}">Twitter</a>
        @endif
      </li>
    </ul>
  </div>

</div>
