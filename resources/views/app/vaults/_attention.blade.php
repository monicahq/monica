{{-- The dates of the vault that are overdue, due today or due within a week, each answerable here. --}}
{{-- @var \App\ViewModels\Vaults\VaultDashboardAttention $attention --}}
<x-page-section :title="__('Needs your attention')" :description="__('Overdue, today and the next seven days.')" icon="bell">
  @include('app.vaults._attention-dates', ['attention' => $attention, 'showsAllReminders' => true])
</x-page-section>
