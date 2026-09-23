{{-- What one endpoint takes, one row per parameter. --}}
{{-- @var \App\ViewModels\Docs\ApiDocsSection $section --}}
@props(['section'])

@if ($section->hasParams())
  <h2 class="mt-9 mb-1 text-xl text-ink">{{ $section->paramsTitle }}</h2>
  <p class="mb-1.5 text-sm text-muted">Sent as JSON. Anything not listed here is ignored.</p>

  <div class="flex flex-col">
    @foreach ($section->params as $param)
      <div class="flex flex-col gap-1.5 border-t border-hairline-soft py-4">
        <div class="flex flex-wrap items-baseline gap-2.5">
          <span class="font-mono text-[14px] font-semibold text-ink">{{ $param['name'] }}</span>
          <span class="font-mono text-xs text-muted">{{ $param['type'] }}</span>
          <span class="font-mono text-xs {{ $param['required'] ? 'text-error' : 'text-muted-soft' }}">
            {{ $param['required'] ? 'required' : 'optional' }}
          </span>
          @if ($param['default'])
            <span class="font-mono text-xs text-muted-soft">defaults to {{ $param['default'] }}</span>
          @endif
        </div>

        <p class="text-[14px] leading-relaxed text-pretty text-body">{{ $param['description'] }}</p>
      </div>
    @endforeach
  </div>
@endif
