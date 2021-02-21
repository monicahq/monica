<div class="sidebar-box">

  <div class="sidebar-box-title">
    <h3>{{ trans('people.work_information') }}</h3>
  </div>

  <div class="work dark-gray">
    <ul class="mb2">
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

    <p class="mb0">
      <a href="{{ route('people.work.edit', $contact) }}">{{ trans('people.work_add_cta') }}</a>
    </p>
  </div>

</div>
