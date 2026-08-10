{{--
  One field on the list, and underneath it whatever a repeater asks for on every
  repetition. A repeater cannot hold another, so the indent only ever goes one
  step in.

  @var array $field
--}}
<a
  href="{{ $field['url'] }}"
  x-target="builder"
  @class([
    'grid w-full grid-cols-[minmax(90px,1fr)_minmax(0,auto)] items-center gap-2.5 rounded-md border px-2.5 py-2.5 text-left transition-colors duration-150 hover:bg-hover',
    'border-hairline-strong bg-sunken' => $field['isSelected'],
    'border-transparent' => ! $field['isSelected'],
  ])
>
  <span class="grid min-w-0 gap-px">
    <span class="truncate text-sm font-semibold text-ink">{{ $field['label'] }}</span>

    <span class="truncate text-xs leading-snug text-muted">{{ $field['help'] ?? '—' }}</span>
  </span>

  <span class="flex max-w-48 flex-wrap items-center justify-end gap-1.5">
    @if ($field['isSensitive'])
      <span class="rounded-full border border-hairline bg-sunken px-2.5 py-0.5 text-xs text-warning">{{ __('Sensitive') }}</span>
    @endif

    <span class="rounded-full border border-hairline bg-sunken px-2.5 py-0.5 text-xs whitespace-nowrap text-body">{{ $field['typeLabel'] }}</span>
  </span>
</a>

@if ($field['children'] !== [])
  <div class="ml-3.5 space-y-0.5 border-l border-hairline pl-2.5">
    @foreach ($field['children'] as $child)
      @include('app.settings.account.templates._field', ['field' => $child])
    @endforeach
  </div>
@endif
