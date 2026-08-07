{{--
  An inline link, underlined the way the rest of the running text is.

  Turbo is off by default and a link opts in with `turbo`, which is worth doing when it
  points at another screen built on the same layout: only the body then changes hands.

  @var bool $turbo
--}}
@props([
  'turbo' => false,
])

<a @if ($turbo) data-turbo="true" @endif {{
  $attributes->class([
    'inline underline underline-offset-2',
    'decoration-hairline-strong',
    'transition-colors duration-150',
    'hover:text-brand hover:decoration-brand',
  ])
}}>{{ $slot }}</a>
