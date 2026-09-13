{{-- One person in the family tree: their face, their name, and whether they lead anywhere. --}}
{{--
  @var array{name: string, url: string|null, avatarUrl: string|null, isNameOnly: bool} $node
  @var int $size
  @var bool $isSubject
  @var string $nameOnlyLine
--}}
<span class="flex w-20 flex-col items-center gap-1.5">
  @if ($node['isNameOnly'])
    <span
      class="flex shrink-0 items-center justify-center rounded-full border border-dashed border-hairline-strong text-muted-soft"
      style="width: {{ $size }}px; height: {{ $size }}px;"
      aria-hidden="true"
    >
      <x-icon name="profile" :size="14" :stroke-width="1.5" />
    </span>
  @else
    <x-contact-avatar
      :name="$node['name']"
      :url="$node['avatarUrl']"
      :size="$size"
      @class(['ring-2 ring-primary ring-offset-2 ring-offset-card' => $isSubject])
    />
  @endif

  @if ($node['url'] === null)
    <span class="w-20 truncate text-center text-xs {{ $isSubject ? 'font-semibold text-ink' : 'text-body' }}">
      {{ $node['name'] }}
    </span>
  @else
    <a
      href="{{ $node['url'] }}"
      data-turbo="true"
      class="w-20 truncate rounded-sm text-center text-xs text-body no-underline focus-ring"
    >{{ $node['name'] }}</a>
  @endif

  @if ($isSubject)
    <span class="sr-only">{{ __('The person this page is about') }}</span>
  @endif

  @if ($node['isNameOnly'])
    <span class="sr-only">{{ $nameOnlyLine }}</span>
  @endif
</span>
