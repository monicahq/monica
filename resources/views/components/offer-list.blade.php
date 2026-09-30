{{-- The plans somebody may take up: the subscription beside the one time fee, each sent to Stripe. --}}
{{--
  @var array<int, array{kind: \App\Enums\OfferKindEnum, price: string, amount: string, price_id: string, upgrade: bool}> $offers
  @var string|null $saving
--}}
@props([
  'offers',
  'saving' => null,
])

@php
  $renewing = array_values(array_filter($offers, fn (array $offer): bool => $offer['kind']->renews()));
  $once = array_values(array_filter($offers, fn (array $offer): bool => ! $offer['kind']->renews()));
@endphp

@if ($offers !== [])
  <div @class(['grid items-stretch gap-4', 'lg:grid-cols-[minmax(0,1.35fr)_minmax(0,1fr)]' => $renewing !== [] && $once !== []])>
    @if ($renewing !== [])
      <section aria-labelledby="plans-subscription" class="flex flex-col rounded-lg border border-hairline bg-canvas shadow-panel">
        <div class="flex flex-wrap items-baseline justify-between gap-3 border-b border-hairline px-6 py-4">
          <h3 id="plans-subscription" class="text-section-title text-ink">{{ __('Subscription') }}</h3>

          <p class="text-base text-muted">{{ __('Billed on repeat. Cancel renewal anytime.') }}</p>
        </div>

        <ul @class(['grid flex-1', 'sm:grid-cols-2' => count($renewing) > 1])>
          @foreach ($renewing as $offer)
            <li @class([
              'flex flex-col gap-1.5 p-6',
              'border-t border-hairline bg-card sm:border-t-0 sm:border-l' => ! $loop->first,
              'sm:rounded-br-lg' => $loop->last && ! $loop->first,
            ])>
              <h4 class="flex flex-wrap items-center gap-2">
                <x-caption tone="muted">{{ $offer['kind'] === \App\Enums\OfferKindEnum::Monthly ? __('Monthly') : __('Yearly') }}</x-caption>

                @if ($offer['kind'] === \App\Enums\OfferKindEnum::Yearly && $saving !== null)
                  <span class="rounded-full border border-ink px-2 text-xs font-semibold text-ink">{{ __('Save :amount', ['amount' => $saving]) }}</span>
                @endif
              </h4>

              <p class="text-[44px] leading-none font-semibold tracking-[-0.05em] whitespace-nowrap text-ink">
                {{ $offer['amount'] }}<span class="text-[16px] font-medium tracking-normal text-muted"> {{ $offer['kind'] === \App\Enums\OfferKindEnum::Monthly ? __('/ month') : __('/ year') }}</span>
              </p>

              <p class="mt-2 text-base font-medium text-ink">{{ __($offer['kind']->tagline()) }}</p>

              <p class="text-base text-pretty text-muted">{{ __($offer['kind']->description()) }}</p>

              <div class="mt-auto pt-5">
                @if ($offer['upgrade'])
                  <x-modal.form
                    :title="__('Switch to :plan?', ['plan' => __($offer['kind']->label())])"
                    :description="__('Nothing is charged today. The subscription becomes this one at once, and the new price is billed at the renewal already planned, on the same date as before.')"
                    :confirm="__('Switch')"
                    method="put"
                    :action="route('settings.subscription.update', ['price' => $offer['price_id']])"
                  >
                    <x-slot:trigger>
                      <x-button type="button" @click="open = true">{{ __('Switch to this plan') }}</x-button>
                    </x-slot:trigger>
                  </x-modal.form>
                @else
                  <x-form method="post" :action="route('settings.checkout.store', ['price' => $offer['price_id']])">
                    <x-button :variant="$offer['kind'] === \App\Enums\OfferKindEnum::Yearly ? 'primary' : 'secondary'">{{ __($offer['kind']->callToAction()) }}</x-button>
                  </x-form>
                @endif
              </div>
            </li>
          @endforeach
        </ul>
      </section>
    @endif

    @foreach ($once as $offer)
      <section aria-labelledby="plans-lifetime" class="flex flex-col gap-1.5 rounded-lg bg-primary px-6 py-7 text-on-primary shadow-[0_20px_40px_-12px_rgb(0_0_0/0.45)]">
        <span class="self-start rounded-full bg-on-primary px-2.5 py-0.75 text-xs font-semibold tracking-[0.06em] text-primary uppercase">{{ __('One time fee') }}</span>

        <h3 id="plans-lifetime" class="mt-3.5 text-[14px] font-semibold">{{ __('Lifetime') }}</h3>

        <p class="text-[56px] leading-none font-semibold tracking-[-0.05em] whitespace-nowrap">
          {{ $offer['amount'] }}<span class="text-[16px] font-medium tracking-normal"> {{ __('once') }}</span>
        </p>

        <p class="mt-2.5 text-base font-semibold">{{ __($offer['kind']->tagline()) }}</p>

        <p class="text-base text-pretty opacity-85">{{ __($offer['kind']->description()) }}</p>

        <div class="mt-auto pt-5">
          <x-form method="post" :action="route('settings.checkout.store', ['price' => $offer['price_id']])">
            <button type="submit" class="focus-ring inline-flex h-10 cursor-pointer items-center rounded-md bg-on-primary px-5 text-base font-semibold text-primary transition duration-(--duration-fast) ease-out hover:opacity-90">{{ __($offer['kind']->callToAction()) }}</button>
          </x-form>
        </div>
      </section>
    @endforeach
  </div>
@endif
