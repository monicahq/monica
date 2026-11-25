{{-- The numbered steps of a flow, with the one somebody is on marked as current. --}}
{{--
  @var list<string> $steps
  @var int $current
--}}
@props([
  'steps',
  'current',
])

<div {{ $attributes->class(['text-sm']) }}>
  <ol class="flex flex-wrap items-center gap-x-4 gap-y-2" aria-label="{{ __('Steps') }}">
    @foreach ($steps as $index => $step)
      @php
        $number = $index + 1;
        $isCurrent = $number === $current;
        $isDone = $number < $current;
      @endphp

      <li @if ($isCurrent) aria-current="step" @endif class="flex items-center gap-2 {{ $isCurrent ? 'font-semibold text-ink' : 'text-muted' }}">
        <span
          aria-hidden="true"
          class="flex size-5.5 items-center justify-center rounded-full border text-xs tabular-nums {{ $isCurrent ? 'border-ink bg-ink text-canvas' : ($isDone ? 'border-hairline-strong bg-hover text-ink' : 'border-hairline-strong') }}"
        >
          @if ($isDone)
            <x-icon name="check" :size="12" :stroke-width="2" />
          @else
            {{ $number }}
          @endif
        </span>

        <span>
          {{ $step }}
          @if ($isDone)
            <span class="sr-only">{{ __('(done)') }}</span>
          @endif
        </span>
      </li>
    @endforeach
  </ol>
</div>
