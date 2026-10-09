{{-- Where somebody who already has an account signs back in. --}}
{{-- @var \App\ViewModels\Auth\LoginViewModel $viewModel --}}
<x-auth-page
  :title="__('Welcome back')"
  :quote="$viewModel->quote()"
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

  @foreach ($viewModel->providersToOffer() as $provider)
    <x-button.secondary :href="$provider['url']" data-turbo="false" class="w-full">
      {{ __('Continue with :provider', ['provider' => $provider['name']]) }}
    </x-button.secondary>
  @endforeach

  @if (! $viewModel->passkeysAreAvailable())
    <x-auth-footnote>
      {{ __('Passkeys need a secure connection. Reach this instance over https and you can sign in with one.') }}
    </x-auth-footnote>
  @endif

  @if ($viewModel->passkeysAreAvailable())
    <x-box
      padding="p-4"
      class="rounded-lg"
      x-data="passkey({{ \Illuminate\Support\Js::from([
        'redirectUrl' => route('home.index'),
        'text' => [
          'cancelled' => __('Nothing was signed in. The prompt was dismissed.'),
          'alreadyRegistered' => __('That passkey cannot be used here.'),
          'failed' => __('That did not work. Try again, or sign in with your password.'),
        ],
      ]) }})"
    >
      <div x-cloak x-show="supported" class="flex flex-col gap-2.5">
        <x-button.secondary
          type="button"
          class="w-full"
          x-bind:disabled="busy"
          @click="signIn()"
        >{{ __('Sign in with a passkey') }}</x-button.secondary>

        <p
          x-text="message"
          role="status"
          aria-live="polite"
          class="text-center text-sm empty:hidden"
          x-bind:class="failed ? 'text-error' : 'text-body'"
        ></p>
      </div>
    </x-box>
  @endif

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

  @if ($viewModel->magicLinkIsEnabled())
    <x-auth-footnote :href="route('auth.magicLink.new')" :label="__('Send me a link instead')">
      {{ __('Sign in without a password?') }}
    </x-auth-footnote>
  @endif

  @if ($viewModel->registrationIsEnabled())
    <x-auth-footnote :href="route('auth.register.new')" :label="__('Create an account')">
      {{ __('New to :app?', ['app' => config('app.name')]) }}
    </x-auth-footnote>
  @endif

  <div class="flex items-center gap-3">
    <x-language-picker :locales="$viewModel->locales()" :current="$viewModel->currentLocale()" />

    <span class="text-xs text-muted-soft">{{ __('You can change it any time.') }}</span>
  </div>
</x-auth-page>
