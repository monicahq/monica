{{--
  Everything the account has to describe a record with, split into what somebody
  here built and what the product supplied.

  The split is the point of the screen: a supplied template cannot be edited in
  place, so which side of the line a template falls on is what tells somebody
  whether to open it or copy it first.

  The filter is a plain GET form so the narrowed list has a URL, and the region
  it redraws is the one both the chips and the panels aim at.

  @var \App\ViewModels\Settings\Account\Templates\TemplatesViewModel $viewModel
--}}
<x-shell :title="__('Templates')">
  <x-slot:bar>
    <a href="{{ route('settings.index') }}" data-turbo="true" class="erica-btn erica-btn--secondary erica-btn--sm">
      <x-icon name="chevron-right" :size="15" :stroke-width="1.7" class="rotate-180" />
      {{ __('Back to settings') }}
    </a>
  </x-slot:bar>

  <x-slot:dock>
    <x-dock :user="$viewModel->user()" />
  </x-slot:dock>

  <header class="flex flex-wrap items-start justify-between gap-x-6 gap-y-4">
    <div class="flex min-w-0 flex-col gap-2">
      <h1 class="erica-page-title">{{ __('Templates') }}</h1>

      <p class="erica-lede max-w-[56ch]">{{ __('A template decides what a record looks like: which sections it carries, and which fields sit in them. Records keep the version they were made with until you move them.') }}</p>
    </div>

    <x-button :href="route('settings.templates.new')" data-turbo="true" class="max-sm:w-full">
      {{ __('Create a template') }}
    </x-button>
  </header>

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

      <span class="erica-caption ml-auto">{{ $viewModel->summary() }}</span>
    </div>

    <section class="flex flex-col gap-3">
      <div class="flex flex-wrap items-baseline gap-x-3 gap-y-1 px-0.5">
        <h2 class="erica-section-title shrink-0">{{ __('Your templates') }}</h2>

        <p class="erica-small">{{ __('Yours to change, version and publish.') }}</p>
      </div>

      <x-panel :flush="true">
        @forelse ($viewModel->ownTemplates() as $template)
          @include('app.settings.account.templates._row', ['template' => $template])
        @empty
          <x-empty-state :title="__('Nothing here yet')" hue="var(--indigo)" tint="var(--indigo-tint)">
            <x-slot:icon>
              <x-icon name="file" :size="30" :stroke-width="1.4" />
            </x-slot:icon>

            {{ __('Create one, or copy a supplied template and change what you need.') }}
          </x-empty-state>
        @endforelse
      </x-panel>
    </section>

    @if ($viewModel->suppliedTemplates() !== [])
      <section class="flex flex-col gap-3">
        <div class="flex flex-wrap items-baseline gap-x-3 gap-y-1 px-0.5">
          <h2 class="erica-section-title shrink-0">{{ __('Supplied with Monica') }}</h2>

          <p class="erica-small">{{ __('They work out of the box. Copy one to change it.') }}</p>
        </div>

        <x-panel :flush="true">
          @foreach ($viewModel->suppliedTemplates() as $template)
            @include('app.settings.account.templates._row', ['template' => $template])
          @endforeach
        </x-panel>
      </section>
    @endif
  </div>

  <p class="erica-caption px-0.5">{{ __('Deleting a template is refused while a record still points at one of its versions. Turn it off instead and it stops being offered to anything new.') }}</p>
</x-shell>
