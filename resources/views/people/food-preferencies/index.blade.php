<div class="sidebar-box">

  @if (is_null($contact->food_preferencies))

    <p class="sidebar-box-title">
      <strong>{{ trans('people.food_preferencies_title') }}</strong>
    </p>

    <p class="sidebar-box-paragraph">
      <a href="/people/{{ $contact->hashID() }}/food">{{ trans('app.add') }}</a>
    </p>

  @else

    <p class="sidebar-box-title">
      <strong>{{ trans('people.food_preferencies_title') }}</strong>
    </p>

    {{-- Information about the significant other --}}
    <p class="sidebar-box-paragraph">
      {{ $contact->food_preferencies }}
      <a href="/people/{{ $contact->hashID() }}/food" class="action-link {{ \App\Helpers\LocaleHelper::getDirection() }}">{{ trans('app.edit') }}</a>
    </p>

  @endif

</div>
