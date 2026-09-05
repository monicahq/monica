{{-- A titled panel. --}}
{{--
  @var string|null $title
  @var string|null $description
  @var string|null $additionalInfo
  @var string $padding
--}}
@props([
  'title' => null,
  'padding' => 'p-4 sm:p-4',
  'description' => null,
  'additionalInfo' => null,
])

<div class="space-y-2">
  @isset($title)
    <div class="flex items-center justify-between">
      <div class="flex items-center gap-2">
        <h2 class="text-lg font-semibold text-ink">{{ $title }}</h2>

        @isset($help)
          {{ $help }}
        @endisset
      </div>

      @isset($actions)
        <div>{{ $actions }}</div>
      @endisset
    </div>
  @endisset

  @isset($description)
    <div class="space-y-2 text-sm text-muted">
      {{ $description }}
    </div>
  @endisset

  @isset($additionalInfo)
    {{ $additionalInfo }}
  @endisset

  <div {{ $attributes->merge(['class' => 'rounded-lg border border-hairline bg-canvas shadow-panel ' . $padding]) }}>
    {{ $slot }}

    @isset($footer)
      <div {{ $footer->attributes->class(['rounded-b-lg px-4 py-3 text-center text-sm']) }}>
        {{ $footer }}
      </div>
    @endisset
  </div>
</div>
