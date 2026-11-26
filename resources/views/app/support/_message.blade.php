{{-- One message of a support thread, as its author reads it, with the files sent with it. --}}
{{-- @var array{id: string, author: string, isSupportTeam: bool, when: string, body: string, files: list<array{name: string, size: string, url: string}>} $message --}}
<article
  @class([
    'flex gap-3 border-b border-hairline-soft py-4 pr-5 last:border-b-0',
    'border-l-[3px] border-l-ink bg-hover pl-9 sm:pl-16' => $message['isSupportTeam'],
    'pl-5' => ! $message['isSupportTeam'],
  ])
  aria-labelledby="message-{{ $message['id'] }}"
>
  <x-avatar-initials :name="$message['author']" :size="32" />

  <div class="min-w-0 flex-1 space-y-1.5">
    <div class="flex flex-wrap items-center gap-x-2 gap-y-1">
      <h3 id="message-{{ $message['id'] }}" class="text-sm font-semibold text-ink">{{ $message['author'] }}</h3>

      @if ($message['isSupportTeam'])
        <x-badge tone="accent">{{ __('Support team') }}</x-badge>
      @endif

      <span class="text-xs text-muted">{{ $message['when'] }}</span>
    </div>

    <x-support.message-body :markdown="$message['body']" class="max-w-prose" />

    @if ($message['files'] !== [])
      <ul class="flex flex-wrap gap-2 pt-0.5">
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
  </div>
</article>
