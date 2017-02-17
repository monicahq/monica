<div class="section significantother">

  <div class="section-heading">
    <img src="/img/people/dashboard/significantother/love.svg">
    {{ trans('people.significant_other_sidebar_title') }}
  </div>

  @if (is_null($contact->getCurrentSignificantOther()))

    {{-- Blank state --}}
    <div class="section-blank">

      <p>
        <a href="/people/{{ $contact->id }}/significantother/add">{{ trans('people.significant_other_blank_link') }}</a> {{ trans('people.significant_other_blank_link_description_'.$contact->gender) }}.
      </p>

    </div>

  @else

    {{-- Information about the significant other --}}
    <ul class="significantother-list">
      <li>
        {{ $contact->getCurrentSignificantOther()->getCompleteName() }}
        ({{ $contact->getCurrentSignificantOther()->getAge() }}).
        Birthday: {{ App\Helpers\DateHelper::getShortDate($contact->getCurrentSignificantOther()->getBirthdate(), Auth::user()->locale) }}

        <div class="inline-action">
          <a href="/people/{{ $contact->id }}/significantother/{{ $contact->getCurrentSignificantOther()->id }}/edit" class="action-link">{{ trans('app.edit') }}</a>
          <a href="/people/{{ $contact->id }}/significantother/{{ $contact->getCurrentSignificantOther()->id }}/delete" onclick="return confirm('{{ trans('people.significant_other_delete_confirmation') }}');" class="action-link">{{ trans('app.delete') }}</a>
        </div>
      </li>
    </ul>

  @endif

</div>
