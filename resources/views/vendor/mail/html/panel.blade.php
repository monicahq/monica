{{-- A sentence set apart in a sunken block, usually what to do if this was not you. --}}
<table class="email-panel-table" width="100%" cellpadding="0" cellspacing="0" role="presentation">
<tr>
<td class="email-panel-wrapper">
<table width="100%" cellpadding="0" cellspacing="0" role="presentation">
<tr>
<td class="email-panel">
{{ Illuminate\Mail\Markdown::parse($slot) }}
</td>
</tr>
</table>
</td>
</tr>
</table>
