{{-- An inline link, underlined the way the rest of the running text is. --}}
{{-- @var bool $turbo --}}
@props([
  'turbo' => true,
])

@php
  $drivenByTurbo = $turbo && ! $attributes->has('data-turbo') && $attributes->whereStartsWith('x-target')->isEmpty();
@endphp

<a @if ($drivenByTurbo) data-turbo="true" @endif {{
  $attributes->class([
    'inline font-medium text-link underline underline-offset-2',
    'decoration-hairline-strong transition-colors duration-(--duration-fast)',
    'hover:decoration-ink',
  ])
}}>{{ $slot }}</a>
