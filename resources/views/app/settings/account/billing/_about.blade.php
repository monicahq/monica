{{-- Why Monica asks for money, and where its source code is. --}}
<section aria-labelledby="billing-about" class="grid gap-8 border-t border-ink pt-8 lg:grid-cols-[minmax(0,1.3fr)_minmax(0,1fr)] lg:gap-12">
  <h2 id="billing-about" class="text-[32px] leading-[1.15] font-semibold tracking-[-0.035em] text-balance text-ink">{{ __('Your relationships are for the long run. We’re building Monica that way, too.') }}</h2>

  <div class="flex flex-col gap-3">
    <p class="text-[14px] leading-relaxed text-pretty text-body">{{ __('Since 2016, we’ve been building Monica around a simple idea: helping people take better care of their relationships.') }}</p>

    <p class="text-[14px] leading-relaxed text-pretty text-body">{{ __('Monica is open source. You can explore the code, follow its development, and host it yourself. Choosing a paid plan helps support the work behind it, and lets us take care of the hosting for you.') }}</p>

    <p class="pt-1">
      <x-link href="https://github.com/monicahq/monica" class="inline-flex items-center gap-1.5 font-medium">
        {{ __('Explore the source code') }}
        <x-icon name="arrow-right" :size="16" />
      </x-link>
    </p>
  </div>
</section>
