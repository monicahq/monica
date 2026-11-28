{{-- The HTTP status codes an endpoint can answer with, one row per code. --}}
{{-- @var \App\ViewModels\Docs\ApiDocsSection $section --}}
@props(['section'])

@if ($section->hasStatusCodes())
  <h2 class="mt-9 mb-1 text-xl text-ink">Status codes</h2>
  <p class="mb-4 text-sm text-muted">Every code the API answers with, and what the body holds when it does.</p>

  <div class="overflow-x-auto rounded-lg border border-hairline shadow-panel">
    <table class="w-full min-w-120 border-collapse text-left rtl:text-right">
      <thead class="bg-sunken">
        <tr>
          <th scope="col" class="px-4 py-2.5 text-xs font-medium text-muted">Code</th>
          <th scope="col" class="px-4 py-2.5 text-xs font-medium text-muted">When</th>
          <th scope="col" class="px-4 py-2.5 text-xs font-medium text-muted">Body</th>
        </tr>
      </thead>

      <tbody>
        @foreach ($section->statusCodes as $code)
          @php
            $tone = match (intdiv($code['status'], 100)) {
              2 => 'border-success-edge bg-success-tint text-success',
              4 => 'border-warning-edge bg-warning-tint text-warning',
              default => 'border-error-edge bg-error-tint text-error',
            };
          @endphp

          <tr class="border-t border-hairline align-top">
            <th scope="row" class="px-4 py-3.5 font-normal whitespace-nowrap">
              <span class="rounded-sm border px-1.5 py-px font-mono text-xs font-semibold {{ $tone }}">{{ $code['status'] }}</span>
              <span class="mt-1.5 block text-sm font-medium text-ink">{{ $code['name'] }}</span>
            </th>
            <td class="px-4 py-3.5 text-sm leading-relaxed text-pretty text-body">{{ $code['when'] }}</td>
            <td class="px-4 py-3.5 text-sm leading-relaxed text-pretty text-body">{{ $code['body'] }}</td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>
@endif
