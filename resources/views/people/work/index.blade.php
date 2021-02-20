<div class="sidebar-box">

  <p class="sidebar-box-title">
    <strong>{{ trans('people.work_information') }}</strong>
  </p>

  <div class="work">
    <ul>

      {{-- Work information --}}
      <li>
        <i class="fa fa-building-o" aria-hidden="true"></i>
        @if (is_null($information['work']['job']) and is_null($information['work']['company']))
          {{ trans('people.information_no_work_defined') }}
        @else
          @if (!is_null($information['work']['job']))
            {{ $contact->job }}

            @if (!is_null($information['work']['company']))
              {{ trans('people.information_work_at', ['company' => $information['work']['company']]) }}
            @endif
          @else
            {{ $information['work']['company'] }}
          @endif

        @endif
      </li>
    </ul>

    <p class="sidebar-box-paragraph">
      <a href="{{ route('people.work.edit', $contact) }}">{{ trans('people.work_add_cta') }}</a>
    </p>
  </div>

</div>
