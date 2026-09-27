{{-- The reminder a date field may carry, asked for under the date itself. --}}
{{--
  @var \App\Models\Field $field
  @var \App\ViewModels\Vaults\ContactReminderBlock $reminder  the panel for this date
  @var string $name  the prefix the inputs are named by, which says which date they belong to
  @var string $id
  @var string $dateId  the date input the next reminder is worked out from
  @var string|null $yearId  the box saying the year is unknown, when the date has one
  @var \Illuminate\Support\ViewErrorBag|null $messages
  @var \Illuminate\View\ComponentSlot|null $beside
--}}
@props([
  'field',
  'reminder',
  'name',
  'id',
  'dateId',
  'yearId' => null,
  'messages' => null,
])

@php
  $panelId = $id.'-panel';
  $nextId = $id.'-next';
@endphp

<div
  class="flex flex-col gap-3"
  x-data="fieldReminder(@js([...$reminder->live(), 'dateId' => $dateId, 'yearId' => $yearId]))"
>
  <div class="flex flex-wrap gap-x-6 gap-y-2">
    {{ $beside ?? '' }}

    <x-checkbox
      :id="$id.'-on'"
      :name="$name.'[on]'"
      :checked="$reminder->isOn()"
      x-model="on"
      ::aria-expanded="on"
      :aria-controls="$panelId"
    >{{ __('Remind me about this date') }}</x-checkbox>
  </div>

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
    class="flex flex-col gap-4 rounded-lg border border-hairline bg-card p-4"
  >
    <div class="flex flex-col gap-2">
      <div class="grid grid-cols-[repeat(auto-fit,minmax(150px,1fr))] gap-3">
        <x-select
          :id="$id.'-repeat'"
          :name="$name.'[repeat]'"
          :label="__('Repeats')"
          x-model="repeat"
          :error="$messages?->get($reminder->errorKey().'.cadence') ?: $messages?->get($reminder->errorKey().'.interval')"
        >
          @foreach ($reminder->repeats() as $value => $label)
            <option value="{{ $value }}" @selected($reminder->repeat() === $value)>{{ $label }}</option>
          @endforeach
        </x-select>

        <x-input
          type="time"
          :id="$id.'-time'"
          :name="$name.'[time]'"
          :label="__('At')"
          :value="$reminder->time()"
          x-model="time"
          :error="$messages?->get($reminder->errorKey().'.time')"
        />

        <x-select
          :id="$id.'-timezone'"
          :name="$name.'[timezone]'"
          :label="__('Time zone')"
          :error="$messages?->get($reminder->errorKey().'.timezone')"
        >
          @foreach ($reminder->zones() as $zone)
            <option value="{{ $zone['timezone'] }}" @selected($reminder->timezone() === $zone['timezone'])>{{ $zone['label'] }}</option>
          @endforeach
        </x-select>
      </div>

      <p class="text-xs text-pretty text-muted">{{ $reminder->zoneNote() }}</p>
    </div>

    <div class="flex flex-col gap-1.5 border-t border-hairline pt-4">
      <x-input
        :id="$id.'-name'"
        :name="$name.'[name]'"
        :label="__('It will say')"
        :optional="true"
        :value="$reminder->name()"
        :placeholder="$reminder->placeholder()"
        :error="$messages?->get($reminder->errorKey().'.name')"
      />

      <p id="{{ $nextId }}" class="text-sm text-body" aria-live="polite" x-html="nextLine">{!! $reminder->nextLine() !!}</p>
    </div>
  </div>
</div>
