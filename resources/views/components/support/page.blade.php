{{-- The shell every support screen sits in: the way back, the support column, and the screen itself. --}}
{{--
  @var string $title
  @var string $current
  @var int $openCount
  @var array<int, array{label: string, url?: string|null}> $crumbs
--}}
@props([
  'title',
  'current',
  'openCount' => 0,
  'crumbs' => [],
])

<x-shell :title="$title" {{ $attributes }}>
  <x-slot:breadcrumb>
    <x-breadcrumb :items="$crumbs" />
  </x-slot:breadcrumb>

  <x-slot:sidebar>
    <x-support.sidebar :current="$current" :open-count="$openCount" />
  </x-slot:sidebar>

  @isset($actions)
    <x-slot:actions>{{ $actions }}</x-slot:actions>
  @endisset

  @isset($footer)
    <x-slot:footer>{{ $footer }}</x-slot:footer>
  @endisset

  {{ $slot }}
</x-shell>
