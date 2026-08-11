{{--
  An inline link, underlined the way the rest of the running text is.

  It is terracotta rather than the blue a link is expected to be, because that is
  the colour the whole product acts in and a link is an action. The underline is
  what says it is a link, which is why it is never removed.

  Turbo is off by default and a link opts in with `turbo`, which is worth doing when it
  points at another screen built on the same layout: only the body then changes hands.

  @var bool $turbo
--}}
@props([
  'turbo' => false,
])

<a @if ($turbo) data-turbo="true" @endif {{
  $attributes->class([
    'inline font-medium text-link underline underline-offset-2',
    'decoration-link/35 transition-colors duration-150',
    'hover:text-link-hover hover:decoration-link-hover/60',
  ])
}}>{{ $slot }}</a>
