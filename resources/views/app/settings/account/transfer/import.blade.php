{{-- One import: what the file holds, where it goes, how far it is, and what became of each thing. --}}
{{-- @var \App\ViewModels\Settings\Account\Transfer\AccountImportViewModel $viewModel --}}
<x-settings.page :title="$viewModel->title()" :user="$viewModel->user()" current="transfer" :crumbs="$viewModel->breadcrumb()">
  <div id="import" class="flex flex-col gap-7">
    <x-page-header :title="$viewModel->title()" class="max-w-prose" />

    <div role="status" aria-live="polite" class="flex flex-col gap-2.5">
      <p class="text-sm text-pretty text-ink">{{ $viewModel->statusSentence() }}</p>

      @if ($viewModel->isMoving())
        <progress max="100" value="{{ $viewModel->progressPercent() }}" class="h-2 w-full max-w-prose" aria-label="{{ __('Progress of the import') }}"></progress>

        <div x-data="{ timer: null, init() { this.timer = setInterval(() => this.$ajax('{{ $viewModel->refreshUrl() }}', { target: 'import' }), 4000) }, destroy() { clearInterval(this.timer) } }"></div>
      @endif
    </div>

    @if ($viewModel->isRefused())
      <x-notice tone="danger" :title="__('This file cannot be imported')">
        {{ $viewModel->refusal() }}

        <span class="mt-1 block text-xs text-muted">{{ __('Nothing was written. Upload another file from the previous screen.') }}</span>
      </x-notice>
    @endif

    @if ($viewModel->isRead())
      <x-page-section :title="__('What the file holds')" :description="__('Counted as the file has it. What will not come over is listed with each kind.')">
        <x-detail-list>
          @foreach ($viewModel->summary() as $row)
            <x-detail-list.row :label="$row['label']">
              {{ $row['found'] }}
              @if ($row['left_out'] > 0)
                <span class="text-xs text-muted">{{ trans_choice('(:count will not come over)|(:count will not come over)', $row['left_out']) }}</span>
              @endif
            </x-detail-list.row>
          @endforeach
        </x-detail-list>
      </x-page-section>

      @if ($viewModel->reasonsForLeavingOut() !== [])
        <x-page-section :title="__('Why some things will not come over')">
          <x-detail-list>
            @foreach ($viewModel->reasonsForLeavingOut() as $row)
              <x-detail-list.row :label="$row['reason']">{{ $row['count'] }}</x-detail-list.row>
            @endforeach
          </x-detail-list>
        </x-page-section>
      @endif

      @if ($viewModel->vaultsImportedIntoBefore() !== [])
        <x-notice tone="warning" :title="__('This file was imported before')">
          {{ __('It already came into :vaults. Importing it again adds a second copy of everything it holds.', ['vaults' => implode(', ', $viewModel->vaultsImportedIntoBefore())]) }}
        </x-notice>
      @endif

      <x-page-section :title="__('Where the file goes')">
        <x-panel>
          <div x-data="{ vault: @js($viewModel->chosenVault()) }">
            <x-form method="put" :action="$viewModel->startUrl()" class="flex flex-col gap-4">
              @if ($viewModel->holdsSeveralVaults())
                <p class="text-sm text-pretty text-ink">{{ __('This file holds several vaults, and each one arrives as a new vault of its own.') }}</p>

                <x-error :messages="$errors->get('vault')" />
              @else
                <x-select id="vault" name="vault" :label="__('Vault')" :error="$errors->get('vault')" x-model="vault">
                  <option value="">{{ __('A new vault') }}</option>
                  @foreach ($viewModel->vaults() as $vault)
                    <option value="{{ $vault['id'] }}" @selected($viewModel->chosenVault() === (string) $vault['id'])>{{ $vault['name'] }}</option>
                  @endforeach
                </x-select>

                <fieldset x-show="vault === ''" :disabled="vault !== ''" class="flex flex-col gap-4">
                  <x-input
                    id="vault_name"
                    name="vault_name"
                    :label="__('Name of the new vault')"
                    :value="old('vault_name', $viewModel->sourceName())"
                    :error="$errors->get('vault_name')"
                  />
                </fieldset>

                <x-select id="me" name="me" :label="__('Which of these contacts is you')" :help="__('That contact joins you in the vault. Nothing you already have on yourself is overwritten.')">
                  <option value="">{{ __('None of them is me') }}</option>
                  @foreach ($viewModel->contactsOfTheFile() as $contact)
                    <option value="{{ $contact['uuid'] }}" @selected(old('me', $viewModel->meSourceUuid()) === $contact['uuid'])>{{ $contact['name'] }}</option>
                  @endforeach
                </x-select>
              @endif

              @if ($viewModel->storageNote() !== null)
                <p class="text-xs text-pretty text-muted">{{ $viewModel->storageNote() }}</p>
              @endif

              <div>
                <x-button>{{ __('Start the import') }}</x-button>
              </div>
            </x-form>
          </div>
        </x-panel>
      </x-page-section>
    @endif

    @if ($viewModel->isCompleted())
      <x-page-section :title="__('What came over')">
        <x-panel flush>
          <table class="w-full text-sm">
            <caption class="sr-only">{{ __('What came over, kind by kind') }}</caption>
            <thead>
              <tr class="border-b border-hairline text-left text-xs text-muted">
                <th scope="col" class="px-4 py-2.5 font-medium">{{ __('Kind') }}</th>
                <th scope="col" class="px-4 py-2.5 text-right font-medium">{{ __('Came over') }}</th>
                <th scope="col" class="px-4 py-2.5 text-right font-medium">{{ __('Left out') }}</th>
              </tr>
            </thead>
            <tbody>
              @foreach ($viewModel->report() as $row)
                <tr class="border-b border-hairline last:border-b-0">
                  <th scope="row" class="px-4 py-2.5 text-left font-normal text-ink">{{ $row['label'] }}</th>
                  <td class="px-4 py-2.5 text-right text-ink">{{ $row['imported'] }}</td>
                  <td class="px-4 py-2.5 text-right text-ink">{{ $row['left_out'] }}</td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </x-panel>
      </x-page-section>

      @if ($viewModel->leftOut() !== [])
        <x-page-section :title="__('Left out, and why')" :description="$viewModel->leftOutListNote()">
          <x-panel flush>
            <ul class="flex flex-col">
              @foreach ($viewModel->leftOut() as $item)
                <li class="flex flex-col gap-0.5 border-b border-hairline px-4 py-2.5 last:border-b-0">
                  <span class="text-sm text-ink">{{ $item['kind'] }}@if ($item['name'] !== '') · {{ $item['name'] }}@endif</span>
                  <span class="text-xs text-pretty text-muted">{{ $item['reason'] }}</span>
                </li>
              @endforeach
            </ul>
          </x-panel>
        </x-page-section>
      @endif

      @if ($viewModel->vaultUrl() !== null)
        <div>
          <x-link :href="$viewModel->vaultUrl()">{{ __('Open the vault') }}</x-link>
        </div>
      @endif
    @endif

    @if ($viewModel->isRead() || $viewModel->isRefused())
      <x-delete-action
        :title="__('Discard this import?')"
        :confirm="__('Discard it')"
        :action="$viewModel->cancelUrl()"
        :label="__('Discard this import')"
      >
        <p>{{ __('The uploaded file is deleted. Nothing was written to any vault.') }}</p>
      </x-delete-action>
    @endif

    @if ($viewModel->isWriting())
      <x-delete-action
        :title="__('Stop this import?')"
        :confirm="__('Stop and undo it')"
        :action="$viewModel->cancelUrl()"
        :label="__('Stop the import')"
      >
        <p>{{ __('Everything it has written so far is removed, and your account is left as it was before it started.') }}</p>
      </x-delete-action>
    @endif
  </div>
</x-settings.page>
