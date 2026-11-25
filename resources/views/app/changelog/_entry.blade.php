{{-- One feature or improvement of What’s new, in full. --}}
{{--
  @var array<string, mixed> $entry
  @var bool $first
  @var bool $feedback
--}}
<article aria-labelledby="changelog-entry-{{ $entry['id'] }}" @class(['flex max-w-160 flex-col gap-2.5', 'border-t border-hairline pt-8' => ! $first])>
  <div class="flex flex-wrap items-center gap-2.5">
    <x-badge :tone="$entry['tone']">{{ $entry['typeLabel'] }}</x-badge>

    @if ($entry['area'] !== null)
      <span class="text-sm text-muted">{{ $entry['area'] }}</span>
    @endif

    @if ($entry['isNew'])
      <x-badge tone="accent">{{ __('New') }}</x-badge>
    @endif
  </div>

  <h3 id="changelog-entry-{{ $entry['id'] }}" class="text-lg font-semibold tracking-tight text-ink">{{ $entry['title'] }}</h3>

  <div class="text-base text-body text-pretty [&_a]:underline [&_p+p]:mt-2">{!! $entry['bodyHtml'] !!}</div>

  @if ($entry['screenshots'] !== [])
    <x-screenshot-gallery :screenshots="$entry['screenshots']" :id="$entry['id']" :title="$entry['title']" />
  @endif

  @if ($feedback)
    @include('app.changelog._feedback', ['entry' => $entry])
  @endif
</article>
