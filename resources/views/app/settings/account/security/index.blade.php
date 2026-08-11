{{--
  What somebody can change about the way they sign in: their password, whether a
  code is asked for on top of it, and the API keys that let something other than
  a browser act as them.

  Three sections, in the order somebody would ask about them: the thing everybody
  has, the thing that makes it stronger, and the thing only a few people want. Each
  one is kept in a partial beside this file.

  @var \App\ViewModels\Settings\Account\Security\SecurityViewModel $viewModel
--}}
<x-shell :title="__('Security and access')">
  <x-slot:bar>
    <a href="{{ route('settings.index') }}" data-turbo="true" class="erica-btn erica-btn--secondary erica-btn--sm">
      <x-icon name="chevron-right" :size="15" :stroke-width="1.7" class="rotate-180" />
      {{ __('Back to settings') }}
    </a>
  </x-slot:bar>

  <x-slot:dock>
    <x-dock :user="$viewModel->user()" />
  </x-slot:dock>

  <header class="flex flex-col gap-2">
    <h1 class="erica-page-title">{{ __('Security and access') }}</h1>

    <p class="erica-lede max-w-[56ch]">{{ __('How you sign in to your account, and what else may act as you.') }}</p>
  </header>

  <section class="flex flex-col gap-3">
    <div class="flex flex-wrap items-baseline gap-x-3 gap-y-1 px-0.5">
      <h2 class="erica-section-title shrink-0">{{ __('Password') }}</h2>

      <p class="erica-small">{{ __('Choose one you use nowhere else. Long beats complicated.') }}</p>
    </div>

    @include('app.settings.account.security._change-password', ['viewModel' => $viewModel])
  </section>

  <section class="flex flex-col gap-3">
    <div class="flex flex-wrap items-baseline gap-x-3 gap-y-1 px-0.5">
      <h2 class="erica-section-title shrink-0">{{ __('Two factor authentication') }}</h2>

      <p class="erica-small">{{ __('A password can be guessed, reused or stolen. A code that changes every thirty seconds, on a phone in your pocket, cannot be any of those from a distance.') }}</p>
    </div>

    <x-panel :flush="true">
      @if ($viewModel->usesTwoFactorAuthentication())
        @include('app.settings.account.security._two-factor-on', ['viewModel' => $viewModel])
      @else
        @include('app.settings.account.security._two-factor-off')
      @endif
    </x-panel>
  </section>

  <section class="flex flex-col gap-3">
    <div class="flex flex-wrap items-baseline gap-x-3 gap-y-1 px-0.5">
      <h2 class="erica-section-title shrink-0">{{ __('API keys') }}</h2>

      <p class="erica-small">{{ __('A key is a password for machines. It lets a script or another system act as you, with exactly the access you have.') }}</p>
    </div>

    <x-notice>{{ __('There is nothing to point a key at yet. The API that accepts them is still being built, and a key you make now works the moment it lands.') }}</x-notice>

    <x-panel :flush="true">
      @include('app.settings.account.security._api-keys', ['viewModel' => $viewModel])
    </x-panel>
  </section>
</x-shell>
