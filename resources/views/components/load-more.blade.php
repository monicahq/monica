{{-- Rows read from the top, inside another element: the way to ask for the next page, and the word that it arrived. --}}
{{--
  @var string $rowsId
  @var string $footerId
  @var string|null $nextPageUrl
  @var bool $showsEnd  draws the end of list line in place of Load more on the last page
  @var int $count  how many rows this page holds, for the announcement
  @var string|null $rowsClass
--}}
@props([
  'rowsId',
  'footerId',
  'nextPageUrl' => null,
  'showsEnd' => false,
  'count' => 0,
  'rowsClass' => null,
])

<div x-data x-on:ajax:merged="$refs.announcement.textContent = $root.querySelector('[data-added]')?.dataset.added ?? ''" {{ $attributes }}>
  <p x-ref="announcement" aria-live="polite" class="sr-only"></p>

  <div id="{{ $rowsId }}" x-merge="append" @if ($rowsClass) class="{{ $rowsClass }}" @endif>{{ $slot }}</div>

  <div id="{{ $footerId }}" data-added="{{ trans_choice(':count row added|:count rows added', $count) }}" @class(['pt-3' => $nextPageUrl || $showsEnd])>
    @if ($nextPageUrl)
      <x-link x-target="{{ $rowsId }} {{ $footerId }}" :href="$nextPageUrl" x-autofocus class="text-sm">{{ __('Load more') }}</x-link>
    @elseif ($showsEnd)
      <p tabindex="-1" x-autofocus class="text-sm text-muted focus-ring">{{ __('End of list') }}</p>
    @endif
  </div>
</div>
