{{-- The quiet line under the card: a small mark and the copyright. --}}
<tr>
<td class="email-footer">
<table cellpadding="0" cellspacing="0" role="presentation">
<tr>
<td class="email-footer-mark">
<img class="email-mark-light" src="{{ asset('images/email/mark-light.png') }}" width="16" height="15" alt="">
<img class="email-mark-dark" src="{{ asset('images/email/mark-dark.png') }}" width="16" height="15" alt="">
</td>
<td>
{{ Illuminate\Mail\Markdown::parse($slot) }}
</td>
</tr>
</table>
</td>
</tr>
