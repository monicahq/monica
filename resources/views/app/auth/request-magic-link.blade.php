{{--
  Where somebody asks for a link that signs them in without a password.

  @var \App\ViewModels\Auth\MagicLinkViewModel $viewModel
--}}
<x-guest-layout :title="__('Get a link to sign in')">
  <div class="grid min-h-screen grid-cols-1 lg:grid-cols-[minmax(0,1fr)_minmax(0,0.92fr)]">
    <main class="flex min-w-0 flex-col justify-center px-6 py-10 sm:px-15">
      <div class="mx-auto w-full max-w-md space-y-6">
        <div class="space-y-2">
          <div class="flex items-center gap-3">
            <x-logo :size="30" />

            <h1 class="text-2xl font-semibold tracking-tight text-ink">{{ __('Get a link to sign in') }}</h1>

            <x-theme-toggle class="ml-auto" />
          </div>

          <p class="text-sm text-body">{{ __('Give us your email address and we will send you a link that signs you straight in.') }}</p>
        </div>

        <x-box>
          <x-form method="post" :action="route('auth.magicLink.create')" class="space-y-4">
            <x-input
              type="email"
              id="email"
              :label="__('Email address')"
              :value="old('email')"
              placeholder="john@doe.com"
              autocomplete="username"
              :error="$errors->get('email')"
              allowPasswordManager
              required
              autofocus
            />

            <x-button class="w-full">{{ __('Email me a link') }}</x-button>
          </x-form>
        </x-box>

        <x-box padding="p-4" class="rounded-lg text-center text-sm text-body">
          {{ __('Rather use your password?') }}
          <x-link :href="route('auth.login.new')" class="font-semibold text-ink">{{ __('Back to sign in') }}</x-link>
        </x-box>
      </div>

      <p class="mt-auto pt-10 text-center text-xs text-muted-soft">
        &copy; {{ config('app.name') }} {{ now()->format('Y') }} &middot; {{ __('Open source HR') }}
      </p>
    </main>

    @include('app.auth._quote', ['quote' => $viewModel->quote()])
  </div>
</x-guest-layout>
