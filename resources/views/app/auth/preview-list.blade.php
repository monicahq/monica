{{-- What a visitor sees at the root address while registration is closed: a way onto the waiting list. --}}
<x-auth-page
  :title="__(':app is opening soon', ['app' => config('app.name')])"
  :description="__(':app keeps track of the people who matter to you. Leave your email address and we will write to you when you can create your account.', ['app' => config('app.name')])"
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
</x-auth-page>
