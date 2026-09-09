{{-- The offers somebody may take up, each with the button that sends them to Stripe's own checkout. --}}
{{-- @var array<int, array{kind: \App\Enums\OfferKindEnum, price: string, price_id: string, upgrade: bool}> $offers --}}
@props([
  'offers',
])

<ul class="grid gap-3 md:grid-cols-3">
  @foreach ($offers as $offer)
    <li class="flex flex-col gap-4 rounded-lg border border-hairline bg-canvas p-4 shadow-panel">
      <div class="flex flex-col gap-1">
        <h3 class="text-card-title text-ink">{{ __($offer['kind']->label()) }}</h3>

        <p class="text-page-title text-ink">{{ $offer['price'] }}</p>

        <p class="text-sm text-pretty text-muted">{{ __($offer['kind']->description()) }}</p>
      </div>

      @if ($offer['upgrade'])
        <x-modal.form
          :title="__('Switch to :offer?', ['offer' => __($offer['kind']->label())])"
          :description="__('Nothing is charged today. The subscription becomes this one at once, and the new price is billed at the renewal already planned, on the same date as before.')"
          :confirm="__('Switch')"
          method="put"
          :action="route('settings.subscription.update', ['price' => $offer['price_id']])"
        >
          <x-slot:trigger>
            <x-button type="button" @click="open = true">{{ __('Switch to this offer') }}</x-button>
          </x-slot:trigger>
        </x-modal.form>
      @else
        <x-form method="post" :action="route('settings.checkout.store', ['price' => $offer['price_id']])">
          <x-button>{{ __($offer['kind']->callToAction()) }}</x-button>
        </x-form>
      @endif
    </li>
  @endforeach
</ul>
