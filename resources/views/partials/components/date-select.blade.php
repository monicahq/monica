{{-- Data comes from DateSelectViewComposer --}}

<div class="mt2">

  <select id="{{ $class }}_month" name="{{ $class }}_month" class="mr2">
    @foreach($months as $month => $value)
      <option value="{{ $month + 1 }}"
        {{ ($date == null) ? '' : (($date->date->month == ($month + 1)) ? 'selected="selected"': '') }}
      >
        {{ $value }}
      </option>
    @endforeach
  </select>

  <select id="{{ $class }}_day" name="{{ $class }}_day" class="mr2">
    @for ($day=1 ; $day < 32 ; $day++)
    <option value="{{ $day }}"
      {{ ($date == null) ? '' : (($date->date->day == $day) ? 'selected="selected"': '') }}
    >
      {{ $day }}
    </option>
    @endfor
  </select>

  <select id="{{ $class }}_year" name="{{ $class }}_year">
    <option value="0">Unknown</option>
    @foreach($years as $year => $value)
      <option value="{{ $value }}"
      {{ ($date == null) ? '' : (($date->date->year == $value) ? 'selected="selected"': '') }}
      >
        {{ $value }}
      </option>
    @endforeach
  </select>

</div>
