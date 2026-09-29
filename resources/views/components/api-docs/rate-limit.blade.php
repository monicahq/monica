{{-- How often one endpoint may be called, said on the endpoint rather than only in the guide. --}}
{{-- @var \App\ViewModels\Docs\ApiDocsSection $section --}}
@props(['section'])

<x-docs.notice title="Rate limit" class="mt-9">
  {{ $section->rateLimit }}
  Every answer carries the budget left in <span class="font-mono text-ink">X-RateLimit-Remaining</span>.
</x-docs.notice>
