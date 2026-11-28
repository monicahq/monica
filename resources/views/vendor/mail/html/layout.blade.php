{{-- The frame every email is drawn in: the page, the card, and the colours for a dark reader. --}}
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ \App\Helpers\AvailableLocales::direction(app()->getLocale()) }}">
<head>
<title>{{ config('app.name') }}</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<meta name="color-scheme" content="light dark">
<meta name="supported-color-schemes" content="light dark">
<style>
:root {
color-scheme: light dark;
}

@media only screen and (max-width: 600px) {
.email-page-cell {
padding: 16px 16px 24px !important;
}

.email-column {
width: 100% !important;
}

.email-header,
.email-content,
.email-strip,
.email-footer {
padding-left: 24px !important;
padding-right: 24px !important;
}
}

@media (prefers-color-scheme: dark) {
body,
.email-page,
.email-page-cell {
background-color: #09090b !important;
}

.email-card {
background-color: #111113 !important;
border-color: #27272a !important;
box-shadow: none !important;
}

.email-header {
border-color: #27272a !important;
}

.email-header-name,
.email-content h1,
.email-content strong,
.email-content a {
color: #fafafa !important;
}

.email-content p {
color: #d4d4d8 !important;
}

.email-closing p {
color: #a1a1aa !important;
}

.email-content .email-button-link {
background-color: #fafafa !important;
border-color: #fafafa !important;
color: #18181b !important;
}

.email-content .email-button-success {
background-color: #4ade80 !important;
border-color: #4ade80 !important;
color: #18181b !important;
}

.email-content .email-button-error {
background-color: #f87171 !important;
border-color: #f87171 !important;
color: #18181b !important;
}

.email-panel {
background-color: #18181b !important;
border-color: #27272a !important;
}

.email-content .email-panel p {
color: #d4d4d8 !important;
}

.email-strip {
background-color: #18181b !important;
border-color: #27272a !important;
}

.email-strip p {
color: #a1a1aa !important;
}

.email-strip a {
color: #e4e4e7 !important;
text-decoration-color: #52525b !important;
}

.email-footer p {
color: #a1a1aa !important;
}

.email-mark-light {
display: none !important;
}

.email-mark-dark {
display: block !important;
}
}

[data-ogsb] .email-page,
[data-ogsb] .email-page-cell {
background-color: #09090b !important;
}

[data-ogsb] .email-card {
background-color: #111113 !important;
}

[data-ogsb] .email-panel,
[data-ogsb] .email-strip {
background-color: #18181b !important;
}

[data-ogsb] .email-content .email-button-link {
background-color: #fafafa !important;
}

[data-ogsb] .email-content .email-button-success {
background-color: #4ade80 !important;
}

[data-ogsb] .email-content .email-button-error {
background-color: #f87171 !important;
}

[data-ogsc] .email-header-name,
[data-ogsc] .email-content h1,
[data-ogsc] .email-content strong,
[data-ogsc] .email-content a {
color: #fafafa !important;
}

[data-ogsc] .email-content p,
[data-ogsc] .email-content .email-panel p {
color: #d4d4d8 !important;
}

[data-ogsc] .email-closing p,
[data-ogsc] .email-strip p,
[data-ogsc] .email-footer p {
color: #a1a1aa !important;
}

[data-ogsc] .email-strip a {
color: #e4e4e7 !important;
}

[data-ogsc] .email-content .email-button-link {
color: #18181b !important;
}
</style>
{!! $head ?? '' !!}
</head>
<body>

<table class="email-page" width="100%" cellpadding="0" cellspacing="0" role="presentation">
<tr>
<td class="email-page-cell" align="center">
<table class="email-column" align="center" width="600" cellpadding="0" cellspacing="0" role="presentation">
<tr>
<td>
<table class="email-card" width="100%" cellpadding="0" cellspacing="0" role="presentation">
{!! $header ?? '' !!}

<tr>
<td class="email-content">
{!! Illuminate\Mail\Markdown::parse($slot) !!}
</td>
</tr>

{!! $subcopy ?? '' !!}
</table>
</td>
</tr>

{!! $footer ?? '' !!}
</table>
</td>
</tr>
</table>
</body>
</html>
