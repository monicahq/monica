{{-- The records a relation field could name, answering what somebody typed in its picker. --}}
{{-- @var \App\ViewModels\Vaults\FieldChoicesViewModel $viewModel --}}
<x-record-picker.candidates
  id="record-choices"
  :candidates="$viewModel->candidates()"
  :empty-line="$viewModel->noCandidatesLine()"
/>
