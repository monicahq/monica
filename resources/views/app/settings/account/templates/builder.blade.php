{{--
  Where a template is actually built: the sections it carries, the fields inside
  them, and one field open for editing.

  This is the one settings screen that asks for `wide`. It has two columns of its
  own, and the reading width the rest of them use would stack the inspector under
  the sections and put the field being edited a screen away from the list it was
  picked from.

  Everything saves as it is done. There is no save button and no unsaved state,
  which is how the rest of the settings screens work and the only way a screen
  this long stays trustworthy.

  What is open in the inspector travels in the query string, so the screen
  somebody is looking at has a URL. The whole builder is one ajax region, since
  most changes here move two things at once: adding a field changes the list on
  the left and opens it on the right.

  A published version opens read only. That is section 6 made visible rather
  than a limitation: records are relying on what it says.

  @var \App\ViewModels\Settings\Account\Templates\TemplateBuilderViewModel $viewModel
--}}
@php
  $template = $viewModel->template();
  $version = $viewModel->version();
@endphp
<x-shell :title="$template->name" wide>
  <x-slot:bar>
    <x-button.bar :href="route('settings.templates.index')" data-turbo="true" size="small">
      <x-icon name="chevron-right" :size="15" :stroke-width="1.7" class="rotate-180" />
      {{ __('Back to the templates') }}
    </x-button.bar>
  </x-slot:bar>

  <x-slot:dock>
    <x-dock :user="$viewModel->user()" />
  </x-slot:dock>

  <div id="builder" x-merge="replace" class="flex flex-col gap-6">
    <header class="flex flex-wrap items-start justify-between gap-x-6 gap-y-4">
      <div class="flex min-w-0 flex-col gap-2">
        <div class="flex flex-wrap items-center gap-x-3 gap-y-1.5">
          <h1 class="font-display text-page-title text-ink">{{ $template->name }}</h1>

          <x-badge :tone="$viewModel->isEditable() ? 'warning' : 'success'">{{ $viewModel->statusLabel() }}</x-badge>

          <code class="font-mono text-[12.5px] text-muted">{{ $viewModel->versionLabel() }}</code>
        </div>

        <p class="text-sm text-pretty text-muted">
          {{ $viewModel->savedLabel() }}

          <x-link :href="route('settings.templates.edit', $template)" turbo>{{ __('Rename it') }}</x-link>
        </p>
      </div>

      {{--
        Sharing sits beside publishing rather than in a menu, because it is the
        one thing somebody does with a template once it is finished, and a
        template nobody can get out of the account it was built in is a template
        that dies with the account. It is offered only on the version the
        template actually stands behind.
      --}}
      <div class="flex flex-wrap items-center gap-2.5 max-sm:w-full">
        @if ($viewModel->isShareable())
          <x-button.secondary :href="route('settings.templateShare.index', $template)" data-turbo="true" class="max-sm:w-full">
            <x-slot:icon>
              <x-icon name="relationship" :size="16" :stroke-width="1.6" />
            </x-slot:icon>

            {{ __('Share this template') }}
          </x-button.secondary>
        @endif

        {{--
          A section arrives on the page that is open, which is why the button is
          here rather than on a screen that would have to ask which page. Only a
          draft takes one: a published version is frozen, and a section landing
          in one would change what records already render.
        --}}
        @if ($viewModel->isEditable() && $viewModel->selectedPage() !== null)
          <x-button.secondary
            :href="route('settings.sectionImport.new', [$template, $viewModel->selectedPage()])"
            data-turbo="true"
            class="max-sm:w-full"
          >
            <x-slot:icon>
              <x-icon name="file" :size="16" :stroke-width="1.6" />
            </x-slot:icon>

            {{ __('Import a section') }}
          </x-button.secondary>
        @endif

        @if ($viewModel->isEditable())
          <x-form method="put" :action="route('settings.templateVersionPublication.update', [$template, $version])" x-target="builder" class="max-sm:w-full">
            @if ($version?->previous_version_id !== null)
              <input type="hidden" name="change_note" value="{{ __('Changed in the builder.') }}" />
            @endif

            <x-button class="max-sm:w-full">{{ __('Publish this version') }}</x-button>
          </x-form>
        @else
          <x-form method="post" :action="route('settings.templateVersions.create', $template)" x-target="builder" class="max-sm:w-full">
            <x-button class="max-sm:w-full">{{ __('Start a draft') }}</x-button>
          </x-form>
        @endif
      </div>
    </header>

    @unless ($viewModel->isEditable())
      <x-notice tone="info" :title="__('This version is published, so it is frozen')">
        {{ __('Start a draft to make one beside it. Every record on this version goes on rendering exactly what it does now.') }}
      </x-notice>
    @endunless

    @include('app.settings.account.templates._pages', [
      'viewModel' => $viewModel,
      'template' => $template,
      'isEditable' => $viewModel->isEditable(),
    ])

    <div class="grid items-start gap-6 lg:grid-cols-[minmax(0,1.35fr)_minmax(0,1fr)]">
      <div class="flex flex-col gap-4" x-data="sortableRows" data-sortable>
        @forelse ($viewModel->sections() as $section)
          @include('app.settings.account.templates._section', [
            'section' => $section,
            'template' => $template,
            'isEditable' => $viewModel->isEditable(),
            'isShareable' => $viewModel->isShareable(),
            'otherPages' => $viewModel->otherPages(),
          ])
        @empty
          <x-panel variant="inset" class="border-dashed">
            <p class="text-base font-semibold text-ink">{{ __('This page has nothing on it yet') }}</p>

            <p class="mt-1.5 text-sm text-pretty text-muted">{{ __('A page holds sections, and a section holds fields. Add the first section to decide what this page is for.') }}</p>
          </x-panel>
        @endforelse

        @if ($viewModel->isEditable())
          <div x-data="{ adding: false }">
            <button
              x-show="! adding"
              type="button"
              @click="adding = true; $nextTick(() => $refs.name.focus())"
              class="flex w-full cursor-pointer items-center justify-center gap-2.5 rounded-lg border border-dashed border-hairline-strong px-5 py-5 text-sm font-semibold text-ink transition-colors duration-[110ms] hover:bg-hover"
            >
              <x-icon name="plus" :size="16" :stroke-width="1.6" />
              {{ $viewModel->selectedPage() === null ? __('Add a section') : __('Add a section to :page', ['page' => $viewModel->selectedPage()->label]) }}
            </button>

            <x-form
              x-cloak
              x-show="adding"
              method="post"
              :action="route('settings.templateSections.create', $template)"
              x-target="builder"
              class="flex flex-wrap items-end gap-3 rounded-lg border border-dashed border-hairline-strong p-4"
            >
              <input type="hidden" name="page" value="{{ $viewModel->selectedPage()?->uuid }}" />

              <x-input
                id="section-name"
                name="name"
                x-ref="name"
                size="small"
                :label="__('Name of the section')"
                :placeholder="__('Health')"
                :error="$errors->get('name')"
                class="max-w-72"
                maxlength="255"
                required
              />

              <div class="flex items-center gap-2">
                <x-button size="small">{{ __('Add it') }}</x-button>

                <x-button.ghost size="small" type="button" @click="adding = false">{{ __('Cancel') }}</x-button.ghost>
              </div>
            </x-form>
          </div>
        @endif
      </div>

      @include('app.settings.account.templates._inspector', [
        'viewModel' => $viewModel,
        'template' => $template,
      ])
    </div>

    <div class="flex flex-wrap items-center gap-4 border-t border-hairline pt-4">
      <p class="max-w-[64ch] text-sm text-pretty text-muted">{{ $viewModel->publishingNote() }}</p>

      @if ($viewModel->isEditable() && $version?->previous_version_id !== null)
        <div class="ml-auto">
          <x-modal.warning
            :title="__('Throw this draft away?')"
            :confirm="__('Throw it away')"
            :action="route('settings.templateVersions.destroy', [$template, $version])"
          >
            <x-slot:trigger>
              <x-button.secondary size="small" type="button" @click="open = true" class="hover:text-error">
                {{ __('Discard the draft') }}
              </x-button.secondary>
            </x-slot:trigger>

            <p>{{ __('Everything changed since the last published version goes with it.') }}</p>

            <p>{{ __('What was published is untouched, and every record on it goes on as before.') }}</p>
          </x-modal.warning>
        </div>
      @endif
    </div>
  </div>
</x-shell>
