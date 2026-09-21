{{-- Where somebody edits their own profile: their name, their address and their photo. --}}
{{-- @var \App\ViewModels\Settings\Account\Profile\ProfileViewModel $viewModel --}}
<x-settings.page
  :title="__('Profile')"
  :user="$viewModel->user()"
  current="profile"
>
  <x-modal.confirm-password
    :confirmed="$viewModel->passwordIsConfirmed()"
    :seconds-left="$viewModel->secondsBeforeTheConfirmationExpires()"
    :offers-password="$viewModel->credentials()->offersPassword()"
    :offers-passkey="$viewModel->credentials()->offersPasskey()"
  />

  <x-page-header :title="__('Profile')" :description="__('Your name, the address you sign in with, and your photo.')" />

  <x-page-section :title="__('Profile photo')">
    <x-panel>
      <div
        class="flex flex-wrap items-center gap-5"
        x-data="{ tooBig: false, tooBigMessage: @js(__('The photo must be under 5 MB.')) }"
      >
        <x-avatar id="profile-avatar" :user="$viewModel->user()" :size="72" />

        <div class="flex min-w-0 flex-1 flex-col gap-3">
          <p class="text-sm text-pretty text-muted">{{ __('A square photo reads best. JPEG, PNG or WebP, up to 5 MB. Without one you keep your initials on a coloured background.') }}</p>

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
  </x-page-section>

  <x-page-section :title="__('Details')">
    <x-form
      method="put"
      :action="route('settings.profile.update')"
      id="details-form"
      x-target="details-form profile-avatar shell-avatar"
      class="dim-when-busy"
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
  </x-page-section>

  <x-page-section :title="__('Email address')" :description="__('What you sign in with, and where every way back into the account is sent.')">
    <x-form method="put" :action="route('settings.email.update')">
      <x-panel>
        <x-input
          type="email"
          id="email"
          :label="__('Email address')"
          :value="$viewModel->details()['email']"
          autocomplete="username"
          :help="__('Change it and we will ask you to confirm the new one. We ask for a credential first.')"
          :error="$errors->get('email')"
          required
        />

        <x-slot:footer>
          <div class="flex justify-end">
            <x-button size="small" type="submit" @click.prevent="$dispatch('confirm-password', { form: $el.form })">{{ __('Save this address') }}</x-button>
          </div>
        </x-slot:footer>
      </x-panel>
    </x-form>
  </x-page-section>

  <section id="delete-your-account" class="flex scroll-mt-20 flex-col gap-3">
    <div class="px-0.5">
      <h2 class="text-section-title text-ink">{{ __('Delete your account') }}</h2>
    </div>

    <x-panel>
      <div class="flex flex-wrap items-center gap-6" x-data="{ confirming: @js($errors->has('confirm_email')) }">
        <p class="max-w-[46ch] flex-1 text-sm text-pretty text-muted">
          {{ __('Everything you have put in :app is deleted with it: every vault, every person in them, every note and every photograph. We keep no copy, so nothing can be recovered afterwards.', ['app' => config('app.name')]) }}
        </p>

        @if ($viewModel->isSubscribed())
          <p class="max-w-[46ch] flex-1 text-sm text-pretty text-muted">
            {{ __('Your subscription stops the moment the account is gone. The days already paid for are not refunded, and your invoices stay with Stripe.') }}
          </p>
        @endif

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

            <x-button.danger size="small" class="max-sm:w-full" type="submit" @click.prevent="$dispatch('confirm-password', { form: $el.form })">{{ __('Delete it for good') }}</x-button.danger>
          </div>
        </x-form>
      </div>
    </x-panel>
  </section>
</x-settings.page>
