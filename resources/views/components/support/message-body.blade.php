{{-- The text of one support message, its Markdown drawn through the only renderer allowed to draw it. --}}
{{-- @var string $markdown --}}
@props([
  'markdown',
])

@php
  $inline = '[&_strong]:font-semibold [&_strong]:text-ink [&_code]:rounded-xs [&_code]:border [&_code]:border-hairline [&_code]:bg-card [&_code]:px-1.25 [&_code]:py-px [&_code]:font-mono [&_code]:text-xs [&_code]:text-ink [&_a]:rounded-xs [&_a]:text-ink [&_a]:underline [&_a]:decoration-hairline-strong [&_a]:underline-offset-3 [&_a:hover]:decoration-ink [&_a]:focus-ring';
  $prose = "text-base leading-[1.65] text-pretty wrap-anywhere text-body {$inline} [&>*+*]:mt-3 [&_:is(h4,h5,h6)]:mt-5 [&_:is(h4,h5,h6)]:font-semibold [&_:is(h4,h5,h6)]:text-ink [&_:is(h4,h5,h6)]:text-sm [&_ul]:list-disc [&_ul]:ps-5 [&_ol]:list-decimal [&_ol]:ps-5 [&_li+li]:mt-1 [&_blockquote]:border-s-2 [&_blockquote]:border-hairline-strong [&_blockquote]:ps-4 [&_hr]:border-hairline [&_pre]:rounded-lg [&_pre]:border [&_pre]:border-hairline [&_pre]:bg-sunken [&_pre]:px-4 [&_pre]:py-3 [&_pre]:whitespace-pre-wrap [&_pre_code]:border-0 [&_pre_code]:bg-transparent [&_pre_code]:p-0 [&_table]:w-full [&_table]:text-sm [&_th]:border-b [&_th]:border-hairline [&_th]:py-2 [&_th]:pe-4 [&_th]:text-start [&_td]:border-b [&_td]:border-hairline-soft [&_td]:py-2 [&_td]:pe-4";
@endphp

<div {{ $attributes->merge(['class' => $prose]) }}>{!! \App\Helpers\SupportMarkdown::toHtml($markdown) !!}</div>
