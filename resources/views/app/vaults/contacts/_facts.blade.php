{{-- The few facts under the name of somebody, each with its reminder when it has one. --}}
{{-- @var \App\ViewModels\Vaults\ContactViewModel $viewModel --}}
<div id="contact-facts" x-merge="replace">
  @php
    $birthdayLine = $viewModel->birthdayLine();
  @endphp
  @if ($birthdayLine !== null)
    <p class="mt-1.5 flex flex-wrap items-center gap-x-2 gap-y-1 text-base text-body">
      @if ($birthdayLine['age'] !== null)
        <span>{{ $birthdayLine['age'] }}</span>
      @endif

      @if ($birthdayLine['age'] !== null && $birthdayLine['upcoming'] !== null)
        <span aria-hidden="true" class="text-muted-soft">·</span>
      @endif

      @if ($birthdayLine['upcoming'] !== null)
        <span class="flex items-center gap-1.5 font-semibold text-ink">
          <x-icon name="gift" :size="15" aria-hidden="true" />
          {{ $birthdayLine['upcoming'] }}
        </span>
      @endif
    </p>
  @endif

  <ul class="mt-4 flex list-none flex-wrap gap-x-5.5 gap-y-2">
    @foreach ($viewModel->headerFacts() as $fact)
      <li class="flex min-w-0 items-center gap-1.75 text-sm wrap-anywhere text-body">
        <x-icon :name="$fact['icon']" :size="16" :stroke-width="1.5" class="shrink-0 text-muted" />
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
</div>
