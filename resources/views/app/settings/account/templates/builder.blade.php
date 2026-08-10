{{--
  Where a template is actually built: the sections it carries, the fields inside
  them, and one field open for editing.

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
<x-app-layout :title="$template->name">
  <x-slot:sidebar>
    <x-settings.sidebar :user="$viewModel->user()" current="templates" />
  </x-slot:sidebar>

  <x-slot:breadcrumb>
    <x-breadcrumb
      :items="[
        ['label' => __('Templates'), 'url' => route('settings.templates.index')],
        ['label' => $template->name],
      ]"
    />
  </x-slot:breadcrumb>

  <div id="builder" x-merge="replace" class="space-y-6">
    <div class="grid items-start gap-x-7 gap-y-4 sm:grid-cols-[minmax(0,1fr)_auto]">
      <div class="min-w-0 space-y-1.5">
        <div class="flex flex-wrap items-center gap-x-2.5 gap-y-1">
          <h1 class="text-2xl font-semibold tracking-tight text-ink">{{ $template->name }}</h1>

          <span class="rounded-sm border border-hairline bg-sunken px-2 py-0.5 text-xs font-semibold text-body">{{ $viewModel->statusLabel() }}</span>

          <span class="font-mono text-xs text-muted-soft">{{ $viewModel->versionLabel() }}</span>
        </div>

        <p class="text-sm text-muted">
          {{ $viewModel->savedLabel() }}

          <a href="{{ route('settings.templates.edit', $template) }}" data-turbo="true" class="text-brand hover:underline">{{ __('Rename it') }}</a>
        </p>
      </div>

      <div class="flex flex-wrap items-center gap-2.5 sm:justify-self-end">
        @if ($viewModel->isEditable())
          <x-form method="put" :action="route('settings.templateVersionPublication.update', [$template, $version])" x-target="builder">
            @if ($version?->previous_version_id !== null)
              <input type="hidden" name="change_note" value="{{ __('Changed in the builder.') }}" />
            @endif

            <x-button>{{ __('Publish this version') }}</x-button>
          </x-form>
        @else
          <x-form method="post" :action="route('settings.templateVersions.create', $template)" x-target="builder">
            <x-button>{{ __('Start a draft') }}</x-button>
          </x-form>
        @endif
      </div>
    </div>

    @unless ($viewModel->isEditable())
      <x-notice>
        {{ __('This version has been published, so it is frozen and cannot be changed. Start a draft to make one beside it. Every record on this version goes on rendering exactly what it does now.') }}
      </x-notice>
    @endunless

    @include('app.settings.account.templates._pages', [
      'viewModel' => $viewModel,
      'template' => $template,
      'isEditable' => $viewModel->isEditable(),
    ])

    <div class="grid items-start gap-6 lg:grid-cols-[minmax(0,1.35fr)_minmax(0,1fr)]">
      <div class="space-y-4" x-data="sortableRows" data-sortable>
        @forelse ($viewModel->sections() as $section)
          @include('app.settings.account.templates._section', [
            'section' => $section,
            'template' => $template,
            'isEditable' => $viewModel->isEditable(),
            'otherPages' => $viewModel->otherPages(),
          ])
        @empty
          <div class="max-w-140 space-y-1.5 rounded-xl border border-dashed border-hairline-strong bg-sunken p-5">
            <p class="text-sm font-semibold text-ink">{{ __('This page has nothing on it yet') }}</p>

            <p class="text-sm leading-relaxed text-muted">{{ __('A page holds sections, and a section holds fields. Add the first section to decide what this page is for.') }}</p>
          </div>
        @endforelse

        @if ($viewModel->isEditable())
          <div x-data="{ adding: false }">
            <button
              x-show="! adding"
              type="button"
              @click="adding = true; $nextTick(() => $refs.name.focus())"
              class="flex w-full cursor-pointer items-center justify-center gap-2.5 rounded-xl border border-dashed border-muted px-5 py-5 text-sm font-semibold text-ink transition-colors duration-150 hover:bg-hover"
            >
              <svg width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" aria-hidden="true">
                <line x1="8" y1="3.5" x2="8" y2="12.5"></line>
                <line x1="3.5" y1="8" x2="12.5" y2="8"></line>
              </svg>
              {{ $viewModel->selectedPage() === null ? __('Add a section') : __('Add a section to :page', ['page' => $viewModel->selectedPage()->label]) }}
            </button>

            <x-form
              x-cloak
              x-show="adding"
              method="post"
              :action="route('settings.templateSections.create', $template)"
              x-target="builder"
              class="flex flex-wrap items-end gap-3 rounded-xl border border-dashed border-muted p-4"
            >
              <input type="hidden" name="page" value="{{ $viewModel->selectedPage()?->uuid }}" />

              <x-input
                id="section-name"
                name="name"
                x-ref="name"
                :label="__('Name of the section')"
                :placeholder="__('Health')"
                :error="$errors->get('name')"
                class="max-w-72"
                maxlength="255"
                required
              />

              <div class="flex items-center gap-2">
                <x-button>{{ __('Add it') }}</x-button>

                <button type="button" @click="adding = false" class="cursor-pointer rounded-md px-2 py-1.5 text-sm text-muted transition-colors duration-150 hover:bg-hover hover:text-ink">{{ __('Cancel') }}</button>
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
      <p class="text-sm text-muted">{{ $viewModel->publishingNote() }}</p>

      @if ($viewModel->isEditable() && $version?->previous_version_id !== null)
        <div class="ml-auto">
          <x-modal.warning
            :title="__('Throw this draft away?')"
            :confirm="__('Throw it away')"
            :action="route('settings.templateVersions.destroy', [$template, $version])"
          >
            <x-slot:trigger>
              <button
                type="button"
                @click="open = true"
                class="cursor-pointer rounded-md border border-hairline-strong bg-canvas px-3 py-2 text-sm text-body transition-colors duration-150 hover:bg-hover hover:text-error"
              >{{ __('Discard the draft') }}</button>
            </x-slot:trigger>

            <p>{{ __('Everything changed since the last published version goes with it.') }}</p>

            <p>{{ __('What was published is untouched, and every record on it goes on as before.') }}</p>
          </x-modal.warning>
        </div>
      @endif
    </div>
  </div>
</x-app-layout>
