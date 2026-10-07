{{-- One person, and everything written down about them. --}}
{{-- @var \App\ViewModels\Vaults\ContactViewModel $viewModel --}}
@php
  $vault = $viewModel->vault();
@endphp
<x-shell :title="$viewModel->title()" wide>
  <x-slot:breadcrumb>
    <x-breadcrumb.vault :vault="$vault" :items="[['label' => __('People'), 'url' => $viewModel->peopleUrl()],
        ['label' => $viewModel->title()],]" />
  </x-slot:breadcrumb>

  <x-slot:sidebar>
    <x-vault.sidebar :user="$viewModel->user()" :vault="$vault" current="contacts" />
  </x-slot:sidebar>

  <header class="flex flex-wrap items-start gap-5.5">
    <div
      id="contact-favourite"
      x-merge="replace"
      class="relative shrink-0 rounded-full border-2 p-0.75 transition-colors duration-(--duration-fast) ease-out {{ $viewModel->isFavourite() ? 'border-warning-ring' : 'border-transparent' }}"
    >
      <x-contact-avatar :name="$viewModel->title()" :url="$viewModel->avatarUrl()" :size="104" />

      <x-form
        method="put"
        :action="$viewModel->favouriteUrl()"
        x-target="contact-favourite"
        class="absolute -right-0.5 -bottom-0.5"
      >
        <input type="hidden" name="favourite" value="{{ $viewModel->isFavourite() ? 0 : 1 }}" />

        <button
          type="submit"
          aria-pressed="{{ $viewModel->isFavourite() ? 'true' : 'false' }}"
          aria-label="{{ $viewModel->favouriteLabel() }}"
          title="{{ $viewModel->favouriteLabel() }}"
          class="flex size-6.5 cursor-pointer items-center justify-center rounded-full shadow-panel transition-colors duration-(--duration-fast) ease-out focus-ring {{ $viewModel->isFavourite() ? 'border-2 border-canvas bg-warning text-canvas hover:bg-warning/90' : 'border border-hairline-strong bg-canvas text-muted-soft hover:bg-warning-tint hover:text-warning' }}"
        >
          <x-icon :name="$viewModel->isFavourite() ? 'star-filled' : 'star'" :size="14" :stroke-width="1.6" />
        </button>
      </x-form>
    </div>

    <div class="min-w-60 flex-1">
      <h1 class="text-page-title text-balance text-ink">{{ $viewModel->title() }}</h1>

      @if ($viewModel->isArchived())
        <p class="mt-2">
          <x-badge>{{ __('Archived') }}</x-badge>
        </p>
      @endif

      <ul id="contact-facts" x-merge="replace" class="mt-4 flex list-none flex-wrap gap-x-5.5 gap-y-2">
        @foreach ($viewModel->headerFacts() as $fact)
          <li class="flex items-center gap-1.75 text-sm text-body">
            <x-icon :name="$fact['icon']" :size="16" :stroke-width="1.5" class="text-muted" />
            @if (isset($fact['url']))
              <a href="{{ $fact['url'] }}" data-turbo="true" class="rounded-xs text-body no-underline hover:text-link focus-ring">
                {{ $fact['label'] }} <b class="font-semibold text-ink">{{ $fact['value'] }}</b>
              </a>
            @else
              {{ $fact['label'] }} <b class="font-semibold text-ink">{{ $fact['value'] }}</b>
            @endif

            @if ($fact['reminder'])
              <x-tooltip :text="$fact['reminder']">
                <x-icon name="alarm" :size="15" class="text-muted" />
              </x-tooltip>
            @endif
          </li>
        @endforeach
      </ul>
    </div>

    <div class="ml-auto flex flex-wrap items-center gap-2.5">
      <x-button :href="$viewModel->editUrl()" data-turbo="true" size="small">
        <x-slot:icon>
          <x-icon name="profile" :size="15" :stroke-width="1.6" />
        </x-slot:icon>
        {{ __('Edit contact') }}
      </x-button>

      <x-actions-menu :label="__('More actions for :name', ['name' => $viewModel->contact()->name])">
        <x-actions-menu.item :href="$viewModel->shareLinksUrl()" data-turbo="true">
          {{ $viewModel->shareLine() }}
        </x-actions-menu.item>

        <x-actions-menu.item :href="$viewModel->mergeUrl()" data-turbo="true">
          {{ __('Merge') }}
        </x-actions-menu.item>

        <div role="separator" class="-mx-1 my-1 h-px bg-hairline"></div>

        <x-actions-menu.item :href="$viewModel->vCardUrl()" download>
          {{ __('Export as vCard') }}
        </x-actions-menu.item>

        <x-actions-menu.item :href="$viewModel->versionHistoryUrl()" data-turbo="true">
          {{ __('Version history') }}
        </x-actions-menu.item>
      </x-actions-menu>
    </div>

  </header>

  @if ($viewModel->undoMergeUrl() !== null)
    <x-notice tone="quiet" :title="__('This record absorbed another')">
      <p class="text-sm">{{ $viewModel->undoMergeLine() }}</p>

      <x-form method="delete" :action="$viewModel->undoMergeUrl()" class="mt-2">
        <x-button.secondary type="submit" size="small">
          {{ __('Undo the merge') }}
        </x-button.secondary>
      </x-form>
    </x-notice>
  @endif

  <x-tabs>
    @foreach ($viewModel->pages() as $page)
      <x-tabs.item
        :href="$page['url']"
        :selected="$page['isSelected']"
        :icon="$page['icon']"
        :count="$page['count']"
        :count-label="$page['count'] === null ? null : trans_choice(':count entry on this page|:count entries on this page', $page['count'])"
        data-turbo="true"
      >{{ $page['label'] }}</x-tabs.item>
    @endforeach
  </x-tabs>

  @if ($update = $viewModel->templateUpdate())
    <x-notice tone="info" icon="file" :title="$update['title']">
      {{ $update['body'] }}

      <x-slot:action>
        <x-form method="put" :action="$update['applyUrl']">
          <x-button size="small">{{ $update['label'] }}</x-button>
        </x-form>
      </x-slot:action>
    </x-notice>
  @endif

  @if ($viewModel->onVersionHistory())
    @include('app.vaults.contacts._version-history')
  @elseif ($viewModel->onRelationships())
    @include('app.vaults.contacts._relationships', [
      'relationships' => $viewModel->relationships(),
      'tree' => $viewModel->familyTree(),
    ])
  @elseif ($viewModel->onOverview())
    <div class="grid items-start gap-5 lg:grid-cols-[minmax(0,1.5fr)_minmax(0,1fr)]">
      <div class="flex flex-col gap-5">
        <section
          id="contact-timeline"
          x-merge="replace"
          x-data="lifeEventPanel(@js($viewModel->lifeEventForm()->projection()))"
          data-life-event-panel
          aria-labelledby="contact-timeline-title"
          class="flex flex-col gap-5"
        >
          <div class="flex flex-wrap items-end justify-between gap-3 px-0.5">
            <h2 id="contact-timeline-title" class="text-section-title text-ink">{{ __('Timeline') }}</h2>

            <x-button.secondary
              type="button"
              x-ref="addButton"
              x-on:click="show()"
              x-bind:aria-expanded="open ? 'true' : 'false'"
              :aria-controls="$viewModel->lifeEventForm()->isRendered() ? 'life-event-panel' : null"
              x-bind:disabled="open"
            >
              <x-slot:icon><x-icon name="plus" :size="16" /></x-slot:icon>
              {{ __('Add a life event') }}
            </x-button.secondary>
          </div>

          @if ($deleted = $viewModel->deletedLifeEvent())
            <div x-data="{ shown: true }" x-show="shown">
              <x-notice tone="quiet" icon="check-circle">
                {{ $deleted['line'] }}

                <x-slot:action>
                  <div class="flex items-center gap-2">
                    <x-form method="put" :action="$deleted['restoreUrl']" x-target="contact-timeline">
                      <x-button.secondary size="small" x-autofocus>{{ __('Undo') }}</x-button.secondary>
                    </x-form>

                    <button type="button" x-on:click="shown = false" class="focus-ring flex size-7 items-center justify-center rounded-md text-muted hover:bg-hover hover:text-ink" aria-label="{{ __('Dismiss') }}">
                      <x-icon name="close" :size="14" />
                    </button>
                  </div>
                </x-slot:action>
              </x-notice>
            </div>
          @endif

          @if ($viewModel->lifeEventForm()->isRendered() && ! ($viewModel->lifeEventForm()->isEditing() && $viewModel->timeline()->lists((int) $viewModel->lifeEventForm()->editingId())))
            @include('app.vaults.contacts._life-event-panel', ['form' => $viewModel->lifeEventForm()])
          @endif

          @include('app.vaults.contacts._timeline', ['timeline' => $viewModel->timeline(), 'form' => $viewModel->lifeEventForm()])
        </section>
      </div>

      <div class="flex flex-col gap-5">
        @include('app.vaults.contacts._immediate-family', ['family' => $viewModel->immediateFamily()])

        @include('app.vaults.contacts._reminders', ['deadlines' => $viewModel->deadlines()])
      </div>
    </div>
  @else
    @include('app.vaults.contacts._page')
  @endif
</x-shell>
