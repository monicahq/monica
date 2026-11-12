{{-- The form changing a Knowledge list itself: its title, its folder and its description. --}}
{{-- @var \App\ViewModels\Vaults\KnowledgeListViewModel $viewModel --}}
<x-form method="put" :action="$viewModel->updateUrl()" class="flex flex-col gap-4">
  <div class="grid grid-cols-1 gap-4 sm:grid-cols-[minmax(0,2fr)_minmax(0,1fr)]">
    <x-input id="knowledge-list-title" name="title" :label="__('Title')" required :value="old('title', $viewModel->title())" :error="$errors->get('title')" />

    <x-select id="knowledge-list-folder" name="folder" :label="__('Folder')" :error="$errors->get('folder')">
      @foreach ($viewModel->folders() as $folder)
        <option value="{{ $folder['id'] }}" @selected(old('folder') !== null ? old('folder') === $folder['id'] : $folder['isSelected'])>{{ $folder['name'] }}</option>
      @endforeach
    </x-select>
  </div>

  <x-textarea
    id="knowledge-list-description"
    name="description"
    :label="__('Description')"
    optional
    :rows="3"
    :value="old('description', $viewModel->description())"
    :placeholder="__('What the list is for')"
    :help="__('Markdown: **bold** and web addresses.')"
    :error="$errors->get('description')"
  />

  <div class="flex flex-wrap gap-2">
    <x-button>{{ __('Save changes') }}</x-button>
    <x-button.secondary :href="$viewModel->showUrl()">{{ __('Cancel') }}</x-button.secondary>
  </div>
</x-form>
