{{--
  One template on the list.

  The version and what state it is in sit together on the right, because they
  are one fact: "v4, draft" and "v4, published" mean very different things about
  whether anybody is relying on it.

  @var array $template
--}}
<div class="flex flex-wrap items-center gap-x-4 gap-y-3 border-b border-hairline-soft px-4 py-3.5 transition-colors duration-150 first:rounded-t-xl last:rounded-b-xl last:border-b-0 hover:bg-hover">
  <div class="min-w-0 flex-1 space-y-1">
    <div class="flex flex-wrap items-center gap-x-2 gap-y-1">
      <a
        href="{{ route('settings.templateBuilder.index', $template['slug']) }}"
        data-turbo="true"
        class="text-sm font-semibold text-ink hover:underline"
      >{{ $template['name'] }}</a>

      @if ($template['isSystem'])
        <span class="rounded-sm border border-hairline bg-sunken px-2 py-px text-xs text-muted">{{ __('Supplied with Monica') }}</span>
      @endif

      @if ($template['hasDraft'])
        <span class="rounded-sm border border-hairline bg-sunken px-2 py-px text-xs text-warning">{{ __('Draft') }}</span>
      @endif

      @unless ($template['isActive'])
        <span class="rounded-sm border border-dashed border-hairline-strong px-1.5 py-px text-xs text-warning">{{ __('Not offered') }}</span>
      @endunless

      <span class="font-mono text-xs text-muted-soft">{{ $template['slug'] }}</span>
    </div>

    @if ($template['description'])
      <p class="text-sm leading-relaxed text-body">{{ $template['description'] }}</p>
    @endif

    <div class="flex flex-wrap items-center gap-x-3.5 gap-y-1 text-xs text-muted">
      @if ($template['entityTypeName'])
        <span>{{ __('Describes :name', ['name' => $template['entityTypeName']]) }}</span>
      @else
        <span>{{ __('Describes a contact') }}</span>
      @endif

      <span>{{ $template['sectionLabel'] }}</span>

      <span class="text-muted-soft">{{ $template['updatedLabel'] }}</span>
    </div>
  </div>

  <div class="flex shrink-0 flex-wrap items-center gap-2.5">
    <span class="grid justify-items-end gap-px">
      <span class="font-mono text-xs text-body">{{ $template['versionLabel'] }}</span>

      <span class="text-xs text-muted-soft">{{ $template['versionSubLabel'] }}</span>
    </span>

    <x-form method="post" :action="route('settings.templateCopy.create', $template['slug'])">
      <button
        type="submit"
        class="cursor-pointer rounded-md border border-hairline-strong bg-canvas px-2.5 py-1.5 text-sm text-body transition-colors duration-150 hover:bg-hover hover:text-ink"
      >{{ __('Copy') }}</button>
    </x-form>

    <a
      href="{{ route('settings.templateBuilder.index', $template['slug']) }}"
      data-turbo="true"
      class="rounded-md border border-hairline-strong bg-canvas px-2.5 py-1.5 text-sm font-semibold text-ink transition-colors duration-150 hover:bg-hover"
    >{{ $template['openLabel'] }}</a>
  </div>
</div>
