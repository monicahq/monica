{{-- Where somebody decides whether an assistant may reach their account through the MCP server. --}}
{{-- @var \App\ViewModels\Auth\OAuthAuthorizationViewModel $viewModel --}}
<x-auth-page
  :title="__('Connect :name', ['name' => $viewModel->clientName()])"
  :description="__('This assistant is asking to read and change what your account holds, on your behalf.')"
  :quote="$viewModel->quote()"
>
  <x-box class="space-y-4">
    <x-detail-list>
      <x-detail-list.row :label="__('Signed in as')">{{ $viewModel->email() }}</x-detail-list.row>
      <x-detail-list.row :label="__('Sends you back to')">{{ $viewModel->redirectHost() }}</x-detail-list.row>

      @if(count($viewModel->scopes) > 0)
        <x-detail-list.row :label="__('Permissions')">
          <ul class="space-y-2">
            @foreach($viewModel->scopes as $scope)
              <li class="flex items-start gap-2">
                <span class="text-sm">{{ __($scope->description) }}</span>
              </li>
            @endforeach
          </ul>
        </x-detail-list.row>
      @endif

    </x-detail-list>

    <p class="text-sm leading-relaxed text-body">
      {{ __('The assistant names itself, so check the address it sends you back to. Only allow it if you started this connection yourself.') }}
    </p>

    <div class="flex gap-3">
      <x-form method="delete" :action="route('passport.authorizations.deny')" class="flex-1">
        <input type="hidden" name="state" value="" />
        <input type="hidden" name="client_id" value="{{ $viewModel->clientId() }}" />
        <input type="hidden" name="auth_token" value="{{ $viewModel->authToken() }}" />
        <x-button variant="secondary" class="w-full">{{ __('Deny') }}</x-button>
      </x-form>

      <x-form method="post" :action="route('passport.authorizations.approve')" class="flex-1">
        <input type="hidden" name="state" value="" />
        <input type="hidden" name="client_id" value="{{ $viewModel->clientId() }}" />
        <input type="hidden" name="auth_token" value="{{ $viewModel->authToken() }}" />
        <x-button class="w-full">{{ __('Allow') }}</x-button>
      </x-form>
    </div>
  </x-box>

  <x-notice>
    {{ __('You can disconnect the assistant at any time from the security settings of your account.') }}
  </x-notice>
</x-auth-page>
