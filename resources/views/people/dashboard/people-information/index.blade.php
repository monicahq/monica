<div class="sidebar-box">

  <p class="sidebar-box-title">
    <strong>{{ trans('people.section_personal_information') }}</strong>
  </p>

  <div class="people-information">
    <ul>

      {{-- Birthdate --}}
      <li>
        <i class="fa fa-birthday-cake"></i>
        @if (is_null($contact->getBirthdate()))
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
        @if (is_null($contact->getEmail()))
        {{ trans('people.information_no_email_defined') }}
        @else
        {{ $contact->getEmail() }}
        @endif
      </li>

      {{-- Phone --}}
      <li>
        <i class="fa fa-volume-control-phone"></i>
        @if (is_null($contact->getPhone()))
        {{ trans('people.information_no_phone_defined') }}
        @else
        {{ $contact->getPhone() }}
        @endif
      </li>

      {{-- Facebook --}}
      <li>
        <i class="fa fa-facebook-official"></i>
        @if (is_null($contact->getFacebook()))
        {{ trans('people.information_no_facebook_defined') }}
        @else
        <a href="{{ $contact->getFacebook() }}">Facebook</a>
        @endif
      </li>

      {{-- Twitter --}}
      <li>
        <i class="fa fa-twitter-square"></i>
        @if (is_null($contact->getTwitter()))
        {{ trans('people.information_no_twitter_defined') }}
        @else
        <a href="{{ $contact->getTwitter() }}">Twitter</a>
        @endif
      </li>

      {{-- LinkedIn --}}
      <li>
        <i class="fa fa-linkedin-square"></i>
        @if (is_null($contact->getLinkedin()))
        {{ trans('people.information_no_linkedin_defined') }}
        @else
        <a href="{{ $contact->getLinkedin() }}">Twitter</a>
        @endif
      </li>
    </ul>
  </div>

</div>
