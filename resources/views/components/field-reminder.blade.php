{{--
  The reminder a date field may carry, asked for under the date itself.

  A date on its own is only a record. This is the box that turns it into
  something somebody will actually hear about, and it sits under the control
  holding the date because the rule is an answer about that date rather than a
  screen of its own. It is written by the block's own save, so Cancel throws it
  away with everything else typed in that block.

  Everything the panel holds is drawn whether the box is ticked or not, and the
  panel is shown rather than built when the box is ticked. The box is a real
  checkbox that says it controls a region and whether that region is open, which
  is the disclosure pattern.

  What is shown and hidden is `x-show` with `x-cloak` beside it, and never the
  `hidden` attribute. `hidden` comes from the browser's own stylesheet, so any
  `flex` or `grid` class on the same element beats it and the thing stays on the
  screen; `x-show` writes an inline style, which nothing beats.

  Nothing inside the panel is revealed by choosing between two things that are
  always both on offer. The cadence and its rhythm are the case: they sit in the
  flow at all times and go inert when the rule fires once, because showing them
  on the choice would move every control below them each time somebody changed
  their mind about it.

  Every moment is drawn by the server, blanks included, and revealed one at a
  time. The same reason <x-field-repetitions> does it: a row is three controls
  whose options are translated, and the browser has no business rebuilding them.
  A moment somebody removes is hidden and disabled, so the save sees an absence.

  The wording under each moment and the sentence at the foot describe what
  somebody is typing rather than what is saved, so they are assembled in the
  browser. The words they are assembled from are translated in
  {@see \App\ViewModels\Vaults\ContactReminderBlock} and handed over whole, so
  the two assemblers read the same strings. Each comes in two wordings, which is
  enough for every locale this product ships in and would not be for a language
  with more than two plural categories.

  What is not assembled in the browser is the next date. It needs real calendar
  arithmetic, it belongs to {@see \App\Helpers\ReminderSchedule}, and it
  describes the rule as saved.

  A rule that has gone quiet is drawn here too, with the reason in words.
  Nothing about a reminder disappears without an explanation, and this is where
  the explanation is read. Unticking the box over a rule that exists says what
  the save is about to remove, before it removes it.

  What a refused save said is given rather than read off the session, the same
  way every input in this system takes its own messages. It is what lets the
  panel be drawn on its own, and it is why there is no `$errors` in here.

  @var \App\Models\Field $field
  @var array<string, mixed> $reminder  what the view model assembled for this date
  @var string $name  the prefix the inputs are named by, which says which date they belong to
  @var string $id
  @var \Illuminate\Support\ViewErrorBag|null $messages
--}}
@props([
  'field',
  'reminder',
  'name',
  'id',
  'messages' => null,
])

@php
  use App\Enums\ReminderCadenceEnum;
  use App\Enums\ReminderMomentDirectionEnum;
  use App\Enums\ReminderMomentUnitEnum;
  use App\Models\Reminder;

  $panelId = $id.'-panel';

  /* How many blank moments one save may add. Each is a full row of controls, so
     this is a real cost on the page rather than a round number. */
  $blanks = 3;
  $held = $reminder['moments'];
  $heldCount = count($held);

  $directionWords = [
    ReminderMomentDirectionEnum::On->value => __('On the day'),
    ReminderMomentDirectionEnum::Before->value => __('Before'),
    ReminderMomentDirectionEnum::After->value => __('After'),
  ];

  $unitWords = [
    ReminderMomentUnitEnum::Days->value => __('days'),
    ReminderMomentUnitEnum::Weeks->value => __('weeks'),
    ReminderMomentUnitEnum::Months->value => __('months'),
  ];

  /* One entry per drawn row, held ones first. A blank starts as a nudge before
     the date, since that is what somebody adding a second moment usually wants
     and it is the one shape that needs no thought to accept. */
  $rows = [];

  foreach ($held as $moment) {
    $rows[] = [
      'direction' => $moment['direction'],
      'amount' => $moment['amount'] ?? Reminder::MINIMUM_AMOUNT,
      'unit' => $moment['unit'] ?? ReminderMomentUnitEnum::Days->value,
      'removed' => false,
    ];
  }

  for ($blank = 0; $blank < $blanks; $blank++) {
    $rows[] = [
      'direction' => ReminderMomentDirectionEnum::Before->value,
      'amount' => 7,
      'unit' => ReminderMomentUnitEnum::Days->value,
      'removed' => false,
    ];
  }

  $control = 'rounded-sm border border-hairline-strong bg-input px-2.5 py-1.5 font-sans text-sm text-ink '
    .'transition-[border-color] duration-(--duration-fast) ease-out '
    .'focus:border-focus focus:ring-[3px] focus:ring-focus-ring/20 focus:outline-none';
