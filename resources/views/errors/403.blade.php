{{-- What somebody sees when they ask for something they are not allowed to do. --}}
{{-- @var \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface $exception --}}
<x-error-page
  :title="__('Not allowed')"
  :heading="__('You’re not allowed to do this.')"
  :line="$exception->getMessage() ?: null"
>
  <x-button :href="url()->previous(route('home.index'))">{{ __('Go back') }}</x-button>
</x-error-page>
