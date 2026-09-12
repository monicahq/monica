{{-- The line that says where you are inside a vault, which always starts at the vault itself. --}}
{{--
  @var \App\Models\Vault $vault
  @var array<int, array{label: string, url?: string|null}> $items
--}}
@props([
  'vault',
  'items' => [],
])

<x-breadcrumb
  :items="array_merge([['label' => $vault->name, 'url' => route('vaults.show', $vault)]], $items)"
  {{ $attributes }}
/>
