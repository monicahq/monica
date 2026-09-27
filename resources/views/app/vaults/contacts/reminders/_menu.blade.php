{{-- The menu on a reminder's row: edit it, or delete it after a warning. --}}
{{-- @var \App\ViewModels\Vaults\ReminderRow $row --}}
@php
  $item = 'flex w-full cursor-pointer items-center gap-2.5 rounded-xs px-2.5 py-2 text-left font-sans text-sm text-ink transition-colors hover:bg-hover focus-ring-inset';
@endphp
@if ($row->editUrl() !== null && $row->deleteUrl() !== null)
  <x-actions-menu variant="ghost" :label="__('Edit or delete :name', ['name' => $row->text()])">
    <x-actions-menu.item :href="$row->editUrl()" data-turbo="true">
      <x-slot:icon>
        <x-icon name="text" :size="16" :stroke-width="1.5" />
      </x-slot:icon>

      {{ __('Edit') }}
    </x-actions-menu.item>

    <x-modal.warning
      :title="__('Delete “:name”?', ['name' => $row->text()])"
      :confirm="__('Delete the reminder')"
      :action="$row->deleteUrl()"
    >
      <x-slot:trigger>
        <button type="button" role="menuitem" @click="menuOpen = false; $refs.menuTrigger.focus(); open = true" class="{{ $item }} text-error">
          <x-icon name="close" :size="16" :stroke-width="1.5" class="shrink-0" />

          {{ __('Delete') }}
        </button>
      </x-slot:trigger>

      {{ __('This cannot be undone. Nothing further is delivered, and dates still waiting for an answer are dropped. The answers you already gave are kept.') }}
    </x-modal.warning>
  </x-actions-menu>
@endif
