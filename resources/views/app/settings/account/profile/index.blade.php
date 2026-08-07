{{--
  Where somebody edits their own employee record: how colleagues see them, and
  who to call if something happens to them.

  @var \App\ViewModels\Settings\Account\Profile\ProfileViewModel $viewModel
--}}
<x-app-layout :title="__('Profile')">
  <x-slot:sidebar>
    <x-settings.sidebar :company-name="$viewModel->companyName()" :name="$viewModel->name()" :employee="$viewModel->employee()" current="profile" />
  </x-slot:sidebar>

  <x-slot:breadcrumb>
    <nav class="text-sm text-muted" aria-label="{{ __('Breadcrumb') }}">
      {{ __('Settings') }}
      <span class="px-1 text-placeholder" aria-hidden="true">/</span>
      <span class="text-ink" aria-current="page">{{ __('Profile') }}</span>
    </nav>
  </x-slot:breadcrumb>

  <x-page-header
    :title="__('Profile')"
    :description="__('Manage your personal details and how colleagues see you.')"
  />

  <x-box :title="__('Avatar')">
    <x-slot:help>
      <x-help :title="__('Avatar')">
        Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.
      </x-help>
    </x-slot:help>

    <div class="grid items-center gap-7 md:grid-cols-[minmax(0,1fr)_auto]">
      <div class="space-y-2.5 text-sm leading-relaxed text-body">
        <p>{{ __('Your avatar appears next to your name across :app.', ['app' => config('app.name')]) }}</p>
        <p>{{ __('Use a square image for the best result. JPEG, PNG and WebP up to 5 MB.') }}</p>
        <p>{{ __('Without an avatar, we show your initials instead.') }}</p>
      </div>

      <div class="flex flex-wrap items-center gap-4">
        <x-avatar id="profile-avatar" :employee="$viewModel->employee()" :name="$viewModel->name()" :size="74" />

        {{--
          These two forms reload the page rather than updating it in place, the
          way the forms below do. A picture is not a field: it is read, turned
          upright, cropped and written twice before there is anything to show,
          and the screen is better off asking the server for the whole thing
          once that is done.

          The message lives in the x-data of this div rather than in the change
          handler, because Blade does not compile a directive written inside an
          attribute of a component tag: @js() would reach the browser as itself
          and Alpine would refuse the whole expression.
        --}}
        <div
          class="space-y-3 max-sm:w-full"
          x-data="{ tooBig: false, tooBigMessage: @js(__('The image must be under 5 MB.')) }"
        >
          <x-form
            method="post"
            :action="route('settings.photo.update')"
            :upload="true"
            id="photo-form"
            class="space-y-3"
            x-on:submit="if (tooBig) $event.preventDefault()"
          >
            <x-file-input
              id="photo"
              accept="image/jpeg,image/png,image/webp"
              :error="$errors->get('photo')"
              required
              x-on:change="tooBig = window.oversizedFiles($event.target.files, 5120).length > 0"
            />

            <p x-show="tooBig" x-cloak x-text="tooBigMessage" class="text-xs text-error"></p>

            <x-button>{{ __('Upload') }}</x-button>
          </x-form>

          @if ($viewModel->hasPhoto())
            {{-- Two steps rather than one, so a photo is never removed by a slip. --}}
            <x-form
              method="delete"
              :action="route('settings.photo.destroy')"
              id="photo-delete-form"
              x-data="{ confirming: false }"
            >
              <button
                type="submit"
                x-on:click="if (! confirming) { $event.preventDefault(); confirming = true }"
                class="cursor-pointer text-sm text-muted hover:text-ink"
                x-text="confirming ? @js(__('Remove it for good?')) : @js(__('Remove the photo'))"
              ></button>
            </x-form>
          @endif
        </div>
      </div>
    </div>
  </x-box>

  <x-box :title="__('Details')">
    <x-slot:help>
      <x-help :title="__('Details')">
        Lorem ipsum dolor sit amet, consectetur adipiscing elit. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip.
      </x-help>
    </x-slot:help>

    <div class="grid gap-9 md:grid-cols-[minmax(0,1fr)_minmax(0,1.05fr)]">
      <div class="space-y-2.5 text-sm leading-relaxed text-body">
        <p>{{ __('These details are shown on your profile. Everyone in your company can see them.') }}</p>
        <p>{{ __('The details you keep private, such as your emergency contact, are never shown to your colleagues.') }}</p>
      </div>

      <x-form
        method="put"
        :action="route('settings.profile.update')"
        id="details-form"
        x-target="details-form profile-avatar sidebar-identity"
        class="space-y-3.5 transition-opacity [&[aria-busy]]:opacity-60"
      >
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
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
          id="display_name"
          :label="__('Display name')"
          :value="$viewModel->details()['display_name']"
          :placeholder="__('Shown instead of your real name')"
          :help="__('Optional.')"
          :error="$errors->get('display_name')"
        />

        <x-input
          type="email"
          id="work_email"
          :label="__('Work email')"
          :value="$viewModel->details()['work_email']"
          :error="$errors->get('work_email')"
        />

        <div class="flex items-center gap-3 pt-1">
          <span id="last-saved" @class(['text-xs text-muted-soft', 'hidden' => ! $viewModel->lastSavedAt()])>
            @if ($viewModel->lastSavedAt())
              {{ __('Last saved :time', ['time' => $viewModel->lastSavedAt()]) }}
            @endif
          </span>

          <x-button class="ml-auto">{{ __('Save') }}</x-button>
        </div>
      </x-form>
    </div>
  </x-box>

  <x-box :title="__('Emergency contact')">
    <x-slot:help>
      <x-help :title="__('Emergency contact')">
        Lorem ipsum dolor sit amet, consectetur adipiscing elit. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore.
      </x-help>
    </x-slot:help>

    <div class="grid gap-9 md:grid-cols-[minmax(0,1fr)_minmax(0,1.05fr)]">
      <div class="space-y-2.5 text-sm leading-relaxed text-body">
        <p>{{ __('Who we should call if something happens to you at work.') }}</p>
        <p>{{ __('Only you and your company administrators can see this.') }}</p>
      </div>

      <x-form
        method="put"
        :action="route('settings.emergencyContact.update')"
        id="emergency-contact-form"
        x-target="emergency-contact-form last-saved"
        class="space-y-3.5 transition-opacity [&[aria-busy]]:opacity-60"
      >
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
          <x-input
            id="name"
            :label="__('Name')"
            :value="$viewModel->emergencyContact()['name']"
            :error="$errors->get('name')"
          />

          <x-input
            id="phone"
            :label="__('Phone number')"
            :value="$viewModel->emergencyContact()['phone']"
            :error="$errors->get('phone')"
          />
        </div>

        <x-input
          id="relationship"
          :label="__('Relationship')"
          :value="$viewModel->emergencyContact()['relationship']"
          :placeholder="__('Partner, parent, friend')"
          :error="$errors->get('relationship')"
        />

        <div class="flex items-center pt-1">
          <x-button class="ml-auto">{{ __('Save') }}</x-button>
        </div>
      </x-form>
    </div>
  </x-box>
</x-app-layout>
