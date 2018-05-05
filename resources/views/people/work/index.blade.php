<div class="sidebar-box">

  <p class="sidebar-box-title">
    <strong>{{ trans('people.work_information') }}</strong>
  </p>

  <div class="work">
    <ul>

      {{-- Work information --}}
      <li>
        <i class="fa fa-building-o" aria-hidden="true"></i>
        @if (is_null($contact->job) and is_null($contact->company))
          {{ trans('people.information_no_work_defined') }}
        @else
          @if (!is_null($contact->job))
            {{ $contact->job }}

            @if (!is_null($contact->company))
              {{ trans('people.information_work_at', ['company' => $contact->company]) }}
            @endif
          @else
            {{ $contact->company }}
          @endif

        @endif
      </li>

      {{-- LinkedIn --}}
      <li>
        <i class="fa fa-linkedin-square"></i>
        @if (is_null($contact->linkedin_profile_url))
        {{ trans('people.information_no_linkedin_defined') }}
        @else
        <a href="{{ $contact->linkedin_profile_url }}">{{ trans('people.linkedin_profile') }}</a>
        @endif
      </li>
    </ul>

    <p class="sidebar-box-paragraph">
      <a href="/people/{{ $contact->hashID() }}/work/edit">{{ trans('people.work_add_cta') }}</a>
    </p>
  </div>

</div>
