{{-- The devices signed in to the account, the one in hand first. --}}
{{-- @var \App\ViewModels\Settings\Account\Security\SecurityViewModel $viewModel --}}
@php($sessions = $viewModel->sessions())

@if (! $sessions->canList())
  @include('app.settings.account.security._sessions-unavailable')
@else
  <div class="flex w-full items-center gap-3 border-b border-hairline px-4 py-3.5 text-left last:border-b-0">
    <div class="min-w-0 flex-1">
      <p class="text-base font-semibold text-ink">{{ $sessions->header() }}</p>
    </div>
  </div>

  @foreach ($sessions->sessions() as $session)
    @include('app.settings.account.security._session-row', ['session' => $session])
  @endforeach
@endif
