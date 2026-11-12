{{-- The sections of a possession's page, the current one marked, those not built yet unavailable. --}}
{{--
  @var \App\ViewModels\Vaults\PossessionViewModel $page
  @var string $current
--}}
<nav aria-label="{{ __('Sections of this possession') }}" class="flex flex-col gap-px">
  @foreach ($page->sections($current) as $section)
    @if ($section['url'] !== null)
      <a
        href="{{ $section['url'] }}"
        @if ($section['isCurrent']) aria-current="page" @endif
        @class([
          'focus-ring flex min-h-8 items-center gap-2.5 rounded-md px-2.5 py-1.5 text-nav',
          'bg-card font-medium text-ink shadow-nav-active' => $section['isCurrent'],
          'text-body transition-colors hover:bg-hover hover:text-ink' => ! $section['isCurrent'],
        ])
      >
        <span class="flex {{ $section['colour'] }}" aria-hidden="true"><x-icon :name="$section['icon']" :size="15" :stroke-width="1.5" /></span>
        <span class="min-w-0 flex-1">{{ $section['label'] }}</span>
        @if ($section['count'] !== null && $section['count'] > 0)
          <span class="text-xs text-muted tabular-nums">{{ $section['count'] }}</span>
        @endif
      </a>
    @else
      <span
        role="link"
        aria-disabled="true"
        aria-describedby="possession-sections-later"
        class="flex min-h-8 cursor-default items-center gap-2.5 rounded-md px-2.5 py-1.5 text-nav text-muted"
      >
        <span class="flex {{ $section['colour'] }}" aria-hidden="true"><x-icon :name="$section['icon']" :size="15" :stroke-width="1.5" /></span>
        <span class="min-w-0 flex-1">{{ $section['label'] }}</span>
      </span>
    @endif
  @endforeach

  <p id="possession-sections-later" class="sr-only">{{ __('Comes in a later version.') }}</p>
</nav>
