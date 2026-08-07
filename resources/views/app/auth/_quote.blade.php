{{--
  The panel on the side of the guest screens, carrying one line from The Office.

  @var array{text: string, author: string, source: string} $quote
--}}
<aside class="quote-panel hidden items-center justify-center border-l border-hairline p-15 lg:flex">
  <div class="w-full max-w-lg">
    <figure class="quote-card space-y-5 rounded-2xl border border-hairline bg-canvas px-6 pt-6 pb-6">
      <blockquote class="text-xl leading-snug tracking-tight text-pretty text-ink">
        &ldquo;{{ $quote['text'] }}&rdquo;
      </blockquote>

      <figcaption class="flex items-center gap-3">
        <x-avatar-initials :name="$quote['author']" />

        <div class="space-y-0.5">
          <div class="text-sm font-semibold text-ink">{{ $quote['author'] }}</div>
          <div class="text-xs text-muted"><em>{{ __('from') }}</em> {{ $quote['source'] }}</div>
        </div>
      </figcaption>
    </figure>
  </div>
</aside>
