{{-- The one thing the email asks the reader to do, neutral unless it reports a success or an error. --}}
{{--
  @var string $url
  @var string $color
--}}
@props([
    'url',
    'color' => 'primary',
])
<table class="email-action" width="100%" cellpadding="0" cellspacing="0" role="presentation">
<tr>
<td class="email-button">
<a href="{{ $url }}" class="email-button-link email-button-{{ $color }}" target="_blank" rel="noopener">{!! $slot !!}</a>
</td>
</tr>
</table>
