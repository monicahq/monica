{{-- Data comes from DateSelectViewComposer --}}

<div class="mt2">

  <select id="{{ $class }}_month" name="{{ $class }}_month" class="mr2">
    @foreach($months as $month)
      <option value="{{ $month['id'] }}"
        {{ ($specialDate == null) ? '' : (($specialDate->date->month == $month['id']) ? 'selected="selected"': '') }}
      >
        {{ $month['name'] }}
      </option>
    @endforeach
  </select>

  <select id="{{ $class }}_day" name="{{ $class }}_day" class="mr2">
    @for ($day=1 ; $day < 32 ; $day++)
    <option value="{{ $day }}"
      {{ ($specialDate == null) ? '' : (($specialDate->date->day == $day) ? 'selected="selected"': '') }}
    >
      {{ $day }}
    </option>
    @endfor
  </select>

    <select id="{{ $class }}_year" name="{{ $class }}_year">

      @if ($specialDate)
        <option value="0" {{ ! $specialDate->is_year_unknown ? '' : 'selected="selected"' }}>{{ trans('app.unknown') }}</option>
      @else
        <option value="0">{{ trans('app.unknown') }}</option>
      @endif

      @foreach($years as $year => $value)
        @if ($specialDate)
          <option value="{{ $value }}" {{ $specialDate->is_year_unknown ? '' : (($specialDate->date->year == $value) ? 'selected="selected"': '') }}>
            {{ $value }}
          </option>
        @else
          <option value="{{ $value }}" >
            {{ $value }}
          </option>
        @endif
      @endforeach
    </select>

</div>
