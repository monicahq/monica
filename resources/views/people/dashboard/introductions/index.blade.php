<div class="sidebar-box kids">

  <p class="sidebar-box-title">
    <strong>{{ trans('people.introductions_sidebar_title') }}</strong>
  </p>

  @if(! $contact->hasFirstMetInformation())
    <p class="sidebar-box-paragraph">
      <a href="{{ route('people.introductions.edit', $contact) }}">{{ trans('people.introductions_blank_cta', ['name' => $contact->getFirstName()]) }}</a>
    </p>
  @else
    <ul>
      <li>Met through <a href="">Ljlkjslf</a></li>
      <li>Met on</li>
      <li>{{ $contat->first_met_additional_info }}</li>
    </ul>

    <p class="sidebar-box-paragraph">
      <a href="{{ route('people.kids.add', $contact) }}">{{ trans('people.kids_blank_cta') }}</a>
    </p>

  @endif

</div>
