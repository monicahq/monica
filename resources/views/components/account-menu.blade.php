{{-- Who is signed in, and the menu their picture opens: their settings and the way out. --}}
{{--
  @var \App\Models\User $user
  @var string $variant
--}}
@props([
  'user',
  'variant' => 'ghost',
])

<x-actions-menu :variant="$variant" :label="__('Your account')">
  <x-slot:glyph>
    <x-avatar id="shell-avatar" :user="$user" :size="28" />

    <x-icon name="chevron-down" :size="14" :stroke-width="1.4" class="-ml-1 text-muted max-sm:hidden" />
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
