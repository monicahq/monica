{{-- What one endpoint takes, one row per parameter. --}}
{{-- @var \App\ViewModels\Docs\ApiDocsSection $section --}}
@props(['section'])

@php
  $typeTints = [
    'string' => 190,
    'integer' => 255,
    'number' => 230,
    'boolean' => 295,
    'array' => 20,
    'date' => 150,
    'enum' => 330,
  ];
@endphp

@if ($section->hasParams())
  <h2 class="mt-9 mb-1 text-xl text-ink">{{ $section->paramsTitle }}</h2>
  <p class="mb-4 text-sm text-muted">Sent as JSON. Anything not listed here is ignored.</p>

  <div class="overflow-hidden rounded-lg border border-hairline shadow-panel">
    @foreach ($section->params as $param)
      @php
        $hue = $typeTints[$param['type']] ?? null;
      @endphp

      <div class="flex flex-col gap-2 border-t border-hairline px-5 py-4 first:border-t-0">
        <div class="flex flex-wrap items-center gap-2.5">
          <span class="font-mono text-base font-bold text-ink">{{ $param['name'] }}</span>

          <span
            @if ($hue !== null) style="--tint: {{ $hue }}" @endif
            @class([
              'rounded-sm border px-2 py-px font-mono text-xs',
              'border-[oklch(0.89_0.06_var(--tint))] bg-[oklch(0.96_0.03_var(--tint))] text-[oklch(0.42_0.14_var(--tint))]' => $hue !== null,
              'border-hairline bg-card text-body' => $hue === null,
            ])
          >{{ $param['type'] }}</span>

          @if ($param['required'])
            <span class="inline-flex items-center gap-1.5 rounded-full border border-error-edge bg-error-tint px-2 py-px text-xs font-medium text-error">
              <span class="size-1.5 rounded-full bg-error"></span>
              Required
            </span>
          @else
            <span class="rounded-full border border-hairline px-2 py-px text-xs text-muted">Optional</span>
          @endif

          @if ($param['default'])
            <span class="rounded-sm border border-hairline bg-card px-2 py-px font-mono text-xs text-body">default: {{ $param['default'] }}</span>
          @endif
        </div>

        <p class="text-base leading-relaxed text-pretty text-body">{{ $param['description'] }}</p>
      </div>
    @endforeach
  </div>
@endif
