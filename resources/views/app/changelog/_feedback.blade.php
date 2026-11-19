{{-- The link, the votes and the comments of one change of What’s new. --}}
{{-- @var array<string, mixed> $entry --}}
<div x-data="{ open: false, loaded: false }" class="flex flex-col gap-2">
  <div class="mt-1 flex flex-wrap items-center gap-4">
    @if ($entry['link'] !== null)
      <a href="{{ $entry['link'] }}" class="focus-ring inline-flex items-center gap-1.5 rounded-sm text-sm font-medium text-ink no-underline hover:underline">
        {{ __('Try it') }}
        <x-icon name="arrow-right" :size="14" />
      </a>
    @endif

    <div class="ml-auto flex items-center gap-1.5">
      <x-icon-button
        icon="comment"
        :label="$entry['commentLabel']"
        aria-expanded="false"
        aria-controls="changelog-comments-{{ $entry['id'] }}"
        x-bind:aria-expanded="open.toString()"
        @click="open = ! open; if (open && ! loaded) { loaded = true; $ajax('{{ $entry['commentsUrl'] }}', { target: 'changelog-comments-{{ $entry['id'] }}', focus: false }) }"
      />

      <span class="mx-1 h-5 w-px bg-hairline" aria-hidden="true"></span>

      <div id="changelog-vote-{{ $entry['id'] }}" role="group" aria-label="{{ __('Is this change useful to you?') }}" class="flex items-center gap-1.5">
        <span class="sr-only" aria-live="polite">{{ $entry['vote'] === null ? '' : __('Thanks, noted') }}</span>

        @foreach ([['direction' => 'up', 'label' => __('I like this change')], ['direction' => 'down', 'label' => __('I do not like this change')]] as $choice)
          @php
            $pressed = $entry['vote'] === $choice['direction'];
          @endphp
          <form method="post" action="{{ $entry['voteUrl'] }}" x-target="changelog-vote-{{ $entry['id'] }}">
            @csrf
            @if ($pressed)
              @method('DELETE')
            @else
              @method('PUT')
              <input type="hidden" name="direction" value="{{ $choice['direction'] }}" />
            @endif

            <button
              type="submit"
              aria-label="{{ $choice['label'] }}"
              aria-pressed="{{ $pressed ? 'true' : 'false' }}"
              @class([
                'focus-ring flex size-7.5 cursor-pointer items-center justify-center rounded-md border',
                'border-primary bg-primary text-on-primary' => $pressed,
                'border-hairline bg-canvas text-body hover:bg-hover hover:text-ink' => ! $pressed,
              ])
            >
              <x-icon name="thumbs-up" :size="15" :class="$choice['direction'] === 'down' ? 'rotate-180' : ''" />
            </button>
          </form>
        @endforeach
      </div>
    </div>
  </div>

  <div x-show="open" x-cloak>
    <div id="changelog-comments-{{ $entry['id'] }}">
      <p role="status" class="text-sm text-muted">{{ __('Loading the comments…') }}</p>
    </div>
  </div>
</div>
