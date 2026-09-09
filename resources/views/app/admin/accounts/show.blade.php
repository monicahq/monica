{{-- One account, as the operator sees it. --}}
{{-- @var \App\ViewModels\Admin\AccountViewModel $viewModel --}}
<x-admin-layout :title="$viewModel->title()">
  <header class="flex flex-col gap-2">
    <x-link :href="route('admin.accounts.index')" data-turbo="true">{{ __('Back to accounts') }}</x-link>

    <h1 class="text-page-title text-ink">{{ $viewModel->title() }}</h1>

    <div class="flex flex-wrap items-center gap-2">
      @foreach ($viewModel->badges() as $badge)
        <x-badge :tone="$badge['tone']">{{ $badge['label'] }}</x-badge>
      @endforeach
    </div>
  </header>

  @if ($viewModel->deletionNotice() !== null)
    <x-notice tone="warning">{{ $viewModel->deletionNotice() }}</x-notice>
  @endif

  <x-panel :title="__('The account')">
    <dl class="grid grid-cols-1 gap-x-8 gap-y-3 sm:grid-cols-2">
      @foreach ([
        ['label' => __('Created'), 'value' => $viewModel->createdOn()],
        ['label' => __('Where it runs'), 'value' => $viewModel->whereItRuns()],
        ['label' => __('Billing address'), 'value' => $viewModel->billingAddressLine()],
        ['label' => __('Storage'), 'value' => $viewModel->storageLine()],
        ['label' => __('What it holds'), 'value' => $viewModel->vaultsLine().', '.$viewModel->contactsLine()],
      ] as $fact)
        <div>
          <dt class="text-[11px] font-medium tracking-caption text-muted-soft uppercase">{{ $fact['label'] }}</dt>
          <dd class="text-base text-ink">{{ $fact['value'] }}</dd>
        </div>
      @endforeach
    </dl>

    @if ($viewModel->isOverStorageLimit())
      <x-slot:footer>
        <p class="text-sm text-body">{{ __('This account holds more than its limit allows. Nothing has been deleted, and no new file will be accepted until the limit is raised or files are removed.') }}</p>
      </x-slot:footer>
    @endif
  </x-panel>

  @if ($viewModel->isBillable())
    <x-panel :title="__('Billing')">
      <dl class="grid grid-cols-1 gap-x-8 gap-y-3 sm:grid-cols-2">
        @foreach ($viewModel->billingFacts() as $fact)
          <div>
            <dt class="text-[11px] font-medium tracking-caption text-muted-soft uppercase">{{ $fact['label'] }}</dt>
            <dd class="text-base text-ink">{{ $fact['value'] }}</dd>
          </div>
        @endforeach
      </dl>

      @if ($viewModel->stripeCustomerUrl() !== null)
        <p class="mt-4 text-sm">
          <x-link :href="$viewModel->stripeCustomerUrl()" target="_blank" rel="noopener noreferrer">{{ __('See the customer on Stripe') }}</x-link>
        </p>
      @endif
    </x-panel>
  @endif

  @if ($viewModel->hasUser())
    <x-panel :title="__('The person')">
      <dl class="grid grid-cols-1 gap-x-8 gap-y-3 sm:grid-cols-2">
        @foreach ([
          ['label' => __('Name'), 'value' => $viewModel->userName()],
          ['label' => __('Address'), 'value' => $viewModel->userEmail()],
          ['label' => __('Address confirmed'), 'value' => $viewModel->emailIsConfirmedLine()],
          ['label' => __('How they sign in'), 'value' => $viewModel->signInLine()],
          ['label' => __('Two-factor authentication'), 'value' => $viewModel->twoFactorLine()],
          ['label' => __('Can sign in'), 'value' => $viewModel->canSignInLine()],
          ['label' => __('Last signed in'), 'value' => $viewModel->lastSignedInLine()],
        ] as $fact)
          <div>
            <dt class="text-[11px] font-medium tracking-caption text-muted-soft uppercase">{{ $fact['label'] }}</dt>
            <dd class="text-base text-ink">{{ $fact['value'] }}</dd>
          </div>
        @endforeach
      </dl>
    </x-panel>
  @endif

  @error('activation')
    <x-notice tone="danger">{{ $message }}</x-notice>
  @enderror

  @if ($viewModel->isActionable())
    <x-panel :title="__('What support can do')" :subtitle="__('Every one of these is written down against your name. None of them deletes anything.')">
      <div class="flex flex-wrap items-center gap-3">
        @if ($viewModel->isTheViewer())
          <p class="text-sm text-body">{{ __('You cannot deactivate yourself. Another administrator has to do that, so nobody can lock themselves out.') }}</p>
        @elseif ($viewModel->canSignIn())
          <x-modal.warning
            :title="__('Stop :name signing in?', ['name' => $viewModel->userName()])"
            :confirm="__('Deactivate them')"
            :action="$viewModel->deactivateUrl()"
          >
            <x-slot:trigger>
              <x-button.danger type="button" size="small" @click="open = true">{{ __('Deactivate') }}</x-button.danger>
            </x-slot:trigger>

            {{ __('They will not be able to sign in. Any session they have open stops working on their next click. Nothing they own is deleted, and you can undo this at any time.') }}
          </x-modal.warning>
        @else
          <x-modal.warning
            :title="__('Let :name sign in again?', ['name' => $viewModel->userName()])"
            :confirm="__('Reactivate them')"
            method="post"
            :action="$viewModel->reactivateUrl()"
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
          :action="$viewModel->storageLimitUrl()"
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
            :value="$viewModel->storageLimitInBytes()"
            :help="__('A limit below the :used already held deletes nothing. It refuses the next upload until the account is back under the limit.', ['used' => $viewModel->storageUsed()])"
            :error="$errors->first('storage_limit_in_bytes')"
            optional
          />
        </x-modal.form>

        @if ($viewModel->holdsAccessForGood())
          <x-modal.warning
            :title="__('Take lifetime access away?')"
            :confirm="__('Withdraw it')"
            :action="$viewModel->withdrawLifetimeAccessUrl()"
          >
            <x-slot:trigger>
              <x-button.danger type="button" size="small" @click="open = true">{{ __('Withdraw lifetime access') }}</x-button.danger>
            </x-slot:trigger>

            {{ __('The account goes back to the free offer. An order that was paid stays on the record, marked as refunded, and nothing is deleted.') }}
          </x-modal.warning>
        @else
          <x-modal.warning
            :title="__('Give this account lifetime access?')"
            :confirm="__('Grant it')"
            method="post"
            :action="$viewModel->grantLifetimeAccessUrl()"
          >
            <x-slot:trigger>
              <x-button.secondary type="button" size="small" @click="open = true">{{ __('Grant lifetime access') }}</x-button.secondary>
            </x-slot:trigger>

            {{ __('The account gets the full product straight away, as though the one time purchase had been made. This takes no payment and issues no invoice.') }}
          </x-modal.warning>
        @endif
      </div>

      @if ($viewModel->isTheViewer())
        <x-slot:footer>
          <p class="text-sm text-body">{{ __('This is your own account. You can change your own storage limit and your own lifetime access, so an instance with one administrator is never blocked.') }}</p>
        </x-slot:footer>
      @endif
    </x-panel>
  @endif

  <x-panel :title="__('What has been done to this account')">
    <p class="text-sm text-body">{{ __('Every action an administrator has taken on this account is in the record.') }}</p>

    <x-slot:footer>
      <x-link :href="$viewModel->recordUrl()" data-turbo="true">{{ __('See the record for this account') }}</x-link>
    </x-slot:footer>
  </x-panel>
</x-admin-layout>
