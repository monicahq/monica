{{--
  An inline link, underlined the way the rest of the running text is.

  It is near black rather than the blue a link is expected to be, because there is
  no accent colour left in this system. The underline is the whole of what says it
  is a link, which is why it is never removed and why it darkens on hover instead
  of the text changing colour.

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
    'decoration-hairline-strong transition-colors duration-(--duration-fast)',
    'hover:decoration-ink',
  ])
}}>{{ $slot }}</a>
