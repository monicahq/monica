{{-- Everything the account describes a record with, split into what it built and what we supplied. --}}
{{-- @var \App\ViewModels\Settings\Account\Templates\TemplatesViewModel $viewModel --}}
<x-settings.page
  :title="__('Templates')"
  :user="$viewModel->user()"
  current="templates"
>
  <x-page-header :title="__('Templates')" :description="__('A template decides what a record shows: which sections it carries, and which fields are in them. Records keep the version they were made with until you move them.')">
    <x-slot:actions>
      <x-button.secondary :href="route('settings.templateImport.new')" data-turbo="true" class="max-sm:w-full">
        {{ __('Import a file') }}
      </x-button.secondary>

      <x-button :href="route('settings.templates.new')" data-turbo="true" class="max-sm:w-full">
        {{ __('Create a template') }}
      </x-button>
    </x-slot:actions>
  </x-page-header>

  <div id="templates" x-merge="replace" class="flex flex-col gap-8">
    <div class="flex flex-wrap items-center gap-2.5">
      <form method="get" action="{{ route('settings.templates.index') }}" x-target="templates" class="flex items-center gap-2.5">
        <input type="hidden" name="filter" value="{{ $viewModel->filter() }}" />

        <x-input
          id="query"
          name="query"
          size="small"
          :value="$viewModel->query()"
          :placeholder="__('Filter by name or slug')"
          :aria-label="__('Filter the templates')"
          class="w-56"
        />

        <x-button.secondary size="small">{{ __('Filter') }}</x-button.secondary>
      </form>

      @foreach ($viewModel->filters() as $chip)
        <x-chip
          :href="route('settings.templates.index', ['filter' => $chip['key'], 'query' => $viewModel->query()])"
          :selected="$chip['isCurrent']"
          x-target="templates"
        >{{ $chip['label'] }}</x-chip>
      @endforeach

      <span class="ml-auto text-xs text-pretty text-muted">{{ $viewModel->summary() }}</span>
    </div>

    <x-page-section :title="__('Your templates')" :description="__('Yours to change, version and publish.')">
      <x-panel :flush="true">
        @forelse ($viewModel->ownTemplates() as $template)
          @include('app.settings.account.templates._row', ['template' => $template])
        @empty
          <x-empty-state :title="__('Nothing here yet')">
            <x-slot:icon>
              <x-icon name="file" :size="30" :stroke-width="1.4" />
            </x-slot:icon>

            {{ __('Create one, or copy a supplied template and change what you need.') }}
          </x-empty-state>
        @endforelse
      </x-panel>
    </x-page-section>

    @if ($viewModel->suppliedTemplates() !== [])
      <x-page-section :title="__('Supplied with Monica')" :description="__('They work as they are. Copy one to change it.')">
        <x-panel :flush="true">
          @foreach ($viewModel->suppliedTemplates() as $template)
            @include('app.settings.account.templates._row', ['template' => $template])
          @endforeach
        </x-panel>
      </x-page-section>
    @endif
  </div>

  <p class="px-0.5 text-xs text-pretty text-muted">{{ __('Deleting a template is refused while a record still points at one of its versions. Turn it off instead and it stops being offered to anything new.') }}</p>
</x-settings.page>
