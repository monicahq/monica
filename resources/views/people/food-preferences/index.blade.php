<div class="sidebar-box">

  <div class="sidebar-box-title">
    <h3>{{ trans('people.food_preferences_title') }}</h3>
  </div>

  <p class="sidebar-box-paragraph">

  @if (is_null($contact->food_preferences))
    <a href="{{ route('people.food.index', $contact) }}">{{ trans('app.add') }}</a>
  @else
    {{ $contact->food_preferences }}
    <a href="{{ route('people.food.index', $contact) }}" class="action-link">{{ trans('app.edit') }}</a>
  @endif

  </p>

</div>
