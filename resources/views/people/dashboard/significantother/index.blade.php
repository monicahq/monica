<div class="sidebar-box significantother">

  <p class="sidebar-box-title">
    <strong>{{ trans('people.significant_other_sidebar_title') }}</strong>
  </p>

  @if ($contact->getCurrentPartners()->count() == 0)

    <p class="sidebar-box-paragraph">
      <a href="/people/{{ $contact->id }}/significant-others/add">{{ trans('people.significant_other_cta') }}</a>
    </p>

  @else

    {{-- Information about the significant other --}}
    @foreach ($contact->getCurrentPartners() as $partner)
      <p class="sidebar-box-paragraph">

        @if ($partner->is_significant_other)

        <span class="name">{{ $partner->getCompleteName() }}</span>

        @if (! is_null($partner->getAge()))
          ({{ $partner->getAge() }})
        @endif

        <a href="/people/{{ $contact->id }}/significant-others/{{ $partner->id }}/edit" class="action-link">{{ trans('app.edit') }}</a>
        <a href="/people/{{ $contact->id }}/significant-others/{{ $partner->id }}/delete" onclick="return confirm('{{ trans('people.significant_other_delete_confirmation') }}');" class="action-link">{{ trans('app.delete') }}</a>

        @else

        <a href="/people/{{ $partner->id }}"><span class="name">{{ $partner->getCompleteName() }}</span></a>

        @if (! is_null($partner->getAge()))
          ({{ $partner->getAge() }})
        @endif

        <a href="/people/{{ $contact->id }}/significant-others/{{ $partner->id }}/unlink" onclick="return confirm('{{ trans('people.significant_other_unlink_confirmation') }}');" class="action-link">Remove</a>

        @endif
      </p>
    @endforeach

    <p class="sidebar-box-paragraph">
      <a href="/people/{{ $contact->id }}/significant-others/add">{{ trans('people.significant_other_cta') }}</a>
    </p>

  @endif

</div>
