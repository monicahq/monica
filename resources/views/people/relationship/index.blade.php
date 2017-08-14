<div class="sidebar-box significantother">

  <p class="sidebar-box-title">
    <strong>{{ trans('people.significant_other_sidebar_title') }}</strong>
  </p>

  @if ($contact->getCurrentPartners()->count() == 0)

    <p class="sidebar-box-paragraph">
      <a href="{{ route('people.relationships.add', $contact) }}">{{ trans('people.significant_other_cta') }}</a>
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

          <a href="{{ route('people.relationships.edit', [$contact, $partner]) }}" class="action-link {{ $contact->id }}-edit-relationship">
            {{ trans('app.edit') }}
          </a>

          <a href="{{ route('people.relationships.delete', [$contact, $partner]) }}" onclick="return confirm('{{ trans('people.significant_other_delete_confirmation') }}');" class="action-link">
            {{ trans('app.delete') }}
          </a>

        @else

          <a href="{{ route('people.show', $partner) }}">{{ $partner->getCompleteName() }}</a>

          @if (! is_null($partner->getAge()))
            ({{ $partner->getAge() }})
          @endif

          <a href="{{ route('people.relationships.unlink', [$contact, $partner]) }}" onclick="return confirm('{{ trans('people.significant_other_unlink_confirmation') }}');" class="action-link {{ $contact->id }}-unlink-relationship">
            {{ trans('app.remove') }}
          </a>

        @endif
      </p>
    @endforeach

    <p class="sidebar-box-paragraph">
      <a href="{{ route('people.relationships.add', $contact) }}">{{ trans('people.significant_other_cta') }}</a>
    </p>

  @endif

</div>
