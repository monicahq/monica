<div class="section kids">

  <div class="section-heading">
    <img src="/img/people/dashboard/kids/children.svg">
    {{ trans('people.kids_sidebar_title') }}

    @if ($contact->getNumberOfKids() != 0)
      <div class="section-action">
        <a href="/people/{{ $contact->id }}/kid/add">{{ trans('people.kids_sidebar_cta') }}</a>
      </div>
    @endif
  </div>


  @if ($contact->getNumberOfKids() == 0)

    {{-- Blank state --}}
    <div class="section-blank">
      <p>
        <a href="/people/{{ $contact->id }}/kid/add">{{ trans('people.kids_blank_link') }}</a> {{ trans('people.kids_blank_link_description_'.$contact->gender) }}.
      </p>
    </div>

  @else

    {{-- List of kids --}}
    <ul class="kids-list">
      @foreach($contact->getKids() as $kid)
      <li>
        {{ $kid->getFirstName() }}
        ({{ $kid->getAge() }}).
        Birthday: {{ App\Helpers\DateHelper::getShortDate($kid->getBirthdate(), Auth::user()->locale) }}

        <div class="inline-action">
          <a href="/people/{{ $contact->id }}/kid/{{ $kid->id }}/edit" class="action-link">Edit</a>
          <a href="/people/{{ $contact->id }}/kid/{{ $kid->id }}/delete" class="action-link" onclick="return confirm('{{ trans('people.kids_delete_confirmation') }}');">Delete</a>
        </div>
      </li>
      @endforeach
    </ul>

  @endif

</div>
