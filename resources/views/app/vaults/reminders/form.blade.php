{{-- Writes a reminder with its own date about a contact or a group, or changes one already written. --}}
{{-- @var \App\ViewModels\Vaults\ReminderForm $viewModel --}}
<x-shell :title="$viewModel->title()">
  <x-slot:breadcrumb>
    <x-breadcrumb.vault :vault="$viewModel->vault()" :items="$viewModel->breadcrumb()" />
  </x-slot:breadcrumb>

  <x-slot:sidebar>
    <x-vault.sidebar :user="$viewModel->user()" :vault="$viewModel->vault()" :current="$viewModel->sidebarEntry()" />
  </x-slot:sidebar>

  <x-page-header
    :title="$viewModel->title()"
    :description="__('You are told on the day itself, once on each channel you switched on. Nothing is sent ahead of it.')"
  />

  <x-form :method="$viewModel->method()" :action="$viewModel->actionUrl()">
    <div x-data="{ cadence: @js(old('cadence', $viewModel->cadence())) }">
    <x-panel>
      <div class="flex flex-col gap-4">
        <x-input
          id="name"
          name="name"
          :label="__('What it should say')"
          :value="old('name', $viewModel->name())"
          :placeholder="__('Ask how the new job is going')"
          :error="$errors->get('name')"
          maxlength="255"
          required
        />

        <div class="grid gap-3 sm:grid-cols-2">
          <x-input
            type="date"
            id="starts_on"
            name="starts_on"
            :label="__('Date')"
            :value="old('starts_on', $viewModel->startsOn())"
            :help="__('The first day you want to be told.')"
            :error="$errors->get('starts_on')"
            required
          />

          <x-input
            type="time"
            id="time"
            name="time"
            :label="__('Local time')"
            :value="old('time', $viewModel->time())"
            :help="__('The hour you want to hear about it, on the day itself.')"
            :error="$errors->get('time')"
            required
          />
        </div>

        <div class="grid gap-3 sm:grid-cols-2">
          <x-select id="cadence" name="cadence" :label="__('How often')" x-model="cadence" :error="$errors->get('cadence')">
            @foreach ($viewModel->cadences() as $value => $label)
              <option value="{{ $value }}" @selected(old('cadence', $viewModel->cadence()) === $value)>{{ $label }}</option>
            @endforeach
          </x-select>

          <fieldset x-show="cadence !== 'once'" x-bind:disabled="cadence === 'once'">
            <x-input
              type="number"
              id="interval"
              name="interval"
              :label="__('Every so many')"
              :value="old('interval', $viewModel->interval())"
              min="1"
              :max="$viewModel->maximumInterval()"
              :error="$errors->get('interval')"
            />
          </fieldset>
        </div>

        <x-select id="timezone" name="timezone" :label="__('Time zone')" :error="$errors->get('timezone')">
          @foreach ($viewModel->zones() as $zone)
            <option value="{{ $zone['timezone'] }}" @selected(old('timezone', $viewModel->timezone()) === $zone['timezone'])>{{ $zone['label'] }}</option>
          @endforeach
        </x-select>
      </div>
    </x-panel>
    </div>

    <div class="mt-5 flex items-center justify-end gap-2.5">
      <x-button.secondary :href="$viewModel->cancelUrl()" data-turbo="true">{{ __('Cancel') }}</x-button.secondary>

      <x-button type="submit">{{ $viewModel->isEditing() ? __('Save') : __('Add the reminder') }}</x-button>
    </div>
  </x-form>
</x-shell>
