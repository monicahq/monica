{{-- The band over a partial contact, offering to make them a full contact. --}}
{{-- @var array{title: string, body: string, url: string, confirmTitle: string, confirmBody: string, refusal: string|null} $promotion --}}
<section aria-labelledby="contact-promotion-title" class="border-b border-warning-edge bg-warning-tint print:hidden">
  <div class="mx-auto flex w-full max-w-350 flex-wrap items-center gap-x-3.5 gap-y-3 px-4 py-3.5 sm:px-6">
    <x-icon name="info" :size="20" :stroke-width="1.6" class="shrink-0 text-warning" />

    <div class="flex min-w-60 flex-1 flex-col gap-0.5">
      <p id="contact-promotion-title" class="text-base font-semibold text-ink">{{ $promotion['title'] }}</p>

      <p class="text-sm text-pretty text-body">{{ $promotion['body'] }}</p>

      @if ($promotion['refusal'] !== null)
        <p id="contact-promotion-refusal" class="text-sm text-pretty text-body">{{ $promotion['refusal'] }}</p>
      @endif

      @error('partial')
        <p id="contact-promotion-error" role="alert" class="text-sm text-pretty text-error">{{ $message }}</p>
      @enderror
    </div>

    @if ($promotion['refusal'] !== null)
      <x-button type="button" aria-disabled="true" aria-describedby="contact-promotion-refusal" class="max-sm:w-full">
        {{ __('Make a full contact') }}
      </x-button>
    @else
      <x-modal.form
        :title="$promotion['confirmTitle']"
        :description="$promotion['confirmBody']"
        :confirm="__('Make a full contact')"
        :action="$promotion['url']"
        method="put"
      >
        <input type="hidden" name="partial" value="0" />

        <x-slot:trigger>
          <x-button type="button" @click="open = true" :aria-describedby="$errors->has('partial') ? 'contact-promotion-error' : null" class="max-sm:w-full">
            {{ __('Make a full contact') }}
          </x-button>
        </x-slot:trigger>
      </x-modal.form>
    @endif
  </div>
</section>
