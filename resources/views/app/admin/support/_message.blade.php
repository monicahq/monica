{{-- One entry of a support thread as the administrators read it: a message, a reply or a note. --}}
{{-- @var array{id: string, author: string, kind: string, when: string, body: string, files: list<array{name: string, size: string, url: string}>} $message --}}
<article
  aria-labelledby="message-{{ $message['id'] }} message-{{ $message['id'] }}-kind"
  @class([
    'space-y-2 rounded-lg border p-4',
    'border-dashed border-warning-edge bg-warning-tint sm:ms-6' => $message['kind'] === 'note',
    'ms-4 border-hairline-strong border-s-[3px] border-s-ink bg-hover sm:ms-12' => $message['kind'] === 'reply',
    'border-hairline bg-canvas shadow-panel' => $message['kind'] === 'message',
  ])
>
  <div class="flex flex-wrap items-center gap-x-2.5 gap-y-1.5">
    @if ($message['kind'] === 'note')
      <span class="flex items-center gap-1.5 text-xs font-semibold text-warning">
        <x-icon name="lock" :size="14" :stroke-width="1.6" />
        {{ __('Internal note') }}
      </span>
    @else
      <x-avatar-initials :name="$message['author']" :size="24" />
    @endif

    <h3 id="message-{{ $message['id'] }}" class="text-sm font-semibold text-ink">{{ $message['author'] }}</h3>

    @if ($message['kind'] === 'reply')
      <x-badge tone="accent">{{ __('Administrator') }}</x-badge>
    @endif

    <span id="message-{{ $message['id'] }}-kind" class="text-xs text-muted">
      @if ($message['kind'] === 'note')
        {{ __('Only administrators see this') }}
      @elseif ($message['kind'] === 'reply')
        {{ __('Reply sent by email') }}
      @else
        {{ __('wrote') }}
      @endif
    </span>

    <span class="ms-auto text-xs text-muted">{{ $message['when'] }}</span>
  </div>

  <x-support.message-body :markdown="$message['body']" class="max-w-[72ch]" />

  @if ($message['files'] !== [])
    <ul class="flex flex-wrap gap-2">
      @foreach ($message['files'] as $file)
        <li>
          <a href="{{ $file['url'] }}" class="inline-flex items-center gap-2 rounded-md border border-hairline bg-card px-2.5 py-1.5 text-xs no-underline hover:bg-hover focus-ring">
            <x-icon name="file" :size="15" :stroke-width="1.5" class="text-muted" />
            <span class="font-medium text-ink">{{ $file['name'] }}</span>
            <span class="text-muted">{{ $file['size'] }}</span>
          </a>
        </li>
      @endforeach
    </ul>
  @endif
</article>
