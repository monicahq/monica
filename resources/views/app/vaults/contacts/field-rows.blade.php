{{-- One page of a repeated field's rows, fetched by Load more on a record. --}}
{{-- @var \App\ViewModels\Vaults\ContactFieldRowsViewModel $viewModel --}}
@include('app.vaults.contacts._field-rows', [
  'field' => $viewModel->field(),
  'rows' => $viewModel->readsAsNotes() ? [] : $viewModel->shapedRows(),
  'notes' => $viewModel->readsAsNotes() ? $viewModel->noteEntries() : null,
  'more' => $viewModel->more(),
])
