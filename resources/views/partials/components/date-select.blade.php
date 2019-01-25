{{-- Data comes from DateSelectViewComposer --}}

<div class="mt2">

  <select id="{{ $class }}_month" name="{{ $class }}_month" class="{{ htmldir() == 'ltr' ? 'mr2' : '' }}">
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

    <select id="{{ $class }}_year" name="{{ $class }}_year" class="{{ htmldir() == 'ltr' ? '' : 'mr2' }}">
      @if ($specialDate && $specialDate instanceof APP\Models\Instance\SpecialDate)
        <option value="0" {{ ! $specialDate->is_year_unknown ? '' : 'selected="selected"' }}>{{ trans('app.unknown') }}</option>
      @else
        <option value="0">{{ trans('app.unknown') }}</option>
      @endif

      @foreach($years as $year)
        <option value="{{ $year['id'] }}"
          @if ($specialDate && $specialDate instanceof Carbon\Carbon)
            {{ $specialDate->year == $year['id'] ? 'selected="selected"': '' }}
          @else
            {{ ($specialDate == null) ? '' : ($specialDate->is_year_unknown ? '' : (($specialDate->date->year == $year['id']) ? 'selected="selected"': '')) }}
          @endif
        >
          {{ $year['id'] }}
        </option>
      @endforeach
    </select>
</div>
