{{-- What full access gives: unlimited contacts and vaults, and room for files and photos. --}}
{{-- @var \App\ViewModels\Settings\Account\Billing\BillingViewModel $viewModel --}}
<section aria-labelledby="billing-benefits" class="flex flex-col gap-6 pt-4">
  <h2 id="billing-benefits" class="text-[22px] font-semibold tracking-[-0.02em] text-ink">{{ $viewModel->hasFullAccess() ? __('What you have') : __('What changes') }}</h2>

  <ul class="grid gap-4 md:grid-cols-2">
    <li class="flex min-h-70 flex-col justify-between gap-8 rounded-2xl border border-hairline bg-card p-8 md:row-span-2">
      <span class="text-[160px] leading-[0.8] font-semibold tracking-[-0.06em] text-ink" aria-hidden="true">∞</span>

      <div class="flex flex-col gap-2">
        <h3 class="flex items-center gap-2 text-[20px] font-semibold text-ink">
          <x-icon name="check" :size="16" />
          {{ __('Unlimited contacts') }}
        </h3>

        <p class="max-w-[34ch] text-[15px] leading-relaxed text-pretty text-body">{{ __('Keep a place for everyone who matters, without having to choose who fits.') }}</p>
      </div>
    </li>

    <li class="flex items-center gap-5 rounded-2xl border border-hairline bg-canvas px-7 py-6">
      <span class="text-[64px] leading-none font-semibold tracking-[-0.05em] text-ink" aria-hidden="true">∞</span>

      <div class="flex flex-col gap-1.5">
        <h3 class="flex items-center gap-2 text-[16px] font-semibold text-ink">
          <x-icon name="check" :size="16" />
          {{ __('Unlimited vaults') }}
        </h3>

        <p class="text-[14px] leading-relaxed text-pretty text-body">{{ __('Give different parts of your life their own space.') }}</p>
      </div>
    </li>

    <li class="flex items-center gap-5 rounded-2xl border border-hairline bg-canvas px-7 py-6">
      <span class="text-[48px] leading-none font-semibold tracking-[-0.05em] whitespace-nowrap text-ink" aria-hidden="true">{{ $viewModel->paidStorage() }}</span>

      <div class="flex flex-col gap-1.5">
        <h3 class="flex items-center gap-2 text-[16px] font-semibold text-ink">
          <x-icon name="check" :size="16" />
          {{ __(':size for your files and photos', ['size' => $viewModel->paidStorage()]) }}
        </h3>

        <p class="text-[14px] leading-relaxed text-pretty text-body">{{ __('Keep the things you want to remember alongside the people they belong to.') }}</p>
      </div>
    </li>
  </ul>

  <p class="text-base text-muted">{{ __('Keep using the notes, reminders, and relationship details you already know, with room to grow.') }}</p>
</section>
