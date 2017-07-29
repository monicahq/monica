<div class="sidebar-box significantother">

  <p class="sidebar-box-title">
    <strong>{{ trans('people.significant_other_sidebar_title') }}</strong>
  </p>

  @if ($contact->getCurrentSignificantOthers()->count() == 0)

    <p class="sidebar-box-paragraph">
      <a href="/people/{{ $contact->id }}/significant-others/add">{{ trans('people.significant_other_cta') }}</a>
    </p>

  @else

    {{-- Information about the significant other --}}
    @foreach ($contact->getCurrentSignificantOthers() as $significantOther)
    <p class="sidebar-box-paragraph">
      <span class="name">{{ $significantOther->getCompleteName() }}</span>

      @if (! is_null($significantOther->getAge()))
        ({{ $significantOther->getAge() }})
      @endif

      <a href="/people/{{ $contact->id }}/significant-others/{{ $significantOther->id }}/edit" class="action-link">{{ trans('app.edit') }}</a>
      <a href="/people/{{ $contact->id }}/significant-others/{{ $significantOther->id }}/delete" onclick="return confirm('{{ trans('people.significant_other_delete_confirmation') }}');" class="action-link">{{ trans('app.delete') }}</a>
    </p>
    @endforeach

    <p class="sidebar-box-paragraph">
      <a href="/people/{{ $contact->id }}/significant-others/add">{{ trans('people.significant_other_cta') }}</a>
    </p>

  @endif

</div>
