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
    {{--
      Both wordings sit in the x-data of this div rather than on the button that
      sets them, because Blade does not compile a directive written inside an
      attribute of a component tag.

      The key is read back out of the box it is printed in, so that what reaches
      the clipboard is what somebody can see. That is why the value stays tight
      against its tags below: textContent would otherwise carry this template's
      newline and indentation into the copy.
    --}}
    <div
      class="flex flex-col gap-2 border-b border-hairline bg-sunken px-4 py-3.5"
      x-data="{
        state: 'idle',
        messages: {
          idle: '',
          copied: @js(__('Copied')),
          failed: @js(__('We could not copy it. Select the key and copy it by hand.')),
        },
      }"
    >
      <p class="text-base font-semibold text-ink">{{ __('Your new API key') }}</p>

      <p class="text-sm text-pretty text-muted">{{ __('This is the only time we can show it to you. Copy it somewhere safe before you leave this screen.') }}</p>

      <p x-ref="key" class="rounded-md border border-hairline bg-canvas px-3 py-2 font-mono text-sm break-all text-ink select-all">{{ session('apiKey') }}</p>

      {{--
        The region is here whether or not it has something to say, so that a
        screen reader is already watching it when the answer arrives, and so the
        panel does not move when it does.
      --}}
      <div class="flex flex-wrap items-center gap-2.5">
        <x-button
          type="button"
          size="small"
          @click="window.copyText($refs.key.textContent).then((ok) => { state = ok ? 'copied' : 'failed'; if (ok) setTimeout(() => { if (state === 'copied') state = 'idle' }, 2500) })"
        >{{ __('Copy to the clipboard') }}</x-button>

        <p
          role="status"
          aria-live="polite"
          class="text-sm"
          :class="{ 'text-success': state === 'copied', 'text-error': state === 'failed' }"
          x-text="messages[state] ?? ''"
        ></p>
      </div>
    </div>
  @endif

  <div class="flex w-full items-center gap-3 border-b border-hairline px-4 py-3.5 text-left transition-[background-color] duration-(--duration-fast) ease-out last:border-b-0">
    <div class="min-w-0 flex-1">
      <p class="text-base font-semibold text-ink">{{ $viewModel->apiKeysHeader() }}</p>
    </div>

    <div class="ml-auto flex shrink-0 items-center gap-2.5">
      <x-button.secondary
        size="small"
        type="button"
        x-show="! creating"
        @click="creating = true; $nextTick(() => $refs.name.focus())"
      >{{ __('Create key') }}</x-button.secondary>
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

      <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
        <x-button.secondary size="small" type="button" @click="creating = false">{{ __('Cancel') }}</x-button.secondary>

        <x-button size="small">{{ __('Create key') }}</x-button>
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
        <div x-data="{ confirming: false }">
          <x-button.secondary size="small" type="button" x-show="! confirming" @click="confirming = true">{{ __('Revoke') }}</x-button.secondary>

          <div x-cloak x-show="confirming" class="flex flex-wrap items-center justify-end gap-2.5">
            <p class="text-xs text-error">{{ __('Anything still using it stops working. Sure?') }}</p>

            <x-button.secondary size="small" type="button" @click="confirming = false">{{ __('Keep it') }}</x-button.secondary>

            <x-form method="delete" :action="route('settings.apiKeys.destroy', $apiKey['id'])" x-target="api-keys">
              <x-button.danger size="small">{{ __('Revoke') }}</x-button.danger>
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
