@if (Route::currentRouteName() == $route)
  <li class="selected">
    <i class="{{ $icon }}" aria-hidden="true"></i>
    <strong>{{ trans($title) }}</strong>
  </li>
@else
  <li class="bg-white">
    <i class="{{ $icon }}" aria-hidden="true"></i>
    <a href="{{ route($route) }}">{{ trans($title) }}</a>
  </li>
@endif
