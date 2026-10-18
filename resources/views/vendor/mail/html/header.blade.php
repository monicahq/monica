{{-- The bar across the top of the card: the mark and the name of the product. --}}
{{-- @var string $url --}}
@props(['url'])
<tr>
<td class="email-header">
<table cellpadding="0" cellspacing="0" role="presentation">
<tr>
<td class="email-header-mark">
<a href="{{ $url }}"><img class="email-mark-light" src="{{ asset('images/email/mark-light.png') }}" width="28" height="25" alt="{{ config('app.name') }}"><img class="email-mark-dark" src="{{ asset('images/email/mark-dark.png') }}" width="28" height="25" alt="{{ config('app.name') }}"></a>
</td>
<td class="email-header-name">
<a class="email-header-name" href="{{ $url }}">{!! $slot !!}</a>
</td>
</tr>
</table>
</td>
</tr>
