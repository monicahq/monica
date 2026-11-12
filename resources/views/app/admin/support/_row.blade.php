{{-- One request in the administrators' list: what it is about, who asked, and the last word written. --}}
{{-- @var array{uuid: string, url: string, current: bool, subject: string, name: string, categoryLabel: string, age: string, lastLine: string} $row --}}
<a
  href="{{ $row['url'] }}"
  data-turbo="true"
  @if ($row['current']) aria-current="true" @endif
  @class([
    'flex gap-3 border-b border-hairline-soft px-4 py-3.5 no-underline last:border-b-0 hover:bg-hover focus-ring-inset',
    'bg-hover shadow-[inset_2px_0_0_var(--color-ink)]' => $row['current'],
  ])
>
  <x-avatar-initials :name="$row['name']" :size="28" />

  <span class="flex min-w-0 flex-1 flex-col gap-0.75">
    <span class="flex items-baseline gap-2">
      <span class="min-w-0 flex-1 truncate text-sm font-semibold text-ink">{{ $row['subject'] }}</span>
      <span @class(['shrink-0 text-xs', 'text-body' => $row['current'], 'text-muted' => ! $row['current']])>{{ $row['age'] }}</span>
    </span>
    <span class="truncate text-xs text-body">{{ $row['name'] }} · {{ $row['categoryLabel'] }}</span>
    <span @class(['truncate text-xs', 'text-body' => $row['current'], 'text-muted' => ! $row['current']])>{{ $row['lastLine'] }}</span>
  </span>
</a>
