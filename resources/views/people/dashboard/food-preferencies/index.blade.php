<div class="section food-preferencies">

  <div class="section-heading">

    <img src="/img/people/food_preferencies.svg">
    {{ trans('people.food_preferencies_title') }}

    @if (! is_null($contact->getFoodPreferencies()))
      <div class="section-action">
        <a href="/people/{{ $contact->id }}/food" class="edit-food-preferencies">{{ trans('app.edit') }}</a>
      </div>
    @endif

  </div>

  @if (is_null($contact->getFoodPreferencies()))

    {{-- Blank state --}}
    <div class="section-blank">
      <p>
        <a href="/people/{{ $contact->id }}/food">{{ trans('people.food_preferencies_cta') }}</a>
      </p>
    </div>

  @else

    {{ $contact->getFoodPreferencies() }}

  @endif

</div>
