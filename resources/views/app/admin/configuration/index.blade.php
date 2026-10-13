{{-- How this instance behaves, and the only screen that changes it without a terminal. --}}
{{-- @var \App\ViewModels\Admin\ConfigurationViewModel $viewModel --}}
<x-admin-layout :title="__('Configuration')">
  <x-modal.confirm-password
    :offers-password="$viewModel->credentials()->offersPassword()"
    :offers-passkey="$viewModel->credentials()->offersPasskey()"
    :description="__('What you are about to change decides how this instance behaves for everybody, so we ask for a credential first. You will not be asked again for a little while.')"
  />

  <x-page-header
    :title="__('Configuration')"
    :description="__('What governs this instance. A change takes effect on the next request, and work already running finishes under the value it started with.')"
  />

  @foreach ($viewModel->groups() as $group)
    <x-page-section :title="$group['label']">
      <div class="divide-y divide-hairline rounded-lg border border-hairline bg-card">
        @foreach ($group['settings'] as $setting)
          <div class="flex flex-col gap-2 px-4 py-3.5 sm:flex-row sm:items-start sm:justify-between sm:gap-6">
            <div class="min-w-0 sm:max-w-md">
              <p class="text-label text-ink">{{ $setting['description'] }}</p>

              <p class="mt-1 flex flex-wrap items-baseline gap-1.5">
                <x-caption>{{ __('Environment variable') }}</x-caption>
                <x-code-value>{{ $setting['environmentVariable'] }}</x-code-value>
              </p>

              @if ($setting['reason'])
                <p id="reason-{{ $setting['name'] }}" class="mt-1 text-xs text-muted">{{ $setting['reason'] }}</p>
              @endif

              @if ($setting['disagreesWithDeployment'])
                <p id="deployment-{{ $setting['name'] }}" class="mt-1 text-xs text-muted">
                  {{ __('This instance was deployed with :value. That value is no longer the one obeyed.', ['value' => $setting['deploymentValue']]) }}
                </p>
              @endif

              @if ($setting['kind'] === 'secret')
                <p class="mt-1 text-xs text-muted">
                  {{ $setting['secretIsSet'] ? __('A secret is set. It is never shown back.') : __('No secret is set.') }}
                </p>
              @endif
            </div>

            <form
              method="POST"
              action="{{ route('admin.configuration.update', ['setting' => $setting['name']]) }}"
              class="flex shrink-0 flex-col items-end gap-2"
              @if ($setting['warning'])
                x-data="{
                  confirming: false,
                  current: @js($setting['value']),
                  mode: @js($setting['warnsWhen']),
                  takesSomethingAway(next) {
                    if (this.mode === 'replaced') {
                      return String(next) !== ''
                    }

                    const isOn = this.current === true || String(this.current) === '1'

                    if (this.mode === 'switchedOff') {
                      return isOn && String(next) === '0'
                    }

                    if (this.mode === 'switchedOn') {
                      return ! isOn && String(next) === '1'
                    }

                    return Number(next) < Number(this.current)
                  },
                }"
              @else
                x-data
              @endif
            >
              @csrf
              @method('PUT')

              <div class="flex items-start gap-2">
              @if ($setting['kind'] === 'boolean')
                <x-select
                  :id="$setting['name']"
                  name="value"
                  x-ref="value"
                  :aria-disabled="$setting['changeable'] ? null : 'true'"
                  :aria-describedby="$setting['reason'] ? 'reason-'.$setting['name'] : null"
                  class="w-44"
                >
                  <option value="1" @selected($setting['value'])>{{ __('Yes') }}</option>
                  <option value="0" @selected(! $setting['value'])>{{ __('No') }}</option>
                </x-select>
              @else
                <x-input
                  :id="$setting['name']"
                  name="value"
                  x-ref="value"
                  :type="$setting['kind'] === 'secret' ? 'password' : 'text'"
                  :value="$setting['kind'] === 'secret' ? null : $setting['value']"
                  :placeholder="$setting['kind'] === 'secret' ? __('Leave empty to keep it') : null"
                  :readonly="! $setting['changeable'] ?: null"
                  :aria-disabled="$setting['changeable'] ? null : 'true'"
                  :aria-describedby="$setting['reason'] ? 'reason-'.$setting['name'] : null"
                  :error="$errors->first('value')"
                  class="w-44"
                />
              @endif

              @if (! $setting['changeable'])
                <x-button.secondary
                  type="submit"
                  size="small"
                  aria-disabled="true"
                  :aria-describedby="$setting['reason'] ? 'reason-'.$setting['name'] : null"
                  onclick="return false"
                >
                  {{ __('Save') }}
                </x-button.secondary>
              @elseif ($setting['warning'])
                <x-button.secondary
                  type="submit"
                  size="small"
                  :aria-describedby="$setting['reason'] ? 'reason-'.$setting['name'] : null"
                  @click.prevent="if (! confirming && takesSomethingAway($refs.value.value)) { confirming = true } else { $dispatch('confirm-password', { form: $el.form }) }"
                >
                  {{ __('Save') }}
                </x-button.secondary>
              @else
                <x-button.secondary
                  type="submit"
                  size="small"
                  :aria-describedby="$setting['reason'] ? 'reason-'.$setting['name'] : null"
                  @click.prevent="$dispatch('confirm-password', { form: $el.form })"
                >
                  {{ __('Save') }}
                </x-button.secondary>
              @endif
              </div>

              @if ($setting['warning'])
                <p x-cloak x-show="confirming" role="alert" class="max-w-xs text-right text-xs text-error">
                  {{ $setting['warning'] }} {{ __('Save again to go ahead.') }}
                </p>
              @endif
            </form>

            @if ($setting['changed'])
              <x-delete-action
                :title="$setting['kind'] === 'secret' ? __('Clear this secret?') : __('Put this back?')"
                :confirm="$setting['kind'] === 'secret' ? __('Clear it') : __('Put it back')"
                :label="$setting['kind'] === 'secret' ? __('Clear') : __('Put back')"
                :action="route('admin.configuration.destroy', ['setting' => $setting['name']])"
                size="small"
                confirms-password
              >
                <p>{{ __('This instance goes back to the value its deployment supplies.') }}</p>
              </x-delete-action>
            @endif
          </div>
        @endforeach
      </div>
    </x-page-section>
  @endforeach

  @include('app.admin.configuration._telegram', ['panel' => $viewModel->telegram()])

  @if ($viewModel->hasOrphaned())
    <x-page-section
      :title="__('No longer used')"
      :description="__('This instance still holds a value for these. The product no longer carries them, so nothing reads them any more.')"
    >
      <div class="divide-y divide-hairline rounded-lg border border-hairline bg-card">
        @foreach ($viewModel->orphaned() as $orphan)
          <div class="flex items-center justify-between gap-3 px-4 py-3.5">
            <x-code-value>{{ $orphan['name'] }}</x-code-value>

            <x-delete-action
              :title="__('Remove this value?')"
              :confirm="__('Remove it')"
              :label="__('Remove')"
              :action="route('admin.configuration.orphans.destroy', ['name' => $orphan['name']])"
              size="small"
              confirms-password
            >
              <p>{{ __('Nothing reads it any more. Removing it only tidies this screen.') }}</p>
            </x-delete-action>
          </div>
        @endforeach
      </div>
    </x-page-section>
  @endif
</x-admin-layout>
