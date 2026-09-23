{{-- A file on its way in, read before it becomes anything. --}}
{{-- @var \App\ViewModels\Settings\Account\Templates\ImportFileViewModel $viewModel --}}
<x-settings.page
  :title="$viewModel->title()"
  :user="$viewModel->user()"
  current="templates"
  :crumbs="$viewModel->breadcrumb()"
  wide
>
  <div class="grid items-start gap-7 lg:grid-cols-[minmax(0,1fr)_20rem]">
    <div class="flex min-w-0 flex-col gap-5" x-data="{ file: @js(old('file', $viewModel->raw())) }">
      <x-page-header :title="$viewModel->title()" :description="$viewModel->intro()" class="max-w-prose" />

      <div id="import" x-merge="replace" class="flex flex-col gap-5 dim-when-busy">
        <x-panel>
          <x-form method="post" :action="$viewModel->checkUrl()" x-target="import" class="flex flex-col gap-4">
            <x-textarea
              id="file"
              :label="__('The file')"
              :value="old('file', $viewModel->raw())"
              :help="__('Open the .json you exported and paste all of it, outer braces included.')"
              :error="$errors->get('file')"
              :rows="12"
              spellcheck="false"
              x-model="file"
              class="font-mono text-xs leading-relaxed"
            />

            <div class="flex flex-wrap items-center gap-3">
              <span
                class="text-xs text-muted"
                x-text="file.trim() === '' ? @js(__('Nothing pasted yet')) : @js(__(':lines lines · :characters characters')).replace(':lines', file.split('\n').length).replace(':characters', file.length)"
              >{{ $viewModel->counterLabel() }}</span>

              <button
                type="button"
                @click="file = @js($viewModel->example())"
                class="cursor-pointer text-xs text-link underline hover:text-link-hover"
              >{{ __('Paste an example instead') }}</button>

              <span class="ml-auto flex items-center gap-2.5">
                <x-button.ghost type="button" size="small" @click="file = ''">{{ __('Clear') }}</x-button.ghost>

                <x-button size="small" ::disabled="file.trim() === ''">
                  <x-slot:icon>
                    <x-icon name="check" :size="15" :stroke-width="1.7" />
                  </x-slot:icon>

                  {{ __('Check this file') }}
                </x-button>
              </span>
            </div>
          </x-form>
        </x-panel>

        @if ($viewModel->isRefused())
          <x-notice tone="danger" :title="__('This file cannot be imported')">
            {{ $viewModel->refusal() }}

            <span class="mt-1 block text-xs text-muted">{{ __('Nothing was imported. Fix the file and check it again.') }}</span>
          </x-notice>
        @endif

        @if ($viewModel->isChecked())
          <x-panel>
            <div class="flex flex-col gap-4">
              <div class="flex gap-2.75">
                <x-icon name="check-circle" :size="18" :stroke-width="1.6" class="mt-px shrink-0 text-forest-deep" />

                <div class="flex min-w-0 flex-col gap-1">
                  <p class="text-base font-semibold text-ink">{{ __('This file can be imported') }}</p>

                  <p class="text-sm text-pretty text-ink-soft">{{ $viewModel->summary() }}</p>
                </div>
              </div>

              <dl class="overflow-hidden rounded-md border border-hairline">
                @foreach ($viewModel->found() as $row)
                  <div class="flex items-baseline gap-4 border-b border-hairline px-3.5 py-2.5 last:border-b-0">
                    <dt class="shrink-0 text-sm text-muted">{{ $row['label'] }}</dt>

                    <dd class="ml-auto min-w-0 text-right text-sm font-semibold text-pretty text-ink">{{ $row['value'] }}</dd>
                  </div>
                @endforeach
              </dl>

              <div class="flex flex-wrap items-center gap-3">
                <x-form method="post" :action="$viewModel->importUrl()">
                  <input type="hidden" name="file" value="{{ $viewModel->raw() }}" />

                  <x-button>
                    <x-slot:icon>
                      <x-icon name="plus" :size="16" :stroke-width="1.7" />
                    </x-slot:icon>

                    {{ $viewModel->isForASection() ? __('Put it on this page') : __('Import it as a draft') }}
                  </x-button>
                </x-form>

                <span class="max-w-reading-narrow text-xs text-pretty text-ink-soft">
                  {{ __('Nothing is published. You review it, then put the records you choose on it yourself.') }}
                </span>
              </div>
            </div>
          </x-panel>
        @endif
      </div>

      <x-notice tone="quiet" icon="lock">
        {{ __('A file only carries the structure. It holds no record, no value, no date and no photo. Importing one never touches what you already have.') }}
      </x-notice>

      <p class="max-w-prose text-sm text-pretty text-muted">
        {{ __('Files written by any Monica running format :version are readable here. A file from a newer format says which version wrote it, so you know what to ask for.', ['version' => $viewModel->formatVersion()]) }}
      </p>
    </div>

    <x-panel :title="__('How it goes')" flush>
      @foreach ($viewModel->steps() as $index => $step)
        <div class="flex gap-3.5 border-b border-hairline px-4.5 py-3.5 last:border-b-0">
          <span class="flex size-6.5 shrink-0 items-center justify-center rounded-sm bg-hover font-mono text-sm text-ink-soft">
            {{ $index + 1 }}
          </span>

          <div class="flex min-w-0 flex-col gap-1">
            <p class="text-base font-semibold text-ink">{{ $step['title'] }}</p>

            <p class="text-sm text-pretty text-body">{{ $step['text'] }}</p>
          </div>
        </div>
      @endforeach
    </x-panel>
  </div>
</x-settings.page>
