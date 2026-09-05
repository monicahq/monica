{{-- Where somebody edits their own profile: their name, their address and their photo. --}}
{{-- @var \App\ViewModels\Settings\Account\Profile\ProfileViewModel $viewModel --}}
<x-shell :title="__('Profile')">
  <x-slot:breadcrumb>
    <x-button.bar :href="route('settings.index')" data-turbo="true" size="small">
      <x-icon name="chevron-right" :size="15" :stroke-width="1.7" class="rotate-180" />
      {{ __('Back to settings') }}
    </x-button.bar>
  </x-slot:breadcrumb>

  <x-slot:sidebar>
    <x-settings.sidebar :user="$viewModel->user()" current="profile" />
  </x-slot:sidebar>

  <header class="flex flex-col gap-2">
    <h1 class="text-page-title text-ink">{{ __('Profile') }}</h1>

    <p class="max-w-[56ch] text-base text-pretty text-body">{{ __('Your name, the address you sign in with, and the photograph on your avatar.') }}</p>
  </header>

  <section class="flex flex-col gap-3">
    <div class="flex flex-wrap items-baseline gap-x-3 gap-y-1 px-0.5">
      <h2 class="shrink-0 text-section-title text-ink">{{ __('Avatar') }}</h2>

      <p class="text-sm text-pretty text-muted">{{ __('Shown next to your name everywhere in the application.') }}</p>
    </div>

    <x-panel>
      <div
        class="flex flex-wrap items-center gap-5"
        x-data="{ tooBig: false, tooBigMessage: @js(__('The image must be under 5 MB.')) }"
      >
        <x-avatar id="profile-avatar" :user="$viewModel->user()" :size="72" />

        <div class="flex min-w-0 flex-1 flex-col gap-3">
          <p class="text-sm text-pretty text-muted">{{ __('A square image reads best. JPEG, PNG or WebP, up to 5 MB. Without a photo you keep your initials on a warm tint.') }}</p>

          <x-form
            method="post"
            :action="route('settings.photo.update')"
            :upload="true"
            id="photo-form"
            class="flex flex-wrap items-center gap-2.5"
            x-on:submit="if (tooBig) $event.preventDefault()"
          >
            <x-file-input
              id="photo"
              accept="image/jpeg,image/png,image/webp"
              :error="$errors->get('photo')"
              required
              class="w-auto"
              aria-label="{{ __('Choose a photo') }}"
              x-on:change="tooBig = window.oversizedFiles($event.target.files, 5120).length > 0"
            />

            <x-button size="small">{{ __('Upload') }}</x-button>
          </x-form>

          <p x-show="tooBig" x-cloak x-text="tooBigMessage" class="text-xs text-error"></p>

          @if ($viewModel->hasPhoto())
            <div
              x-data="{
                confirming: false,
                removeLabel: @js(__('Remove the photo')),
                confirmLabel: @js(__('Remove it for good?')),
              }"
            >
              <x-form method="delete" :action="route('settings.photo.destroy')" id="photo-delete-form">
                <x-button.ghost
                  size="small"
                  x-on:click="if (! confirming) { $event.preventDefault(); confirming = true }"
                  x-text="confirming ? confirmLabel : removeLabel"
                >{{ __('Remove the photo') }}</x-button.ghost>
              </x-form>
            </div>
          @endif
        </div>
      </div>
    </x-panel>
  </section>

  <section class="flex flex-col gap-3">
    <div class="flex flex-wrap items-baseline gap-x-3 gap-y-1 px-0.5">
      <h2 class="shrink-0 text-section-title text-ink">{{ __('Details') }}</h2>

      <p class="text-sm text-pretty text-muted">{{ __('Your name appears everywhere the application refers to you; your address is what you sign in with.') }}</p>
    </div>

    <x-form
      method="put"
      :action="route('settings.profile.update')"
      id="details-form"
      x-target="details-form profile-avatar shell-avatar"
      class="transition-opacity [&[aria-busy]]:opacity-60"
    >
      <x-panel>
        <div class="flex flex-col gap-4.5">
          <div class="grid gap-4 sm:grid-cols-2">
            <x-input
              id="first_name"
              :label="__('First name')"
              :value="$viewModel->details()['first_name']"
              :error="$errors->get('first_name')"
              required
            />

            <x-input
              id="last_name"
              :label="__('Last name')"
              :value="$viewModel->details()['last_name']"
              :error="$errors->get('last_name')"
              required
            />
          </div>

          <x-input
            type="email"
            id="email"
            :label="__('Email address')"
            :value="$viewModel->details()['email']"
            autocomplete="username"
            :help="__('This is what you sign in with. Change it and we will ask you to confirm the new one.')"
            :error="$errors->get('email')"
            required
          />
        </div>

        <x-slot:footer>
          <div class="flex items-center gap-4">
            <span id="last-saved" class="text-xs text-pretty text-muted">
              @if ($viewModel->lastSavedAt())
                {{ __('Last saved :time', ['time' => $viewModel->lastSavedAt()]) }}
              @endif
            </span>

            <x-button size="small" class="ml-auto">{{ __('Save changes') }}</x-button>
          </div>
        </x-slot:footer>
      </x-panel>
    </x-form>
  </section>

  <section id="delete-your-account" class="flex scroll-mt-20 flex-col gap-3">
    <div class="px-0.5">
      <h2 class="text-section-title text-ink">{{ __('Delete your account') }}</h2>
    </div>

    <x-panel>
      <div class="flex flex-wrap items-center gap-6" x-data="{ confirming: @js($errors->has('confirm_email')) }">
        <p class="max-w-[46ch] flex-1 text-sm text-pretty text-muted">
          {{ __('Everything you have put in :app goes with it: every vault, every person in them, every note and every photograph. We keep no copy, so there is nobody to ask afterwards.', ['app' => config('app.name')]) }}
        </p>

        <x-button.secondary
          type="button"
          size="small"
          x-show="! confirming"
          @click="confirming = true"
          class="ml-auto max-sm:w-full"
        >
          {{ __('Delete your account') }}
        </x-button.secondary>

        <x-form
          method="delete"
          :action="route('settings.account.destroy')"
          x-cloak
          x-show="confirming"
          class="flex w-full flex-col gap-3.5"
        >
          <p class="text-xs text-error">{{ __('This deletes your account for good. Type :email below to confirm.', ['email' => $viewModel->details()['email']]) }}</p>

          <x-input
            type="email"
            id="confirm_email"
            :label="__('Email address')"
            autocomplete="off"
            :error="$errors->get('confirm_email')"
            required
          />

          <div class="flex flex-wrap items-center gap-3 max-sm:flex-col max-sm:items-stretch">
            <x-button.secondary type="button" size="small" @click="confirming = false">{{ __('Keep my account') }}</x-button.secondary>

            <x-button.danger size="small" class="max-sm:w-full">{{ __('Delete it for good') }}</x-button.danger>
          </div>
        </x-form>
      </div>
    </x-panel>
  </section>
</x-shell>
