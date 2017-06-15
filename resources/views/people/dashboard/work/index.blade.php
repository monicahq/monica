<div class="sidebar-box">

  <p class="sidebar-box-title">
    <strong>Work information</strong>
  </p>

  <div class="work">
    <ul>

      {{-- Work information --}}
      <li>
        <i class="fa fa-building-o" aria-hidden="true"></i>
        @if (is_null($contact->getJob()) and is_null($contact->getCompany()))
          {{ trans('people.information_no_work_defined') }}
        @else
          @if (!is_null($contact->getJob()))
            {{ $contact->getJob() }}

            @if (!is_null($contact->getCompany()))
              {{ trans('people.information_work_at', ['company' => $contact->getCompany()]) }}
            @endif
          @else
            {{ $contact->getCompany() }}
          @endif

        @endif
      </li>

      {{-- LinkedIn --}}
      <li>
        <i class="fa fa-linkedin-square"></i>
        @if (is_null($contact->getLinkedin()))
        {{ trans('people.information_no_linkedin_defined') }}
        @else
        <a href="{{ $contact->getLinkedin() }}">LinkedIn</a>
        @endif
      </li>
    </ul>

    <p class="sidebar-box-paragraph">
      <a href="/people/{{ $contact->id }}/work/edit">{{ trans('people.work_add_cta') }}</a>
    </p>
  </div>

</div>
