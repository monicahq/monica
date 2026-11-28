{{-- The Knowledge of a vault: what it is for while it holds nothing, else every folder, note and list. --}}
{{-- @var \App\ViewModels\Vaults\KnowledgeViewModel $viewModel --}}
@php
  $vault = $viewModel->vault();
  $tree = $viewModel->tree();
  $target = $tree->targetFolder();
@endphp

<x-shell :title="__('Knowledge')" wide>
  <x-slot:breadcrumb>
    <x-breadcrumb.vault :vault="$vault" :items="[['label' => __('Knowledge')]]" />
  </x-slot:breadcrumb>

  <x-slot:sidebar>
    <x-vault.sidebar :user="$viewModel->user()" :vault="$vault" current="knowledge" />
  </x-slot:sidebar>

  <div class="grid grid-cols-1 items-start gap-6 lg:grid-cols-[17rem_minmax(0,1fr)]">
    @include('app.vaults.knowledge._folders', ['tree' => $tree])

    <section aria-labelledby="knowledge-title" class="mx-auto flex w-full max-w-190 min-w-0 flex-col gap-7">
      @if ($viewModel->isEmpty())
        <div class="flex max-w-[60ch] flex-col gap-3 pt-6">
          <span class="flex size-11 items-center justify-center rounded-lg border border-hairline bg-card text-ink" aria-hidden="true"><x-icon name="journal" :size="22" /></span>
          <h1 id="knowledge-title" class="mt-1 text-page-title text-ink">{{ __('Nothing in Knowledge yet') }}</h1>
          <p class="text-base leading-relaxed text-pretty text-body">{{ __('Knowledge is for everything you want to remember that is not a person or a possession: the paint colours in the kitchen, the books you have read, the plan for a trip.') }}</p>
        </div>

        <ol class="grid grid-cols-[repeat(auto-fit,minmax(12.5rem,1fr))] gap-px overflow-hidden rounded-lg border border-hairline bg-hairline">
          <li class="flex flex-col gap-2 bg-canvas p-4">
            <span class="flex items-center gap-2">
              <span class="flex text-ink-soft" aria-hidden="true"><x-icon name="folder" :size="16" /></span>
              <span class="text-base font-semibold text-ink">{{ __('Folders') }}</span>
            </span>
            <span class="text-sm text-pretty text-body">{{ __('Group notes and lists by theme, such as Home, Travel or Health.') }}</span>
          </li>
          <li class="flex flex-col gap-2 bg-canvas p-4">
            <span class="flex items-center gap-2">
              <span class="flex text-ink-soft" aria-hidden="true"><x-icon name="note" :size="16" /></span>
              <span class="text-base font-semibold text-ink">{{ __('Notes') }}</span>
            </span>
            <span class="text-sm text-pretty text-body">{{ __('Free text in Markdown, with headings, lists, tasks and links to the web.') }}</span>
          </li>
          <li class="flex flex-col gap-2 bg-canvas p-4">
            <span class="flex items-center gap-2">
              <span class="flex text-ink-soft" aria-hidden="true"><x-icon name="text-lines" :size="16" /></span>
              <span class="text-base font-semibold text-ink">{{ __('Lists') }}</span>
            </span>
            <span class="text-sm text-pretty text-body">{{ __('Items with their own columns, such as restaurants with a city, a cuisine and a note.') }}</span>
          </li>
        </ol>

        <div class="flex flex-wrap gap-2">
          <x-form method="post" :action="$tree->newNoteUrl()">
            @if ($target !== null)
              <input type="hidden" name="folder" value="{{ $target->uuid }}" />
            @endif
            <x-button>{{ __('Write a first note') }}</x-button>
          </x-form>

          <x-form method="post" :action="$tree->newListUrl()">
            @if ($target !== null)
              <input type="hidden" name="folder" value="{{ $target->uuid }}" />
            @endif
            <x-button.secondary>{{ __('Start a list') }}</x-button.secondary>
          </x-form>

          <x-modal.form :title="__('New folder')" :confirm="__('Add the folder')" method="post" :action="$tree->newFolderUrl()">
            <x-slot:trigger>
              <x-button variant="ghost" type="button" @click="open = true">{{ __('Create a folder') }}</x-button>
            </x-slot:trigger>

            <x-input id="knowledge-first-folder-name" name="name" :label="__('Name')" required :placeholder="__('Home, Travel, Health')" />
          </x-modal.form>
        </div>

        <p class="max-w-[60ch] text-sm text-pretty text-muted">{{ __('Assistants connected to this vault read and change the same folders, notes and lists, so what you write here is also what they know.') }}</p>
      @else
        <header class="flex flex-col gap-2">
          <h1 id="knowledge-title" class="text-page-title text-ink">{{ __('Contents') }}</h1>
          <p class="max-w-[64ch] text-base text-pretty text-body">{{ __('Every folder, note and list in Knowledge. Notes also show their headings, so you can go straight to one.') }}</p>
          <p class="text-xs text-muted">{{ $viewModel->summary() }}</p>
        </header>

        @foreach ($viewModel->contents() as $folder)
          <section class="flex flex-col gap-2.5" aria-label="{{ $folder['name'] }}">
            <div class="flex items-center gap-2">
              <span class="flex text-ink-soft" aria-hidden="true"><x-icon name="folder" :size="16" /></span>
              <h2 class="text-section-title text-ink">{{ $folder['name'] }}</h2>
              <span class="text-sm text-muted">{{ $folder['count'] }}</span>
            </div>

            @if ($folder['isEmpty'])
              <p class="rounded-lg border border-dashed border-hairline-strong px-4 py-3 text-sm text-muted">{{ __('Nothing in this folder yet.') }}</p>
            @else
              <ol class="overflow-hidden rounded-lg border border-hairline bg-canvas shadow-panel">
                @foreach ($folder['documents'] as $document)
                  <li class="border-b border-hairline-soft last:border-b-0">
                    <a href="{{ $document['url'] }}" class="focus-ring-inset flex items-center gap-3 px-4 py-3 text-ink no-underline hover:bg-hover">
                      <span class="flex text-muted" aria-hidden="true"><x-icon :name="$document['icon']" :size="16" /></span>
                      <span class="min-w-0 flex-1 text-base font-semibold">{{ $document['title'] }}</span>
                      <span class="text-end text-xs text-muted">{{ $document['kind'] }} · <x-timestamp :at="$document['editedAt']">{{ $document['edited'] }}</x-timestamp></span>
                    </a>

                    @if ($document['headings'] !== [])
                      <ol class="flex flex-wrap gap-x-4 gap-y-1 ps-11 pe-4 pb-3" aria-label="{{ __('Headings of :title', ['title' => $document['title']]) }}">
                        @foreach ($document['headings'] as $heading)
                          <li><a href="{{ $heading['url'] }}" class="focus-ring rounded-xs text-sm text-body underline decoration-hairline-strong underline-offset-3 hover:decoration-ink">{{ $heading['text'] }}</a></li>
                        @endforeach
                      </ol>
                    @endif
                  </li>
                @endforeach
              </ol>
            @endif
          </section>
        @endforeach
      @endif
    </section>
  </div>
</x-shell>
