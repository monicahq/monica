{{-- The editor of a Knowledge note: its title, its folder and its Markdown text. --}}
{{-- @var \App\ViewModels\Vaults\KnowledgeNoteViewModel $viewModel --}}
<x-form method="put" :action="$viewModel->updateUrl()" x-data="knowledgeNoteEditor()" class="flex flex-col gap-4">
  <div class="grid grid-cols-1 gap-4 sm:grid-cols-[minmax(0,2fr)_minmax(0,1fr)]">
    <x-input id="knowledge-note-title" name="title" :label="__('Title')" required :value="old('title', $viewModel->title())" :error="$errors->get('title')" />

    <x-select id="knowledge-note-folder" name="folder" :label="__('Folder')" :error="$errors->get('folder')">
      @foreach ($viewModel->folders() as $folder)
        <option value="{{ $folder['id'] }}" @selected(old('folder') !== null ? old('folder') === $folder['id'] : $folder['isSelected'])>{{ $folder['name'] }}</option>
      @endforeach
    </x-select>
  </div>

  <div class="flex flex-col gap-1.5">
    <div class="flex flex-wrap items-center gap-2">
      <label for="knowledge-note-body" class="flex-1 text-sm font-medium text-ink">{{ __('Text') }}</label>
      <x-button variant="ghost" size="small" type="button" @click="askLink()" aria-keyshortcuts="Control+K Meta+K">
        <x-slot:icon><x-icon name="link" :size="14" /></x-slot:icon>
        {{ __('Add a link') }}
      </x-button>
    </div>

    <div x-cloak x-show="linking" @keydown.escape.prevent.stop="closeLink()" class="flex flex-wrap items-end gap-2.5 rounded-lg border border-hairline bg-canvas p-3 shadow-pop">
      <div class="min-w-40 flex-1">
        <x-input id="knowledge-link-text" :label="__('Text')" x-model="linkText" :placeholder="__('Shown in the note')" @keydown.enter.prevent="applyLink()" />
      </div>
      <div class="min-w-55 flex-[2]">
        <x-input id="knowledge-link-address" :label="__('Web address')" x-model="linkAddress" x-ref="address" placeholder="https://" @keydown.enter.prevent="applyLink()" />
      </div>
      <x-button size="small" type="button" @click="applyLink()">{{ __('Add link') }}</x-button>
      <x-button variant="ghost" size="small" type="button" @click="closeLink()">{{ __('Cancel') }}</x-button>
    </div>

    <textarea
      id="knowledge-note-body"
      name="body"
      x-ref="body"
      rows="22"
      spellcheck="true"
      aria-describedby="knowledge-note-body-help"
      @keydown="shortcut($event)"
      @if ($errors->has('body')) aria-invalid="true" @endif
      class="w-full resize-y rounded-md border border-hairline-strong bg-input px-3.5 py-3 font-mono text-[13px] leading-[1.7] text-ink focus-ring-field aria-[invalid=true]:border-error"
    >{{ old('body', $viewModel->body()) }}</textarea>

    <p id="knowledge-note-body-help" class="text-xs text-pretty text-muted">{{ __('Markdown: # for a heading, - for a bullet, - [ ] for a task, > for a quote, **bold**. Web addresses become links on their own.') }}</p>
    <x-error id="knowledge-note-body-error" :messages="$errors->get('body')" />
  </div>

  <div class="flex flex-wrap gap-2">
    <x-button>{{ __('Save changes') }}</x-button>
    <x-button.secondary :href="$viewModel->showUrl()">{{ __('Cancel') }}</x-button.secondary>
  </div>
</x-form>
