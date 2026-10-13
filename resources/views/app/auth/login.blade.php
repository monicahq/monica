{{-- Where somebody who already has an account signs back in. --}}
{{-- @var \App\ViewModels\Auth\LoginViewModel $viewModel --}}
<x-auth-page
  :title="__('Welcome back')"
>
  <x-status :message="session('status')" />

  <x-box>
    <x-form method="post" :action="route('auth.login.create')" class="space-y-4">
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

      <x-input
        type="password"
        id="password"
        :label="__('Password')"
        autocomplete="current-password"
        :error="$errors->get('password')"
        allowPasswordManager
        required
      />

      <x-checkbox id="remember" class="w-fit" :checked="session()->hasOldInput() ? (bool) old('remember') : true">
        {{ trans_choice('Remember me for :count day|Remember me for :count days', config('monica.remember_duration_days')) }}
      </x-checkbox>

      <x-turnstile data-size="flexible" />

      <div class="flex items-center gap-3.5">
        <x-link turbo :href="route('auth.password.new')" class="text-sm text-body">
          {{ __('Forgot your password?') }}
        </x-link>

        <x-button class="ml-auto">{{ __('Continue') }}</x-button>
      </div>
    </x-form>
  </x-box>

  <section aria-labelledby="alternate-login" class="flex flex-col gap-3">
    <div class="flex items-center gap-3">
      <span class="h-px flex-1 bg-hairline" aria-hidden="true"></span>
      <h2 id="alternate-login" class="text-xs font-medium text-muted">{{ __('Alternate login options') }}</h2>
      <span class="h-px flex-1 bg-hairline" aria-hidden="true"></span>
    </div>

    <div class="flex flex-col gap-2">
      @foreach ($viewModel->providersToOffer() as $provider)
        <x-button.secondary :href="$provider['url']" data-turbo="false" class="w-full">
          {{ __('Continue with :provider', ['provider' => $provider['name']]) }}
        </x-button.secondary>
      @endforeach

      @if ($viewModel->passkeysAreAvailable())
        <div
          x-data="passkey({{ \Illuminate\Support\Js::from([
            'redirectUrl' => route('home.index'),
            'text' => [
              'cancelled' => __('Nothing was signed in. The prompt was dismissed.'),
              'alreadyRegistered' => __('That passkey cannot be used here.'),
              'failed' => __('That did not work. Try again, or sign in with your password.'),
            ],
          ]) }})"
        >
          <div x-show="supported" class="flex flex-col gap-2">
            <x-button.secondary
              type="button"
              class="w-full"
              x-bind:disabled="busy"
              @click="signIn()"
            >
              <x-slot:icon><x-icon name="key" :size="16" /></x-slot:icon>
              {{ __('Sign in with a passkey') }}
            </x-button.secondary>

            <p
              x-text="message"
              role="status"
              aria-live="polite"
              class="text-center text-sm empty:hidden"
              x-bind:class="failed ? 'text-error' : 'text-body'"
            ></p>
          </div>
        </div>
      @else
        <p class="text-center text-xs text-muted">
          {{ __('Passkeys need a secure connection. Reach this instance over https and you can sign in with one.') }}
        </p>
      @endif

      @if ($viewModel->magicLinkIsEnabled())
        <x-button.secondary :href="route('auth.magicLink.new')" data-turbo="true" class="w-full">
          <x-slot:icon><x-icon name="mail" :size="16" /></x-slot:icon>
          {{ __('Send me a link instead') }}
        </x-button.secondary>
      @endif
    </div>
  </section>

  @env(config('login-link.allowed_environments'))
    @if ($viewModel->exampleAccountEmail() !== null)
      <x-auth-footnote>
        <x-login-link
          :label="'Sign in as '.$viewModel->exampleAccountName()"
          :email="$viewModel->exampleAccountEmail()"
          :redirect-url="route('home.index')"
          class="inline cursor-pointer font-medium text-ink underline underline-offset-2 decoration-hairline-strong transition-colors duration-150 hover:decoration-ink"
        />
      </x-auth-footnote>
    @endif
  @endenv

  @if ($viewModel->registrationIsEnabled())
    <div class="flex flex-wrap items-center justify-center gap-x-2.5 gap-y-1.5 border-t border-hairline pt-6">
      <span class="text-sm text-body">{{ __('New to :app?', ['app' => config('app.name')]) }}</span>

      <a
        href="{{ route('auth.register.new') }}"
        data-turbo="true"
        class="focus-ring -my-1 inline-flex items-center gap-1.5 rounded-md px-2.5 py-1 text-sm font-semibold text-ink no-underline transition-colors hover:bg-hover"
      >
        {{ __('Create an account') }}
        <x-icon name="arrow-right" :size="15" />
      </a>
    </div>
  @endif

  <x-slot:footer>
    <x-language-picker :locales="$viewModel->locales()" :current="$viewModel->currentLocale()" />
  </x-slot:footer>
</x-auth-page>
