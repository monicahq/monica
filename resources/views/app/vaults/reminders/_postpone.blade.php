{{-- The dialog that moves one deadline to a later instant. --}}
{{--
  @var \App\ViewModels\Vaults\ReminderRow $row
  @var bool $inMenu  true when the dialog opens from an actions menu, which takes focus back on close
--}}
<x-modal.form
  :title="$row->text()"
  :confirm="__('Move it')"
  method="put"
  :action="$row->postponeUrl()"
  :open="$errors->has('postponement')"
>
  <x-slot:trigger>
    @if ($inMenu ?? false)
      <button
        type="button"
        role="menuitem"
        @click="menuOpen = false; $refs.menuTrigger.focus(); open = true"
        class="flex w-full cursor-pointer items-center gap-2.5 rounded-xs px-2.5 py-2 text-left font-sans text-sm text-ink transition-colors hover:bg-hover focus-ring-inset"
      >
        <x-icon name="clock" :size="16" :stroke-width="1.5" class="shrink-0" />

        {{ __('Remind me later') }}
      </button>
    @else
      <x-icon-button icon="clock" :label="__('Remind me later')" align="right" @click="open = true" />
    @endif
  </x-slot:trigger>

  <x-slot:header>
    <div class="flex items-start gap-3">
      @if ($row->contactName() !== null)
        <x-contact-avatar :name="$row->contactName()" :url="$row->avatarUrl()" :size="38" />
      @endif

      <div class="flex min-w-0 flex-col gap-0.5">
        <p class="text-card-title text-ink">{{ $row->text() }}</p>

        <p class="text-sm text-pretty text-muted">{{ $row->currently() }}</p>
      </div>
    </div>
  </x-slot:header>

  <x-error :messages="$errors->get('postponement')" />

  <fieldset class="flex flex-col gap-1.5">
    <legend class="pb-1.5 text-sm font-medium text-ink">{{ __('Move it to') }}</legend>

    @foreach ($row->presets() as $preset)
      <x-option-row
        :id="'preset-'.$row->uuid().'-'.$preset['value']"
        name="preset"
        type="radio"
        :value="$preset['value']"
        :hint="$preset['hint']"
      >{{ $preset['label'] }}</x-option-row>
    @endforeach
  </fieldset>

  <div class="flex flex-col gap-3 border-t border-hairline pt-3.5">
    <p class="text-sm text-pretty text-muted">{{ __('Or pick the moment yourself.') }}</p>

    <div class="flex flex-wrap gap-3">
      <x-input
        type="date"
        :id="'date-'.$row->uuid()"
        name="date"
        :label="__('Date')"
        :value="old('date')"
        :error="$errors->get('date')"
        class="flex-1"
      />

      <x-input
        type="time"
        :id="'time-'.$row->uuid()"
        name="time"
        :label="__('Time')"
        :value="old('time')"
        :error="$errors->get('time')"
        class="flex-1"
      />
    </div>

    <p class="text-xs text-pretty text-muted">{{ __('Times are read in :zone, the time zone this reminder is sent in.', ['zone' => $row->zone()]) }}</p>
  </div>
</x-modal.form>
