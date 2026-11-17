{{-- The tabs of the admin changelog: the comment views and the votes. --}}
{{-- @var list<array{key: string, label: string, url: string, selected: bool, count: int|null}> $tabs --}}
<x-tabs :aria-label="__('Views')">
  @foreach ($tabs as $tab)
    <x-tabs.item :href="$tab['url']" :selected="$tab['selected']" :count="$tab['count']">{{ $tab['label'] }}</x-tabs.item>
  @endforeach
</x-tabs>
