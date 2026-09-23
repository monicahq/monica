{{-- The places in the top bar that take you out of the vault: docs, marketplace, settings. --}}
{{-- @var bool $settings --}}
@props([
  'settings' => true,
])

<div class="flex items-center gap-1">
  @foreach (array_filter([
    ['label' => __('Docs'), 'icon' => 'journal', 'href' => '#'],
    ['label' => __('Marketplace'), 'icon' => 'gift', 'href' => route('marketplace.index')],
    $settings
      ? ['label' => __('Account settings'), 'icon' => 'settings', 'href' => route('settings.index')]
      : null,
    auth()->user()?->isAdministrator()
      ? ['label' => __('Admin space'), 'icon' => 'lock', 'href' => route('admin.overview.index')]
      : null,
  ]) as $link)
    <a
      href="{{ $link['href'] }}"
      data-turbo="true"
      aria-label="{{ $link['label'] }}"
      class="flex h-9 items-center gap-1.75 rounded-md px-2.75 text-sm text-body transition-colors duration-(--duration-fast) ease-out hover:bg-hover hover:text-ink focus-ring"
    >
      <x-icon :name="$link['icon']" :size="18" :stroke-width="1.5" />

      <span class="hidden topbar-labels:inline">{{ $link['label'] }}</span>
    </a>
  @endforeach

  <x-notification-bar />

  <span aria-hidden="true" class="ml-1.5 h-5.5 w-px bg-hairline-strong"></span>
</div>
