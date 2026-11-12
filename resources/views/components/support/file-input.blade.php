{{-- The files sent with a support message, leaving out on the spot any file that cannot be sent. --}}
{{--
  @var string $id
  @var string $accept
  @var int $maximumFiles
  @var int $maximumKilobytes
  @var array<int, string> $error
--}}
@props([
  'id',
  'accept',
  'maximumFiles',
  'maximumKilobytes',
  'error' => [],
])

<div
  x-data="{
    refusals: [],
    tooLarge: @js(__('“:name” is larger than 10 MB, so it was left out.')),
    tooMany: @js(trans_choice('Only :count file can be sent at once, so “:name” was left out.|Only :count files can be sent at once, so “:name” was left out.', $maximumFiles)),
  }"
  class="flex flex-col gap-1.5"
>
  <x-file-input
    :id="$id"
    name="files[]"
    :label="__('Files')"
    :accept="$accept"
    :help="trans_choice('Up to :count file of 10 MB each: screenshots, PDF or text.|Up to :count files of 10 MB each: screenshots, PDF or text.', $maximumFiles)"
    :error="$error"
    multiple
    x-on:change="refusals = window.keepAcceptedFiles($event.target, {{ $maximumKilobytes }}, {{ $maximumFiles }}).map((refusal) => (refusal.reason === 'size' ? tooLarge : tooMany).replace(':name', refusal.name))"
  />

  <div role="status">
    <ul x-show="refusals.length > 0" x-cloak class="space-y-1 text-xs text-error">
      <template x-for="refusal in refusals">
        <li x-text="refusal"></li>
      </template>
    </ul>
  </div>
</div>
