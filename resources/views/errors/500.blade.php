{{-- What somebody sees when the instance fails while answering their request. --}}
@php
  $retryUrl = request()->isMethod('GET') || request()->isMethod('HEAD')
    ? request()->fullUrl()
    : url()->previous(route('home.index'));
@endphp

<x-error-page
  :title="__('Server error')"
  :heading="__('We’re sorry, something went wrong on our end.')"
  :line="__('Please try again in a moment.')"
>
  <div class="flex flex-wrap items-center justify-center gap-3">
    <x-button :href="$retryUrl">
      {{ __('Try again') }}

      <x-slot:icon>
        <x-icon name="reload" :size="16" />
      </x-slot:icon>
    </x-button>

    <x-button.secondary :href="route('home.index')">{{ __('Return home') }}</x-button.secondary>
  </div>

  <x-slot:illustration>
    <img
      src="{{ asset('images/server-error.webp') }}"
      alt=""
      width="1448"
      height="620"
      loading="lazy"
      decoding="async"
      class="h-auto w-full max-w-5xl dark:hidden"
    />

    <img
      src="{{ asset('images/server-error-dark.webp') }}"
      alt=""
      width="1585"
      height="560"
      loading="lazy"
      decoding="async"
      class="hidden h-auto w-full max-w-5xl dark:block"
    />
  </x-slot:illustration>
</x-error-page>
