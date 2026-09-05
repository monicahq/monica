{{-- An inline link, underlined the way the rest of the running text is. --}}
{{-- @var bool $turbo --}}
@props([
  'turbo' => false,
])

<a @if ($turbo) data-turbo="true" @endif {{
  $attributes->class([
    'inline font-medium text-link underline underline-offset-2',
    'decoration-hairline-strong transition-colors duration-(--duration-fast)',
    'hover:decoration-ink',
  ])
}}>{{ $slot }}</a>
