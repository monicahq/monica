{{-- One comment in the admin changelog, with its author, its replies and what an administrator may do. --}}
{{-- @var array<string, mixed> $comment --}}
<div x-data="{ replying: false }" class="flex flex-col gap-3">
  <article
    aria-label="{{ __('Comment by :name', ['name' => $comment['author']]) }}"
    @class([
      'flex flex-col gap-2 rounded-lg p-4',
      'border border-dashed border-hairline-strong bg-card' => $comment['isHidden'],
      'border border-dashed border-warning-edge bg-warning-tint' => ! $comment['isHidden'] && $comment['isPrivate'],
      'border border-hairline bg-canvas shadow-panel' => ! $comment['isHidden'] && ! $comment['isPrivate'],
    ])
  >
    <div class="flex flex-wrap items-center gap-x-2.5 gap-y-1.5">
      <x-avatar-initials :name="$comment['author']" :size="24" />
      <h3 class="text-base font-semibold text-ink">{{ $comment['author'] }}</h3>

      @if ($comment['isAnonymous'])
        <x-badge tone="info">{{ __('Anonymous to others') }}</x-badge>
      @endif

      @if ($comment['isPrivate'])
        <x-badge tone="warning" icon="lock">{{ __('Private') }}</x-badge>
      @endif

      @if ($comment['isNew'])
        <x-badge tone="accent">{{ __('To review') }}</x-badge>
      @endif

      @if ($comment['isHidden'])
        <x-badge>{{ __('Hidden') }}</x-badge>
      @endif

      <x-timestamp :at="$comment['createdAt']" class="ml-auto text-xs text-muted">{{ $comment['createdAt']->diffForHumans() }}</x-timestamp>
    </div>

    <p class="text-xs text-muted">{{ $comment['who'] }}</p>
    <p @class(['max-w-[72ch] text-base text-pretty whitespace-pre-line', 'text-muted' => $comment['isHidden'], 'text-body' => ! $comment['isHidden']])>{{ $comment['body'] }}</p>

    <div class="flex flex-wrap items-center gap-2 pt-1">
      @if ($comment['isNew'])
        <form method="post" action="{{ $comment['reviewUrl'] }}">
          @csrf
          <x-button.secondary size="small">{{ __('Mark as reviewed') }}</x-button.secondary>
        </form>
      @endif

      <x-button.ghost type="button" size="small" x-ref="replyToggle" aria-expanded="false" aria-controls="reply-form-{{ $comment['id'] }}" x-bind:aria-expanded="replying.toString()" @click="replying = ! replying; if (replying) { $nextTick(() => document.getElementById('reply-{{ $comment['id'] }}').focus()) }">{{ __('Reply') }}</x-button.ghost>

      @if (! $comment['isHidden'] && $comment['hideRefusal'] !== null)
        <x-button.ghost type="button" size="small" aria-disabled="true" aria-describedby="hide-refusal-{{ $comment['id'] }}">{{ __('Hide from What’s new') }}</x-button.ghost>
        <span id="hide-refusal-{{ $comment['id'] }}" class="text-xs text-body">{{ $comment['hideRefusal'] }}</span>
      @elseif (! $comment['isHidden'])
        <x-modal.warning :title="__('Hide this comment from What’s new?')" :confirm="__('Hide the comment')" :action="$comment['hidingUrl']" method="post">
          <x-slot:trigger>
            <x-button.ghost type="button" size="small" aria-haspopup="dialog" @click="open = true">{{ __('Hide from What’s new') }}</x-button.ghost>
          </x-slot:trigger>

          <p>{{ __('Nobody but its author will see it under the change. You can show it again later.') }}</p>
        </x-modal.warning>
      @endif

      @if ($comment['isHidden'])
        <form method="post" action="{{ $comment['hidingUrl'] }}">
          @csrf
          @method('DELETE')
          <x-button.ghost size="small">{{ __('Show it again') }}</x-button.ghost>
        </form>
      @endif
    </div>
  </article>

  @foreach ($comment['replies'] as $reply)
    <article class="ml-12 flex flex-col gap-2 rounded-lg border border-hairline-strong border-l-3 border-l-ink bg-hover p-4">
      <div class="flex flex-wrap items-center gap-x-2.5 gap-y-1.5">
        <x-avatar-initials :name="$reply['author']" :size="24" />
        <h3 class="text-base font-semibold text-ink">{{ $reply['author'] }}</h3>
        <x-badge tone="accent">{{ __('Administrator') }}</x-badge>
        <span class="text-xs text-body">{{ $reply['kind'] }}</span>
        <x-timestamp :at="$reply['createdAt']" class="ml-auto text-xs text-body">{{ $reply['createdAt']->diffForHumans() }}</x-timestamp>
      </div>
      <p class="max-w-[72ch] text-base text-pretty whitespace-pre-line text-body">{{ $reply['body'] }}</p>
    </article>
  @endforeach

  <form id="reply-form-{{ $comment['id'] }}" x-show="replying" x-cloak method="post" action="{{ $comment['replyUrl'] }}" class="ml-12 flex flex-col gap-2.5 rounded-lg border border-hairline bg-canvas p-4 shadow-panel">
    @csrf
    <x-textarea :id="'reply-'.$comment['id']" name="body" :label="$comment['replyLabel']" :rows="3" :placeholder="__('Write your reply…')" :help="$comment['replyHelp']" required />
    <div class="flex gap-2">
      <x-button size="small">{{ __('Send reply') }}</x-button>
      <x-button.ghost type="button" size="small" @click="replying = false; $nextTick(() => $refs.replyToggle.focus())">{{ __('Cancel') }}</x-button.ghost>
    </div>
  </form>
</div>
