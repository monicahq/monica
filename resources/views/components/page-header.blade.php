{{-- The title of a screen and the line under it that says what the screen is for. --}}
{{--
  @var string|null $title
  @var string|null $description
--}}
@props([
  'title' => null,
  'description' => null,
])

<div {{ $attributes->class(['space-y-1.5']) }}>
  <h1 class="text-page-title text-balance text-ink">{{ $title ?? $slot }}</h1>

  @isset($description)
    <p class="text-sm text-body">{{ $description }}</p>
  @endisset
</div>
