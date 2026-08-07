{{--
  One email we sent, as a row in a list. Clicking it opens the copy that was
  sent, so somebody can tell what an email actually said rather than only that
  it left. Shown by both the logs screen and the page that holds every email.

  The dot on the left is the delivery: still on its way, delivered, or bounced.
  Colour alone would say nothing to a screen reader, so the same three words are
  written out beside it.

  @var \App\Models\EmailSent $emailSent
--}}
@php
  [$delivery, $deliveryLabel] = match (true) {
    $emailSent->bounced_at !== null => ['bg-error', __('Bounced')],
    $emailSent->delivered_at !== null => ['bg-success', __('Delivered')],
    default => ['bg-warning', __('On its way')],
  };

  $sentAt = $emailSent->sent_at;
  $sentAtLabel = $sentAt ? __('Sent :time', ['time' => $sentAt->diffForHumans()]) : null;
@endphp

<x-box.row x-data="{ open: false }" padding="p-0">
  <button
    type="button"
    @click="open = ! open"
    :aria-expanded="open ? 'true' : 'false'"
    class="flex w-full cursor-pointer items-start gap-x-3 px-4 py-3.5 text-left focus-visible:ring-2 focus-visible:ring-focus focus-visible:outline-none"
  >
    <span class="mt-1.5 size-2 shrink-0 rounded-full {{ $delivery }}" aria-hidden="true"></span>
    <span class="sr-only">{{ $deliveryLabel }}</span>

    <span class="min-w-0 flex-1 text-sm leading-relaxed">
      <span class="block wrap-anywhere text-muted">{{ __('To:') }} {{ $emailSent->email_address }}</span>
      <span class="block text-ink">{{ __('Subject:') }} <span class="font-semibold">{{ $emailSent->subject }}</span></span>

      @if ($sentAt)
        <time datetime="{{ $sentAt->toIso8601String() }}" title="{{ $sentAt->toDayDateTimeString() }}" class="mt-1 block text-xs text-muted sm:hidden">{{ $sentAtLabel }}</time>
      @endif
    </span>

    <span class="flex shrink-0 items-center gap-2 text-xs text-muted">
      @if ($sentAt)
        <time datetime="{{ $sentAt->toIso8601String() }}" title="{{ $sentAt->toDayDateTimeString() }}" class="whitespace-nowrap max-sm:hidden">{{ $sentAtLabel }}</time>
      @endif

      <svg width="13" height="13" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.4" class="mt-0.5 transition-transform duration-200" :class="open && 'rotate-180'" aria-hidden="true">
        <path d="M4 6.4 8 10.4l4-4"></path>
      </svg>
    </span>
  </button>

  {{-- rounded-b-[inherit]: the last row open would otherwise square off the panel. --}}
  <div x-cloak x-show="open" x-transition class="rounded-b-[inherit] border-t border-hairline-soft bg-card px-4 py-3.5">
    <p class="text-center text-xs text-muted italic">{{ __('We remove the links from this copy, since they have probably expired.') }}</p>

    {{-- Purify leaves bare paragraphs behind, so the spacing between them is ours to give. --}}
    <div class="mt-3 space-y-2 text-sm leading-relaxed text-body">{!! $emailSent->body !!}</div>
  </div>
</x-box.row>
