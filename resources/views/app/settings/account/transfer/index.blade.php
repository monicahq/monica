{{-- Taking an account's data out, and bringing data in from an export. --}}
{{-- @var \App\ViewModels\Settings\Account\Transfer\AccountTransferViewModel $viewModel --}}
<x-settings.page :title="__('Export and import')" :user="$viewModel->user()" current="transfer">
  <x-modal.confirm-password
    :offers-password="$viewModel->credentials()->offersPassword()"
    :offers-passkey="$viewModel->credentials()->offersPasskey()"
    :description="__('An export holds everything in your vaults and an import writes a whole file into your account, so we ask for a credential first. You will not be asked again for a little while.')"
  />

  <div class="flex flex-col gap-7">
    <x-page-header
      :title="__('Export and import')"
      :description="__('Take the data of your vaults out as a file, or bring an export of Monica 4, or one made here, into a vault. Nothing reaches a vault until you have seen what the file holds and chosen where it goes.')"
      class="max-w-prose"
    />

    <x-page-section :title="__('Export')" :description="__('One file holding the contacts, entities, relationships, life events, reminders and files of the vaults you choose, with the templates they use. It can be downloaded for seven days.')">
      <x-panel>
        <x-form method="post" :action="$viewModel->exportUrl()" class="flex flex-col gap-4">
          <x-select id="export-vault" name="vault" :label="__('What to export')" :error="$errors->get('vault')">
            <option value="">{{ __('Every vault') }}</option>
            @foreach ($viewModel->vaults() as $vault)
              <option value="{{ $vault['id'] }}" @selected((string) old('vault') === (string) $vault['id'])>{{ $vault['name'] }}</option>
            @endforeach
          </x-select>

          <x-notice tone="warning" :title="__('The file is not encrypted')">
            {{ __('It holds everything in the vaults you choose, sensitive fields included. Anybody who gets hold of it can read all of it, so keep it somewhere only you can reach.') }}
          </x-notice>

          <div id="export-actions" class="flex flex-wrap items-center gap-3.5">
            @if ($viewModel->exportRefusal() !== null)
              <x-button type="button" aria-disabled="true" aria-describedby="export-busy-reason">{{ __('Prepare the export') }}</x-button>

              <p id="export-busy-reason" class="max-w-reading-narrow text-xs text-pretty text-muted">{{ $viewModel->exportRefusal() }}</p>
            @else
              <x-button @click.prevent="$dispatch('confirm-password', { form: $el.form })">{{ __('Prepare the export') }}</x-button>
            @endif
          </div>
        </x-form>
      </x-panel>
    </x-page-section>

    <x-page-section :title="__('Earlier exports')">
      <div id="exports">
        @if ($viewModel->isPreparingAnExport())
          <div x-data="{ timer: null, init() { this.timer = setInterval(() => this.$ajax('{{ $viewModel->refreshUrl() }}', { target: 'exports export-actions' }), 4000) }, destroy() { clearInterval(this.timer) } }"></div>
        @endif

        <x-list-panel :items="$viewModel->exports()">
          <x-slot:empty>
            <x-empty-state :title="__('Nothing exported yet')">
              <x-slot:icon>
                <x-icon name="file" :size="30" :stroke-width="1.4" />
              </x-slot:icon>
            </x-empty-state>
          </x-slot:empty>

          @foreach ($viewModel->exports() as $export)
            <div class="flex flex-wrap items-center gap-3 border-b border-hairline px-4 py-3 last:border-b-0">
              <span class="text-sm font-medium text-ink">{{ $export['vaults'] }}</span>

              <x-badge :tone="$export['tone']" :id="'export-'.$loop->index.'-status'">{{ $export['status'] }}</x-badge>

              @if ($export['size'] !== null)
                <span class="text-xs text-muted">{{ $export['size'] }}</span>
              @endif

              <span class="ml-auto flex flex-wrap items-center gap-3">
                @if ($export['download_url'] !== null)
                  <x-timestamp :at="$export['expires_at']" class="text-xs text-muted">{{ __('Available until :date', ['date' => $export['expires_at']->isoFormat('LL')]) }}</x-timestamp>

                  <form method="get" action="{{ $export['download_url'] }}">
                    <x-button.ghost size="small" @click.prevent="$dispatch('confirm-password', { form: $el.form })">{{ __('Download') }}</x-button.ghost>
                  </form>
                @else
                  <x-timestamp :at="$export['created_at']" class="text-xs text-muted">{{ $export['created_at']->diffForHumans() }}</x-timestamp>

                  <x-button.ghost size="small" type="button" aria-disabled="true" :aria-describedby="'export-'.$loop->index.'-status'">{{ __('Download') }}</x-button.ghost>
                @endif
              </span>
            </div>
          @endforeach
        </x-list-panel>
      </div>
    </x-page-section>

    <x-page-section :title="__('Import')" :description="__('The file is read first. You then choose the vault, and which of its contacts is you.')">
      <x-panel>
        <x-form method="post" :action="$viewModel->uploadUrl()" upload class="flex flex-col gap-4">
          <x-file-input
            id="file"
            name="file"
            :label="__('The export file')"
            accept=".json,application/json"
            :help="__('The .json file exported by Monica 4, or by the export above, up to :size.', ['size' => $viewModel->maximumUpload()])"
            :error="$errors->get('file')"
            required
          />

          <div class="flex flex-wrap items-center gap-3.5">
            @if ($viewModel->importRefusal() !== null)
              <x-button type="button" aria-disabled="true" aria-describedby="import-busy-reason">{{ __('Read this file') }}</x-button>

              <p id="import-busy-reason" class="max-w-reading-narrow text-xs text-pretty text-muted">{{ $viewModel->importRefusal() }}</p>
            @else
              <x-button @click.prevent="$dispatch('confirm-password', { form: $el.form })">{{ __('Read this file') }}</x-button>
            @endif
          </div>
        </x-form>
      </x-panel>
    </x-page-section>

    <x-page-section :title="__('Earlier imports')">
      <x-list-panel :items="$viewModel->imports()">
        <x-slot:empty>
          <x-empty-state :title="__('Nothing imported yet')">
            <x-slot:icon>
              <x-icon name="file" :size="30" :stroke-width="1.4" />
            </x-slot:icon>
          </x-empty-state>
        </x-slot:empty>

        @foreach ($viewModel->imports() as $import)
          <a href="{{ $import['url'] }}" class="flex flex-wrap items-center gap-3 border-b border-hairline px-4 py-3 last:border-b-0 hover:bg-hover focus-ring-inset">
            <span class="text-sm font-medium text-ink">{{ $import['source'] }}</span>

            <x-badge :tone="$import['tone']">{{ $import['status'] }}</x-badge>

            <x-timestamp :at="$import['created_at']" class="ml-auto text-xs text-muted">{{ $import['created_at']->diffForHumans() }}</x-timestamp>
          </a>
        @endforeach
      </x-list-panel>
    </x-page-section>
  </div>
</x-settings.page>
