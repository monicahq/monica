{{-- The banner offering full access to a free account, until its reader hides it for good. --}}
<section aria-labelledby="full-access-offer-text" class="print:hidden">
  <x-notice tone="info" icon="info">
    <span id="full-access-offer-text">{{ __('Subscribe now to lift the limits of the free plan!') }}</span>
    <x-link :href="route('settings.billing.index')">{{ __('See the plans') }}</x-link>

    <x-slot:action>
      <x-form method="put" :action="route('fullAccessOffer.update')">
        <x-icon-button type="submit" icon="close" align="right" :label="__('Hide this offer for good')" />
      </x-form>
    </x-slot:action>
  </x-notice>
</section>
