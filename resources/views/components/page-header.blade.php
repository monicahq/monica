{{-- The title of a screen, the line under it that says what the screen is for, and what you may do there. --}}
{{--
  @var string|null $title
  @var string|null $description
  @var \Illuminate\View\ComponentSlot|null $actions
--}}
@props([
  'title' => null,
  'description' => null,
])

@php
  $hasActions = isset($actions) && $actions->isNotEmpty();

  $shell = $hasActions
      ? 'flex flex-wrap items-start justify-between gap-x-6 gap-y-4'
      : 'flex flex-col gap-2';
@endphp

<header {{ $attributes->class([$shell]) }}>
  @if ($hasActions)
    <div class="flex min-w-0 flex-col gap-2">
  @endif

  <h1 class="text-page-title text-balance text-ink">{{ $title ?? $slot }}</h1>

  @isset($description)
    <p class="max-w-[56ch] text-base text-pretty text-body">{{ $description }}</p>
  @endisset

  @if ($hasActions)
    </div>

    <div class="flex flex-wrap items-center gap-2.5 max-sm:w-full">{{ $actions }}</div>
  @endif
</header>
