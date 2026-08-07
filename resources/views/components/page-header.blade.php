{{--
  The title of a screen and the line under it that says what the screen is for.
  Both can be given as an attribute or as a named slot.

  @var string|null $title
  @var string|null $description
--}}
@props([
  'title' => null,
  'description' => null,
])

<div {{ $attributes->class(['space-y-1.5']) }}>
  <h1 class="text-2xl font-semibold tracking-tight text-ink">{{ $title ?? $slot }}</h1>

  @isset($description)
    <p class="text-sm text-body">{{ $description }}</p>
  @endisset
</div>
