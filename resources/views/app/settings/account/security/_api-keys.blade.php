{{-- The API keys somebody has minted, with the way to make another and the way to revoke one. --}}
{{-- @var \App\ViewModels\Settings\Account\Security\SecurityViewModel $viewModel --}}
<div
  id="api-keys"
  x-merge="replace"
  x-data="{ creating: {{ $errors->has('name') ? 'true' : 'false' }} }"
  class="dim-when-busy"
>
  @if (session('apiKey'))
    <div class="flex flex-col gap-2 border-b border-hairline bg-sunken px-4 py-3.5">
      <p class="text-base font-semibold text-ink">{{ __('Your new API key') }}</p>

      <p class="text-sm text-pretty text-muted">{{ __('This is the only time we can show it to you. Copy it somewhere safe before you leave this screen.') }}</p>

      <x-code-value surface="canvas">{{ session('apiKey') }}</x-code-value>

      <div class="flex flex-wrap items-center gap-2.5">
        <x-copy-button
          :text="session('apiKey')"
          :label="__('Copy to the clipboard')"
          :failure="__('We could not copy it. Select the key and copy it by hand.')"
        />
      </div>
    </div>
  @endif

  <div class="flex w-full items-center gap-3 border-b border-hairline px-4 py-3.5 text-start transition-colors duration-(--duration-fast) ease-out last:border-b-0">
    <div class="min-w-0 flex-1">
      <p class="text-base font-semibold text-ink">{{ $viewModel->apiKeysHeader() }}</p>
    </div>

    <div class="ms-auto flex shrink-0 items-center gap-2.5">
      <x-button
        size="small"
        type="button"
        x-show="! creating"
        @click="creating = true; $nextTick(() => $refs.name.focus())"
      >{{ __('Create key') }}</x-button>
    </div>
  </div>

  <div x-cloak x-show="creating" class="border-b border-hairline px-4 py-4">
    <x-form method="post" :action="route('settings.apiKeys.create')" x-target="api-keys" class="flex flex-col gap-3.5">
      <x-input
        id="name"
        x-ref="name"
        :label="__('Name')"
        :placeholder="__('Deployment script')"
        :help="__('Something you will recognise in a year, such as the machine or the script that uses it.')"
        :error="$errors->get('name')"
        maxlength="255"
        required
      />

      <x-select
        id="client"
        :label="__('What will use this key')"
        :help="__('It tells the two apart on this list and in your journal. It does not change what the key reaches.')"
        :error="$errors->get('client')"
      >
        @foreach (\App\Enums\ApiKeyClientEnum::cases() as $case)
          <option value="{{ $case->value }}" @selected(old('client', 'script') === $case->value)>{{ __($case->label()) }}</option>
        @endforeach
      </x-select>

      <div class="flex flex-col gap-1">
        <x-checkbox id="sensitive_fields" :checked="false">
          {{ __('Let this key read the values of fields marked sensitive') }}
        </x-checkbox>

        <x-help
          :label="__('Help about sensitive fields')"
          :title="__('Why leave this off?')"
          guide="security.api-keys#create-a-key"
        >{{ __('Leave this off unless a device needs them. An address book on a phone can be read by the other applications on it.') }}</x-help>
      </div>

      @if ($viewModel->offersAdministrationScope())
        <div class="flex flex-col gap-1">
          <x-checkbox id="administration" :checked="false" :error="$errors->get('administration')">
            {{ __('Let this key answer support requests for the whole instance. Only a tool you trust should hold it: it reaches every account’s requests.') }}
          </x-checkbox>
        </div>
      @endif

      <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
        <x-button.secondary size="small" type="button" @click="creating = false">{{ __('Cancel') }}</x-button.secondary>

        <x-button size="small" type="submit" @click.prevent="$dispatch('confirm-password', { form: $el.form })">{{ __('Create key') }}</x-button>
      </div>
    </x-form>
  </div>

  @forelse ($viewModel->apiKeys() as $apiKey)
    <x-row
      :title="$apiKey['name']"
      :description="$apiKey['lastUsedAt']
        ? __('Created :created · last used :used', ['created' => $apiKey['createdAt'], 'used' => $apiKey['lastUsedAt']])
        : __('Created :created · never used', ['created' => $apiKey['createdAt']])"
      icon="key"
    >
      <x-slot:end>
        <div class="flex flex-wrap items-center justify-end gap-2.5" x-data="{ confirming: false }">
          <x-badge tone="neutral">{{ $apiKey['client'] }}</x-badge>

          @if ($apiKey['reachesSensitiveFields'])
            <x-badge tone="warning">{{ __('Reads sensitive fields') }}</x-badge>
          @endif

          @if ($apiKey['administers'])
            <x-badge tone="signal">{{ __('Administration') }}</x-badge>
          @endif

          <x-button.secondary size="small" type="button" x-show="! confirming" @click="confirming = true">{{ __('Revoke') }}</x-button.secondary>

          <div x-cloak x-show="confirming" class="flex flex-wrap items-center justify-end gap-2.5">
            <p class="text-xs text-error">{{ __('Anything still using it stops working, and the key cannot be recovered.') }}</p>

            <x-button.secondary size="small" type="button" @click="confirming = false">{{ __('Keep it') }}</x-button.secondary>

            <x-form method="delete" :action="route('settings.apiKeys.destroy', $apiKey['id'])" x-target="api-keys">
              <x-button.danger size="small" type="submit" @click.prevent="$dispatch('confirm-password', { form: $el.form })">{{ __('Revoke') }}</x-button.danger>
            </x-form>
          </div>
        </div>
      </x-slot:end>
    </x-row>
  @empty
    <x-empty-state :title="__('No keys yet')">
      <x-slot:icon>
        <x-icon name="key" :size="30" :stroke-width="1.4" />
      </x-slot:icon>

      {{ __('Make one when you have something to point it at. The key is shown once, when you make it, and only a hash of it is kept afterwards.') }}
    </x-empty-state>
  @endforelse
</div>
