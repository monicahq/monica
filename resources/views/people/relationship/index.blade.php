<div class="sidebar-box significantother">

  <p class="sidebar-box-title">
    <strong>{{ trans('people.significant_other_sidebar_title') }}</strong>
  </p>

  @if ($partnerRelationships->count() == 0)

    <p class="sidebar-box-paragraph">
      <a href="/people/{{ $contact->id }}/relationships/new?type={{ $contact->account->getRelationshipTypeByType('partner')->id }}">{{ trans('people.significant_other_cta') }}</a>
    </p>

  @else

    {{-- Information about the significant other --}}
    @foreach ($partnerRelationships as $partner)
      <div class="sidebar-box-paragraph">
        @if ($partner->withContact->is_partial)
        <span class="name">{{ $partner->withContact->getCompleteName(auth()->user()->name_order) }}</span>
        @else
        <a href="{{ route('people.show', $partner->withContact) }}">{{ $partner->withContact->getCompleteName(auth()->user()->name_order) }}</a>
        @endif

        @if ($partner->withContact->birthday_special_date_id)
          @if ($partner->withContact->birthdate->getAge())
            ({{ $partner->withContact->birthdate->getAge() }})
          @endif
        @endif

        @if ($partner->withContact->is_partial)
        <a href="{{ route('people.relationships.edit', [$contact, $partner->withContact]) }}" class="action-link {{ $contact->id }}-edit-relationship">
          {{ trans('app.edit') }}
        </a>
        <a href="#" onclick="if (confirm('{{ trans('people.relationship_delete_confirmation') }}')) { $(this).closest('.sidebar-box-paragraph').find('.entry-delete-form').submit(); } return false;" class="action-link">
          {{ trans('app.delete') }}
        </a>
        @endif

        @if (! $partner->withContact->is_partial)
        <a href="#" onclick="if (confirm('{{ trans('people.relationship_unlink_confirmation') }}')) { $(this).closest('.sidebar-box-paragraph').find('.entry-delete-form').submit(); } return false;" class="action-link">
          {{ trans('app.delete') }}
        </a>
        @endif

        <form method="POST" action="/people/{{ $contact->id }}/relationships/{{ $partner->withContact->id }}" class="entry-delete-form hidden">
          {{ method_field('DELETE') }}
          {{ csrf_field() }}
        </form>
      </div>
    @endforeach

    <p class="sidebar-box-paragraph">
      <a href="/people/{{ $contact->id }}/relationships/new?type={{ $contact->account->getRelationshipTypeByType('partner')->id }}">{{ trans('people.significant_other_cta') }}</a>
    </p>

  @endif

</div>
