{{--
  One email we sent, as a row in a list. Clicking it opens the copy that was
  sent, so somebody can tell what an email actually said rather than only that
  it left. Shown by both the logs screen and the page that holds every email.

  The delivery is a badge rather than a coloured dot. A dot with the word hidden
  behind `sr-only` is colour alone for anybody who can see it, which is the one
  thing the system does not allow: the four states are four words, and the tint
  behind each only repeats what the word already says. Two of them share a tint,
  which is why the word has to carry the difference.

  @var \App\Models\EmailSent $emailSent
--}}
@use('App\Enums\EmailDeliveryStatusEnum')
@php
  [$tone, $deliveryLabel] = match ($emailSent->delivery_status) {
    EmailDeliveryStatusEnum::Bounced => ['danger', __('Bounced')],
    EmailDeliveryStatusEnum::Complained => ['danger', __('Marked as spam')],
    EmailDeliveryStatusEnum::Delivered => ['success', __('Delivered')],
    EmailDeliveryStatusEnum::Sent => ['warning', __('On its way')],
  };

  $sentAt = $emailSent->sent_at;
@endphp

<div x-data="{ open: false }" class="border-b border-hairline last:border-b-0">
  <button
    type="button"
    @click="open = ! open"
    :aria-expanded="open ? 'true' : 'false'"
    class="flex w-full items-start gap-3 px-4 py-3.5 text-left transition-[background-color] duration-(--duration-fast) ease-out cursor-pointer hover:bg-hover focus-visible:inset-ring-2 focus-visible:inset-ring-focus-ring/45 focus-visible:outline-none"
  >
    <span class="flex size-8 shrink-0 items-center justify-center rounded-sm bg-plum-tint text-plum">
      <x-icon name="mail" :size="18" :stroke-width="1.5" />
    </span>

    <span class="min-w-0 flex-1">
      <span class="block text-base font-semibold text-ink">{{ $emailSent->subject }}</span>

      <span class="mt-px block text-sm text-pretty text-muted">{{ __('To:') }} {{ $emailSent->email_address }}</span>

      <span class="mt-1.5 flex flex-wrap items-center gap-x-2.5 gap-y-1">
        <x-badge :tone="$tone">{{ $deliveryLabel }}</x-badge>

        @if ($sentAt)
          <time
            datetime="{{ $sentAt->toIso8601String() }}"
            title="{{ $sentAt->toDayDateTimeString() }}"
            class="text-xs text-pretty text-muted"
          >{{ __('Sent :time', ['time' => $sentAt->diffForHumans()]) }}</time>
        @endif
      </span>
    </span>

    <span class="ml-auto flex shrink-0 items-center gap-2.5">
      <x-icon
        name="chevron-down"
        :size="16"
        :stroke-width="1.6"
        class="text-muted transition-transform duration-[170ms]"
        ::class="open && 'rotate-180'"
      />
    </span>
  </button>

  <div x-cloak x-show="open" x-transition class="border-t border-hairline bg-sunken px-4 py-3.5">
    <p class="text-center italic text-xs text-pretty text-muted">{{ __('We remove the links from this copy, since they have probably expired.') }}</p>

    {{-- Purify leaves bare paragraphs behind, so the spacing between them is ours to give. --}}
    <div class="mt-3 flex flex-col gap-2 text-sm leading-relaxed text-body">{!! $emailSent->body !!}</div>
  </div>
</div>
