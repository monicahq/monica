{{-- The categories the vocabulary is filed under, and how many natures each one holds. --}}
{{--
  @var array<int, array> $rail
  @var bool $showsEveryType
  @var int $totalCount
--}}
<nav aria-label="{{ __('Categories') }}" class="flex flex-col gap-2.5">
  <x-caption>{{ __('Categories') }}</x-caption>

  <x-panel :flush="true">
    <a
      href="{{ route('settings.lifeEventTypes.index') }}"
      x-target.push="life-event-types"
      @if ($showsEveryType) aria-current="page" @endif
      @class([
        'flex items-center gap-2.5 border-b border-hairline px-3 py-2.5 text-sm font-semibold text-ink transition-colors hover:bg-hover focus-ring-inset',
        'bg-hover' => $showsEveryType,
      ])
    >
      <x-icon name="layers" :size="16" :stroke-width="1.5" class="shrink-0 text-muted" />

      <span class="flex-1">{{ __('Every type') }}</span>

      <span class="shrink-0 text-xs font-normal text-muted">{{ $totalCount }}</span>
    </a>

    @foreach ($rail as $category)
      <a
        href="{{ $category['url'] }}"
        x-target.push="life-event-types"
        @if ($category['isSelected']) aria-current="page" @endif
        @class([
          'flex items-center gap-2 px-3 py-2.5 text-sm transition-colors hover:bg-hover focus-ring-inset',
          'border-b border-hairline' => ! $loop->last,
          'bg-hover font-semibold text-ink' => $category['isSelected'],
          'font-medium text-ink' => ! $category['isSelected'] && $category['isActive'],
          'font-medium text-muted' => ! $category['isSelected'] && ! $category['isActive'],
        ])
      >
        <span class="min-w-0 flex-1 truncate">{{ $category['name'] }}</span>

        @unless ($category['isActive'])
          <x-badge tone="warning" class="shrink-0">{{ __('Off') }}</x-badge>
        @endunless

        <span class="shrink-0 text-xs font-normal text-muted">{{ $category['count'] }}</span>
      </a>
    @endforeach
  </x-panel>

  <p class="px-0.5 text-xs text-pretty text-muted">{{ __('A category only organises the list, the pickers and the journal filters. It never changes what a type does.') }}</p>
</nav>
