<div class="sidebar-box">

  @if (is_null($contact->getFoodPreferencies()))

    <p class="sidebar-box-title">
      <strong>{{ trans('people.food_preferencies_title') }}</strong>
    </p>

    <p class="sidebar-box-paragraph">
      <a href="{{ route('people.food', ['people' => $contact->id]) }}">{{ trans('app.add') }}</a>
    </p>

  @else

    <p class="sidebar-box-title">
      <strong>{{ trans('people.food_preferencies_title') }}</strong>
    </p>

    {{-- Information about the significant other --}}
    <p class="sidebar-box-paragraph">
      {{ $contact->getFoodPreferencies() }}
      <a href="{{ route('people.food', ['people' => $contact->id]) }}" class="action-link">{{ trans('app.edit') }}</a>
    </p>

  @endif

</div>
