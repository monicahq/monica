{{--
  The form that writes somebody down.

  It asks for a name and nothing else. Everything the contact page eventually
  holds comes from the template, and is filled in on the contact once it exists,
  so asking for any of it here would be asking twice.

  The preview under the name order follows what is being typed rather than what
  has been saved, so it is held in the browser.

  The actions sit at the end of the form rather than in a bar pinned to the foot
  of the window, which is what the v2 shell gave them. The dock lives down there
  now, and two rows of controls stacked at the bottom edge is one too many. The
  row is outside the form element and reaches back into it by id, which is what
  lets the "create another one" box travel with the buttons instead of being
  stranded three sections above them.

  @var \App\ViewModels\Vaults\ContactFormViewModel $viewModel
--}}
@use('App\Enums\NameOrderEnum')
@php
  $vault = $viewModel->vault();
  $values = $viewModel->values();
@endphp
<x-shell :title="$viewModel->title()">
  <x-slot:bar>
    <a href="{{ $viewModel->contactsUrl() }}" data-turbo="true" class="erica-btn erica-btn--secondary erica-btn--sm">
      <x-icon name="chevron-right" :size="15" :stroke-width="1.7" class="rotate-180" />

      {{ __('Back to the contacts') }}
    </a>
  </x-slot:bar>

  <x-slot:dock>
    <x-dock :user="$viewModel->user()" :vault="$vault" current="people" />
  </x-slot:dock>

  <header class="flex flex-col gap-2">
    <h1 class="erica-page-title">{{ $viewModel->title() }}</h1>

    <p class="erica-lede max-w-[56ch]">{{ $viewModel->description() }}</p>
  </header>

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
    class="flex flex-col gap-8"
  >
    <x-form id="contact-form" method="post" :action="$viewModel->action()" class="flex flex-col gap-8">
      @if ($viewModel->showsTemplates())
        <section class="flex flex-col gap-3">
          <div class="flex flex-wrap items-baseline gap-x-3 gap-y-1 px-0.5">
            <h2 class="erica-section-title shrink-0">{{ __('What this contact starts from') }}</h2>

            <p class="erica-small">{{ __('The template decides which fields the contact page holds. It is the vault’s own unless you pick another here, and it can be changed on the contact afterwards.') }}</p>
          </div>

          <x-panel>
            <div class="max-w-105">
              <div class="erica-field">
                <x-label for="template_version_id" :value="__('Template')" />

                <select
                  id="template_version_id"
                  name="template_version_id"
                  class="erica-control erica-control--select"
                >
                  @foreach ($viewModel->templates() as $template)
                    <option value="{{ $template['id'] }}" @selected($values['templateVersionId'] === (string) $template['id'])>{{ $template['name'] }}</option>
                  @endforeach
                </select>

                <x-error :messages="$errors->get('template_version_id')" />
              </div>
            </div>
          </x-panel>
        </section>
      @endif

      <section class="flex flex-col gap-3">
        <div class="flex flex-wrap items-baseline gap-x-3 gap-y-1 px-0.5">
          <h2 class="erica-section-title shrink-0">{{ __('Name') }}</h2>

          <p class="erica-small">{{ __('Only the first name is needed. The rest is there for the people whose name does not fit in two boxes.') }}</p>
        </div>

        <x-panel>
          <div class="flex flex-col gap-4.5">
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

            <div class="max-w-105">
              <div class="erica-field">
                <x-label for="name_order" :value="__('Name order')" optional />

                <select
                  id="name_order"
                  name="name_order"
                  x-model="nameOrder"
                  class="erica-control erica-control--select"
                >
                  @foreach ($viewModel->nameOrders() as $order)
                    <option value="{{ $order['value'] }}" @selected($values['nameOrder'] === $order['value'])>{{ $order['label'] }}</option>
                  @endforeach
                </select>

                <p class="erica-field__help">
                  <span x-show="written === ''">{{ __('How this contact is written and sorted.') }}</span>
                  <span x-cloak x-show="written !== ''">{{ __('Written as') }} <span class="font-semibold text-ink" x-text="written"></span></span>
                </p>

                <x-error :messages="$errors->get('name_order')" />
              </div>
            </div>
          </div>
        </x-panel>
      </section>

      <section class="flex flex-col gap-3">
        <div class="flex flex-wrap items-baseline gap-x-3 gap-y-1 px-0.5">
          <h2 class="erica-section-title shrink-0">{{ __('This contact') }}</h2>

          <p class="erica-small">{{ __('Two things you can say about them now. Both are yours to change from their page later.') }}</p>
        </div>

        <x-panel>
          <div class="flex flex-col gap-4">
            @foreach ($viewModel->flags() as $flag)
              <div class="flex flex-col gap-1">
                <x-checkbox :id="$flag['id']" :checked="$flag['checked']">{{ $flag['name'] }}</x-checkbox>

                <p class="erica-caption pl-6.5">{{ $flag['help'] }}</p>
              </div>
            @endforeach
          </div>
        </x-panel>
      </section>

      @if ($viewModel->blocker())
        <x-notice tone="warning">{{ $viewModel->blocker() }}</x-notice>
      @endif
    </x-form>

    {{--
      Nothing disables the button for an empty name: the field is required, so the
      browser refuses the submission and points at the field, which says more than
      a button that has quietly gone grey. The button is only refused when the
      vault has nothing for a contact to start from, which is the one thing
      filling the form in cannot fix.
    --}}
    <div class="flex flex-wrap items-center gap-x-6 gap-y-3.5">
      <x-checkbox id="keep_going" form="contact-form" :checked="$values['keepGoing']">
        {{ __('Create another one after this') }}
      </x-checkbox>

      <div class="flex flex-wrap items-center gap-3 max-sm:w-full sm:ml-auto">
        <x-button.secondary :href="$viewModel->contactsUrl()" data-turbo="true">{{ __('Cancel') }}</x-button.secondary>

        <x-button form="contact-form" :disabled="! $viewModel->canCreate()">{{ __('Create the contact') }}</x-button>
      </div>
    </div>
  </div>
</x-shell>