@endphp

<div
  class="flex flex-col gap-2.5"
  x-data="{
    on: @js($reminder['isOn']),
    shown: 0,
    held: {{ $heldCount }},
    rows: @js($rows),
    cadence: @js($reminder['cadence']),
    recurs: @js($reminder['recurs']),
    interval: @js($reminder['interval']),
    time: @js($reminder['time']),
    zone: @js($reminder['timezone']),
    words: @js($reminder['words']),
    isDrawn(index) {
      return index < this.held + this.shown;
    },
    counts(index) {
      return this.isDrawn(index) && ! this.rows[index].removed;
    },
    get active() {
      return this.rows.filter((row, index) => this.counts(index)).length;
    },
    phrase(row) {
      if (row.direction === 'on') {
        return this.words.onTheDay;
      }

      const count = Number(row.amount) || 1;
      const pair = this.words.units[row.unit] ?? this.words.units.days;
      const span = (count === 1 ? pair.one : pair.other).replace(':n', count);

      return this.words[row.direction === 'before' ? 'before' : 'after'].replace(':span', span);
    },
    get cadenceWords() {
      if (! this.recurs) {
        return this.words.once;
      }

      const count = Number(this.interval) || 1;
      const pair = this.words.intervals[this.cadence] ?? this.words.intervals.yearly;

      return (count === 1 ? pair.one : pair.other).replace(':n', count);
    },
    get summary() {
      const said = this.rows.filter((row, index) => this.counts(index)).map((row) => this.phrase(row));

      if (said.length === 0) {
        return this.words.nothing;
      }

      const listed = said.length === 1
        ? said[0]
        : said.slice(0, -1).join(', ') + ' ' + this.words.and + ' ' + said[said.length - 1];

      return this.words.summary
        .replace(':moments', listed)
        .replace(':cadence', this.cadenceWords)
        .replace(':time', this.time)
        .replace(':zone', this.zone);
    },
  }"
