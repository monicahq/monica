{{-- The kinds of profile the account keeps: contacts, and the types it made itself. --}}
{{-- @var \App\ViewModels\Settings\Account\EntityTypes\EntityTypesViewModel $viewModel --}}
<x-settings.page
  :title="__('Contacts and more')"
  :user="$viewModel->user()"
  current="entity-types"
>
  <x-page-header :title="__('Contacts and more')" :description="__('You already have profiles for people. Create other kinds of profiles for the pets, companies, or things you want to remember. Each has its own information, organized into sections, just like a contact.')" />

  <section aria-label="{{ __('Kinds of profile') }}" class="flex flex-col gap-3 rounded-lg border-[1.5px] border-dashed border-hairline-strong p-4">
    <p class="flex flex-wrap items-center gap-x-2.5 gap-y-1 text-base">
      <span class="inline-flex items-center gap-1.5 font-semibold text-ink">
        <x-icon name="info" :size="16" />
        {{ __('Examples only') }}
      </span>

      <span class="text-pretty text-muted">{{ __('These previews show how profiles work. They aren’t in your vault.') }}</span>
    </p>

    <div aria-hidden="true" class="pointer-events-none grid grid-cols-[repeat(auto-fit,minmax(240px,1fr))] gap-4 opacity-85 select-none">
      @foreach ($viewModel->examples() as $example)
        <x-panel :title="$example['kind']" :icon="$example['icon']" :flush="true">
          <div class="flex flex-col gap-3.5 p-4">
            <div class="flex items-center gap-2.5">
              <x-avatar-initials :name="$example['name']" :size="36" />

              <span class="text-card-title text-ink">{{ $example['name'] }}</span>
            </div>

            <dl class="m-0 flex flex-col gap-2">
              @foreach ($example['fields'] as $field)
                <div class="grid grid-cols-[72px_minmax(0,1fr)] items-center gap-3">
                  <dt class="text-base text-muted">{{ $field }}</dt>
                  <dd class="m-0 h-2 rounded-sm border border-dashed border-hairline-strong bg-card"></dd>
                </div>
              @endforeach
            </dl>
          </div>

          <x-slot:action>
            <x-badge>{{ $example['tag'] }}</x-badge>
          </x-slot:action>

          <x-slot:footer class="text-muted">{{ $example['caption'] }}</x-slot:footer>
        </x-panel>
      @endforeach
    </div>
  </section>

  <section aria-label="{{ __('Create') }}" class="flex flex-wrap items-center gap-x-5 gap-y-3">
    <x-button :href="route('settings.entityTypes.new')" data-turbo="true" class="max-sm:w-full">
      <x-icon name="plus" :size="16" />
      {{ __('Create a profile type') }}
    </x-button>

    <p class="flex-[1_1_280px] text-base text-pretty text-muted">{{ __('Start with a kind, such as “Pets.” You’ll add individual pets, such as “Garfield,” afterward.') }}</p>
  </section>

  <x-page-section
    :title="__('Your profile types')"
    :description="$viewModel->hasTypes() ? __('Drag a row to change the order in the vault navigation.') : null"
  >
    <x-panel :flush="true">
      <div @class(['flex items-center gap-3 py-3.5 pr-4 pl-11', 'border-b border-hairline' => $viewModel->hasTypes()])>
        <span class="flex size-8 shrink-0 items-center justify-center rounded-sm bg-hover text-body" aria-hidden="true">
          <x-icon name="profile" :size="16" />
        </span>

        <div class="min-w-0 flex-1">
          <p class="flex flex-wrap items-center gap-x-2 gap-y-1">
            <span class="text-base font-semibold text-ink">{{ __('Contacts') }}</span>

            <x-badge>{{ __('Built in') }}</x-badge>
          </p>

          <p class="flex flex-wrap items-center gap-x-1.5 gap-y-1 text-xs text-muted">
            <span>{{ $viewModel->contacts()['countLabel'] }}</span>
            <span aria-hidden="true">·</span>
            <span class="inline-flex items-center gap-1">
              <x-icon name="lock" :size="14" />
              {{ __('Can’t be deleted') }}
            </span>
          </p>
        </div>
      </div>

      <div id="entity-types" x-merge="replace" x-data="sortableRows" data-sortable class="dim-when-busy">
        @foreach ($viewModel->types() as $type)
          @include('app.settings.account.entity-types._row', ['type' => $type])
        @endforeach
      </div>
    </x-panel>
  </x-page-section>
</x-settings.page>
