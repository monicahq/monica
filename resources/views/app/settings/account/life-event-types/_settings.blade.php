{{-- How a field of one kind is checked, shown while that kind is the one picked for the row. --}}
{{--
  @var string $reference
  @var string $kind
  @var list<array<string, mixed>> $settings
  @var array<string, mixed> $values
  @var bool $chosen
--}}
<fieldset
  data-kind="{{ $kind }}"
  x-cloak
  x-show="type === @js($kind)"
  :disabled="type !== @js($kind)"
  @disabled(! $chosen)
  class="m-0 flex min-w-0 flex-wrap items-end gap-2.5 border-0 p-0"
>
  <legend class="sr-only">{{ __('How this field is checked') }}</legend>

  @foreach ($settings as $setting)
    @php
      $settingId = 'field-'.$reference.'-'.$kind.'-'.$setting['key'];
      $value = $values[$setting['key']] ?? '';
    @endphp
    <div @class(['flex flex-col gap-1', match ($setting['width']) {
      'narrow' => 'w-24',
      'medium' => 'w-40',
      default => 'w-60 max-w-full',
    }])>
      <x-caption>{{ $setting['label'] }}</x-caption>

      @if ($setting['control'] === 'select')
        <x-select
          :id="$settingId"
          :name="'fields['.$reference.'][settings]['.$setting['key'].']'"
          :aria-label="$setting['label']"
          size="small"
        >
          @foreach ($setting['options'] as $option)
            <option value="{{ $option['value'] }}" @selected($value === $option['value'])>{{ $option['label'] }}</option>
          @endforeach
        </x-select>
      @else
        <x-input
          :id="$settingId"
          :name="'fields['.$reference.'][settings]['.$setting['key'].']'"
          :type="$setting['control'] === 'number' ? 'number' : 'text'"
          :value="$value"
          :aria-label="$setting['label']"
          :placeholder="$setting['placeholder']"
          size="small"
          maxlength="255"
        />
      @endif
    </div>
  @endforeach
</fieldset>
