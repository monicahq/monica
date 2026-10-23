{{-- What somebody sees when the address they asked for leads nowhere. --}}
<x-error-page
  :title="__('Page not found')"
  :heading="__('We’re sorry, but the page you’re looking for doesn’t exist.')"
  :line="__('It may have moved, or the link might be broken.')"
>
  <x-button :href="route('home.index')">{{ __('Return home') }}</x-button>

  <x-slot:illustration>
    <img
      src="{{ asset('images/not-found.webp') }}"
      alt=""
      width="1335"
      height="532"
      loading="lazy"
      decoding="async"
      class="h-auto w-full max-w-5xl dark:hidden"
    />

    <img
      src="{{ asset('images/not-found-dark.webp') }}"
      alt=""
      width="1986"
      height="792"
      loading="lazy"
      decoding="async"
      class="hidden h-auto w-full max-w-5xl dark:block"
    />
  </x-slot:illustration>
</x-error-page>
