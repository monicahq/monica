{{--
  The row at the foot of both navigation columns: who is signed in, and, behind a
  click on that row, the way into their own settings and the way out of the
  product.

  It is one component rather than a block copied into each column, because the
  avatar in it is the target of the Ajax form on the profile screen. There can be
  one `shell-avatar` in a document, so there has to be one definition of it.

  The menu hangs above the row rather than below it, since the row is the last
  thing in the column and there is nothing under it.

  @var \App\Models\User $user
  @var string $item
--}}
@props([
  'user',
  'item',
])

<div x-data="{ open: false }" @keydown.escape.window="open = false" class="relative border-t border-hairline p-2">
  <div
    x-cloak
    x-show="open"
    x-transition.opacity.duration.120ms
    role="menu"
    class="absolute inset-x-2 bottom-full z-10 mb-1 overflow-hidden rounded-lg border border-hairline bg-canvas shadow-pop"
  >
    <a
      href="{{ route('settings.index') }}"
      role="menuitem"
      data-turbo="true"
      class="flex items-center gap-2.5 px-2.5 py-2 text-[13px] text-body transition-colors duration-(--duration-fast) ease-out hover:bg-hover hover:text-ink focus-visible:inset-ring-2 focus-visible:inset-ring-focus-ring/45 focus-visible:outline-none"
    >
      <x-icon name="settings" :size="16" :stroke-width="1.5" />
      {{ __('Account settings') }}
    </a>

    <form action="{{ route('auth.login.destroy') }}" method="post" class="border-t border-hairline">
      @csrf

      <button
        type="submit"
        role="menuitem"
        class="flex w-full cursor-pointer items-center gap-2.5 px-2.5 py-2 text-left text-[13px] text-body transition-colors duration-(--duration-fast) ease-out hover:bg-hover hover:text-ink focus-visible:inset-ring-2 focus-visible:inset-ring-focus-ring/45 focus-visible:outline-none"
      >
        <x-icon name="key" :size="16" :stroke-width="1.5" />
        {{ __('Sign out') }}
      </button>
    </form>
  </div>

  {{--
    The double colon on `aria-expanded` is what stops Blade from reading the
    Alpine expression after it as php.
  --}}
  <button
    type="button"
    @click="open = ! open"
    @click.outside="open = false"
    ::aria-expanded="open ? 'true' : 'false'"
    aria-haspopup="menu"
    class="{{ $item }} w-full cursor-pointer text-body hover:bg-hover hover:text-ink focus-visible:ring-[3px] focus-visible:ring-focus-ring/45 focus-visible:outline-none"
  >
    <x-avatar id="shell-avatar" :user="$user" :size="26" />

    <span class="min-w-0 flex-1 truncate text-left text-nav text-ink">{{ $user->name }}</span>

    <x-icon
      name="chevron-down"
      :size="14"
      :stroke-width="1.5"
      class="ml-auto shrink-0 text-muted transition-transform duration-(--duration-standard)"
      ::class="open && 'rotate-180'"
    />
  </button>
</div>
