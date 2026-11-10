{{-- The logo and the name of the application, leading back to the list of vaults. --}}
{{-- @var int $size --}}
@props([
  'size' => 26,
])

<a
  href="{{ route('vaults.index') }}"
  data-turbo="true"
  class="flex min-w-0 items-center gap-2.5 rounded-md no-underline focus-ring"
>
  <x-logo :size="$size" />

  <span class="truncate text-sm font-semibold tracking-tight text-ink">{{ config('app.name') }}</span>
</a>
