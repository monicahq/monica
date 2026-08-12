{{--
  The top of every screen that sits inside a vault: which vault you are in, where
  in it you are, and the way into the menu that searches it.

  Every screen in a vault says the same three things in the same corner, so the
  vault a screen belongs to is never something to be worked out from the title. It
  replaces the trail the v2 shell drew, which said the same thing in words alone.

  The chip is the vault, and it leads to the vault's own screen rather than opening
  a menu: the dock carries the chip that switches vaults, and two controls with the
  same mark doing two different things is one too many. The steps after it are the
  breadcrumb, the last of which is where you are and is never a link.

  The search is <x-vault.search>, which the vault dashboard puts in its own bar
  beside the way out of the vault.

  @var \App\Models\Vault $vault
  @var array<int, array{label: string, icon?: string|null, url?: string|null, count?: int|string|null}> $items
--}}
@props([
  'vault',
  'items' => [],
])

<div class="flex min-w-0 items-center gap-3">
  <x-button.bar :href="route('vaults.show', $vault)" data-turbo="true" size="small">
    <span
      class="flex size-5.5 shrink-0 items-center justify-center rounded-xs text-[11px] font-bold"
      style="color: {{ $vault->colour }}; background: color-mix(in srgb, {{ $vault->colour }} 12%, transparent)"
      aria-hidden="true"
    >{{ mb_strtoupper(mb_substr($vault->name, 0, 1)) }}</span>

    <span class="max-w-32 truncate">{{ $vault->name }}</span>
  </x-button.bar>

  @if ($items !== [])
    <nav aria-label="{{ __('Breadcrumb') }}" class="flex min-w-0 items-center gap-2 text-sm text-muted max-sm:hidden">
      @foreach ($items as $item)
        <span class="text-placeholder" aria-hidden="true">/</span>

        <span class="flex min-w-0 items-center gap-1.5">
          @isset($item['icon'])
            <x-icon :name="$item['icon']" :size="16" :stroke-width="1.6" />
          @endisset

          @if (($item['url'] ?? null) !== null && ! $loop->last)
            <a href="{{ $item['url'] }}" data-turbo="true" class="truncate transition-colors duration-150 hover:text-brand">{{ $item['label'] }}</a>
          @else
            <span class="truncate font-semibold text-body" @if ($loop->last) aria-current="page" @endif>{{ $item['label'] }}</span>
          @endif

          @isset($item['count'])
            <span class="text-placeholder">{{ $item['count'] }}</span>
          @endisset
        </span>
      @endforeach
    </nav>
  @endif

  <x-vault.search />
</div>
