{{-- The reminder a date field may carry, asked for under the date itself. --}}
{{--
  @var \App\Models\Field $field
  @var \App\ViewModels\Vaults\ContactReminderBlock $reminder  the panel for this date
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
  $panelId = $id.'-panel';

  $control = 'rounded-sm border border-hairline-strong bg-input px-2.5 py-1.5 font-sans text-sm text-ink '
    .'transition-[border-color] duration-(--duration-fast) ease-out '
    .'focus:border-focus focus:ring-[3px] focus:ring-focus-ring/20 focus:outline-none';
@endphp

<div class="flex flex-col gap-2.5" x-data="fieldReminder(@js($reminder->live()))">
  <x-option-row
    :id="$id.'-on'"
    :name="$name.'[on]'"
    value="1"
    :checked="$reminder->isOn()"
    :hint="$reminder->hint()"
    x-model="on"
    ::aria-expanded="on"
    :aria-controls="$panelId"
  >{{ __('Add a reminder') }}</x-option-row>

  @if ($reminder->quietBecause() !== null)
    <x-notice tone="warning">{{ $reminder->quietBecause() }}</x-notice>
  @endif

  @if ($reminder->isOn())
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
      :value="$reminder->name()"
      :placeholder="$reminder->placeholder()"
      :help="__('Left empty, it reads :wording.', ['wording' => $reminder->placeholder()])"
      :error="$messages?->get($reminder->errorKey().'.name')"
    />

    <fieldset class="flex flex-col gap-2">
      <legend class="text-label text-ink">{{ __('How often') }}</legend>

      <div class="grid gap-2 min-[560px]:grid-cols-2">
        <x-option-row
          :id="$id.'-once'"
          :name="$name.'[recurs]'"
          value="0"
          type="radio"
          :checked="! $reminder->recurs()"
          :hint="__('A single reminder, then it is done.')"
          x-on:change="recurs = false"
        >{{ __('Once') }}</x-option-row>

        <x-option-row
          :id="$id.'-recurs'"
          :name="$name.'[recurs]'"
          value="1"
          type="radio"
          :checked="$reminder->recurs()"
          :hint="__('Follows the date, year after year.')"
          x-on:change="recurs = true"
        >{{ __('Every time it comes round') }}</x-option-row>
      </div>

      <div
        @class([
          'flex flex-wrap items-end gap-2.5 pt-0.5',
          'transition-opacity duration-(--duration-fast)',
          'opacity-55' => ! $reminder->recurs(),
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
          :disabled="! $reminder->recurs()"
          :error="$messages?->get($reminder->errorKey().'.cadence')"
        >
          @foreach ($reminder->cadences() as $value => $label)
            <option value="{{ $value }}" @selected($reminder->cadence() === $value)>{{ $label }}</option>
          @endforeach
        </x-select>

        <x-input
          type="number"
          :id="$id.'-interval'"
          :name="$name.'[interval]'"
          :label="__('Every so many')"
          :value="$reminder->interval()"
          min="1"
          :max="$reminder->maximumInterval()"
          class="w-24"
          x-model="interval"
          x-bind:disabled="! recurs"
          :disabled="! $reminder->recurs()"
          :error="$messages?->get($reminder->errorKey().'.interval')"
        />
      </div>
    </fieldset>

    <fieldset class="flex flex-col gap-2">
      <legend class="text-label text-ink">{{ __('When to be told') }}</legend>

      <p class="text-xs text-pretty text-muted">{{ __('One or several. A reminder before, on the day, or a follow up after.') }}</p>

      <div class="flex flex-col gap-2 pt-0.5">
        @foreach ($reminder->rows() as $index => $row)
          <div x-cloak x-show="isDrawn({{ $index }}) && ! rows[{{ $index }}].removed">
            <fieldset x-bind:disabled="! counts({{ $index }})" class="flex flex-wrap items-center gap-2 rounded-md border border-hairline bg-canvas px-2.5 py-2">
              <legend class="sr-only">{{ __('A moment to be told about :label', ['label' => $field->label]) }}</legend>

              <select
                name="{{ $name }}[moments][{{ $index }}][direction]"
                aria-label="{{ __('When') }}"
                x-model="rows[{{ $index }}].direction"
                class="{{ $control }}"
              >
                @foreach ($reminder->directions() as $value => $label)
                  <option value="{{ $value }}" @selected($row['direction'] === $value)>{{ $label }}</option>
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
                  min="{{ $reminder->minimumAmount() }}"
                  :max="maximumFor({{ $index }})"
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
                  @foreach ($reminder->units() as $value => $label)
                    <option value="{{ $value }}" @selected($row['unit'] === $value)>{{ $label }}</option>
                  @endforeach
                </select>
              </span>

              <span class="ml-auto flex items-center gap-2.5">
                <span class="font-mono text-xs text-muted" x-text="phrase(rows[{{ $index }}])">{{ $reminder->moments()[$index]['phrase'] ?? '' }}</span>

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
          x-show="shown < {{ $reminder->blanks() }}"
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
        :value="$reminder->time()"
        :help="__('The hour you want to hear about it.')"
        x-model="time"
        :error="$messages?->get($reminder->errorKey().'.time')"
      />

      <x-select
        :id="$id.'-timezone'"
        :name="$name.'[timezone]'"
        :label="__('Time zone')"
        :help="$reminder->zoneNote()"
        x-model="zone"
        :error="$messages?->get($reminder->errorKey().'.timezone')"
      >
        @foreach ($reminder->zones() as $zone)
          <option value="{{ $zone['timezone'] }}" @selected($reminder->timezone() === $zone['timezone'])>{{ $zone['label'] }}</option>
        @endforeach
      </x-select>
    </div>

    <x-notice tone="info" icon="info">
      <span x-text="summary">{{ $reminder->summary() }}</span>

      @if ($reminder->nextLine() !== null)
        <span class="mt-1 block text-xs text-pretty text-muted">{{ $reminder->nextLine() }}</span>
      @endif
    </x-notice>
  </div>
</div>
