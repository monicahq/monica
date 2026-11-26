{{-- The line under a support message box saying it accepts Markdown, and where the marks are explained. --}}
{{-- @var string $id --}}
@props([
  'id',
])

@php
  $guidePage = \App\ViewModels\Docs\PortalCatalogue::pageForReader('account.support');
  $guideUrl = $guidePage === null ? null : $guidePage->url().'#format-your-message';
@endphp

<p id="{{ $id }}" {{ $attributes->merge(['class' => 'text-xs text-muted']) }}>
  {{ __('You can format your message with Markdown.') }}
  @if ($guideUrl !== null)
    <a href="{{ $guideUrl }}" target="_blank" rel="noopener" class="rounded-xs text-ink underline decoration-hairline-strong underline-offset-3 hover:decoration-ink focus-ring">{{ __('See how') }}<span class="sr-only"> {{ __('(opens in a new tab)') }}</span></a>
  @endif
</p>
