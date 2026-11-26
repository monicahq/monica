{{-- The files tab of a group: everything kept with it, and the way to add another. --}}
{{-- @var \App\ViewModels\Vaults\GroupFilesViewModel $viewModel --}}
@php
  $header = $viewModel->header();
  $files = $viewModel->files();
  $vault = $header->vault();
  $list = $files->files();
@endphp
<x-shell :title="$header->name()" wide>
  <x-slot:breadcrumb>
    <x-breadcrumb.vault :vault="$vault" :items="[['label' => __('Groups'), 'url' => $header->indexUrl()], ['label' => $header->name()]]" />
  </x-slot:breadcrumb>

  <x-slot:sidebar>
    <x-vault.sidebar :user="$header->user()" :vault="$vault" current="groups" />
  </x-slot:sidebar>

  @include('app.vaults.groups._header', ['viewModel' => $header])

  <div class="flex flex-col gap-6">
    @include('app.vaults.groups._tabs', ['viewModel' => $header, 'current' => 'files'])

    <x-form method="post" :action="$files->createUrl()" upload class="flex flex-wrap items-end gap-3">
      <x-file-input id="group-file" name="file" :label="__('Add a file')" :error="$errors->get('file')" required />

      <x-button size="small">{{ __('Keep it with the group') }}</x-button>
    </x-form>

    <x-list-panel :items="$list">
      @foreach ($list as $file)
        <div class="flex min-w-0 flex-wrap items-center gap-3 border-b border-hairline px-4 py-3 last:border-b-0">
          <span class="flex size-8.5 flex-none items-center justify-center rounded-md border border-hairline bg-card text-ink-soft" aria-hidden="true">
            <x-icon :name="$file['icon']" :size="16" />
          </span>

          <span class="min-w-0 flex-1">
            <a href="{{ $file['url'] }}" data-no-instant class="block text-base font-semibold break-all text-ink no-underline hover:underline focus-ring">{{ $file['name'] }}</a>
            <span class="block truncate text-xs text-muted">{{ $file['meta'] }}</span>
          </span>

          <x-button.ghost :href="$file['url']" download data-no-instant size="small" :aria-label="__('Download :name', ['name' => $file['name']])">{{ __('Download') }}</x-button.ghost>

          <x-delete-action
            :title="__('Remove :name from the group?', ['name' => $file['name']])"
            :confirm="__('Remove the file')"
            :action="$file['destroyUrl']"
            :label="__('Remove')"
            :irreversible="false"
            variant="ghost"
            size="small"
          >
            <p>{{ __(':name is no longer kept with the group. It stays wherever else it is used.', ['name' => $file['name']]) }}</p>

            <p>{{ __('It waits 30 days in the trash of this vault, where it can be restored.') }}</p>
          </x-delete-action>
        </div>
      @endforeach

      <x-slot:empty>
        <x-empty-state :title="__('No files yet')">{{ __('A group photo, a schedule or a registration form, kept with the group.') }}</x-empty-state>
      </x-slot:empty>
    </x-list-panel>
  </div>
</x-shell>
