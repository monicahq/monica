{{-- One account, as the operator sees it. --}}
{{-- @var \App\ViewModels\Admin\AccountViewModel $viewModel --}}
<x-admin-layout :title="$viewModel->user()?->name ?? __('Account :id', ['id' => $viewModel->account()->id])">
  <header class="flex flex-col gap-2">
    <x-link :href="route('admin.accounts.index')" data-turbo="true">{{ __('Back to accounts') }}</x-link>

    <h1 class="text-page-title text-ink">
      {{ $viewModel->user()?->name ?? __('Account :id', ['id' => $viewModel->account()->id]) }}
    </h1>

    <div class="flex flex-wrap items-center gap-2">
      @if ($viewModel->account()->is_self_hosted)
        <x-badge>{{ __('Self-hosted') }}</x-badge>
      @endif

      @if ($viewModel->account()->has_lifetime_access)
        <x-badge tone="success">{{ __('Lifetime access') }}</x-badge>
      @endif

      @if (! $viewModel->hasUser())
        <x-badge tone="danger">{{ __('Nobody on this account') }}</x-badge>
      @elseif ($viewModel->userWasDeleted())
        <x-badge tone="danger">{{ __('Person deleted') }}</x-badge>
      @elseif (! $viewModel->user()->is_active)
        <x-badge tone="danger">{{ __('Deactivated') }}</x-badge>
      @endif
    </div>
  </header>

  @if ($viewModel->userWasDeleted())
    <x-notice tone="warning">
      {{ __('The person on this account was deleted on :date. The account and everything in it is still here.', ['date' => $viewModel->user()->deleted_at->isoFormat('LL')]) }}
    </x-notice>
  @endif

  <x-panel :title="__('The account')">
    <dl class="grid grid-cols-1 gap-x-8 gap-y-3 sm:grid-cols-2">
      <div>
        <dt class="text-[11px] font-medium tracking-caption text-muted-soft uppercase">{{ __('Created') }}</dt>
        <dd class="text-base text-ink">{{ $viewModel->account()->created_at->isoFormat('LL') }}</dd>
      </div>

      <div>
        <dt class="text-[11px] font-medium tracking-caption text-muted-soft uppercase">{{ __('Where it runs') }}</dt>
        <dd class="text-base text-ink">{{ $viewModel->account()->is_self_hosted ? __('Self-hosted') : __('Hosted by us') }}</dd>
      </div>

      <div>
        <dt class="text-[11px] font-medium tracking-caption text-muted-soft uppercase">{{ __('Lifetime access') }}</dt>
        <dd class="text-base text-ink">
          @if (! $viewModel->isBillable())
            {{ __('Not applicable') }}
          @elseif ($viewModel->account()->has_lifetime_access)
            {{ $viewModel->account()->purchased_at !== null
              ? __('Yes, bought on :date', ['date' => $viewModel->account()->purchased_at->isoFormat('LL')])
              : __('Yes') }}
          @else
            {{ __('No') }}
          @endif
        </dd>
      </div>

      <div>
        <dt class="text-[11px] font-medium tracking-caption text-muted-soft uppercase">{{ __('Billing address') }}</dt>
        <dd class="text-base text-ink">
          {{ ! $viewModel->isBillable() ? __('Not applicable') : ($viewModel->account()->billing_email ?? __('None given')) }}
        </dd>
      </div>

      <div>
        <dt class="text-[11px] font-medium tracking-caption text-muted-soft uppercase">{{ __('Storage') }}</dt>
        <dd class="text-base text-ink">
          @if ($viewModel->hasStorageLimit())
            {{ __(':used of :limit', ['used' => $viewModel->storageUsed(), 'limit' => $viewModel->storageLimit()]) }}
          @else
            {{ __(':used, no limit', ['used' => $viewModel->storageUsed()]) }}
          @endif
        </dd>
      </div>

      <div>
        <dt class="text-[11px] font-medium tracking-caption text-muted-soft uppercase">{{ __('What it holds') }}</dt>
        <dd class="text-base text-ink">
          {{ trans_choice(':count vault|:count vaults', $viewModel->vaultCount()) }},
          {{ trans_choice(':count contact|:count contacts', $viewModel->contactCount()) }}
        </dd>
      </div>
    </dl>

    @if ($viewModel->isOverStorageLimit())
      <x-slot:footer>
        <p class="text-sm text-body">{{ __('This account holds more than its limit allows. Nothing has been deleted, and no new file will be accepted until the limit is raised or files are removed.') }}</p>
      </x-slot:footer>
    @endif
  </x-panel>

  @if ($viewModel->hasUser())
    <x-panel :title="__('The person')">
      <dl class="grid grid-cols-1 gap-x-8 gap-y-3 sm:grid-cols-2">
        <div>
          <dt class="text-[11px] font-medium tracking-caption text-muted-soft uppercase">{{ __('Name') }}</dt>
          <dd class="text-base text-ink">{{ $viewModel->user()->name }}</dd>
        </div>

        <div>
          <dt class="text-[11px] font-medium tracking-caption text-muted-soft uppercase">{{ __('Address') }}</dt>
          <dd class="text-base text-ink">{{ $viewModel->user()->email }}</dd>
        </div>

        <div>
          <dt class="text-[11px] font-medium tracking-caption text-muted-soft uppercase">{{ __('Address confirmed') }}</dt>
          <dd class="text-base text-ink">{{ $viewModel->user()->hasVerifiedEmail() ? __('Yes') : __('No') }}</dd>
        </div>

        <div>
          <dt class="text-[11px] font-medium tracking-caption text-muted-soft uppercase">{{ __('How they sign in') }}</dt>
          <dd class="text-base text-ink">
            {{ $viewModel->user()->usesSingleSignOn()
              ? __('Single sign-on through :provider', ['provider' => $viewModel->user()->sso_provider])
              : __('With a password') }}
          </dd>
        </div>

        <div>
          <dt class="text-[11px] font-medium tracking-caption text-muted-soft uppercase">{{ __('Two factor authentication') }}</dt>
          <dd class="text-base text-ink">{{ $viewModel->user()->usesTwoFactorAuthentication() ? __('On') : __('Off') }}</dd>
        </div>

        <div>
          <dt class="text-[11px] font-medium tracking-caption text-muted-soft uppercase">{{ __('Can sign in') }}</dt>
          <dd class="text-base text-ink">{{ $viewModel->user()->is_active ? __('Yes') : __('No, deactivated') }}</dd>
        </div>

        <div>
          <dt class="text-[11px] font-medium tracking-caption text-muted-soft uppercase">{{ __('Last signed in') }}</dt>
          <dd class="text-base text-ink">
            {{ $viewModel->user()->last_login_at?->isoFormat('LL') ?? __('Never') }}
          </dd>
        </div>
      </dl>
    </x-panel>
  @endif

  @error('activation')
    <x-notice tone="danger">{{ $message }}</x-notice>
  @enderror

  @if ($viewModel->hasUser() && ! $viewModel->userWasDeleted())
    <x-panel :title="__('What support can do')" :subtitle="__('Every one of these is written down against your name. None of them deletes anything.')">
      <div class="flex flex-wrap items-center gap-3">
        @if ($viewModel->user()->is(auth()->user()))
          <p class="text-sm text-body">{{ __('You cannot deactivate yourself. Another administrator has to do that, so nobody can lock themselves out.') }}</p>
        @elseif ($viewModel->user()->is_active)
          <x-modal.warning
            :title="__('Stop :name signing in?', ['name' => $viewModel->user()->name])"
            :confirm="__('Deactivate them')"
            :action="route('admin.accounts.activation.destroy', ['account' => $viewModel->account()->id])"
          >
            <x-slot:trigger>
              <x-button.danger type="button" size="small" @click="open = true">{{ __('Deactivate') }}</x-button.danger>
            </x-slot:trigger>

            {{ __('They will not be able to sign in, and any session they have open right now will stop working on their next click. Nothing they own is deleted, and you can undo this at any time.') }}
          </x-modal.warning>
        @else
          <x-modal.warning
            :title="__('Let :name sign in again?', ['name' => $viewModel->user()->name])"
            :confirm="__('Reactivate them')"
            method="post"
            :action="route('admin.accounts.activation.create', ['account' => $viewModel->account()->id])"
          >
            <x-slot:trigger>
              <x-button.secondary type="button" size="small" @click="open = true">{{ __('Reactivate') }}</x-button.secondary>
            </x-slot:trigger>

            {{ __('They will be able to sign in again, and everything they owned is where they left it.') }}
          </x-modal.warning>
        @endif

        <x-modal.form
          :title="__('Change the storage limit')"
          :description="__('In bytes. Leave it empty for no limit at all.')"
          :confirm="__('Save the limit')"
          method="put"
          :action="route('admin.accounts.storageLimit.update', ['account' => $viewModel->account()->id])"
          :open="$errors->has('storage_limit_in_bytes')"
        >
          <x-slot:trigger>
            <x-button.secondary type="button" size="small" @click="open = true">{{ __('Storage limit') }}</x-button.secondary>
          </x-slot:trigger>

          <x-input
            id="storage_limit_in_bytes"
            name="storage_limit_in_bytes"
            type="number"
            :label="__('Limit in bytes')"
            :value="$viewModel->account()->storage_limit_in_bytes"
            :help="__('A limit below the :used already held deletes nothing. It refuses the next upload until they are back under it.', ['used' => $viewModel->storageUsed()])"
            :error="$errors->first('storage_limit_in_bytes')"
            optional
          />
        </x-modal.form>

        @if ($viewModel->account()->has_lifetime_access)
          <x-modal.warning
            :title="__('Take lifetime access away?')"
            :confirm="__('Withdraw it')"
            :action="route('admin.accounts.lifetimeAccess.destroy', ['account' => $viewModel->account()->id])"
          >
            <x-slot:trigger>
              <x-button.danger type="button" size="small" @click="open = true">{{ __('Withdraw lifetime access') }}</x-button.danger>
            </x-slot:trigger>

            {{ __('The account goes back to what it was before the purchase. If a purchase was made, the date of it stays on the record.') }}
          </x-modal.warning>
        @else
          <x-modal.warning
            :title="__('Give this account lifetime access?')"
            :confirm="__('Grant it')"
            method="post"
            :action="route('admin.accounts.lifetimeAccess.create', ['account' => $viewModel->account()->id])"
          >
            <x-slot:trigger>
              <x-button.secondary type="button" size="small" @click="open = true">{{ __('Grant lifetime access') }}</x-button.secondary>
            </x-slot:trigger>

            {{ __('The account gets the full product straight away, as though the one time purchase had been made. This takes no payment and issues no invoice.') }}
          </x-modal.warning>
        @endif
      </div>

      @if ($viewModel->user()->is(auth()->user()))
        <x-slot:footer>
          <p class="text-sm text-body">{{ __('This is your own account. Your own storage limit and your own lifetime access are yours to change, so a single administrator instance is never stuck.') }}</p>
        </x-slot:footer>
      @endif
    </x-panel>
  @endif

  <x-panel :title="__('What has been done to this account')">
    <p class="text-sm text-body">{{ __('Every action an administrator has taken on this account is in the record.') }}</p>

    <x-slot:footer>
      <x-link :href="route('admin.logs.index', ['account' => $viewModel->account()->id])" data-turbo="true">{{ __('See the record for this account') }}</x-link>
    </x-slot:footer>
  </x-panel>
</x-admin-layout>
