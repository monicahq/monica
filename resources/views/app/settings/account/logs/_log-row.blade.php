{{--
  One thing that happened on the account, as a row in a list.

  The address is what makes this screen worth reading: the entry that came from
  somewhere you have never been is the one to find. There is none when the
  application acted on its own, and the row says so rather than leaving a gap.

  The action is the name the code gave the event, so it is set in mono. Everything
  else here is a sentence somebody wrote, so nothing else is.

  The row hangs its mark from the top rather than centring it, because a
  description can run to two lines and a mark halfway down a paragraph reads as a
  mistake.

  @var \App\Models\Log $log
--}}
<div class="erica-row erica-row--top">
  <span class="erica-row__mark" style="background: var(--forest-tint); color: var(--forest)">
    <x-icon name="timeline" :size="18" :stroke-width="1.5" />
  </span>

  <div class="erica-row__main">
    <p class="flex flex-wrap items-baseline gap-x-2 gap-y-0.5">
      <span class="erica-row__title">{{ $log->author }}</span>

      <code class="font-mono text-[12.5px] wrap-anywhere text-muted">{{ $log->action }}</code>
    </p>

    <p class="erica-row__desc">{{ $log->description }}</p>

    <p class="mt-1 flex flex-wrap items-baseline gap-x-2 gap-y-0.5">
      <time
        datetime="{{ $log->created_at->toIso8601String() }}"
        title="{{ $log->created_at->toDayDateTimeString() }}"
        class="erica-caption"
      >{{ $log->created_at->diffForHumans() }}</time>

      <span class="erica-caption" aria-hidden="true">·</span>

      @if ($log->ip_address)
        <span class="font-mono text-[12.5px] whitespace-nowrap text-muted">{{ $log->ip_address }}</span>
      @else
        <span class="erica-caption">{{ __('No address, so this was the application itself') }}</span>
      @endif
    </p>
  </div>
</div>
