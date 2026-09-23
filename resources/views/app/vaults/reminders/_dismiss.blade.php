{{-- The dialog that asks before one deadline is dismissed. --}}
{{-- @var \App\ViewModels\Vaults\ReminderRow $row --}}
<x-modal.form
  :title="__('Dismiss “:text”?', ['text' => $row->text()])"
  :description="$row->dismissalNote()"
  :confirm="__('Dismiss')"
  method="put"
  :action="$row->dismissUrl()"
>
  <x-slot:trigger>
    <x-button.secondary type="button" size="small" @click="open = true">
      <x-slot:icon>
        <x-icon name="check" :size="15" :stroke-width="1.6" />
      </x-slot:icon>

      {{ __('Dismiss') }}
    </x-button.secondary>
  </x-slot:trigger>
</x-modal.form>
