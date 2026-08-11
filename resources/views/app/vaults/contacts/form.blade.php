{{--
  The form that writes somebody down.

  It asks for a name and nothing else. Everything the contact page eventually
  holds comes from the template, and is filled in on the contact once it exists,
  so asking for any of it here would be asking twice.

  The preview under the name order follows what is being typed rather than what
  has been saved, so it is held in the browser. The actions sit in a bar that
  stays at the foot of the window: the form is longer than a screen, and a
  button somebody has to scroll to find is a button they hunt for every time.

  @var \App\ViewModels\Vaults\ContactFormViewModel $viewModel
--}}
@use('App\Enums\NameOrderEnum')
@php
  $vault = $viewModel->vault();
  $values = $viewModel->values();
@endphp
<x-app-layout :title="$viewModel->title()">
  <x-slot:sidebar>
    <x-vault.sidebar
      :vault="$vault"
      :vaults="$viewModel->vaults()"
      :user="$viewModel->user()"
      current="contacts"
    />
  </x-slot:sidebar>

  <x-slot:breadcrumb>
    <x-breadcrumb
      :items="[
        ['label' => $vault->name, 'url' => $viewModel->backUrl()],
        ['label' => __('Contacts'), 'url' => $viewModel->contactsUrl()],
        ['label' => $viewModel->title()],
      ]"
    />
  </x-slot:breadcrumb>

  <x-page-header :title="$viewModel->title()" :description="$viewModel->description()" class="max-w-140" />

  <div
    x-data="{
      firstName: @js($values['firstName']),
      lastName: @js($values['lastName']),
      nameOrder: @js($values['nameOrder']),
      get given() { return this.firstName.trim() },
      get family() { return this.lastName.trim() },
      get written() {
        const parts = this.nameOrder === @js(NameOrderEnum::FamilyFirst->value)
          ? [this.family, this.given]
          : [this.given, this.family]
        return parts.filter(Boolean).join(' ')
      },
    }"
  >
    <x-form id="contact-form" method="post" :action="$viewModel->action()" class="space-y-8.5">
      @if ($viewModel->showsTemplates())
        <x-box :title="__('What this contact starts from')" :description="__('The template decides which fields the contact page holds. It is the vault’s own unless you pick another here, and it can be changed on the contact afterwards.')">
          <div class="max-w-105 space-y-1.5">
            <x-label for="template_version_id" :value="__('Template')" />

            <select
              id="template_version_id"
              name="template_version_id"
              class="block w-full appearance-none rounded-md border border-hairline-strong bg-input px-3 py-2.5 text-base text-ink sm:text-sm"
            >
              @foreach ($viewModel->templates() as $template)
                <option value="{{ $template['id'] }}" @selected($values['templateVersionId'] === (string) $template['id'])>{{ $template['name'] }}</option>
              @endforeach
            </select>

            <x-error :messages="$errors->get('template_version_id')" />
          </div>
        </x-box>
      @endif

      <x-box :title="__('Name')" :description="__('Only the first name is needed. The rest is there for the people whose name does not fit in two boxes.')">
        <div class="space-y-4">
          <div class="grid gap-4 sm:grid-cols-2">
            <x-input
              id="first_name"
              x-model="firstName"
              :value="$values['firstName']"
              :label="__('First name')"
              :placeholder="__('Samir')"
              :error="$errors->get('first_name')"
              maxlength="255"
              autofocus
              required
              required-mark
            />

            <x-input
              id="last_name"
              x-model="lastName"
              :value="$values['lastName']"
              :label="__('Last name')"
              :placeholder="__('Haddad')"
              :error="$errors->get('last_name')"
              maxlength="255"
              optional
            />
          </div>

          <div class="grid gap-4 sm:grid-cols-[minmax(0,7rem)_minmax(0,1fr)_minmax(0,7rem)]">
            <x-input
              id="prefix"
              :value="$values['prefix']"
              :label="__('Prefix')"
              :placeholder="__('Dr')"
              :error="$errors->get('prefix')"
              maxlength="50"
              optional
            />

            <x-input
              id="middle_name"
              :value="$values['middleName']"
              :label="__('Middle name')"
              :error="$errors->get('middle_name')"
              maxlength="255"
              optional
            />

            <x-input
              id="suffix"
              :value="$values['suffix']"
              :label="__('Suffix')"
              :placeholder="__('Jr')"
              :error="$errors->get('suffix')"
              maxlength="50"
              optional
            />
          </div>

          <div class="grid gap-4 sm:grid-cols-2">
            <x-input
              id="nickname"
              :value="$values['nickname']"
              :label="__('Nickname')"
              :help="__('What you actually call them.')"
              :error="$errors->get('nickname')"
              maxlength="255"
              optional
            />

            <x-input
              id="maiden_name"
              :value="$values['maidenName']"
              :label="__('Maiden name')"
              :help="__('Kept so searching either name finds them.')"
              :error="$errors->get('maiden_name')"
              maxlength="255"
              optional
            />
          </div>

          <div class="max-w-105 space-y-1.5">
            <x-label for="name_order" :value="__('Name order')" optional />

            <select
              id="name_order"
              name="name_order"
              x-model="nameOrder"
              class="block w-full appearance-none rounded-md border border-hairline-strong bg-input px-3 py-2.5 text-base text-ink sm:text-sm"
            >
              @foreach ($viewModel->nameOrders() as $order)
                <option value="{{ $order['value'] }}" @selected($values['nameOrder'] === $order['value'])>{{ $order['label'] }}</option>
              @endforeach
            </select>

            <p class="text-xs text-muted">
              <span x-show="written === ''">{{ __('How this contact is written and sorted.') }}</span>
              <span x-cloak x-show="written !== ''">{{ __('Written as') }} <span class="font-semibold text-ink" x-text="written"></span></span>
            </p>

            <x-error :messages="$errors->get('name_order')" />
          </div>
        </div>
      </x-box>

      <x-box :title="__('This contact')" :description="__('Two things you can say about them now. Both are yours to change from their page later.')">
        <div class="space-y-4">
          @foreach ($viewModel->flags() as $flag)
            <div class="space-y-1">
              <x-checkbox :id="$flag['id']" :checked="$flag['checked']">{{ $flag['name'] }}</x-checkbox>

              <p class="pl-6.5 text-xs leading-relaxed text-muted">{{ $flag['help'] }}</p>
            </div>
          @endforeach
        </div>
      </x-box>

      @if ($viewModel->blocker())
        <x-notice>{{ $viewModel->blocker() }}</x-notice>
      @endif
    </x-form>
  </div>

  {{--
    The actions live in the layout's bar at the foot of the window rather than at
    the end of the form, since the form is longer than a screen and a button
    somebody has to scroll to find is a button they hunt for every time.

    They sit outside the form element and are tied back to it by id, which is
    what lets them be down here at all. Nothing disables the button for an empty
    name: the field is required, so the browser refuses the submission and points
    at the field, which says more than a button that has quietly gone grey.
  --}}
  <x-slot:footer>
    <label for="keep_going" class="flex cursor-pointer items-center gap-2.5">
      <input
        type="checkbox"
        id="keep_going"
        name="keep_going"
        form="contact-form"
        value="1"
        @checked($values['keepGoing'])
        class="peer sr-only"
      />

      <span
        aria-hidden="true"
        class="flex size-4 shrink-0 items-center justify-center rounded-sm border-2 border-hairline-strong bg-canvas text-transparent transition-colors peer-checked:border-primary peer-checked:bg-primary peer-checked:text-on-primary peer-focus-visible:ring-2 peer-focus-visible:ring-focus"
      >
        <svg width="10" height="10" viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
          <path d="M2.5 6.3 4.8 8.6 9.5 3.8"></path>
        </svg>
      </span>

      <span class="text-sm text-body">{{ __('Create another one after this') }}</span>
    </label>

    <div class="flex flex-wrap items-center gap-2.5 max-sm:w-full sm:ml-auto">
      <x-button.secondary :href="$viewModel->backUrl()" data-turbo="true">{{ __('Cancel') }}</x-button.secondary>

      <x-button form="contact-form" :disabled="! $viewModel->canCreate()">{{ __('Create the contact') }}</x-button>
    </div>
  </x-slot:footer>
</x-app-layout>
