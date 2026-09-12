{{-- The button that puts a value on the clipboard, and the line that says whether it got there. --}}
{{--
  @var string $text
  @var string $label
  @var string $failure
  @var string $variant
  @var string $size
--}}
@props([
  'text',
  'label',
  'failure',
  'variant' => 'primary',
  'size' => 'small',
])

<div
  class="contents"
  x-data="{
    state: 'idle',
    text: @js($text),
    messages: {
      idle: '',
      copied: @js(__('Copied')),
      failed: @js($failure),
    },
    copy() {
      window.copyText(this.text).then((ok) => {
        this.state = ok ? 'copied' : 'failed'

        if (ok) {
          setTimeout(() => { if (this.state === 'copied') this.state = 'idle' }, 2500)
        }
      })
    },
  }"
>
  <x-button :variant="$variant" :href="null" type="button" :size="$size" @click="copy()">{{ $label }}</x-button>

  <p
    role="status"
    aria-live="polite"
    class="text-sm"
    :class="{ 'text-success': state === 'copied', 'text-error': state === 'failed' }"
    x-text="messages[state] ?? ''"
  ></p>
</div>
