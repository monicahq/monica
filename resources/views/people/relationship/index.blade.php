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
      <div class="sidebar-box-paragraph">

        @if ($partner->is_partial)

          <span class="name">{{ $partner->getCompleteName(auth()->user()->name_order) }}</span>

          @if ($partner->birthday_special_date_id)
            @if ($partner->birthdate->getAge())
              ({{ $partner->birthdate->getAge() }})
            @endif
          @endif

          <a href="{{ route('people.relationships.edit', [$contact, $partner]) }}" class="action-link {{ $contact->id }}-edit-relationship">
            {{ trans('app.edit') }}
          </a>

          <a href="#" onclick="if (confirm('{{ trans('people.significant_other_delete_confirmation') }}')) { $(this).closest('.sidebar-box-paragraph').find('.entry-delete-form').submit(); } return false;" class="action-link">
            {{ trans('app.delete') }}
          </a>

          <form method="POST" action="{{ route('people.relationships.delete', [$contact, $partner]) }}" class="entry-delete-form hidden">
            {{ method_field('DELETE') }}
            {{ csrf_field() }}
          </form>

        @else

          <a href="{{ route('people.show', $partner) }}">{{ $partner->getCompleteName(auth()->user()->name_order) }}</a>

          @if ($partner->birthday_special_date_id)
            @if ($partner->birthdate->getAge())
              ({{ $partner->birthdate->getAge() }})
            @endif
          @endif

          <a href="#" onclick="if (confirm('{{ trans('people.significant_other_unlink_confirmation') }}')) { $(this).closest('.sidebar-box-paragraph').find('.entry-delete-form').submit(); } return false;" class="action-link {{ $contact->id }}-unlink-relationship">
            {{ trans('app.remove') }}
          </a>

          <form method="POST" action="{{ route('people.relationships.unlink', [$contact, $partner]) }}" class="entry-delete-form hidden">
            {{ csrf_field() }}
          </form>

        @endif
      </div>
    @endforeach

    <p class="sidebar-box-paragraph">
      <a href="{{ route('people.relationships.add', $contact) }}">{{ trans('people.significant_other_cta') }}</a>
    </p>

  @endif

</div>
