{{-- The two faces of billing: what the account is on, and what it paid. --}}
{{--
  @var string $current
  @var int $paymentCount
--}}
<x-tabs>
  <x-tabs.item :href="route('settings.billing.index')" :selected="$current === 'billing'" icon="coin" data-turbo="true">
    {{ __('Billing') }}
  </x-tabs.item>

  <x-tabs.item :href="route('settings.invoices.index')" :selected="$current === 'invoices'" icon="file" :count="$paymentCount" data-turbo="true">
    {{ __('Invoices') }}
  </x-tabs.item>
</x-tabs>
