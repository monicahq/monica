{{--
  Everything the account has to describe a record with, split into what somebody
  here built and what the product supplied.

  The split is the point of the screen: a supplied template cannot be edited in
  place, so which side of the line a template falls on is what tells somebody
  whether to open it or copy it first.

  The filter is a plain GET form so the narrowed list has a URL, and the region
  it redraws is the one both the chips and the box aim at.

  @var \App\ViewModels\Settings\Account\Templates\TemplatesViewModel $viewModel
--}}
<x-app-layout :title="__('Templates')">
  <x-slot:sidebar>
    <x-settings.sidebar :user="$viewModel->user()" current="templates" />
  </x-slot:sidebar>

  <x-slot:breadcrumb>
    <nav class="text-sm text-muted" aria-label="{{ __('Breadcrumb') }}">
      {{ __('Settings') }}
      <span class="px-1 text-placeholder" aria-hidden="true">/</span>
      <span class="text-ink" aria-current="page">{{ __('Templates') }}</span>
    </nav>
  </x-slot:breadcrumb>

  <div class="grid items-start gap-x-7 gap-y-4 sm:grid-cols-[minmax(0,1fr)_auto]">
    <x-page-header
      :title="__('Templates')"
      :description="__('A template decides what a record looks like: which sections it carries, and which fields sit in them. Records keep the version they were made with until you move them.')"
      class="max-w-140"
    />

    <x-button :href="route('settings.templates.new')" data-turbo="true" class="max-sm:w-full sm:justify-self-end">
      {{ __('Create a template') }}
    </x-button>
  </div>

  <div id="templates" x-merge="replace" class="space-y-6">
    <div class="flex flex-wrap items-center gap-2">
      <form method="get" action="{{ route('settings.templates.index') }}" x-target="templates" class="flex items-center gap-2">
        <input type="hidden" name="filter" value="{{ $viewModel->filter() }}" />

        <x-input
          id="query"
          name="query"
          :value="$viewModel->query()"
          :placeholder="__('Filter by name or slug')"
          :aria-label="__('Filter the templates')"
          class="w-56"
        />

        <x-button.secondary>{{ __('Filter') }}</x-button.secondary>
      </form>

      @foreach ($viewModel->filters() as $chip)
        <a
          href="{{ route('settings.templates.index', ['filter' => $chip['key'], 'query' => $viewModel->query()]) }}"
          x-target="templates"
          @if ($chip['isCurrent']) aria-current="true" @endif
          class="rounded-md border px-2.5 py-1.5 text-sm transition-colors duration-150 {{ $chip['isCurrent'] ? 'border-primary bg-sunken text-ink' : 'border-hairline-strong bg-canvas text-muted hover:bg-hover' }}"
        >{{ $chip['label'] }}</a>
      @endforeach

      <span class="ml-auto text-xs text-muted-soft">{{ $viewModel->summary() }}</span>
    </div>

    <x-box :title="__('Your templates')" padding="p-0">
      <x-slot:help>
        <x-help :title="__('Templates')">
          {{ __('A template holds no fields of its own. It holds versions, and a version is an ordered set of sections. That is what lets a record keep rendering the way it always did while you change the template around it.') }}
        </x-help>
      </x-slot:help>

      <x-slot:description>
        <p>{{ __('Yours to change, version and publish.') }}</p>
      </x-slot:description>

      @forelse ($viewModel->ownTemplates() as $template)
        @include('app.settings.account.templates._row', ['template' => $template])
      @empty
        <x-empty-state :title="__('Nothing here yet')">
          <x-slot:icon>
            <x-entity-icon name="info" :size="20" />
          </x-slot:icon>

          {{ __('Create one, or copy a supplied template and change what you need.') }}
        </x-empty-state>
      @endforelse
    </x-box>

    @if ($viewModel->suppliedTemplates() !== [])
      <x-box :title="__('Supplied with Monica')" padding="p-0">
        <x-slot:description>
          <p>{{ __('They work out of the box. Copy one to change it.') }}</p>
        </x-slot:description>

        @foreach ($viewModel->suppliedTemplates() as $template)
          @include('app.settings.account.templates._row', ['template' => $template])
        @endforeach
      </x-box>
    @endif
  </div>

  <p class="text-xs leading-relaxed text-muted-soft">{{ __('Deleting a template is refused while a record still points at one of its versions. Turn it off instead and it stops being offered to anything new.') }}</p>
</x-app-layout>
