<div class="sidebar-box significantother">

  <p class="sidebar-box-title">
    <strong>{{ trans('people.significant_other_sidebar_title') }}</strong>
  </p>

  @if (is_null($contact->getCurrentSignificantOther()))

    <p class="sidebar-box-paragraph">
      <a href="/people/{{ $contact->id }}/significant-others/add">{{ trans('people.significant_other_cta') }}</a>
    </p>

  @else

    {{-- Information about the significant other --}}
    <p class="sidebar-box-paragraph">
      <span class="name">{{ $contact->getCurrentSignificantOther()->getName() }}</span>

      @if (! is_null($contact->getCurrentSignificantOther()->getAge()))
        ({{ $contact->getCurrentSignificantOther()->getAge() }})
      @endif

      <a href="/people/{{ $contact->id }}/significant-others/{{ $contact->getCurrentSignificantOther()->id }}/edit" class="action-link">{{ trans('app.edit') }}</a>
      <a href="/people/{{ $contact->id }}/significant-others/{{ $contact->getCurrentSignificantOther()->id }}/delete" onclick="return confirm('{{ trans('people.significant_other_delete_confirmation') }}');" class="action-link">{{ trans('app.delete') }}</a>
    </p>

  @endif

</div>
