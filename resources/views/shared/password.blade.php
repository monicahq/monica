{{-- The password a protected link asks for before the page behind it opens. --}}
{{--
  @var string $token
  @var bool $wrong
--}}
<x-guest-layout :title="__('This page asks for a password')">
  <main class="flex min-h-screen flex-col items-center justify-center gap-6 px-6 py-10">
    <x-logo :size="34" />

    <div class="w-full max-w-md rounded-lg border border-hairline bg-canvas p-6 ">
      <div class="flex flex-col gap-4">
        <div class="flex flex-col gap-2">
          <span class="flex size-9 items-center justify-center rounded-md border border-hairline bg-card tint text-primary" aria-hidden="true">
            <x-icon name="lock" :size="19" :stroke-width="1.6" />
          </span>

          <h1 class="text-section-title text-ink">{{ __('This page asks for a password') }}</h1>

          <p class="text-sm text-pretty text-body">
            {{ __('Whoever shared this page with you set a password on it, and will have sent it to you separately from the address.') }}
          </p>
        </div>

        <x-form method="post" :action="route('share.password.create', ['token' => $token])" class="flex flex-col gap-4">
          <x-input
            id="password"
            type="password"
            :label="__('Password')"
            :error="$wrong ? [__('That password does not open this link.')] : $errors->get('password')"
            autocomplete="off"
            maxlength="255"
            required
            autofocus
          />

          <x-button class="w-full">{{ __('Open the page') }}</x-button>
        </x-form>
      </div>
    </div>

    <p class="max-w-md text-center text-xs text-pretty text-muted-soft">
      {{ __('There is nothing to sign in to here and no account to make.') }}
    </p>
  </main>
</x-guest-layout>
