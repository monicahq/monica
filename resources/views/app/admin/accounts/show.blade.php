{{-- One account, as the operator sees it. --}}
{{-- @var \App\ViewModels\Admin\AccountViewModel $viewModel --}}
<x-admin-layout :title="$viewModel->title()">
  <div class="mx-auto flex w-full max-w-240 flex-col gap-8">
    <header class="flex flex-col gap-4">
      <x-breadcrumb :items="[
        ['label' => __('Accounts'), 'url' => route('admin.accounts.index')],
        ['label' => $viewModel->title()],
      ]" />

      <div class="flex flex-wrap items-start gap-4">
        <x-avatar-initials :name="$viewModel->title()" :size="48" />

        <div class="flex min-w-0 flex-1 flex-col gap-1.5">
          <h1 class="text-page-title text-balance text-ink">{{ $viewModel->title() }}</h1>

          @if ($viewModel->hasUser())
            <p class="font-mono text-sm break-all text-body">{{ $viewModel->userEmail() }}</p>
          @endif

          <div class="mt-1 flex flex-wrap gap-1.5">
            @foreach ($viewModel->badges() as $badge)
              <x-badge :tone="$badge['tone']" :icon="$badge['icon']">{{ $badge['label'] }}</x-badge>
            @endforeach
          </div>
        </div>

        <x-button.secondary :href="$viewModel->recordUrl()">
          <x-slot:icon>
            <x-icon name="timeline" :size="16" />
          </x-slot:icon>

          {{ __('See the record for this account') }}
        </x-button.secondary>
      </div>
    </header>

    @error('activation')
      <x-notice tone="danger" role="alert">{{ $message }}</x-notice>
    @enderror

    @if ($viewModel->deletionNotice() !== null)
      <x-notice tone="warning">{{ $viewModel->deletionNotice() }}</x-notice>
    @endif

    @if ($viewModel->isTheViewer())
      <x-notice tone="info">{{ __('This is your own account. You can change your own storage limit and your own lifetime access, so an instance with one administrator is never blocked. Every change on this screen is written down against your name, and none of them deletes anything.') }}</x-notice>
    @endif

    <div class="grid grid-cols-[repeat(auto-fit,minmax(min(100%,360px),1fr))] items-start gap-6">
      <x-panel :title="__('The account')" icon="vault" :flush="true">
        <x-detail-list layout="stacked" label-style="caption">
          <x-detail-list.row :label="__('Created')">{{ $viewModel->createdOn() }}</x-detail-list.row>
          <x-detail-list.row :label="__('Where it runs')">{{ $viewModel->whereItRuns() }}</x-detail-list.row>
          <x-detail-list.row :label="__('What it holds')">{{ $viewModel->vaultsLine() }}, {{ $viewModel->contactsLine() }}</x-detail-list.row>
          <x-detail-list.row :label="__('Billing address')">{{ $viewModel->billingAddressLine() }}</x-detail-list.row>
        </x-detail-list>
      </x-panel>

      @if ($viewModel->hasUser())
        <x-panel :title="__('The person')" icon="profile" :flush="true">
          <x-detail-list layout="stacked" label-style="caption">
            <x-detail-list.row :label="__('Name')">{{ $viewModel->userName() }}</x-detail-list.row>
            <x-detail-list.row :label="__('Address')" value-class="text-base break-all text-ink">{{ $viewModel->userEmailLine() }}</x-detail-list.row>
            <x-detail-list.row :label="__('How they sign in')">{{ $viewModel->signInLine() }}</x-detail-list.row>
            <x-detail-list.row :label="__('Two-factor authentication')">{{ $viewModel->twoFactorLine() }}</x-detail-list.row>
            <x-detail-list.row :label="__('Last signed in')">{{ $viewModel->lastSignedInLine() }}</x-detail-list.row>
          </x-detail-list>
        </x-panel>
      @endif
    </div>

    @if ($viewModel->isBillable())
      <x-panel :title="__('Billing')" :flush="true">
        <x-detail-list label-style="caption">
          @foreach ($viewModel->billingFacts() as $fact)
            <x-detail-list.row :label="$fact['label']">{{ $fact['value'] }}</x-detail-list.row>
          @endforeach
        </x-detail-list>

        @if ($viewModel->stripeCustomerUrl() !== null)
          <x-slot:footer>
            <x-link :href="$viewModel->stripeCustomerUrl()" target="_blank" rel="noopener noreferrer">{{ __('See the customer on Stripe') }}</x-link>
          </x-slot:footer>
        @endif
      </x-panel>
    @endif

    @if ($viewModel->isActionable())
      <x-page-section :title="__('Limits and access')" :description="__('What support can change for this account. Each change goes in the record.')">
        <x-panel :flush="true">
          <x-row :title="__('Storage')" :description="$viewModel->storageLine()" :stack="true">
            @if ($viewModel->storageSharePercent() !== null)
              <div role="img" aria-label="{{ $viewModel->storageShareLine() }}" class="mt-2 h-1.5 max-w-80 overflow-hidden rounded-xs bg-hairline-strong">
                <span class="block h-full bg-ink" style="width: {{ $viewModel->storageSharePercent() }}%"></span>
              </div>
            @endif

            @if ($viewModel->isOverStorageLimit())
              <p class="mt-2 text-sm text-body">{{ __('This account holds more than its limit allows. Nothing has been deleted, and no new file will be accepted until the limit is raised or files are removed.') }}</p>
            @endif

            <x-slot:end>
              <x-modal.form
                :title="__('Change the storage limit')"
                :description="__('In bytes. Leave it empty for no limit at all.')"
                :confirm="__('Save the limit')"
                method="put"
                :action="$viewModel->storageLimitUrl()"
                :open="$errors->has('storage_limit_in_bytes')"
              >
                <x-slot:trigger>
                  <x-button.secondary type="button" @click="open = true">{{ __('Change limit') }}</x-button.secondary>
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
            </x-slot:end>
          </x-row>

          @if ($viewModel->isBillable())
            <x-row :title="__('Plan')" :description="$viewModel->accessKindLabel()" />
          @endif

          <x-row :title="__('Lifetime access')" :description="$viewModel->accessForGoodLine()" :stack="true">
            <x-slot:end>
              @if ($viewModel->holdsAccessForGood())
                <x-modal.warning
                  :title="__('Take lifetime access away?')"
                  :confirm="__('Withdraw it')"
                  :action="$viewModel->withdrawLifetimeAccessUrl()"
                >
                  <x-slot:trigger>
                    <x-button.secondary type="button" @click="open = true">{{ __('Withdraw lifetime access') }}</x-button.secondary>
                  </x-slot:trigger>

                  {{ __('The account goes back to the free plan. An order that was paid stays on the record, marked as refunded, and nothing is deleted.') }}
                </x-modal.warning>
              @else
                <x-modal.warning
                  :title="__('Give this account lifetime access?')"
                  :confirm="__('Grant it')"
                  method="post"
                  :action="$viewModel->grantLifetimeAccessUrl()"
                >
                  <x-slot:trigger>
                    <x-button.secondary type="button" @click="open = true">{{ __('Grant lifetime access') }}</x-button.secondary>
                  </x-slot:trigger>

                  {{ __('The account gets the full product straight away, as though the one time purchase had been made. This takes no payment and issues no invoice.') }}
                </x-modal.warning>
              @endif
            </x-slot:end>
          </x-row>
        </x-panel>
      </x-page-section>
    @endif

    @if ($viewModel->isActionable() && $viewModel->flags() !== [])
      <x-page-section :title="__('Feature flags')" :description="__('A flag switched here applies to this account alone, until somebody switches it for every account.')">
        <x-slot:actions>
          <x-link :href="route('admin.flags.index')">{{ __('Flags for every account') }}</x-link>
        </x-slot:actions>

        <x-panel :flush="true">
          <ul>
            @foreach ($viewModel->flags() as $flag)
              <li class="flex flex-wrap items-center gap-3 border-b border-hairline px-4 py-2.5 last:border-b-0">
                <code class="grow font-mono text-sm break-all text-ink">{{ $flag['name'] }}</code>

                @if ($flag['isOn'])
                  <x-badge tone="success">{{ __('On') }}</x-badge>

                  <x-form method="delete" :action="$flag['deactivateUrl']">
                    <x-button.secondary type="submit" size="small">{{ __('Switch off') }}<span class="sr-only"> {{ $flag['name'] }}</span></x-button.secondary>
                  </x-form>
                @else
                  <x-badge>{{ __('Off') }}</x-badge>

                  <x-form method="post" :action="$flag['activateUrl']">
                    <x-button.secondary type="submit" size="small">{{ __('Switch on') }}<span class="sr-only"> {{ $flag['name'] }}</span></x-button.secondary>
                  </x-form>
                @endif
              </li>
            @endforeach
          </ul>
        </x-panel>
      </x-page-section>
    @endif

    @if ($viewModel->isActionable())
      @if ($viewModel->canSignIn())
        <x-panel tone="danger" :title="__('Deactivate this account')" :subtitle="__(':name would no longer be able to sign in. Their vaults and contacts stay where they are.', ['name' => $viewModel->userName()])">
          <div class="flex flex-wrap items-center gap-4">
            @if ($viewModel->isTheViewer())
              <p id="deactivation-refusal" class="min-w-60 flex-1 text-pretty text-body">{{ __('You cannot deactivate yourself. Another administrator has to do that, so nobody can lock themselves out.') }}</p>

              <x-button.danger type="button" aria-disabled="true" aria-describedby="deactivation-refusal">{{ __('Deactivate account') }}</x-button.danger>
            @else
              <x-modal.warning
                :title="__('Stop :name signing in?', ['name' => $viewModel->userName()])"
                :confirm="__('Deactivate them')"
                :action="$viewModel->deactivateUrl()"
              >
                <x-slot:trigger>
                  <x-button.danger type="button" @click="open = true">{{ __('Deactivate account') }}</x-button.danger>
                </x-slot:trigger>

                {{ __('They will not be able to sign in. Any session they have open stops working on their next click. Nothing they own is deleted, and you can undo this at any time.') }}
              </x-modal.warning>
            @endif
          </div>
        </x-panel>
      @else
        <x-panel :title="__('Reactivate this account')" :subtitle="__(':name cannot sign in at the moment. Their vaults and contacts are where they left them.', ['name' => $viewModel->userName()])">
          <x-modal.warning
            :title="__('Let :name sign in again?', ['name' => $viewModel->userName()])"
            :confirm="__('Reactivate them')"
            method="post"
            :action="$viewModel->reactivateUrl()"
          >
            <x-slot:trigger>
              <x-button.secondary type="button" @click="open = true">{{ __('Reactivate account') }}</x-button.secondary>
            </x-slot:trigger>

            {{ __('They will be able to sign in again, and everything they owned is where they left it.') }}
          </x-modal.warning>
        </x-panel>
      @endif
    @endif
  </div>
</x-admin-layout>
