{{--
  One template on the list.

  The version and what state it is in sit together at the end, because they are one
  fact: "v4, draft" and "v4, published" mean very different things about whether
  anybody is relying on it.

  The name is the link, and the button beside it says the same thing in words, since
  a row whose only way in is its title is a row somebody has to guess at.

  @var array $template
--}}
<div class="flex w-full items-start gap-3 border-b border-hairline px-4 py-3.5 text-left transition-[background-color] duration-(--duration-fast) ease-out last:border-b-0">
  <span class="flex size-8 shrink-0 items-center justify-center rounded-sm bg-indigo-tint text-indigo">
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

      <code class="font-mono text-[12.5px] text-muted">{{ $template['slug'] }}</code>
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

    <x-form method="post" :action="route('settings.templateCopy.create', $template['slug'])">
      <x-button.secondary size="small">{{ __('Copy') }}</x-button.secondary>
    </x-form>

    <x-button.secondary
      size="small"
      :href="route('settings.templateBuilder.index', $template['slug'])"
      data-turbo="true"
    >{{ $template['openLabel'] }}</x-button.secondary>
  </div>
</div>
