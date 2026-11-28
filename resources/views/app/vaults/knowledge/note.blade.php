{{-- One note of a vault's Knowledge, read with its Markdown drawn, or open in its editor. --}}
{{-- @var \App\ViewModels\Vaults\KnowledgeNoteViewModel $viewModel --}}
@php
  $vault = $viewModel->vault();
  $inline = '[&_strong]:font-semibold [&_strong]:text-ink [&_code]:rounded-xs [&_code]:border [&_code]:border-hairline [&_code]:bg-card [&_code]:px-1.25 [&_code]:py-px [&_code]:font-mono [&_code]:text-xs [&_code]:text-ink [&_a]:rounded-xs [&_a]:text-ink [&_a]:underline [&_a]:decoration-hairline-strong [&_a]:underline-offset-3 [&_a:hover]:decoration-ink';
  $prose = "max-w-[68ch] text-base leading-[1.65] text-pretty text-body {$inline} [&>*+*]:mt-3 [&_:is(h1,h2,h3)]:mt-7 [&_:is(h1,h2,h3)]:scroll-mt-20 [&_:is(h1,h2,h3)]:text-section-title [&_:is(h1,h2,h3)]:text-ink [&_:is(h4,h5,h6)]:font-semibold [&_:is(h4,h5,h6)]:text-ink [&_ul]:list-disc [&_ul]:ps-5 [&_ol]:list-decimal [&_ol]:ps-5 [&_li+li]:mt-1 [&_li:has(>input)]:list-none [&_li:has(>input)]:-ms-5 [&_li>input]:me-2 [&_li>input]:size-3.75 [&_li>input]:accent-ink [&_li>input]:align-[-2px] [&_blockquote]:border-s-2 [&_blockquote]:border-hairline-strong [&_blockquote]:ps-4 [&_blockquote]:text-muted [&_hr]:border-hairline [&_pre]:overflow-x-auto [&_pre]:rounded-lg [&_pre]:border [&_pre]:border-hairline [&_pre]:bg-sunken [&_pre]:px-4 [&_pre]:py-3 [&_pre_code]:border-0 [&_pre_code]:bg-transparent [&_pre_code]:p-0 [&_table]:w-full [&_table]:text-sm [&_th]:border-b [&_th]:border-hairline [&_th]:py-2 [&_th]:pe-4 [&_th]:text-start [&_td]:border-b [&_td]:border-hairline-soft [&_td]:py-2 [&_td]:pe-4";
@endphp

<x-shell :title="$viewModel->title()" wide>
  <x-slot:breadcrumb>
    <x-breadcrumb.vault :vault="$vault" :items="[
      ['label' => __('Knowledge'), 'url' => route('knowledge.index', $vault)],
      ['label' => $viewModel->folderName()],
      ['label' => $viewModel->title()],
    ]" />
  </x-slot:breadcrumb>

  <x-slot:sidebar>
    <x-vault.sidebar :user="$viewModel->user()" :vault="$vault" current="knowledge" />
  </x-slot:sidebar>

  <div class="grid grid-cols-1 items-start gap-6 lg:grid-cols-[17rem_minmax(0,1fr)]">
    @include('app.vaults.knowledge._folders', ['tree' => $viewModel->tree()])

    <section aria-labelledby="note-title" class="mx-auto flex w-full max-w-190 min-w-0 flex-col gap-5">
      @if ($viewModel->isEditing())
        <h1 id="note-title" class="sr-only">{{ __('Edit the note “:title”', ['title' => $viewModel->title()]) }}</h1>

        @include('app.vaults.knowledge._note-form', ['viewModel' => $viewModel])
      @else
        <div
          class="contents"
          x-data="{
            source: false,
            copyState: 'idle',
            markdown: @js($viewModel->markdown()),
            copy() {
              this.copyState = 'idle'

              window.copyText(this.markdown).then((ok) => {
                this.copyState = ok ? 'copied' : 'failed'

                if (ok) {
                  setTimeout(() => { if (this.copyState === 'copied') this.copyState = 'idle' }, 2000)
                }
              })
            },
          }"
        >
          <header x-data="{ renaming: false }" class="flex flex-wrap items-start gap-x-4 gap-y-3">
            <div class="flex min-w-60 flex-1 flex-col gap-2">
              <p class="flex items-center gap-1.5 text-sm text-muted">
                <x-icon name="folder" :size="14" />
                <span>{{ $viewModel->folderName() }}</span>
                <span class="text-muted-soft" aria-hidden="true">·</span>
                <span>{{ __('Note') }}</span>
              </p>

              <x-form x-cloak x-show="renaming" method="put" :action="$viewModel->updateUrl()">
                <input
                  x-ref="title"
                  name="title"
                  value="{{ $viewModel->title() }}"
                  maxlength="255"
                  aria-label="{{ __('Title') }}"
                  @keydown.escape.prevent="renaming = false; $refs.title.value = @js($viewModel->title())"
                  @blur="if (renaming) $el.form.requestSubmit()"
                  class="-mx-2 w-[calc(100%+1rem)] rounded-md border border-ink bg-input px-2 py-0.5 text-page-title text-ink focus-ring-field"
                />
              </x-form>

              <h1 id="note-title" x-show="! renaming" class="text-page-title text-ink">
                <button
                  type="button"
                  title="{{ __('Rename') }}"
                  @click="renaming = true; $nextTick(() => $refs.title.select())"
                  class="focus-ring -mx-2 cursor-text rounded-md px-2 py-0.5 text-start hover:bg-hover"
                >{{ $viewModel->title() }}</button>
              </h1>

              <p class="text-xs text-muted"><x-timestamp :at="$viewModel->editedAt()">{{ $viewModel->edited() }}</x-timestamp></p>
            </div>

            <div class="ms-auto flex items-center gap-2">
              @unless ($viewModel->isEmpty())
                <x-actions-menu :label="__('Markdown')" align="left">
                  <x-slot:glyph>
                    <x-markdown-mark :size="12" />
                  </x-slot:glyph>

                  <x-slot:text>
                    <span x-text="copyState === 'copied' ? @js(__('Copied')) : @js(__('Markdown'))">{{ __('Markdown') }}</span>
                    <x-icon name="chevron-down" :size="14" class="text-muted" />
                  </x-slot:text>

                  <button type="button" role="menuitem" @click="menuOpen = false; $refs.menuTrigger.focus(); copy()" class="flex w-full cursor-pointer items-center gap-2.5 min-h-8 rounded-md px-2.5 text-start font-sans text-base text-ink transition-colors hover:bg-hover focus-ring-inset">
                    <x-icon name="copy" :size="16" />
                    {{ __('Copy') }}
                  </button>

                  <button type="button" role="menuitem" @click="menuOpen = false; $refs.menuTrigger.focus(); source = ! source" class="flex w-full cursor-pointer items-center gap-2.5 min-h-8 rounded-md px-2.5 text-start font-sans text-base text-ink transition-colors hover:bg-hover focus-ring-inset">
                    <x-icon name="code" :size="16" />
                    <span x-text="source ? @js(__('View formatted')) : @js(__('View as MD'))">{{ __('View as MD') }}</span>
                  </button>

                  <x-actions-menu.item :href="$viewModel->markdownUrl()" download data-no-instant>
                    <x-slot:icon>
                      <x-icon name="download" :size="16" />
                    </x-slot:icon>

                    {{ __('Download as MD') }}
                  </x-actions-menu.item>
                </x-actions-menu>
              @endunless

              <x-button.secondary size="small" :href="$viewModel->editUrl()">{{ __('Edit') }}</x-button.secondary>

              <x-delete-action
                :title="$viewModel->deleteTitle()"
                :confirm="__('Delete the note')"
                :action="$viewModel->destroyUrl()"
                :label="__('Delete')"
                variant="ghost"
                size="small"
              >
                <p>{{ __('The note is deleted for good.') }}</p>
              </x-delete-action>
            </div>
          </header>

          <p
            role="status"
            x-cloak
            :class="copyState === 'failed' ? 'text-sm text-error' : 'sr-only'"
            x-text="{ copied: @js(__('Copied')), failed: @js(__('Could not copy. Download the file instead.')) }[copyState] ?? ''"
          ></p>

          @if ($viewModel->isEmpty())
            <p class="text-base text-muted">{{ __('This note is empty. Use Edit to write in it.') }}</p>
          @else
            <div x-data x-show="! source">
              <x-error id="knowledge-task-error" :messages="$errors->get('task')" />

              <x-form method="put" :action="$viewModel->taskUrl()" x-ref="taskForm" class="hidden">
                <input type="hidden" name="task" x-ref="task" />
                <input type="hidden" name="body_hash" value="{{ $viewModel->bodyHash() }}" />
              </x-form>

              <article
                @change="if ($event.target.dataset.task) { $refs.task.value = $event.target.dataset.task; $refs.taskForm.requestSubmit() }"
                class="{{ $prose }}"
              >{!! $viewModel->html() !!}</article>
            </div>

            <pre x-cloak x-show="source" class="max-w-[68ch] rounded-lg border border-hairline bg-card p-4 font-mono text-xs leading-[1.65] whitespace-pre-wrap wrap-anywhere text-body">{{ $viewModel->markdown() }}</pre>
          @endif
        </div>
      @endif
    </section>
  </div>
</x-shell>
