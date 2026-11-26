{{-- Where somebody decides whether an assistant, or the mobile app, may reach their account. --}}
{{-- @var \App\ViewModels\Auth\OAuthAuthorizationViewModel $viewModel --}}
<x-auth-page
  :title="$viewModel->title()"
  :description="$viewModel->description()"
>
  <x-box class="space-y-4">
    <x-detail-list>
      <x-detail-list.row :label="__('Signed in as')">{{ $viewModel->email() }}</x-detail-list.row>
      @unless($viewModel->isMobileApp())
        <x-detail-list.row :label="__('Sends you back to')">{{ $viewModel->redirectHost() }}</x-detail-list.row>
      @endunless

      @if(count($viewModel->permissions()) > 0)
        <x-detail-list.row :label="__('Permissions')">
          <ul class="space-y-2">
            @foreach($viewModel->permissions() as $permission)
              <li class="flex items-start gap-2">
                <span class="text-sm">{{ $permission }}</span>
              </li>
            @endforeach
          </ul>
        </x-detail-list.row>
      @endif

    </x-detail-list>

    <p class="text-sm leading-relaxed text-body">
      {{ $viewModel->caution() }}
    </p>

    <div class="flex gap-3">
      <x-form method="delete" :action="route('passport.authorizations.deny')" data-turbo="false" class="flex-1">
        <input type="hidden" name="state" value="" />
        <input type="hidden" name="client_id" value="{{ $viewModel->clientId() }}" />
        <input type="hidden" name="auth_token" value="{{ $viewModel->authToken() }}" />
        <x-button variant="secondary" class="w-full">{{ __('Deny') }}</x-button>
      </x-form>

      <x-form method="post" :action="route('passport.authorizations.approve')" data-turbo="false" class="flex-1">
        <input type="hidden" name="state" value="" />
        <input type="hidden" name="client_id" value="{{ $viewModel->clientId() }}" />
        <input type="hidden" name="auth_token" value="{{ $viewModel->authToken() }}" />
        <x-button class="w-full">{{ __('Allow') }}</x-button>
      </x-form>
    </div>
  </x-box>

  <x-notice>
    {{ $viewModel->notice() }}
  </x-notice>
</x-auth-page>
