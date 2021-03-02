<div class="sidebar-box">

  <div class="sidebar-box-title">
    <h3>{{ trans('people.work_information') }}</h3>
  </div>

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
    </ul>

    <p class="sidebar-box-paragraph">
      <a href="{{ route('people.work.edit', $contact) }}">{{ trans('people.work_add_cta') }}</a>
    </p>
  </div>

</div>
