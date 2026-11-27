{{-- A field picking a country: the favourites first, then every country, filtered as you type. --}}
{{--
  @var string $id
  @var string $name
  @var string $label
  @var array<int, array{value: string, label: string, submits?: string, typed?: string}> $extras
  @var array<int, array{value: string, label: string}> $favourites
  @var string $value
  @var string|null $typedName
  @var string $valueOfATypedName
  @var string|null $placeholder
  @var string|null $hint
  @var string|array<int, string>|null $error
  @var bool $autofocus
--}}
@props([
  'id',
  'name',
  'label',
  'extras' => [],
  'favourites' => [],
  'value' => '',
  'typedName' => null,
  'valueOfATypedName' => '',
  'placeholder' => null,
  'hint' => null,
  'error' => null,
  'autofocus' => false,
])

@php
  $countries = collect(\App\Helpers\CountryCodes::all(app()->getLocale()))
      ->map(fn (string $country, string $code): array => ['value' => $code, 'label' => $country])
      ->values()
      ->all();
  $errorId = $error ? $id.'-error' : null;
  $describedBy = collect([$hint !== null ? $id.'-hint' : null, $errorId])->filter()->implode(' ');
  $labels = [
      'favourites' => __('Favourites'),
      'all' => __('All countries'),
      'noMatch' => __('No country matches'),
      'count' => __('Number of matching countries: :number'),
      'add' => __('Add “:name”'),
  ];
@endphp

<div
  x-data="countryPicker({
    id: @js($id),
    extras: @js($extras),
    favourites: @js($favourites),
    countries: @js($countries),
    labels: @js($labels),
    value: @js((string) $value),
    acceptsATypedName: @js($typedName !== null),
    valueOfATypedName: @js($valueOfATypedName),
  })"
  {{ $attributes->class(['flex flex-col gap-1.5']) }}
>
  <x-label :for="$id" :value="$label" id="{{ $id }}-label" />

  <template x-if="true">
    <input type="hidden" name="{{ $name }}" :value="submittedValue" />
  </template>

  @if ($typedName !== null)
    <template x-if="true">
      <input type="hidden" name="{{ $typedName }}" :value="submittedName" />
    </template>
  @endif

  <noscript>
    <select id="{{ $id }}-fallback" name="{{ $name }}" aria-labelledby="{{ $id }}-label" @if ($errorId) aria-invalid="true" @endif @if ($describedBy !== '') aria-describedby="{{ $describedBy }}" @endif class="h-9 w-full rounded-md border border-hairline-strong bg-input px-3 text-ink">
      @if ($extras === [])
        <option value="">{{ $placeholder ?? '' }}</option>
      @endif

      @foreach ($extras as $extra)
        <option value="{{ $extra['submits'] ?? $extra['value'] }}" @selected($extra['value'] === (string) $value)>{{ $extra['label'] }}</option>
      @endforeach

      @if ($favourites !== [])
        <optgroup label="{{ $labels['favourites'] }}">
          @foreach ($favourites as $favourite)
            <option value="{{ $favourite['value'] }}" @selected($favourite['value'] === (string) $value)>{{ $favourite['label'] }}</option>
          @endforeach
        </optgroup>
      @endif

      <optgroup label="{{ $labels['all'] }}">
        @foreach ($countries as $country)
          <option value="{{ $country['value'] }}" @selected($country['value'] === (string) $value)>{{ $country['label'] }}</option>
        @endforeach
      </optgroup>
    </select>

    @if ($typedName !== null)
      <input type="text" name="{{ $typedName }}" value="{{ collect($extras)->firstWhere('value', (string) $value)['typed'] ?? '' }}" maxlength="255" aria-label="{{ __('Or type a name for a place not in the list') }}" placeholder="{{ __('Or type a name for a place not in the list') }}" class="mt-2 h-9 w-full rounded-md border border-hairline-strong bg-input px-3 text-ink" />
    @endif
  </noscript>

  <div x-cloak>
    <x-searchable-list :id="$id" :label-id="$id.'-label'" :search-label="__('Search the countries')" :invalid="$errorId !== null" :described-by="$describedBy">
      <x-slot:trigger :x-autofocus="$autofocus" class="focus-ring-field relative flex h-9 w-full cursor-pointer items-center gap-2.5 rounded-md border border-hairline-strong bg-input pr-8.5 pl-3 text-left text-[16px] text-ink transition duration-(--duration-fast) ease-out hover:border-muted-soft data-invalid:border-error data-invalid:ring-[3px] data-invalid:ring-error/20 sm:text-base">
        <span class="truncate" :class="chosenLabel === '' && 'text-muted'" x-text="chosenLabel === '' ? @js($placeholder ?? '') : chosenLabel"></span>

        <x-icon
          name="chevron-down"
          :size="16"
          :stroke-width="1.5"
          class="pointer-events-none absolute top-1/2 right-3 -translate-y-1/2 text-muted"
        />
      </x-slot:trigger>

      <x-slot:option>
        <span class="flex w-3.5 items-center justify-center" aria-hidden="true">
          <span x-show="value === option.value && group.key !== 'typed'"><x-icon name="check" :size="13" :stroke-width="1.9" /></span>
        </span>
        <span class="min-w-0 flex-1 truncate" x-text="option.label"></span>
      </x-slot:option>

      <x-slot:footer>
        <p x-show="groups.every((group) => group.key === 'extras')" class="px-3 pb-3 text-sm text-muted" aria-hidden="true">{{ $labels['noMatch'] }}</p>
      </x-slot:footer>
    </x-searchable-list>

    <p class="sr-only" aria-live="polite" x-text="countLine"></p>
  </div>

  @if ($hint !== null)
    <p id="{{ $id }}-hint" class="text-xs text-pretty text-muted">{{ $hint }}</p>
  @endif

  <x-error :id="$errorId" :messages="$error" />
</div>
