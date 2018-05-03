{{-- Data comes from DateSelectViewComposer --}}

<div class="mt2">

  <select id="{{ $class }}_month" name="{{ $class }}_month" class="{{ \App\Helpers\LocaleHelper::getDirection() == 'ltr' ? 'mr2' : '' }}">
    @foreach($months as $month)
      <option value="{{ $month['id'] }}"
        @if ($specialDate && $specialDate instanceof Carbon\Carbon)
          {{ ($specialDate->month == $month['id']) ? 'selected="selected"': '' }}
        @else
          {{ ($specialDate == null) ? '' : (($specialDate->date->month == $month['id']) ? 'selected="selected"': '') }}
        @endif
      >
        {{ $month['name'] }}
      </option>
    @endforeach
  </select>

  <select id="{{ $class }}_day" name="{{ $class }}_day" class="mr2">
    @for ($day=1 ; $day < 32 ; $day++)
      <option value="{{ $day }}"
        @if ($specialDate && $specialDate instanceof Carbon\Carbon)
          {{ $specialDate->day == $day ? 'selected="selected"': '' }}
        @else
          {{ ($specialDate == null) ? '' : (($specialDate->date->day == $day) ? 'selected="selected"': '') }}
        @endif
      >
        {{ $day }}
      </option>
    @endfor
  </select>

    <select id="{{ $class }}_year" name="{{ $class }}_year" class="{{ \App\Helpers\LocaleHelper::getDirection() == 'ltr' ? '' : 'mr2' }}">
      @if ($specialDate && $specialDate instanceof APP\SpecialDate)
        <option value="0" {{ ! $specialDate->is_year_unknown ? '' : 'selected="selected"' }}>{{ trans('app.unknown') }}</option>
      @else
        <option value="0">{{ trans('app.unknown') }}</option>
      @endif

      @foreach($years as $year => $value)
        <option value="{{ $value }}"
          @if ($specialDate && $specialDate instanceof Carbon\Carbon)
            {{ $specialDate->year == $value ? 'selected="selected"': '' }}
          @else
            {{ ($specialDate == null) ? '' : ($specialDate->is_year_unknown ? '' : (($specialDate->date->year == $value) ? 'selected="selected"': '')) }}
          @endif
        >
          {{ $value }}
        </option>
      @endforeach
    </select>
</div>
