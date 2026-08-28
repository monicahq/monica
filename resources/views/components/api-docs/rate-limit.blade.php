{{--
  How often one endpoint may be called, said on the endpoint rather than only in
  the guide, because somebody reading about one call is not reading the guide.

  @var \App\ViewModels\Docs\ApiDocsSection $section
--}}
@props(['section'])

<div class="mt-9 rounded-lg border border-hairline bg-sunken p-4">
  <h3 class="mb-1.5 text-[14px] font-semibold text-ink">Rate limit</h3>

  <p class="text-[14px] leading-relaxed text-body">
    {{ $section->rateLimit }}
    Every answer carries the budget left in <span class="font-mono text-[13px] text-ink">X-RateLimit-Remaining</span>.
  </p>
</div>
