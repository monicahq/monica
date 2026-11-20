{{-- One template on the list. --}}
{{-- @var array $template --}}
<div class="flex w-full items-start gap-3 border-b border-hairline px-4 py-3.5 text-left transition-colors duration-(--duration-fast) ease-out last:border-b-0">
  <span class="flex size-8 shrink-0 items-center justify-center rounded-sm bg-card text-body">
    <x-icon name="file" :size="18" :stroke-width="1.5" />
  </span>

  <div class="min-w-0 flex-1">
    <p class="flex flex-wrap items-center gap-x-2 gap-y-1">
      <a
        href="{{ route('settings.templateBuilder.index', $template['slug']) }}"
        data-turbo="true"
        class="text-base font-semibold text-ink hover:underline"
      >{{ $template['name'] }}</a>

      @if ($template['isSystem'])
        <x-badge>{{ __('Supplied with Monica') }}</x-badge>
      @endif

      @if ($template['hasDraft'])
        <x-badge tone="warning">{{ __('Draft') }}</x-badge>
      @endif

      @unless ($template['isActive'])
        <x-badge tone="outline">{{ __('Not offered') }}</x-badge>
      @endunless

      <code class="font-mono text-xs text-muted">{{ $template['slug'] }}</code>
    </p>

    @if ($template['description'])
      <p class="mt-px text-sm text-pretty text-muted">{{ $template['description'] }}</p>
    @endif

    <p class="mt-1 flex flex-wrap items-baseline gap-x-2 gap-y-0.5">
      <span class="text-xs text-pretty text-muted">
        @if ($template['entityTypeName'])
          {{ __('Describes :name', ['name' => $template['entityTypeName']]) }}
        @else
          {{ __('Describes a contact') }}
        @endif
      </span>

      <span class="text-xs text-pretty text-muted" aria-hidden="true">·</span>

      <span class="text-xs text-pretty text-muted">{{ $template['sectionLabel'] }}</span>

      <span class="text-xs text-pretty text-muted" aria-hidden="true">·</span>

      <span class="text-xs text-pretty text-muted">{{ $template['updatedLabel'] }}</span>
    </p>
  </div>

  <div class="ml-auto flex shrink-0 items-center gap-2.5">
    <span class="text-right">
      <b class="block font-mono text-sm font-semibold whitespace-nowrap text-ink">{{ $template['versionLabel'] }}</b>
      <span class="block text-xs whitespace-nowrap text-muted">{{ $template['versionSubLabel'] }}</span>
    </span>

    <x-button.secondary
      size="small"
      :href="route('settings.templateCopy.new', $template['slug'])"
      :aria-label="__('Copy :name', ['name' => $template['name']])"
      data-turbo="true"
    >{{ __('Copy') }}</x-button.secondary>

    <x-button.secondary
      size="small"
      :href="route('settings.templateBuilder.index', $template['slug'])"
      data-turbo="true"
    >{{ $template['openLabel'] }}</x-button.secondary>
  </div>
</div>
