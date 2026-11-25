{{-- The card that asks before the people selected on the contact list are deleted. --}}
{{-- @var \App\ViewModels\Vaults\ContactDeletionViewModel $viewModel --}}
<div id="contact-deletion">
  @if ($viewModel->holdsAnybodyToDelete())
    <x-modal.warning
      :title="$viewModel->title()"
      :confirm="$viewModel->confirm()"
      :action="$viewModel->action()"
      x-data="{ sending: false }"
      @submit="if (sending) { $event.preventDefault() } sending = true"
    >
      <x-slot:trigger>
        <span x-init="open = true"></span>
      </x-slot:trigger>

      <x-slot:fields>
        @foreach ($viewModel->uuids() as $uuid)
          <input type="hidden" name="contacts[]" value="{{ $uuid }}" />
        @endforeach
      </x-slot:fields>

      <p>{{ $viewModel->names() }}</p>

      <p>{{ $viewModel->consequence() }}</p>

      @if ($viewModel->keptLine() !== null)
        <p>{{ $viewModel->keptLine() }}</p>
      @endif
    </x-modal.warning>
  @elseif ($viewModel->keptLine() !== null)
    <x-notice tone="warning">{{ $viewModel->keptLine() }}</x-notice>
  @endif
</div>
