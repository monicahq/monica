{{-- One record a search found: its name, where it lives, and what the words were found in. --}}
{{-- @var array{name: string, markedName: \Illuminate\Support\HtmlString, url: ?string, avatarUrl: ?string, isLifeEvent: bool, vault: ?string, isArchived: bool, date: ?string, matchedLabel: ?string, matchedExcerpt: ?\Illuminate\Support\HtmlString} $row --}}
@php
  $tag = $row['url'] !== null ? 'a' : 'div';
@endphp

<{{ $tag }}
  @if ($row['url'] !== null) href="{{ $row['url'] }}" @endif
  @class([
    'flex items-start gap-3.5 border-t border-hairline px-4 py-3 first:border-t-0 text-inherit no-underline [&_mark]:rounded-xs [&_mark]:bg-warning-tint [&_mark]:text-ink',
    'focus-ring-inset hover:bg-hover' => $row['url'] !== null,
  ])
>
  <span class="mt-px flex shrink-0">
    @if ($row['isLifeEvent'])
      <span class="flex size-8 items-center justify-center rounded-md border border-hairline bg-card text-body" aria-hidden="true">
        <x-icon name="calendar" :size="16" />
      </span>
    @else
      <x-contact-avatar :name="$row['name']" :url="$row['avatarUrl']" :size="32" />
    @endif
  </span>

  <span class="flex min-w-0 flex-1 flex-col gap-1">
    <span class="flex flex-wrap items-baseline gap-x-2.5 gap-y-1">
      <span class="font-semibold text-ink">{{ $row['markedName'] }}</span>

      @if ($row['date'] !== null)
        <span class="text-sm text-muted">{{ $row['date'] }}</span>
      @endif

      @if ($row['vault'] !== null)
        <x-badge>{{ $row['vault'] }}</x-badge>
      @endif

      @if ($row['isArchived'])
        <x-badge>{{ __('Archived') }}</x-badge>
      @endif
    </span>

    @if ($row['matchedExcerpt'] !== null)
      <span class="flex items-baseline gap-2 text-sm text-body">
        <x-caption tone="muted" class="min-w-19 shrink-0 whitespace-nowrap">{{ $row['matchedLabel'] }}</x-caption>
        <span class="min-w-0 text-pretty">{{ $row['matchedExcerpt'] }}</span>
      </span>
    @elseif ($row['matchedLabel'] !== null)
      <span class="text-sm text-muted">{{ $row['matchedLabel'] }}</span>
    @endif
  </span>
</{{ $tag }}>
