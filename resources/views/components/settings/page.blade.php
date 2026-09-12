{{-- The shell every settings screen sits in: the way back, the list of settings, and the screen itself. --}}
{{--
  @var string $title
  @var string $current
  @var \App\Models\User $user
  @var string|null $backHref
  @var string|null $backLabel
  @var array<int, array{label: string, url?: string|null}>|null $crumbs
  @var bool $wide
--}}
@props([
  'title',
  'current',
  'user',
  'backHref' => null,
  'backLabel' => null,
  'crumbs' => null,
  'wide' => false,
])

<x-shell :title="$title" :wide="$wide" {{ $attributes }}>
  <x-slot:breadcrumb>
    @isset($crumbs)
      <x-breadcrumb :items="$crumbs" />
    @endisset

    @unless (isset($crumbs))
      <x-back-link :href="$backHref ?? route('settings.index')">{{ $backLabel ?? __('Back to settings') }}</x-back-link>
    @endunless
  </x-slot:breadcrumb>

  <x-slot:sidebar>
    <x-settings.sidebar :user="$user" :current="$current" />
  </x-slot:sidebar>

  @isset($actions)
    <x-slot:actions>{{ $actions }}</x-slot:actions>
  @endisset

  @isset($footer)
    <x-slot:footer>{{ $footer }}</x-slot:footer>
  @endisset

  {{ $slot }}
</x-shell>
