{{-- Data comes from DateSelectViewComposer --}}

<div class="date-picker {{ ($contact->deceased_date == null) ? 'hidden' : '' }}" id="datesSelector">

  <select id="month" name="monthDeceased">
    @foreach($months as $month => $value)
      <option value="{{ $month }}"
        {{ ($contact->deceased_date == null) ? '' : (($contact->deceased_date->month == $month) ? 'selected="selected"': '') }}
      >
        {{ $value }}
      </option>
    @endforeach
  </select>

  <select id="day" name="dayDeceased">
    @for ($day=1 ; $day < 32 ; $day++)
    <option value="{{ $day }}"
      {{ ($contact->deceased_date == null) ? '' : (($contact->deceased_date->day == $day) ? 'selected="selected"': '') }}
    >
      {{ $day }}
    </option>
    @endfor
  </select>

  <select id="year" name="yearDeceased">
    @foreach($years as $year => $value)
      <option value="{{ $value }}"
      {{ ($contact->deceased_date == null) ? '' : (($contact->deceased_date->year == $value) ? 'selected="selected"': '') }}
      >
        {{ $value }}
      </option>
    @endforeach
  </select>

</div>