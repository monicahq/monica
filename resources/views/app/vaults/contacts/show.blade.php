{{-- One person, and everything written down about them. --}}
{{-- @var \App\ViewModels\Vaults\ContactViewModel $viewModel --}}
@php
  $vault = $viewModel->vault();
  $contact = $viewModel->contact();
  $unbuilt = $viewModel->unbuilt();

  $inert = 'inline-flex h-8.5 cursor-default items-center gap-2 rounded-md border border-hairline px-3.25 '
    .'font-sans text-[13.5px] font-semibold text-placeholder';
@endphp
<x-shell :title="$viewModel->title()" wide>
  <x-slot:breadcrumb>
    <x-breadcrumb :items="[['label' => $vault->name, 'url' => route('vaults.show', $vault)], ['label' => __('People'), 'url' => $viewModel->peopleUrl()],
        ['label' => $viewModel->title()],]" />
  </x-slot:breadcrumb>

  <x-slot:sidebar>
    <x-vault.sidebar :user="$viewModel->user()" :vault="$vault" current="contacts" />
  </x-slot:sidebar>

  <a
    href="{{ $viewModel->peopleUrl() }}"
    data-turbo="true"
    class="flex w-fit items-center gap-1.75 text-sm text-muted transition-colors duration-(--duration-fast) ease-out hover:text-ink"
  >
    <x-icon name="chevron-right" :size="15" :stroke-width="1.7" class="rotate-180" />
    {{ __('All people') }}
  </a>

  <header class="flex flex-wrap items-start gap-5.5">
    <div
      id="contact-favourite"
      x-merge="replace"
      class="relative shrink-0 rounded-full border-2 p-[3px] transition-colors duration-(--duration-fast) ease-out {{ $viewModel->isFavourite() ? 'border-warning-ring' : 'border-transparent' }}"
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
          class="flex size-6.5 cursor-pointer items-center justify-center rounded-full shadow-panel transition-colors duration-(--duration-fast) ease-out focus-visible:ring-[3px] focus-visible:ring-focus-ring/45 focus-visible:outline-none {{ $viewModel->isFavourite() ? 'border-2 border-canvas bg-warning text-canvas hover:bg-warning/90' : 'border border-hairline-strong bg-canvas text-muted-soft hover:bg-warning-tint hover:text-warning' }}"
        >
          <x-icon :name="$viewModel->isFavourite() ? 'star-filled' : 'star'" :size="14" :stroke-width="1.6" />
        </button>
      </x-form>
    </div>

    <div class="min-w-60 flex-1">
      <h1 class="text-page-title text-balance text-ink">{{ $viewModel->title() }}</h1>

      <p class="mt-2 text-base text-body">
        {{ $viewModel->relationshipLine() }}

        @if ($viewModel->nickname())
          {{ __('Known as :nickname.', ['nickname' => $viewModel->nickname()]) }}
        @endif

        @if ($viewModel->isArchived())
          <x-badge class="ml-1 align-middle">{{ __('Archived') }}</x-badge>
        @endif
      </p>

      <ul id="contact-facts" x-merge="replace" class="mt-4 flex list-none flex-wrap gap-x-5.5 gap-y-2">
        @foreach ($viewModel->headerFacts() as $fact)
          <li class="flex items-center gap-1.75 text-sm text-body">
            <x-icon :name="$fact['icon']" :size="16" :stroke-width="1.5" class="text-muted" />
            {{ $fact['label'] }} <b class="font-semibold text-ink">{{ $fact['value'] }}</b>

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
      <x-button.secondary :href="$viewModel->shareLinksUrl()" data-turbo="true" size="small">
        <x-icon name="link" :size="15" :stroke-width="1.6" />
        {{ $viewModel->shareLine() }}
      </x-button.secondary>

      <x-button.secondary :href="$viewModel->editUrl()" data-turbo="true" size="small">
        <x-icon name="profile" :size="15" :stroke-width="1.6" />
        {{ __('Edit contact') }}
      </x-button.secondary>

      <x-button.secondary :href="$viewModel->mergeUrl()" data-turbo="true" size="small">
        <x-icon name="contact" :size="15" :stroke-width="1.6" />
        {{ __('Merge') }}
      </x-button.secondary>
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
        @class(['ml-auto' => $page['isPinned']])
      >{{ $page['label'] }}</x-tabs.item>
    @endforeach
  </x-tabs>

  @if (! $viewModel->onVersionHistory() && $update = $viewModel->templateUpdate())
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
  @elseif ($viewModel->onOverview())
    <div class="grid items-start gap-5 lg:grid-cols-[minmax(0,1.5fr)_minmax(0,1fr)]">
      <div class="flex flex-col gap-5">
        <x-panel :title="__('Write a note')" icon="note">
          <x-textarea
            id="entry"
            :placeholder="__('A call, a visit, something they said.')"
            :rows="3"
            disabled
          />

          <div class="mt-3 flex items-center gap-3 border-t border-hairline pt-3">
            <span class="max-w-[44ch] text-xs text-pretty text-muted">{{ $unbuilt['compose'] }}</span>

            <span aria-disabled="true" class="{{ $inert }} ml-auto">{{ __('Save to their history') }}</span>
          </div>
        </x-panel>

        <section class="flex flex-col gap-3.5">
          <div class="flex flex-wrap items-baseline gap-x-4 gap-y-1 px-0.5">
            <h2 class="text-section-title text-ink">{{ __('History') }}</h2>

            <p class="text-sm text-pretty text-muted">{{ $viewModel->historyNote() }}</p>
          </div>

          <ol class="relative list-none pl-7.5 before:absolute before:top-1.5 before:bottom-1.5 before:left-2.75 before:w-0.5 before:rounded-[1px] before:bg-hairline before:content-['']">
            @foreach ($viewModel->history() as $entry)
              <li class="relative pb-6 last:pb-0">
                <span
                  class="absolute top-px -left-7.5 flex size-6 items-center justify-center rounded-full bg-card text-muted ring-4 ring-page"
                  aria-hidden="true"
                >
                  <x-icon :name="$entry['icon']" :size="14" :stroke-width="1.6" />
                </span>

                <p class="text-xs text-muted">{{ $entry['when'] }}</p>

                <p class="mt-0.5 text-base font-semibold text-pretty text-ink">{{ $entry['title'] }}</p>

                <p class="mt-1.5 max-w-180 text-sm text-pretty text-body">{{ $entry['body'] }}</p>
              </li>
            @endforeach
          </ol>
        </section>
      </div>

      <div class="flex flex-col gap-5">
        <x-panel :title="__('Facts')" icon="info">
          <dl class="grid gap-0">
            @foreach ($viewModel->facts() as $fact)
              <div class="grid grid-cols-[150px_minmax(0,1fr)] items-baseline gap-4 border-b border-hairline py-2.75 last:border-b-0 max-[560px]:grid-cols-1 max-[560px]:gap-0.5">
                <dt class="flex items-center gap-2 text-sm text-muted">
                  <x-icon :name="$fact['icon']" :size="15" :stroke-width="1.5" />
                  {{ $fact['label'] }}
                </dt>

                <dd @class([
                  'text-base text-pretty',
                  $fact['value'] ? 'text-ink' : 'text-placeholder',
                  'inline-flex items-center gap-2' => $fact['reminder'] !== null,
                ])>
                  {{ $fact['value'] ?? __('Not recorded') }}

                  @if ($fact['reminder'])
                    <x-tooltip :text="$fact['reminder']">
                      <x-icon name="alarm" :size="15" class="text-muted" />
                    </x-tooltip>
                  @endif
                </dd>
              </div>
            @endforeach
          </dl>
        </x-panel>

        <x-panel :title="__('Relationships')" icon="relationship">
          <p class="text-sm text-pretty text-muted">{{ $unbuilt['relationships'] }}</p>
        </x-panel>

        <x-panel :title="__('Reminders')" icon="reminder">
          <p class="text-sm text-pretty text-muted">{{ $unbuilt['reminders'] }}</p>
        </x-panel>

        <x-panel :title="__('Things you said you would do')" icon="tasks">
          <p class="text-sm text-pretty text-muted">{{ $unbuilt['tasks'] }}</p>
        </x-panel>
      </div>
    </div>
  @else
    @include('app.vaults.contacts._page')
  @endif
</x-shell>
