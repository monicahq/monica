{{-- The vault colours a possession tag can take, as a group of radio buttons drawn as swatches. --}}
{{--
  @var list<array{value: string, label: string, isDefault: bool}> $colours
  @var string $checked
  @var string $idPrefix
  @var string|null $model
--}}
<fieldset class="flex flex-col gap-1.5">
  <legend class="mb-1.5 text-label text-ink">{{ __('Colour') }}</legend>

  <div class="flex h-9 flex-wrap items-center gap-1.5">
    @foreach ($colours as $colour)
      <label for="{{ $idPrefix }}-{{ $loop->index }}" class="flex size-7 cursor-pointer items-center justify-center rounded-full has-checked:ring-2 has-checked:ring-ink">
        <input
          type="radio"
          id="{{ $idPrefix }}-{{ $loop->index }}"
          name="colour"
          value="{{ $colour['value'] }}"
          @checked($colour['value'] === $checked)
          @isset($model) x-model="{{ $model }}" @endisset
          style="background: {{ $colour['value'] }}"
          class="size-5 cursor-pointer appearance-none rounded-full focus-ring"
        />
        <span class="sr-only">{{ $colour['label'] }}</span>
      </label>
    @endforeach
  </div>
</fieldset>
