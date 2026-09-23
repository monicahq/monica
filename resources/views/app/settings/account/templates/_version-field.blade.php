{{-- One field inside a frozen section, with the fields a repeater holds under it. --}}
{{--
  @var array $field
  @var bool $nested
--}}
<div class="flex items-center gap-3 border-b border-hairline-soft px-3.5 py-2.5 last:border-b-0 {{ $nested ? 'pl-8' : '' }}">
  <span class="text-sm text-ink">{{ $field['label'] }}</span>

  <code class="ml-auto font-mono text-xs text-muted">{{ $field['type'] }}</code>
</div>

@foreach ($field['children'] as $child)
  @include('app.settings.account.templates._version-field', ['field' => $child, 'nested' => true])
@endforeach
