{{-- The people who can narrow the dashboard timeline, answering what somebody typed in its picker. --}}
{{-- @var \App\ViewModels\Vaults\TimelineContactCandidatesViewModel $viewModel --}}
<x-record-picker.candidates
  id="record-choices"
  :candidates="$viewModel->candidates()"
  :empty-line="$viewModel->noCandidatesLine()"
/>
