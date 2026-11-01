{{-- The people a group could take in, answering what somebody typed in its picker. --}}
{{-- @var \App\ViewModels\Vaults\GroupMemberCandidatesViewModel $viewModel --}}
<x-record-picker.candidates
  id="record-choices"
  :candidates="$viewModel->candidates()"
  :empty-line="$viewModel->noCandidatesLine()"
/>
