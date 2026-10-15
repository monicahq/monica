{{-- Who is signed in, and the menu their picture opens: their settings and the way out. --}}
{{-- @var \App\Models\User $user --}}
@props([
  'user',
])

<x-actions-menu variant="ghost" :label="__('Account')">
  <x-slot:glyph>
    <x-avatar :user="$user" :size="26" />
  </x-slot:glyph>

  <x-actions-menu.item :href="route('settings.index')" data-turbo="true">
    <x-slot:icon>
      <x-icon name="settings" :size="16" :stroke-width="1.5" />
    </x-slot:icon>

    {{ __('Account settings') }}
  </x-actions-menu.item>

  <form action="{{ route('auth.login.destroy') }}" method="post">
    @csrf

    <button
      type="submit"
      role="menuitem"
      class="flex w-full cursor-pointer items-center gap-2.5 min-h-8 rounded-md px-2.5 text-left font-sans text-base text-ink transition-colors hover:bg-hover focus-ring-inset"
    >
      <x-icon name="key" :size="16" :stroke-width="1.5" class="shrink-0" />

      {{ __('Sign out') }}
    </button>
  </form>
</x-actions-menu>
