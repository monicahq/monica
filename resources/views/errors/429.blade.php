{{-- What somebody sees when they repeat the same request too often in a short time. --}}
<x-error-page
  :title="__('Too many attempts')"
  :heading="__('That was too many attempts in a short time.')"
  :line="__('Wait a moment, then try again.')"
>
  <x-button :href="url()->previous(route('home.index'))">{{ __('Go back') }}</x-button>
</x-error-page>
