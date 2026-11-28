{{-- The contacts a loan could be made with, answering what somebody typed in its contact search. --}}
{{-- @var \App\ViewModels\Vaults\PossessionLoanContactCandidatesViewModel $viewModel --}}
<x-record-picker.candidates
  id="record-choices"
  :candidates="$viewModel->candidates()"
  :empty-line="$viewModel->noCandidatesLine()"
>
  <button
    type="button"
    x-show="canCreateContact"
    x-on:click="startNewContact(@js($viewModel->query()))"
    class="focus-ring mt-2 flex w-full items-center gap-2 rounded-md border border-dashed border-hairline px-3 py-2.25 text-start text-sm font-semibold text-ink hover:bg-hover"
  >
    <span class="flex" aria-hidden="true"><x-icon name="plus" :size="14" /></span>
    {{ $viewModel->createLabel() }}
  </button>
</x-record-picker.candidates>
