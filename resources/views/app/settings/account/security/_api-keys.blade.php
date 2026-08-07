{{--
  The API keys somebody has minted, and the two things they can do about them:
  make another, and revoke one.

  A key is shown exactly once, in the block at the top, because only a hash of
  it is written down. Somebody who does not copy it there and then has to make
  another rather than look this one up, which is what the block says.

  Making a key needs a name, which the button alone cannot ask for, so it opens
  a field in place instead of leading to a screen of its own. A name that comes
  back rejected reopens it, which is what the `creating` flag reads from the
  errors rather than starting closed and hiding the message.

  Revoking asks before it acts, the way the two factor buttons above do: the
  button swaps itself for what is about to be lost and the button that goes
  through with it.

  Both forms ask for the screen again and swap this block for what comes back,
  so making a key and revoking one leave the rest of the page where it was. The
  swap replaces the block rather than morphing it, the way the rest of the app
  does, because `creating` has to come back from the server: morphing keeps the
  flag that was set when the field was opened, and the field never closes.

  @var \App\ViewModels\Settings\Account\Security\SecurityViewModel $viewModel
--}}
<div
  id="api-keys"
  x-merge="replace"
  x-data="{ creating: {{ $errors->has('name') ? 'true' : 'false' }} }"
  class="transition-opacity [&[aria-busy]]:opacity-60"
>
  @if (session('apiKey'))
    <div class="space-y-2 rounded-t-xl border-b border-hairline-soft bg-sunken px-4 py-3.5">
      <p class="text-sm font-semibold text-ink">{{ __('Your new API key') }}</p>

      <p class="text-sm text-muted">{{ __('This is the only time we can show it to you. Copy it somewhere safe before you leave this screen.') }}</p>

      <p class="rounded-md border border-hairline bg-canvas px-3 py-2 font-mono text-sm break-all text-ink select-all">{{ session('apiKey') }}</p>
    </div>
  @endif

  <x-box.row class="grid gap-x-6 gap-y-3 sm:grid-cols-[minmax(0,1fr)_auto] sm:items-center">
    <p class="text-sm font-semibold text-ink">{{ $viewModel->apiKeysHeader() }}</p>

    <x-button.secondary
      type="button"
      x-show="! creating"
      @click="creating = true; $nextTick(() => $refs.name.focus())"
      class="max-sm:w-full"
    >{{ __('Create key') }}</x-button.secondary>
  </x-box.row>

  <div x-cloak x-show="creating" class="border-b border-hairline-soft px-4 py-3.5">
    <x-form method="post" :action="route('settings.apiKeys.create')" x-target="api-keys" class="space-y-3.5">
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

      <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
        <x-button.secondary type="button" @click="creating = false">{{ __('Cancel') }}</x-button.secondary>

        <x-button>{{ __('Create key') }}</x-button>
      </div>
    </x-form>
  </div>

  @forelse ($viewModel->apiKeys() as $apiKey)
    <x-box.row class="grid gap-x-6 gap-y-3 sm:grid-cols-[minmax(0,1fr)_auto] sm:items-center">
      <div class="flex items-center gap-x-3">
        <span class="flex size-8 shrink-0 items-center justify-center rounded-md bg-sunken text-placeholder" aria-hidden="true">
          <svg width="15" height="15" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round">
            <circle cx="10.4" cy="5.6" r="3"></circle>
            <path d="M8.3 7.7 3 13"></path>
            <path d="M4.6 11.4 6 12.8"></path>
          </svg>
        </span>

        <div class="min-w-0 space-y-0.5">
          <p class="truncate text-sm font-semibold text-ink">{{ $apiKey['name'] }}</p>

          <p class="text-sm text-muted">
            @if ($apiKey['lastUsedAt'])
              {{ __('Created :created · last used :used', ['created' => $apiKey['createdAt'], 'used' => $apiKey['lastUsedAt']]) }}
            @else
              {{ __('Created :created · never used', ['created' => $apiKey['createdAt']]) }}
            @endif
          </p>
        </div>
      </div>

      <div x-data="{ confirming: false }">
        <x-button.secondary type="button" x-show="! confirming" @click="confirming = true" class="max-sm:w-full">{{ __('Revoke') }}</x-button.secondary>

        <div x-cloak x-show="confirming" class="flex flex-wrap items-center gap-3 max-sm:flex-col max-sm:items-stretch">
          <p class="text-sm text-error max-sm:text-center">{{ __('Anything still using it stops working. Sure?') }}</p>

          <x-button.secondary type="button" @click="confirming = false">{{ __('Keep it') }}</x-button.secondary>

          <x-form method="delete" :action="route('settings.apiKeys.destroy', $apiKey['id'])" x-target="api-keys">
            <x-button class="bg-error hover:bg-error/88 max-sm:w-full">{{ __('Revoke') }}</x-button>
          </x-form>
        </div>
      </div>
    </x-box.row>
  @empty
    <x-empty-state :title="__('No API keys yet')">
      <x-slot:icon>
        <svg width="20" height="20" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round">
          <circle cx="10.4" cy="5.6" r="3"></circle>
          <path d="M8.3 7.7 3 13"></path>
          <path d="M4.6 11.4 6 12.8"></path>
        </svg>
      </x-slot:icon>

      {{ __('Make one when you have something to point it at. The key is shown once, when you make it.') }}
    </x-empty-state>
  @endforelse
</div>