>
  <x-option-row
    :id="$id.'-on'"
    :name="$name.'[on]'"
    value="1"
    :checked="$reminder['isOn']"
    :hint="$reminder['hint']"
    x-model="on"
    ::aria-expanded="on"
    :aria-controls="$panelId"
  >{{ __('Add a reminder') }}</x-option-row>

  @if ($reminder['quietBecause'] !== null)
    <x-notice tone="warning">{{ $reminder['quietBecause'] }}</x-notice>
  @endif

  @if ($reminder['isOn'])
    {{-- Unticking the box takes a rule away, and a save that removes something
         has to say so before it happens rather than after. The date is named
         because that is the thing somebody will be afraid of losing. --}}
    <x-notice tone="danger" x-cloak x-show="! on">
      {{ __('Saving now removes this reminder. The date itself stays exactly where it is.') }}
    </x-notice>
  @endif

  <div
    id="{{ $panelId }}"
    x-cloak
    x-show="on"
    class="flex flex-col gap-4 rounded-md border border-hairline bg-sunken p-3.5"
  >
    <div class="flex flex-col gap-0.5">
      <p class="text-label text-ink">{{ __('The reminder') }}</p>

      <p class="text-xs text-pretty text-muted">{{ __('It is attached to this date. Change the date and the reminder follows.') }}</p>
    </div>

    <x-input
      :id="$id.'-name'"
      :name="$name.'[name]'"
      :label="__('What it should say')"
      :value="$reminder['name']"
      :placeholder="$reminder['placeholder']"
      :help="__('Left empty, it reads :wording.', ['wording' => $reminder['placeholder']])"
      :error="$messages?->get($reminder['errorKey'].'.name')"
    />

    <fieldset class="flex flex-col gap-2">
      <legend class="text-label text-ink">{{ __('How often') }}</legend>

      <div class="grid gap-2 min-[560px]:grid-cols-2">
        <x-option-row
          :id="$id.'-once'"
          :name="$name.'[recurs]'"
          value="0"
          type="radio"
          :checked="! $reminder['recurs']"
          :hint="__('A single reminder, then it is done.')"
          x-on:change="recurs = false"
        >{{ __('Once') }}</x-option-row>

        <x-option-row
          :id="$id.'-recurs'"
          :name="$name.'[recurs]'"
          value="1"
          type="radio"
          :checked="$reminder['recurs']"
          :hint="__('Follows the date, year after year.')"
          x-on:change="recurs = true"
        >{{ __('Every time it comes round') }}</x-option-row>
      </div>

      {{-- The cadence and its rhythm stay in the flow whether the rule comes
           back or not, and go inert when it does not. Revealing them would move
           everything below this point the moment somebody changed their mind,
           which is a form rearranging itself under the person filling it in. --}}
      <div
        @class([
          'flex flex-wrap items-end gap-2.5 pt-0.5',
          'transition-opacity duration-(--duration-fast)',
          'opacity-55' => ! $reminder['recurs'],
        ])
        x-bind:class="recurs ? '' : 'opacity-55'"
      >
        <x-select
          :id="$id.'-cadence'"
          :name="$name.'[cadence]'"
          :label="__('Comes back')"
          class="w-44"
          x-model="cadence"
          x-bind:disabled="! recurs"
          :disabled="! $reminder['recurs']"
          :error="$messages?->get($reminder['errorKey'].'.cadence')"
        >
          @foreach (ReminderCadenceEnum::recurring() as $cadence)
            <option value="{{ $cadence->value }}" @selected($reminder['cadence'] === $cadence->value)>{{ __($cadence->label()) }}</option>
          @endforeach
        </x-select>

        <x-input
          type="number"
          :id="$id.'-interval'"
          :name="$name.'[interval]'"
          :label="__('Every so many')"
          :value="$reminder['interval']"
          min="1"
          :max="Reminder::MAXIMUM_INTERVAL"
          class="w-24"
          x-model="interval"
          x-bind:disabled="! recurs"
          :disabled="! $reminder['recurs']"
          :error="$messages?->get($reminder['errorKey'].'.interval')"
        />
      </div>
    </fieldset>

    <fieldset class="flex flex-col gap-2">
      <legend class="text-label text-ink">{{ __('When to be told') }}</legend>

      <p class="text-xs text-pretty text-muted">{{ __('One or several. A nudge before, on the day, or a follow up after.') }}</p>

      <div class="flex flex-col gap-2 pt-0.5">
        @foreach ($rows as $index => $row)
          <div x-cloak x-show="isDrawn({{ $index }}) && ! rows[{{ $index }}].removed">
            <fieldset x-bind:disabled="! counts({{ $index }})" class="flex flex-wrap items-center gap-2 rounded-md border border-hairline bg-canvas px-2.5 py-2">
              <legend class="sr-only">{{ __('A moment to be told about :label', ['label' => $field->label]) }}</legend>

              <select
                name="{{ $name }}[moments][{{ $index }}][direction]"
                aria-label="{{ __('When') }}"
                x-model="rows[{{ $index }}].direction"
                class="{{ $control }}"
              >
                @foreach (ReminderMomentDirectionEnum::cases() as $direction)
                  <option value="{{ $direction->value }}" @selected($row['direction'] === $direction->value)>{{ $directionWords[$direction->value] }}</option>
                @endforeach
              </select>

              <span
                class="flex items-center gap-2"
                x-cloak
                x-show="rows[{{ $index }}].direction !== 'on'"
              >
                <input
                  type="number"
                  name="{{ $name }}[moments][{{ $index }}][amount]"
                  value="{{ $row['amount'] }}"
                  min="{{ Reminder::MINIMUM_AMOUNT }}"
                  max="{{ Reminder::MAXIMUM_AMOUNT }}"
                  aria-label="{{ __('How much') }}"
                  x-model="rows[{{ $index }}].amount"
                  class="w-20 {{ $control }}"
                />

                <select
                  name="{{ $name }}[moments][{{ $index }}][unit]"
                  aria-label="{{ __('Counted in') }}"
                  x-model="rows[{{ $index }}].unit"
                  class="{{ $control }}"
                >
                  @foreach (ReminderMomentUnitEnum::cases() as $unit)
                    <option value="{{ $unit->value }}" @selected($row['unit'] === $unit->value)>{{ $unitWords[$unit->value] }}</option>
                  @endforeach
                </select>
              </span>

              <span class="ml-auto flex items-center gap-2.5">
                <span class="font-mono text-xs text-muted" x-text="phrase(rows[{{ $index }}])">{{ $held[$index]['phrase'] ?? '' }}</span>

                <button
                  type="button"
                  x-cloak
                  x-show="active > 1"
                  x-on:click="rows[{{ $index }}].removed = true"
                  class="cursor-pointer rounded-sm p-1 text-muted-soft hover:text-ink focus-visible:ring-[3px] focus-visible:ring-focus-ring/45 focus-visible:outline-none"
                >
                  <span class="sr-only">{{ __('Remove this moment') }}</span>

                  <x-icon name="close" :size="14" :stroke-width="1.5" />
                </button>
              </span>
            </fieldset>
          </div>
        @endforeach

        <button
          type="button"
          x-cloak
          x-show="shown < {{ $blanks }}"
          x-on:click="shown++"
          class="flex cursor-pointer items-center gap-2 self-start rounded-md border border-dashed border-hairline-strong bg-transparent px-3 py-2 font-sans text-sm text-body hover:bg-hover hover:text-ink focus-visible:ring-[3px] focus-visible:ring-focus-ring/45 focus-visible:outline-none"
        >
          <x-icon name="plus" :size="14" :stroke-width="1.5" />

          {{ __('Add another moment') }}
        </button>
      </div>
    </fieldset>

    <div class="grid gap-3 min-[560px]:grid-cols-[minmax(0,160px)_minmax(0,1fr)]">
      <x-input
        type="time"
        :id="$id.'-time'"
        :name="$name.'[time]'"
        :label="__('Local time')"
        :value="$reminder['time']"
        :help="__('The hour you want to hear about it.')"
        x-model="time"
        :error="$messages?->get($reminder['errorKey'].'.time')"
      />

      <x-select
        :id="$id.'-timezone'"
        :name="$name.'[timezone]'"
        :label="__('Time zone')"
        :help="$reminder['zoneNote']"
        x-model="zone"
        :error="$messages?->get($reminder['errorKey'].'.timezone')"
      >
        @foreach ($reminder['zones'] as $zone)
          <option value="{{ $zone['timezone'] }}" @selected($reminder['timezone'] === $zone['timezone'])>{{ $zone['label'] }}</option>
        @endforeach
      </x-select>
    </div>

    <x-notice tone="info" icon="info">
      <span x-text="summary">{{ $reminder['summary'] }}</span>

      @if ($reminder['nextLine'] !== null)
        <span class="mt-1 block text-xs text-pretty text-muted">{{ $reminder['nextLine'] }}</span>
      @endif
    </x-notice>
  </div>
</div>
