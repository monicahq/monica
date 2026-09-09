{{-- Who runs this instance. --}}
{{-- @var \App\ViewModels\Admin\AdministratorsViewModel $viewModel --}}
<x-admin-layout :title="__('Administrators')">
  <header class="flex flex-col gap-2">
    <h1 class="text-page-title text-ink">{{ __('Administrators') }}</h1>

    <p class="max-w-[64ch] text-base text-pretty text-body">{{ __('Everybody who can reach this area. Nobody can give themselves this access, and nobody can take it away from themselves.') }}</p>
  </header>

  @error('administrator')
    <x-notice tone="danger">{{ $message }}</x-notice>
  @enderror

  <x-panel :flush="true">
    <div id="administrators">
      @foreach ($viewModel->administrators() as $administrator)
        <div class="flex flex-col gap-2 border-b border-hairline px-4 py-3.5 last:border-b-0 sm:flex-row sm:items-center sm:gap-4">
          <div class="grow">
            <p class="font-semibold text-ink">{{ $administrator->user->name }}</p>

            <p class="text-sm text-muted">
              {{ $administrator->user->email }}
              ⋅
              @if ($administrator->grantedBy !== null)
                {{ __('granted by :name on :date', ['name' => $administrator->grantedBy->name, 'date' => $administrator->created_at->isoFormat('LL')]) }}
              @else
                {{ __('designated from the console on :date', ['date' => $administrator->created_at->isoFormat('LL')]) }}
              @endif
            </p>
          </div>

          @if (! $administrator->user->is_active)
            <x-badge tone="danger">{{ __('Cannot sign in') }}</x-badge>
          @endif

          @if ($administrator->user->is(auth()->user()))
            <span class="shrink-0 text-sm text-muted">{{ __('This is you') }}</span>
          @elseif (! $viewModel->isTheLastOne())
            <x-modal.warning
              :title="__('Take the admin space away from :name?', ['name' => $administrator->user->name])"
              :confirm="__('Withdraw it')"
              :action="route('admin.administrators.destroy', ['user' => $administrator->user->id])"
            >
              <x-slot:trigger>
                <x-button.danger type="button" size="small" @click="open = true">{{ __('Withdraw') }}</x-button.danger>
              </x-slot:trigger>

              {{ __('They lose every screen of the admin space straight away, including one they already have open. Their own account and everything in it is untouched.') }}
            </x-modal.warning>
          @endif
        </div>
      @endforeach
    </div>

    @if ($viewModel->administrators()->hasMorePages())
      <x-slot:footer>
        <div class="text-center">
          <x-link :href="$viewModel->administrators()->nextPageUrl()">{{ __('Load more') }}</x-link>
        </div>
      </x-slot:footer>
    @endif
  </x-panel>

  <x-panel :title="__('Add an administrator')" :subtitle="__('They have to have an account on this instance already. Nobody is invited into the admin space from outside it.')">
    <x-modal.form
      :title="__('Give somebody the admin space?')"
      :description="__('They will be able to moderate the marketplace, look up any account, and act on it. Nothing held inside a vault is visible to them.')"
      :confirm="__('Grant it')"
      method="post"
      :action="route('admin.administrators.create')"
      :open="$errors->has('email')"
    >
      <x-slot:trigger>
        <x-button type="button" @click="open = true">{{ __('Grant administrator access') }}</x-button>
      </x-slot:trigger>

      <x-input
        id="email"
        name="email"
        type="email"
        :label="__('Their address')"
        :value="old('email')"
        placeholder="dwight.schrute@dundermifflin.com"
        :error="$errors->first('email')"
        required
      />
    </x-modal.form>
  </x-panel>

  @if ($viewModel->isTheLastOne())
    <x-notice tone="warning">
      {{ __('There is one administrator on this instance, so access cannot be withdrawn from anybody. Grant it to a second person first.') }}
    </x-notice>
  @endif
</x-admin-layout>
