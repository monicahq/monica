{{-- The questions people ask before paying, each answered in a disclosure, the first one open. --}}
{{-- @var \App\ViewModels\Settings\Account\Billing\BillingViewModel $viewModel --}}
@php
  $questions = [
      [__('You’re free to change your mind.'), [
          __('Cancel your subscription directly in your account, anytime. Your full access continues until the end of the period you’ve paid for. After that, your account returns to the free plan. Nothing you’ve added is deleted.'),
          __('Happy with the free plan? You’re welcome to stay.'),
      ]],
      [__('What’s the difference between the three plans?'), [
          __('Just how you pay. Monthly, yearly, and lifetime all give you the same full access: unlimited contacts, unlimited vaults, and :size for your files and photos. Pick the payment option that works best for you.', ['size' => $viewModel->paidStorage()]),
      ]],
      [__('Can I cancel whenever I want?'), [
          __('Yes. You can cancel directly in your account, without contacting us. This stops your next renewal. You keep full access until the end of the month or year you’ve already paid for.'),
      ]],
      [__('What happens to my information if I cancel?'), [
          __('It stays. When your paid period ends, your account returns to the free plan and its limits apply again. Cancelling your subscription doesn’t delete the people, memories, or files you’ve added.'),
      ]],
      [__('What does lifetime access mean?'), [
          __('One payment, with no subscription and no recurring charges. You get the same full access as subscribers for the lifetime of the Monica service.'),
      ]],
      [__('Can I start monthly and switch later?'), [
          __('Yes. You can move from monthly to yearly billing, or buy lifetime access later.'),
          __('If you switch to yearly, your new billing cycle starts at your next renewal, with nothing charged for the switch today. If you buy lifetime access, your existing subscription stops renewing.'),
      ]],
      [__('Do I have to upgrade?'), [
          __('No. You’re welcome to keep using the free plan. Upgrade when you want more room for your contacts, vaults, and files.'),
      ]],
      [__('Why pay for something that’s open source?'), [
          __('The code is open source, and you can host Monica yourself. A paid plan gives you more room on the service we host and maintain for you. It also helps support Monica’s continued development.'),
      ]],
      [__('How do I know Monica will be around?'), [
          __('We’ve been building Monica since 2016. It’s an ongoing project with public source code and a development history you can explore yourself.'),
          __('We can’t promise the future. But we can show you the work, and your support helps us keep doing it.'),
      ]],
      [__('Are there extra charges at checkout?'), [
          __('The displayed prices include tax. You complete your payment securely through Stripe. Monthly and yearly plans renew automatically until you cancel; lifetime access is a single payment.'),
      ]],
  ];
@endphp

<section aria-labelledby="billing-questions" class="grid gap-8 border-t border-ink pt-8 lg:grid-cols-[minmax(0,1fr)_minmax(0,2fr)] lg:gap-12">
  <h2 id="billing-questions" class="self-start text-[32px] leading-[1.15] font-semibold tracking-[-0.035em] text-balance text-ink lg:sticky lg:top-20">{{ __('A few things you might be wondering.') }}</h2>

  <div class="border-t border-hairline">
    @foreach ($questions as [$question, $answers])
      <details class="group border-b border-hairline" @if ($loop->first) open @endif>
        <summary class="focus-ring flex cursor-pointer list-none items-center justify-between gap-4 rounded-sm py-4.5 text-[16px] font-semibold text-ink [&::-webkit-details-marker]:hidden">
          {{ $question }}

          <span class="text-[20px] font-normal text-muted transition-transform duration-(--duration-fast) group-open:rotate-45 motion-reduce:transition-none" aria-hidden="true">+</span>
        </summary>

        <div class="flex max-w-[62ch] flex-col gap-3 pb-5">
          @foreach ($answers as $answer)
            <p class="text-[14px] leading-relaxed text-pretty text-body">{{ $answer }}</p>
          @endforeach
        </div>
      </details>
    @endforeach
  </div>
</section>
