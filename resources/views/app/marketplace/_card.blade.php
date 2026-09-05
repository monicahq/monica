{{-- One listing in the catalogue. --}}
{{-- @var array<string, mixed> $listing --}}
<article class="flex flex-col gap-2.25 rounded-lg border border-hairline bg-canvas p-4 ">
  <div class="flex min-w-0 items-center gap-2">
    <span class="flex size-6.5 shrink-0 items-center justify-center rounded-sm bg-card text-muted">
      <x-icon :name="$listing['typeIcon']" :size="16" :stroke-width="1.5" />
    </span>

    <span class="text-xs tracking-caption uppercase text-muted">{{ $listing['typeLabel'] }}</span>

    <span class="truncate text-xs text-muted">· {{ $listing['categoryName'] }}</span>

    @if ($listing['hasUpdate'])
      <span class="ml-auto flex shrink-0 items-center gap-1 rounded-sm bg-marigold-tint px-1.75 py-0.5 text-xs text-marigold-deep">
        <x-icon name="arrow-right" :size="13" :stroke-width="1.8" />

        {{ __('Update available') }}
      </span>
    @elseif ($listing['isInstalled'])
      <span class="ml-auto flex shrink-0 items-center gap-1 rounded-sm bg-forest-tint px-1.75 py-0.5 text-xs text-forest-deep">
        <x-icon name="check" :size="13" :stroke-width="1.8" />

        {{ __('Installed') }}
      </span>
    @endif
  </div>

  <h3 class="text-card-title text-ink">{{ $listing['name'] }}</h3>

  <p class="text-sm text-pretty text-body">{{ $listing['summary'] }}</p>

  @if ($listing['facts'] !== '')
    <p class="mt-auto border-t border-hairline pt-2.5 text-xs text-ink-soft">{{ $listing['facts'] }}</p>
  @endif

  <p class="flex items-center gap-1.5 text-xs text-muted">
    <span class="truncate">{{ $listing['authorLabel'] }}</span>

    <span aria-hidden="true">·</span>

    <span>{{ $listing['language'] }}</span>

    <span class="ml-auto shrink-0">{{ $listing['installLabel'] }}</span>
  </p>
</article>
