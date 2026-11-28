{{-- The form that writes somebody down. --}}
{{-- @var \App\ViewModels\Vaults\ContactFormViewModel $viewModel --}}
@php
  $vault = $viewModel->vault();
  $values = $viewModel->values();
  $ceiling = $viewModel->ceilingMessage();
@endphp
<x-shell :title="$viewModel->title()">
  <x-slot:breadcrumb>
    <x-breadcrumb.vault :vault="$vault" :items="[['label' => __('People'), 'url' => $viewModel->contactsUrl()],
        ['label' => $viewModel->title()],]" />
  </x-slot:breadcrumb>

  <x-slot:sidebar>
    <x-vault.sidebar :user="$viewModel->user()" :vault="$vault" current="contacts" />
  </x-slot:sidebar>

  <x-page-header :title="$viewModel->title()" />

  @if ($ceiling !== null)
    <x-paywall-prompt :message="$ceiling" id="contact-form-ceiling" />
  @endif

  <div class="flex flex-col gap-8">
    <x-form id="contact-form" method="post" :action="$viewModel->action()" class="flex flex-col gap-8">
      @if ($viewModel->showsTemplates())
        <x-page-section :title="__('What this contact starts from')" :description="__('The template decides which fields the contact page holds. The vault’s own template is used unless you pick another here. It can be changed on the contact afterwards.')">
          <x-panel>
            <x-select
              id="structure_version_id"
              :label="__('Template')"
              :error="$errors->get('structure_version_id')"
              class="max-w-105"
            >
              @foreach ($viewModel->templates() as $template)
                <option value="{{ $template['id'] }}" @selected($values['structureVersionId'] === (string) $template['id'])>{{ $template['name'] }}</option>
              @endforeach
            </x-select>
          </x-panel>
        </x-page-section>
      @endif

      <x-page-section :title="__('Name')">
        <x-panel>
          <div class="flex flex-col gap-6">
            <x-contact-name-editor :block="$viewModel->nameEditor()" :short-name="__('this contact')" />

            <div class="h-px bg-hairline"></div>

            <div class="grid gap-4 sm:grid-cols-2">
              <x-input
                id="nickname"
                :value="$values['nickname']"
                :label="__('Nickname')"
                :error="$errors->get('nickname')"
                maxlength="255"
                optional
              />

              <x-input
                id="maiden_name"
                :value="$values['maidenName']"
                :label="__('Name before marriage')"
                :error="$errors->get('maiden_name')"
                maxlength="255"
                optional
              />
            </div>
          </div>
        </x-panel>
      </x-page-section>

      <div class="flex max-w-130 flex-col gap-4.5">
        @foreach ($viewModel->flags() as $flag)
          <div class="flex flex-col gap-0.5">
            <label for="{{ $flag['id'] }}" class="flex cursor-pointer items-center gap-3.5">
              <x-switch
                :id="$flag['id']"
                :checked="$flag['checked']"
                :aria-describedby="$flag['id'].'-help'"
              />

              <span class="text-base font-semibold text-ink">{{ $flag['name'] }}</span>
            </label>

            <p id="{{ $flag['id'] }}-help" class="ps-13.5 text-sm text-pretty text-muted">{{ $flag['help'] }}</p>
          </div>
        @endforeach
      </div>

      @if ($viewModel->blocker())
        <x-notice tone="warning">{{ $viewModel->blocker() }}</x-notice>
      @endif
    </x-form>

    <div class="flex flex-wrap items-center gap-x-6 gap-y-3.5 border-t border-hairline pt-4.5">
      <x-checkbox id="keep_going" form="contact-form" :checked="$values['keepGoing']">
        {{ __('Create another one after this') }}
      </x-checkbox>

      <div class="flex flex-wrap items-center gap-3 max-sm:w-full sm:ms-auto" x-data="{ held: false }" @name-editor-busy.window="held = $event.detail">
        <p id="contact-form-held" x-show="held" x-cloak class="text-sm text-muted">{{ __('Finish the change to the name first, or cancel it.') }}</p>

        <x-button.secondary :href="$viewModel->contactsUrl()" data-turbo="true">{{ __('Cancel') }}</x-button.secondary>

        <x-button
          form="contact-form"
          :disabled="! $viewModel->canCreate() || $ceiling !== null"
          ::aria-disabled="held"
          :aria-describedby="$ceiling !== null ? 'contact-form-ceiling' : 'contact-form-held'"
        >{{ __('Create the contact') }}</x-button>
      </div>
    </div>
  </div>
</x-shell>
