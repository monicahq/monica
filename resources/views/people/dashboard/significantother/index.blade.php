<div class="sidebar-box significantother">

  <p class="sidebar-box-title">
    <strong>{{ trans('people.significant_other_sidebar_title') }}</strong>
  </p>

  @if (is_null($contact->getCurrentSignificantOther()))

    <p class="sidebar-box-paragraph">
      <a href="{{ route('people.dashboard.significantother.add', ['people' => $contact->id]) }}">{{ trans('people.significant_other_cta') }}</a>
    </p>

  @else

    {{-- Information about the significant other --}}
    <p class="sidebar-box-paragraph">
      <span class="name">{{ $contact->getCurrentSignificantOther()->getName() }}</span>

      @if (! is_null($contact->getCurrentSignificantOther()->getAge()))
      ({{ $contact->getCurrentSignificantOther()->getAge() }})
      @endif

      <a href="{{ route('people.dashboard.significantother.edit', ['people' => $contact->id, 'significantother' => $contact->getCurrentSignificantOther()->id]) }}" class="action-link">{{ trans('app.edit') }}</a>
      <a href="{{ route('people.dashboard.significantother.delete', ['people' => $contact->id, 'significantother' => $contact->getCurrentSignificantOther()->id]) }}" onclick="return confirm('{{ trans('people.significant_other_delete_confirmation') }}');" class="action-link">{{ trans('app.delete') }}</a>
    </p>

  @endif

</div>
