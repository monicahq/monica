{{-- The comments on one change of What’s new and the form to add one, loaded when the comments are opened. --}}
{{-- @var \App\ViewModels\ChangelogCommentsViewModel $viewModel --}}
@php
  $comments = $viewModel->comments();
  $entryId = $viewModel->entryId();
@endphp
<div id="changelog-comments-{{ $entryId }}" class="mt-2 flex flex-col overflow-hidden rounded-lg border border-hairline bg-canvas shadow-panel">
  @if ($comments === [])
    <p class="border-b border-hairline px-4 py-3.5 text-base text-muted">{{ __('No public comments yet. Yours can be the first, or you can write to Monica’s team privately.') }}</p>
  @else
    <ul class="flex flex-col">
      @foreach ($comments as $comment)
        <li class="flex gap-3 border-b border-hairline px-4 py-3.5">
          @if ($comment['isAnonymous'])
            <span class="size-7 flex-none rounded-full bg-hover" aria-hidden="true"></span>
          @else
            <x-avatar-initials :name="$comment['authorName']" :size="28" />
          @endif

          <div class="flex min-w-0 flex-1 flex-col gap-1">
            <div class="flex flex-wrap items-center gap-2">
              <span class="text-base font-semibold text-ink">{{ $comment['authorName'] }}</span>
              <x-timestamp :at="$comment['createdAt']" class="text-sm text-muted">{{ $comment['createdAt']->diffForHumans() }}</x-timestamp>

              @if ($comment['isMine'])
                <x-badge tone="accent">{{ __('You') }}</x-badge>
              @endif

              @if ($comment['isMine'] && $comment['isAnonymous'])
                <span class="text-sm text-muted">{{ __('Only you can see this is yours') }}</span>
              @endif

              @if ($comment['isPrivate'])
                <x-badge icon="lock">{{ __('Private, only the team sees it') }}</x-badge>
              @endif

              @if ($comment['isHidden'])
                <x-badge>{{ __('Hidden from others by the team') }}</x-badge>
              @endif
            </div>

            <p class="text-base text-body text-pretty whitespace-pre-line">{{ $comment['body'] }}</p>

            @foreach ($comment['replies'] as $reply)
              <div class="mt-2 flex flex-col gap-1 border-l-2 border-ink pl-3">
                <div class="flex flex-wrap items-center gap-2">
                  <span class="text-base font-semibold text-ink">{{ $reply['authorName'] }}</span>
                  <x-badge tone="accent">{{ __('Administrator') }}</x-badge>
                  <x-timestamp :at="$reply['createdAt']" class="text-sm text-muted">{{ $reply['createdAt']->diffForHumans() }}</x-timestamp>
                </div>
                <p class="text-base text-body text-pretty whitespace-pre-line">{{ $reply['body'] }}</p>
              </div>
            @endforeach
          </div>
        </li>
      @endforeach
    </ul>
  @endif

  <p role="status" class="sr-only">{{ session('status') }}</p>

  <form
    method="post"
    action="{{ $viewModel->formUrl() }}"
    x-target="changelog-comments-{{ $entryId }}"
    x-data="{ body: @js(old('body', '')), visibility: @js(old('visibility', 'public')) }"
    class="flex flex-col gap-2.5 bg-card px-4 py-3.5"
  >
    @csrf

    <x-textarea
      id="changelog-comment-body-{{ $entryId }}"
      name="body"
      :label="__('Your comment')"
      :rows="3"
      :placeholder="__('What works for you, what does not')"
      :error="$errors->get('body')"
      :value="old('body')"
      maxlength="2000"
      :x-autofocus="$errors->has('body')"
      x-model="body"
    />

    <div class="flex flex-wrap items-center gap-4">
      <fieldset class="flex flex-wrap gap-4">
        <legend class="sr-only">{{ __('Who can see it') }}</legend>
        <label class="inline-flex cursor-pointer items-center gap-1.5 text-base text-ink">
          <input type="radio" name="visibility" value="public" x-model="visibility" checked class="accent-primary" />
          {{ __('Public, everyone sees it') }}
        </label>
        <label class="inline-flex cursor-pointer items-center gap-1.5 text-base text-ink">
          <input type="radio" name="visibility" value="private" x-model="visibility" class="accent-primary" />
          {{ __('Private, only the team') }}
        </label>
      </fieldset>

      <fieldset x-show="visibility === 'public'" x-bind:disabled="visibility !== 'public'" class="flex flex-wrap items-center gap-4 border-l border-hairline-strong pl-4">
        <legend class="float-left mr-4 text-sm text-muted">{{ __('Post as') }}</legend>
        <label class="inline-flex cursor-pointer items-center gap-1.5 text-base text-ink">
          <input type="radio" name="identity" value="self" checked class="accent-primary" />
          {{ $viewModel->userName() }}
        </label>
        <label class="inline-flex cursor-pointer items-center gap-1.5 text-base text-ink">
          <input type="radio" name="identity" value="anonymous" class="accent-primary" />
          {{ __('Anonymous') }}
        </label>
      </fieldset>

      <x-button size="small" class="ml-auto" x-bind:disabled="body.trim() === ''">{{ __('Post comment') }}</x-button>
    </div>
  </form>
</div>
