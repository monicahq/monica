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
    @include('app.vaults.contacts._favourite', ['viewModel' => $viewModel])

    <div class="min-w-60 flex-1">
      <div class="flex flex-wrap items-center gap-3">
        <h1 class="text-page-title text-balance text-ink">{{ $viewModel->title() }}</h1>

        @if ($viewModel->isMe())
          <x-badge tone="accent">{{ __('You') }}</x-badge>
        @endif
      </div>

      @if ($viewModel->isArchived())
        <p class="mt-2">
          <x-badge>{{ __('Archived') }}</x-badge>
        </p>
      @endif

      @include('app.vaults.contacts._facts', ['viewModel' => $viewModel])
    </div>

    <div class="ml-auto flex flex-wrap items-center gap-2.5">
      <x-button :href="$viewModel->editUrl()" data-turbo="true" size="small">
        <x-slot:icon>
          <x-icon name="profile" :size="15" :stroke-width="1.6" />
        </x-slot:icon>
        {{ __('Edit contact') }}
      </x-button>

      <div x-data="contactMarkdownCopy({ url: @js($viewModel->markdownUrl()), copied: @js(__('Copied')), failed: @js(__('Could not copy. Download the file instead.')) })" class="contents">
        <x-actions-menu :label="__('More actions for :name', ['name' => $viewModel->contact()->name])">
          <x-actions-menu.item :href="$viewModel->shareLinksUrl()" data-turbo="true">
            {{ $viewModel->shareLine() }}
          </x-actions-menu.item>

          <x-actions-menu.item :href="$viewModel->mergeUrl()" data-turbo="true">
            {{ __('Merge') }}
          </x-actions-menu.item>

          <div role="separator" class="-mx-1 my-1 h-px bg-hairline"></div>

          <x-actions-menu.item :href="$viewModel->markdownUrl()" download data-no-instant>
            {{ __('Export as Markdown') }}
          </x-actions-menu.item>

          <button type="button" role="menuitem" @click="menuOpen = false; $refs.menuTrigger.focus(); copyMarkdown()" class="flex w-full cursor-pointer items-center gap-2.5 min-h-8 rounded-md px-2.5 text-left font-sans text-base text-ink transition-colors hover:bg-hover focus-ring-inset">
            {{ __('Copy as Markdown') }}
          </button>

          @if ($viewModel->vCardUrl() !== null)
            <x-actions-menu.item :href="$viewModel->vCardUrl()" download data-no-instant>
              {{ __('Export as vCard') }}
            </x-actions-menu.item>
          @endif

          <x-actions-menu.item :href="$viewModel->versionHistoryUrl()" data-turbo="true">
            {{ __('Version history') }}
          </x-actions-menu.item>
        </x-actions-menu>

        <p role="status" class="text-sm text-muted" x-text="message"></p>
      </div>
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
        @include('app.vaults.contacts._timeline-section', ['viewModel' => $viewModel])
      </div>

      <div class="flex flex-col gap-5">
        @include('app.vaults.contacts._immediate-family', ['family' => $viewModel->immediateFamily()])

        @if ($groups = $viewModel->groups())
          @include('app.vaults.contacts._group-memberships', ['groups' => $groups])
        @endif

        @include('app.vaults.contacts._reminders', ['deadlines' => $viewModel->deadlines()])

        @if ($loans = $viewModel->loans())
          @include('app.vaults.contacts._loans', ['loans' => $loans])
        @endif
      </div>
    </div>
  @else
    @include('app.vaults.contacts._page')
  @endif
</x-shell>
