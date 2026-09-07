{{-- The form that writes somebody down. --}}
{{-- @var \App\ViewModels\Vaults\ContactFormViewModel $viewModel --}}
@use('App\Enums\NameOrderEnum')
@php
  $vault = $viewModel->vault();
  $values = $viewModel->values();
@endphp
<x-shell :title="$viewModel->title()">
  <x-slot:breadcrumb>
    <x-breadcrumb :items="[['label' => $vault->name, 'url' => route('vaults.show', $vault)], ['label' => __('People'), 'url' => $viewModel->contactsUrl()],
        ['label' => $viewModel->title()],]" />
  </x-slot:breadcrumb>

  <x-slot:sidebar>
    <x-vault.sidebar :user="$viewModel->user()" :vault="$vault" current="contacts" />
  </x-slot:sidebar>

  <header class="flex flex-col gap-2">
    <h1 class="text-page-title text-ink">{{ $viewModel->title() }}</h1>

    <p class="max-w-[56ch] text-base text-pretty text-body">{{ $viewModel->description() }}</p>
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
            <h2 class="shrink-0 text-section-title text-ink">{{ __('What this contact starts from') }}</h2>

            <p class="text-sm text-pretty text-muted">{{ __('The template decides which fields the contact page holds. The vault’s own template is used unless you pick another here. It can be changed on the contact afterwards.') }}</p>
          </div>

          <x-panel>
            <x-select
              id="template_version_id"
              :label="__('Template')"
              :error="$errors->get('template_version_id')"
              class="max-w-105"
            >
              @foreach ($viewModel->templates() as $template)
                <option value="{{ $template['id'] }}" @selected($values['templateVersionId'] === (string) $template['id'])>{{ $template['name'] }}</option>
              @endforeach
            </x-select>
          </x-panel>
        </section>
      @endif

      <section class="flex flex-col gap-3">
        <div class="flex flex-wrap items-baseline gap-x-3 gap-y-1 px-0.5">
          <h2 class="shrink-0 text-section-title text-ink">{{ __('Name') }}</h2>

          <p class="text-sm text-pretty text-muted">{{ __('Only the first name is needed. The rest is there for the people whose name does not fit in two boxes.') }}</p>
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

            <x-select
              id="name_order"
              :label="__('Name order')"
              :error="$errors->get('name_order')"
              x-model="nameOrder"
              optional
              class="max-w-105"
            >
              @foreach ($viewModel->nameOrders() as $order)
                <option value="{{ $order['value'] }}" @selected($values['nameOrder'] === $order['value'])>{{ $order['label'] }}</option>
              @endforeach

              <x-slot:help>
                <span x-show="written === ''">{{ __('How this contact is written and sorted.') }}</span>
                <span x-cloak x-show="written !== ''">{{ __('Written as') }} <span class="font-semibold text-ink" x-text="written"></span></span>
              </x-slot:help>
            </x-select>
          </div>
        </x-panel>
      </section>

      <section class="flex flex-col gap-3">
        <div class="flex flex-wrap items-baseline gap-x-3 gap-y-1 px-0.5">
          <h2 class="shrink-0 text-section-title text-ink">{{ __('This contact') }}</h2>

          <p class="text-sm text-pretty text-muted">{{ __('Two things you can say about them now. Both are yours to change from their page later.') }}</p>
        </div>

        <x-panel>
          <div class="flex flex-col gap-4">
            @foreach ($viewModel->flags() as $flag)
              <div class="flex flex-col gap-1">
                <x-checkbox :id="$flag['id']" :checked="$flag['checked']">{{ $flag['name'] }}</x-checkbox>

                <p class="text-xs text-pretty text-muted pl-6.5">{{ $flag['help'] }}</p>
              </div>
            @endforeach
          </div>
        </x-panel>
      </section>

      @if ($viewModel->blocker())
        <x-notice tone="warning">{{ $viewModel->blocker() }}</x-notice>
      @endif
    </x-form>

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
