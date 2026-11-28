{{-- Who is behind the open request, and how its status moved. --}}
{{--
  @var \App\ViewModels\Admin\SupportRequestContext $context
  @var list<array{line: string, by: string, when: string, tone: string}> $history
--}}
<aside aria-label="{{ __('About the person asking') }}" class="min-w-0 space-y-4">
  <x-panel :flush="true">
    <div class="flex items-center gap-3 border-b border-hairline px-4 py-3">
      <x-avatar-initials :name="$context->name()" :size="32" />
      <div class="flex min-w-0 flex-col">
        <span class="text-sm font-semibold text-ink">{{ $context->name() }}</span>
        <span class="text-xs wrap-anywhere text-muted">{{ $context->email() }}</span>
      </div>
    </div>

    <dl class="grid grid-cols-[auto_minmax(0,1fr)]">
      @foreach ($context->facts() as $fact)
        <dt class="border-b border-hairline-soft py-2.25 pe-3 ps-4 text-xs text-muted">{{ $fact['label'] }}</dt>
        <dd class="border-b border-hairline-soft py-2.25 pe-4 text-end text-xs wrap-anywhere text-ink">{{ $fact['value'] }}</dd>
      @endforeach
    </dl>

    <div class="px-4 py-2.5">
      <x-link :href="$context->accountUrl()" class="text-xs" turbo>{{ __('Open this account') }}</x-link>
    </div>
  </x-panel>

  <x-panel :title="__('Status history')">
    <ol class="space-y-3">
      @foreach ($history as $change)
        <li class="flex gap-2.5">
          <span @class([
            'mt-1.5 size-1.75 shrink-0 rounded-full',
            'bg-warning' => $change['tone'] === 'warning',
            'bg-success' => $change['tone'] === 'success',
            'bg-muted-soft' => $change['tone'] === 'neutral',
          ]) aria-hidden="true"></span>
          <span class="flex min-w-0 flex-col gap-px">
            <span class="text-xs font-medium text-ink">{{ $change['line'] }}</span>
            <span class="text-xs text-muted">{{ $change['by'] }} · {{ $change['when'] }}</span>
          </span>
        </li>
      @endforeach
    </ol>
  </x-panel>
</aside>
