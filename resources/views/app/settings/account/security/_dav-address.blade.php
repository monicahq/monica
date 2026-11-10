{{-- The address a contacts or calendar application of a phone or a computer connects to. --}}
{{-- @var \App\ViewModels\Settings\Account\Security\SecurityViewModel $viewModel --}}
<div class="flex flex-col gap-2 px-4 py-3.5">
  <x-code-value surface="canvas">{{ $viewModel->davAddress() }}</x-code-value>

  <div class="flex flex-wrap items-center gap-2.5">
    <x-copy-button
      variant="secondary"
      :text="$viewModel->davAddress()"
      :label="__('Copy the address')"
      :failure="__('We could not copy it. Select the address and copy it by hand.')"
    />
  </div>
</div>
