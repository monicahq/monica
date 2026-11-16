{{-- What a visitor sees at the root address while registration is closed: a way onto the waiting list. --}}
<x-auth-page
  :title="__(':app is opening soon', ['app' => config('app.name')])"
  :description="__(':app keeps track of the people who matter to you. Leave your email address and we will write to you when you can create your account.', ['app' => config('app.name')])"
  class="bg-[#fdfaf7] dark:bg-[#2a3553]"
>
  <x-status :message="session('status')" />

  <x-box>
    <x-form method="post" :action="route('auth.previewList.create')" data-turbo="false" class="space-y-4">
      <x-input
        type="email"
        id="email"
        :label="__('Email address')"
        :value="old('email')"
        placeholder="john@doe.com"
        autocomplete="email"
        :error="$errors->get('email')"
        required
        autofocus
      />

      <x-turnstile data-size="flexible" />

      <x-button class="w-full">{{ __('Join the waiting list') }}</x-button>
    </x-form>
  </x-box>

  <x-auth-footnote :href="route('auth.login.new')" :label="__('Sign in instead')">
    {{ __('Already have an account?') }}
  </x-auth-footnote>

  <x-slot:landscape>
    <img
      src="{{ asset('images/preview-landscape.webp') }}"
      alt=""
      width="1536"
      height="1024"
      decoding="async"
      aria-hidden="true"
      class="pointer-events-none absolute inset-x-0 bottom-0 -z-10 w-full mask-t-from-80% select-none dark:hidden"
    />

    <img
      src="{{ asset('images/preview-landscape-dark.webp') }}"
      alt=""
      width="1448"
      height="1086"
      decoding="async"
      aria-hidden="true"
      class="pointer-events-none absolute inset-x-0 bottom-0 -z-10 hidden w-full mask-t-from-80% select-none dark:block"
    />
  </x-slot:landscape>
</x-auth-page>
