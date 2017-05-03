<div class="sidebar-box">

  @if (is_null($contact->getFoodPreferencies()))

    <p class="sidebar-box-title">
      <img src="/img/people/food_preferencies.svg">
      <strong>{{ trans('people.food_preferencies_title') }}</strong>
      <a href="/people/{{ $contact->id }}/significantother/add">{{ trans('app.add') }}</a>
    </p>

    <p class="sidebar-box-paragraph">
      {{ trans('people.significant_other_sidebar_blank') }}
    </p>

  @else

    <p class="sidebar-box-title">
      <img src="/img/people/food_preferencies.svg">
      <strong>{{ trans('people.food_preferencies_title') }}</strong>
      <a href="/people/{{ $contact->id }}/food" class="edit-food-preferencies">{{ trans('app.edit') }}</a>
    </p>

    {{-- Information about the significant other --}}
    <p class="sidebar-box-paragraph">
      {{ $contact->getFoodPreferencies() }}
    </p>

  @endif

</div>
