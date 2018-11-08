<div class="sidebar-box introductions">

  <p class="sidebar-box-title">
    <strong>{{ trans('people.introductions_sidebar_title') }}</strong>
  </p>

  @if(! $contact->hasFirstMetInformation())
    <p class="sidebar-box-paragraph">
      <a href="{{ route('people.introductions.edit', $contact) }}">{{ trans('people.introductions_blank_cta', ['name' => $contact->first_name]) }}</a>
    </p>
  @else
    <ul>
      @if ($introducer = $contact->getIntroducer())
      <li>
        <i class="fa fa-sign-language"></i>
        {!! trans('people.introductions_met_through', ['url' => route('people.show', $introducer), 'name' => $introducer->name]) !!}
      </li>
      @endif

      @if ($contact->firstMetDate)
      <li>
        <i class="fa fa-hourglass-start"></i>
        {{ trans('people.introductions_met_date', ['date' => $contact->firstMetDate->toShortString()]) }}
      </li>
      @endif

      @if ($contact->first_met_additional_info)
      <li>
        <i class="fa fa-id-card-o"></i>
        {{ $contact->first_met_additional_info }}
      </li>
      @endif
    </ul>

    <p class="sidebar-box-paragraph">
      <a href="{{ route('people.introductions.edit', $contact) }}">{{ trans('app.edit') }}</a>
    </p>

  @endif

</div>
