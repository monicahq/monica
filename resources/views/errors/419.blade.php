{{-- What somebody sees when they send a form from a page that stayed open after their session ended. --}}
<x-error-page
  :title="__('Page expired')"
  :heading="__('This page has expired.')"
  :line="__('It stayed open too long. Go back, then try again.')"
>
  <x-button :href="url()->previous(route('home.index'))">{{ __('Go back') }}</x-button>
</x-error-page>
