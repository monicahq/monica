{{-- A field that picks a currency from a list of flags and codes, the chosen one ticked. --}}
{{--
  @var string $id
  @var string|null $name
  @var string|null $label
  @var bool $optional
  @var \App\Enums\CurrencyEnum|null $value
  @var string|array<int, string>|null $error
--}}
@props([
  'id',
  'name' => null,
  'label' => null,
  'optional' => false,
  'value' => null,
  'error' => null,
])

@php
  $currencies = \App\Enums\CurrencyEnum::cases();
  $flag = 'inline-block h-3.5 w-5 shrink-0 rounded-sm bg-cover inset-ring inset-ring-hairline-strong';
  $errorId = $error ? $id.'-error' : null;
  $options = collect($currencies)->map(fn ($currency) => [
    'value' => $currency->value,
    'code' => $currency->code(),
    'flag' => $currency->flag(),
  ])->all();
@endphp

<div
  x-data="{
    open: false,
    options: @js($options),
    value: @js($value?->value),
    active: 0,
    get chosen() { return this.options.find((option) => option.value === this.value) ?? null },
    show() {
      this.open = true
      this.active = Math.max(0, this.options.findIndex((option) => option.value === this.value))
      this.$nextTick(() => this.$refs.list.focus())
    },
    hide(returnFocus = true) {
      this.open = false
      if (returnFocus) this.$nextTick(() => this.$refs.button.focus())
    },
    choose(index) {
      this.value = this.options[index].value
      this.$dispatch('currency-chosen', { value: this.value })
      this.hide()
    },
    move(step) {
      this.active = Math.min(this.options.length - 1, Math.max(0, this.active + step))
      this.$refs.list.querySelectorAll('[role=option]')[this.active]?.scrollIntoView({ block: 'nearest' })
    },
  }"
  @focusout="if (open && ! $el.contains($event.relatedTarget)) hide(false)"
  {{ $attributes->class(['flex flex-col gap-1.5']) }}
>
  @if ($label)
    <x-label :for="$id" :value="$label" :optional="$optional" id="{{ $id }}-label" />
  @endif

  <input type="hidden" name="{{ $name ?? $id }}" :value="value ?? ''" value="{{ $value?->value }}" />

  <div class="relative">
    <button
      type="button"
      id="{{ $id }}"
      x-ref="button"
      @click="open ? hide() : show()"
      @keydown.arrow-down.prevent="show()"
      @keydown.arrow-up.prevent="show()"
      aria-haspopup="listbox"
      aria-controls="{{ $id }}-list"
      :aria-expanded="open ? 'true' : 'false'"
      aria-expanded="false"
      @if ($label) aria-labelledby="{{ $id }}-label {{ $id }}" @endif
      @if ($errorId) data-invalid aria-describedby="{{ $errorId }}" @endif
      class="focus-ring-field flex h-9 w-full cursor-pointer items-center gap-2.5 rounded-md border border-hairline-strong bg-input pr-8.5 pl-3 text-left text-[16px] text-ink transition duration-(--duration-fast) ease-out hover:border-muted-soft data-invalid:border-error data-invalid:ring-[3px] data-invalid:ring-error/20 sm:text-base"
    >
      <template x-if="chosen">
        <span class="flex items-center gap-2.5">
          <span class="{{ $flag }}" :style="`background: ${chosen.flag}`" aria-hidden="true"></span>
          <span x-text="chosen.code"></span>
        </span>
      </template>
      <span x-show="! chosen" class="text-placeholder">{{ __('Choose a currency') }}</span>
      <noscript>{{ $value?->code() }}</noscript>

      <x-icon
        name="chevron-down"
        :size="16"
        :stroke-width="1.5"
        class="pointer-events-none absolute top-1/2 right-3 -translate-y-1/2 text-muted"
      />
    </button>

    <ul
      id="{{ $id }}-list"
      x-ref="list"
      x-cloak
      x-show="open"
      x-transition.opacity.duration.120ms
      role="listbox"
      tabindex="-1"
      @if ($label) aria-labelledby="{{ $id }}-label" @endif
      :aria-activedescendant="open ? '{{ $id }}-option-' + active : null"
      @click="const option = $event.target.closest('[role=option]'); if (option) choose([...$el.children].indexOf(option))"
      @keydown.arrow-down.prevent="move(1)"
      @keydown.arrow-up.prevent="move(-1)"
      @keydown.home.prevent="active = 0"
      @keydown.end.prevent="active = options.length - 1"
      @keydown.enter.prevent="choose(active)"
      @keydown.space.prevent="choose(active)"
      @keydown.escape.prevent.stop="hide()"
      @keydown.tab="hide(false)"
      class="absolute top-full left-0 z-20 mt-1.5 max-h-72 w-full min-w-44 overflow-y-auto overscroll-contain rounded-lg border border-hairline bg-canvas p-1 shadow-pop focus:outline-none"
    >
      @foreach ($currencies as $index => $currency)
        <li
          id="{{ $id }}-option-{{ $index }}"
          role="option"
          aria-label="{{ __($currency->label()) }}"
          :aria-selected="value === '{{ $currency->value }}' ? 'true' : 'false'"
          aria-selected="{{ $value === $currency ? 'true' : 'false' }}"
          @mousemove="active = {{ $index }}"
          :class="active === {{ $index }} ? 'bg-hover' : ''"
          class="flex cursor-pointer items-center gap-2.5 rounded-md px-2 py-1.5 text-sm text-ink"
        >
          <span class="flex w-3.5 items-center justify-center" :class="value === '{{ $currency->value }}' ? '' : 'opacity-0'" aria-hidden="true">
            <x-icon name="check" :size="13" :stroke-width="1.9" />
          </span>
          <span class="{{ $flag }}" style="background: {{ $currency->flag() }};" aria-hidden="true"></span>
          <span aria-hidden="true">{{ $currency->code() }}</span>
        </li>
      @endforeach
    </ul>
  </div>

  <x-error :id="$errorId" :messages="$error" />
</div>
