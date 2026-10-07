{{-- The few facts under the name of somebody, each with its reminder when it has one. --}}
{{-- @var \App\ViewModels\Vaults\ContactViewModel $viewModel --}}
<ul id="contact-facts" x-merge="replace" class="mt-4 flex list-none flex-wrap gap-x-5.5 gap-y-2">
  @foreach ($viewModel->headerFacts() as $fact)
    <li class="flex items-center gap-1.75 text-sm text-body">
      <x-icon :name="$fact['icon']" :size="16" :stroke-width="1.5" class="text-muted" />
      @if (isset($fact['url']))
        <a href="{{ $fact['url'] }}" data-turbo="true" class="rounded-xs text-body no-underline hover:text-link focus-ring">
          {{ $fact['label'] }} <b class="font-semibold text-ink">{{ $fact['value'] }}</b>
        </a>
      @else
        {{ $fact['label'] }} <b class="font-semibold text-ink">{{ $fact['value'] }}</b>
      @endif

      @if ($fact['reminder'])
        <x-tooltip :text="$fact['reminder']">
          <x-icon name="alarm" :size="15" class="text-muted" />
        </x-tooltip>
      @endif
    </li>
  @endforeach
</ul>
