{{-- A possession type file on its way in, read before it becomes a type. --}}
{{-- @var \App\ViewModels\Settings\Account\PossessionTypes\PossessionTypeImportViewModel $viewModel --}}
<x-settings.page
  :title="$viewModel->title()"
  :user="$viewModel->user()"
  current="possession-types"
  :crumbs="$viewModel->breadcrumb()"
>
  <x-page-header :title="$viewModel->title()" :description="$viewModel->intro()" />

  <div id="import" x-merge="replace" class="flex flex-col gap-5 dim-when-busy" x-data="{ file: @js(old('file', $viewModel->raw())) }">
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

        <div class="flex justify-end">
          <x-button size="small" ::disabled="file.trim() === ''">{{ __('Check this file') }}</x-button>
        </div>
      </x-form>
    </x-panel>

    @if ($viewModel->problems() !== [])
      <div tabindex="-1" x-autofocus class="flex flex-col gap-3 rounded-md focus-ring">
        <x-notice tone="danger" :title="__('This file cannot be imported')">{{ __('Nothing was imported. Fix the file and check it again.') }}</x-notice>

        <x-panel>
          <ul class="list-disc pl-5 text-sm text-body" aria-label="{{ __('What is wrong with the file') }}">
            @foreach ($viewModel->problems() as $problem)
              <li>{{ $problem }}</li>
            @endforeach
          </ul>
        </x-panel>
      </div>
    @endif

    @if ($viewModel->isChecked())
      <x-panel>
        <div class="flex flex-col gap-4">
          <p tabindex="-1" x-autofocus class="rounded-md text-base font-semibold text-ink focus-ring">{{ __('This file can be imported') }}</p>

          <p class="text-sm text-pretty text-ink-soft">{{ $viewModel->summary() }}</p>

          <x-form method="post" :action="$viewModel->importUrl()">
            <input type="hidden" name="file" value="{{ $viewModel->raw() }}" />

            <x-button>{{ __('Import it') }}</x-button>
          </x-form>
        </div>
      </x-panel>
    @endif
  </div>
</x-settings.page>
