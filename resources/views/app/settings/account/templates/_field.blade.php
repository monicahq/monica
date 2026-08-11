{{--
  One field on the list, and underneath it whatever a repeater asks for on every
  repetition. A repeater cannot hold another, so the indent only ever goes one
  step in.

  The field open in the inspector carries a border and a tint as well as
  `aria-current`, since which one is being edited is the only thing telling
  somebody what the panel on the right is about.

  @var array $field
--}}
<a
  href="{{ $field['url'] }}"
  x-target="builder"
  @if ($field['isSelected']) aria-current="true" @endif
  @class([
    'grid w-full grid-cols-[minmax(90px,1fr)_minmax(0,auto)] items-center gap-2.5 rounded-md border px-2.5 py-2.5 text-left transition-colors duration-[110ms] hover:bg-hover',
    'border-hairline-strong bg-sunken' => $field['isSelected'],
    'border-transparent' => ! $field['isSelected'],
  ])
>
  <span class="grid min-w-0 gap-px">
    <span class="erica-row__title truncate">{{ $field['label'] }}</span>

    <span class="erica-caption truncate">{{ $field['help'] ?? '—' }}</span>
  </span>

  <span class="flex max-w-48 flex-wrap items-center justify-end gap-1.5">
    @if ($field['isSensitive'])
      <x-badge tone="warning">{{ __('Sensitive') }}</x-badge>
    @endif

    <x-badge tone="outline">{{ $field['typeLabel'] }}</x-badge>
  </span>
</a>

@if ($field['children'] !== [])
  <div class="ml-3.5 flex flex-col gap-0.5 border-l border-hairline pl-2.5">
    @foreach ($field['children'] as $child)
      @include('app.settings.account.templates._field', ['field' => $child])
    @endforeach
  </div>
@endif
