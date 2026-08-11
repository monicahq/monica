{{--
  One template on the list.

  The version and what state it is in sit together at the end, because they are one
  fact: "v4, draft" and "v4, published" mean very different things about whether
  anybody is relying on it.

  The name is the link, and the button beside it says the same thing in words, since
  a row whose only way in is its title is a row somebody has to guess at.

  @var array $template
--}}
<div class="erica-row erica-row--top">
  <span class="erica-row__mark" style="background: var(--indigo-tint); color: var(--indigo)">
    <x-icon name="file" :size="18" :stroke-width="1.5" />
  </span>

  <div class="erica-row__main">
    <p class="flex flex-wrap items-center gap-x-2 gap-y-1">
      <a
        href="{{ route('settings.templateBuilder.index', $template['slug']) }}"
        data-turbo="true"
        class="erica-row__title hover:underline"
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
      <p class="erica-row__desc">{{ $template['description'] }}</p>
    @endif

    <p class="mt-1 flex flex-wrap items-baseline gap-x-2 gap-y-0.5">
      <span class="erica-caption">
        @if ($template['entityTypeName'])
          {{ __('Describes :name', ['name' => $template['entityTypeName']]) }}
        @else
          {{ __('Describes a contact') }}
        @endif
      </span>

      <span class="erica-caption" aria-hidden="true">·</span>

      <span class="erica-caption">{{ $template['sectionLabel'] }}</span>

      <span class="erica-caption" aria-hidden="true">·</span>

      <span class="erica-caption">{{ $template['updatedLabel'] }}</span>
    </p>
  </div>

  <div class="erica-row__end">
    <span class="erica-row__meta">
      <b class="font-mono">{{ $template['versionLabel'] }}</b>
      <span>{{ $template['versionSubLabel'] }}</span>
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
